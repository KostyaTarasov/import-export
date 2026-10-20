<?php

declare(strict_types=1);

namespace App\Dto;

use App\Http\QueryParams;

final readonly class PageRequest
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
    ) {}

    public static function fromParams(QueryParams $params): self
    {
        return new self(
            $params->integer('page', 1, 100000, 'Номер страницы должен быть целым числом от 1 до 100000'),
            $params->integer('limit', 20, 100, 'Размер страницы должен быть целым числом от 1 до 100'),
        );
    }

    /**
     * @param array<array-key, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $params = new QueryParams($query);
        $request = self::fromParams($params);
        $params->validate();

        return $request;
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
