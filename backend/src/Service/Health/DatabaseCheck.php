<?php

declare(strict_types=1);

namespace App\Service\Health;

use Doctrine\DBAL\Connection;

final readonly class DatabaseCheck implements HealthCheck
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function name(): string
    {
        return 'database';
    }

    public function check(): void
    {
        $this->connection->executeQuery('SELECT 1');
    }
}
