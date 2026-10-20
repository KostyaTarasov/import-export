<?php

declare(strict_types=1);

namespace App\Service\Health;

use AMQPConnection;
use InvalidArgumentException;

final readonly class RabbitMqCheck implements HealthCheck
{
    public function __construct(
        private string $dsn,
    ) {}

    public function name(): string
    {
        return 'rabbitmq';
    }

    public function check(): void
    {
        $connection = new AMQPConnection($this->credentials());
        $connection->connect();
        $connection->disconnect();
    }

    /**
     * @return array<string, int|string>
     */
    private function credentials(): array
    {
        $parts = parse_url($this->dsn);

        if ($parts === false || !isset($parts['host'])) {
            throw new InvalidArgumentException('Некорректный MESSENGER_TRANSPORT_DSN');
        }

        $vhost = explode('/', trim($parts['path'] ?? '', '/'))[0];

        return [
            'host' => $parts['host'],
            'port' => $parts['port'] ?? 5672,
            'login' => urldecode($parts['user'] ?? 'guest'),
            'password' => urldecode($parts['pass'] ?? 'guest'),
            'vhost' => $vhost === '' ? '/' : urldecode($vhost),
            'connect_timeout' => 2,
        ];
    }
}
