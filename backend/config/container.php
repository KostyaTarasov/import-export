<?php

declare(strict_types=1);

use App\Command\ImportProductsCommand;
use App\Command\ImportWorkerCommand;
use App\Command\LoadFixturesCommand;
use App\Doctrine\ResettableEntityManager;
use App\Fixture\ProductFixture;
use App\Fixture\UserFixture;
use App\Service\Auth\JwtEncoder;
use App\Service\Health\DatabaseCheck;
use App\Service\Health\HealthService;
use App\Service\Health\RabbitMqCheck;
use App\Service\Image\HttpImageDownloader;
use App\Service\Image\ImageDownloader;
use App\Service\Image\ImageStorage;
use App\Service\Import\ImportFileValidator;
use App\Service\Import\ImportService;
use Doctrine\DBAL\Configuration as DbalConfiguration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command as Migrations;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function DI\autowire;
use function DI\get;

return [
    App::class => static function (ContainerInterface $container): App {
        $app = AppFactory::createFromContainer($container);
        $app->getRouteCollector()->setDefaultInvocationStrategy(new RequestHandler(true));

        (require __DIR__ . '/routes.php')($app);
        (require __DIR__ . '/middleware.php')($app);

        return $app;
    },

    Application::class => static function (ContainerInterface $container): Application {
        $dependencyFactory = $container->get(DependencyFactory::class);

        $console = new Application('product-import');
        $console->addCommands([
            new Migrations\DiffCommand($dependencyFactory),
            new Migrations\ExecuteCommand($dependencyFactory),
            new Migrations\GenerateCommand($dependencyFactory),
            new Migrations\MigrateCommand($dependencyFactory),
            new Migrations\StatusCommand($dependencyFactory),
            new Migrations\UpToDateCommand($dependencyFactory),
            $container->get(LoadFixturesCommand::class),
            $container->get(ImportProductsCommand::class),
            $container->get(ImportWorkerCommand::class),
        ]);

        return $console;
    },

    ResponseFactoryInterface::class => autowire(ResponseFactory::class),

    LoggerInterface::class => static function (ContainerInterface $container): LoggerInterface {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler('php://stderr', $container->get('log.level')));
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    },

    Connection::class => static function (ContainerInterface $container): Connection {
        $config = new DbalConfiguration();
        $config->setSchemaAssetsFilter(static fn(string $table): bool => !in_array($table, ['cache_items', 'public.cache_items'], true));

        return DriverManager::getConnection($container->get('db'), $config);
    },

    Configuration::class => static function (ContainerInterface $container): Configuration {
        $debug = $container->get('app.debug');
        $cache = $debug ? new ArrayAdapter() : new PhpFilesAdapter('doctrine', 0, $container->get('path.cache'));

        $config = ORMSetup::createAttributeMetadataConfig([dirname(__DIR__) . '/src/Entity'], $debug, cache: $cache);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $config->enableNativeLazyObjects(true);

        return $config;
    },

    EntityManagerInterface::class => get(ResettableEntityManager::class),

    ResettableEntityManager::class => static fn(ContainerInterface $container): ResettableEntityManager => new ResettableEntityManager(
        static fn(): EntityManagerInterface => new EntityManager(
            $container->get(Connection::class),
            $container->get(Configuration::class),
        ),
    ),

    DependencyFactory::class => static fn(ContainerInterface $container): DependencyFactory => DependencyFactory::fromEntityManager(
        new ConfigurationArray([
            'migrations_paths' => ['App\Migrations' => dirname(__DIR__) . '/migrations'],
            'all_or_nothing' => true,
            'transactional' => true,
            'check_database_platform' => true,
            'organize_migrations' => 'none',
        ]),
        new ExistingEntityManager($container->get(EntityManagerInterface::class)),
    ),

    ImageStorage::class => autowire()->constructorParameter('root', get('path.storage')),

    HttpClientInterface::class => static fn(ContainerInterface $container): HttpClientInterface => HttpClient::create(
        ['headers' => ['User-Agent' => 'product-import/1.0']],
        $container->get('image.concurrency'),
    ),

    ImageDownloader::class => get(HttpImageDownloader::class),

    HttpImageDownloader::class => autowire()
        ->constructorParameter('timeout', get('image.timeout'))
        ->constructorParameter('maxSize', get('image.max_size')),

    ImportFileValidator::class => autowire()->constructorParameter('maxSize', get('import.max_file_size')),

    ImportService::class => autowire()->constructorParameter('storagePath', get('path.storage')),

    RateLimiterFactoryInterface::class => static fn(ContainerInterface $container): RateLimiterFactoryInterface => new RateLimiterFactory(
        [
            'id' => 'import',
            'policy' => 'sliding_window',
            'limit' => $container->get('import.rate_limit'),
            'interval' => $container->get('import.rate_interval'),
        ],
        new CacheStorage(new DoctrineDbalAdapter($container->get(Connection::class))),
    ),

    JwtEncoder::class => autowire()
        ->constructorParameter('secret', get('jwt.secret'))
        ->constructorParameter('ttl', get('jwt.ttl')),

    UserFixture::class => autowire()
        ->constructorParameter('email', get('admin.email'))
        ->constructorParameter('password', get('admin.password')),

    ProductFixture::class => autowire()->constructorParameter('imagesDir', get('path.seed_images')),

    RabbitMqCheck::class => autowire()->constructorParameter('dsn', get('messenger.dsn')),

    HealthService::class => static fn(ContainerInterface $container): HealthService => new HealthService(
        [
            $container->get(DatabaseCheck::class),
            $container->get(RabbitMqCheck::class),
        ],
        $container->get(LoggerInterface::class),
    ),
];
