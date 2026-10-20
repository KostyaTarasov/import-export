<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Service\Health\HealthCheck;
use RuntimeException;

final readonly class StaticHealthCheck implements HealthCheck
{
    public function __construct(
        private string $name,
        private bool $healthy,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function check(): void
    {
        if (!$this->healthy) {
            throw new RuntimeException('Сервис недоступен');
        }
    }
}
