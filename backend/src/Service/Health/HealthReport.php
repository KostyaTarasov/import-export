<?php

declare(strict_types=1);

namespace App\Service\Health;

final readonly class HealthReport
{
    /**
     * @param array<string, bool> $checks
     */
    public function __construct(
        public array $checks,
    ) {}

    public function isHealthy(): bool
    {
        return !in_array(false, $this->checks, true);
    }
}
