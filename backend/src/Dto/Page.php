<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * @template T
 */
final readonly class Page
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {}

    public function totalPages(): int
    {
        return (int) ceil($this->total / $this->limit);
    }
}
