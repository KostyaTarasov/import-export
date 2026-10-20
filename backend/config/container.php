<?php

declare(strict_types=1);

use App\Service\Health\DatabaseCheck;
use App\Service\Health\HealthService;
use App\Service\Health\RabbitMqCheck;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
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

    ResponseFactoryInterface::class => autowire(ResponseFactory::class),

    LoggerInterface::class => static function (ContainerInterface $container): LoggerInterface {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler('php://stderr', $container->get('log.level')));
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    },

    Connection::class => static fn(ContainerInterface $container): Connection => DriverManager::getConnection($container->get('db')),

    RabbitMqCheck::class => autowire()->constructorParameter('dsn', get('messenger.dsn')),

    HealthService::class => static fn(ContainerInterface $container): HealthService => new HealthService(
        [
            $container->get(DatabaseCheck::class),
            $container->get(RabbitMqCheck::class),
        ],
        $container->get(LoggerInterface::class),
    ),
];
