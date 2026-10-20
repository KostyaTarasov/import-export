<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Support\ApiTestCase;
use RuntimeException;

final class ErrorHandlingTest extends ApiTestCase
{
    public function testUnknownRoute(): void
    {
        $response = $this->request('GET', '/api/unknown');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'not_found', 'message' => 'Ресурс не найден']],
            $this->decode($response),
        );
    }

    public function testWrongMethod(): void
    {
        $response = $this->request('DELETE', '/api/health');

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('GET', $response->getHeaderLine('Allow'));
        self::assertSame('method_not_allowed', $this->decode($response)['error']['code'] ?? null);
    }

    public function testExceptionDetailsHiddenByDefault(): void
    {
        $this->replaceService('app.debug', false);
        $this->app()->get('/api/failing', fn() => throw new RuntimeException('секретная деталь'));

        $response = $this->request('GET', '/api/failing');

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'internal_error', 'message' => 'Внутренняя ошибка сервера']],
            $this->decode($response),
        );
    }

    public function testExceptionDetailsShownInDebug(): void
    {
        $this->replaceService('app.debug', true);
        $this->app()->get('/api/failing', fn() => throw new RuntimeException('секретная деталь'));

        $body = $this->decode($this->request('GET', '/api/failing'));

        self::assertSame('секретная деталь', $body['error']['debug']['message'] ?? null);
    }
}
