<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Service\Health\HealthService;
use App\Tests\Support\ApiTestCase;
use App\Tests\Support\StaticHealthCheck;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Slim\App;

final class HealthTest extends ApiTestCase
{
    public function testAllServicesAvailable(): void
    {
        $response = $this->request($this->appWithChecks(database: true, rabbitmq: true), 'GET', '/api/health');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame(
            ['status' => 'ok', 'checks' => ['database' => 'ok', 'rabbitmq' => 'ok']],
            $this->decode($response),
        );
    }

    public function testBrokerUnavailable(): void
    {
        $response = $this->request($this->appWithChecks(database: true, rabbitmq: false), 'GET', '/api/health');

        self::assertSame(503, $response->getStatusCode());
        self::assertSame(
            ['status' => 'fail', 'checks' => ['database' => 'ok', 'rabbitmq' => 'fail']],
            $this->decode($response),
        );
    }

    /**
     * @return App<ContainerInterface>
     */
    private function appWithChecks(bool $database, bool $rabbitmq): App
    {
        return $this->createApp([
            HealthService::class => new HealthService(
                [
                    new StaticHealthCheck('database', $database),
                    new StaticHealthCheck('rabbitmq', $rabbitmq),
                ],
                new NullLogger(),
            ),
        ]);
    }
}
