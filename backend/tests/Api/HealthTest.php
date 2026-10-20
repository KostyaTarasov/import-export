<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Service\Health\HealthService;
use App\Tests\Support\ApiTestCase;
use App\Tests\Support\StaticHealthCheck;
use Psr\Log\NullLogger;

final class HealthTest extends ApiTestCase
{
    public function testAllServicesAvailable(): void
    {
        $this->useChecks(database: true, rabbitmq: true);

        $response = $this->request('GET', '/api/health');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame(
            ['status' => 'ok', 'checks' => ['database' => 'ok', 'rabbitmq' => 'ok']],
            $this->decode($response),
        );
    }

    public function testBrokerUnavailable(): void
    {
        $this->useChecks(database: true, rabbitmq: false);

        $response = $this->request('GET', '/api/health');

        self::assertSame(503, $response->getStatusCode());
        self::assertSame(
            ['status' => 'fail', 'checks' => ['database' => 'ok', 'rabbitmq' => 'fail']],
            $this->decode($response),
        );
    }

    private function useChecks(bool $database, bool $rabbitmq): void
    {
        $this->replaceService(HealthService::class, new HealthService(
            [
                new StaticHealthCheck('database', $database),
                new StaticHealthCheck('rabbitmq', $rabbitmq),
            ],
            new NullLogger(),
        ));
    }
}
