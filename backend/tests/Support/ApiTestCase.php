<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class ApiTestCase extends TestCase
{
    /**
     * @param array<string, mixed> $definitions
     *
     * @return App<ContainerInterface>
     */
    protected function createApp(array $definitions = []): App
    {
        $container = (require dirname(__DIR__, 2) . '/config/bootstrap.php')(
            $definitions + [LoggerInterface::class => new NullLogger()],
        );

        return $container->get(App::class);
    }

    /**
     * @param App<ContainerInterface> $app
     */
    protected function request(App $app, string $method, string $uri): ResponseInterface
    {
        return $app->handle(new ServerRequestFactory()->createServerRequest($method, $uri));
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}
