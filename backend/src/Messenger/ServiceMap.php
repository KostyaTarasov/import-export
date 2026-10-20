<?php

declare(strict_types=1);

namespace App\Messenger;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final readonly class ServiceMap implements ContainerInterface
{
    /**
     * @param array<string, object> $services
     */
    public function __construct(
        private array $services,
    ) {}

    public function get(string $id): object
    {
        return $this->services[$id]
            ?? throw new class (sprintf('Сервис «%s» не зарегистрирован', $id)) extends InvalidArgumentException implements NotFoundExceptionInterface {};
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
