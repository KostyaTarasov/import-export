<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponder;
use App\Service\Health\HealthService;
use Psr\Http\Message\ResponseInterface;

final readonly class HealthController
{
    public function __construct(
        private HealthService $health,
        private JsonResponder $responder,
    ) {}

    public function check(): ResponseInterface
    {
        $report = $this->health->check();

        return $this->responder->json(
            [
                'status' => $report->isHealthy() ? 'ok' : 'fail',
                'checks' => array_map(static fn(bool $ok): string => $ok ? 'ok' : 'fail', $report->checks),
            ],
            $report->isHealthy() ? 200 : 503,
        );
    }
}
