<?php

declare(strict_types=1);

namespace App\Service\Health;

use Psr\Log\LoggerInterface;
use Throwable;

final readonly class HealthService
{
    /**
     * @param list<HealthCheck> $checks
     */
    public function __construct(
        private array $checks,
        private LoggerInterface $logger,
    ) {}

    public function check(): HealthReport
    {
        $results = [];

        foreach ($this->checks as $check) {
            try {
                $check->check();
                $results[$check->name()] = true;
            } catch (Throwable $e) {
                $results[$check->name()] = false;
                $this->logger->warning('Проверка {check} не пройдена', [
                    'check' => $check->name(),
                    'reason' => $e->getMessage(),
                ]);
            }
        }

        return new HealthReport($results);
    }
}
