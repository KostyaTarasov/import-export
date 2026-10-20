<?php

declare(strict_types=1);

namespace App\Dto;

use App\Http\QueryParams;
use Brick\Math\BigDecimal;

final readonly class ProductFilter
{
    private const string PRICE_ERROR = 'Цена должна быть неотрицательным числом, например 900 или 1599,90';

    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        public ?string $name = null,
        public ?string $priceFrom = null,
        public ?string $priceTo = null,
    ) {}

    /**
     * @param array<array-key, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $params = new QueryParams($query);

        $page = PageRequest::fromParams($params);
        $name = $params->text('name', 255);
        $priceFrom = $params->decimal('priceFrom', self::PRICE_ERROR);
        $priceTo = $params->decimal('priceTo', self::PRICE_ERROR);

        if ($priceFrom !== null && $priceTo !== null && BigDecimal::of($priceFrom)->isGreaterThan($priceTo)) {
            $params->addError('priceTo', 'Верхняя граница цены меньше нижней');
        }

        $params->validate();

        return new self($page->page, $page->limit, $name, $priceFrom, $priceTo);
    }
}
