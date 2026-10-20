<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\ProductFilter;
use App\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductFilterTest extends TestCase
{
    public function testDefaults(): void
    {
        self::assertEquals(new ProductFilter(1, 20, null, null, null), ProductFilter::fromQuery([]));
    }

    public function testEmptyValuesAreIgnored(): void
    {
        $filter = ProductFilter::fromQuery(['page' => '', 'limit' => '', 'name' => '  ', 'priceFrom' => '', 'priceTo' => ' ']);

        self::assertEquals(new ProductFilter(), $filter);
    }

    public function testParsesAllParameters(): void
    {
        $filter = ProductFilter::fromQuery([
            'page' => '3',
            'limit' => '50',
            'name' => ' легинсы ',
            'priceFrom' => '599,9',
            'priceTo' => '1000',
        ]);

        self::assertEquals(new ProductFilter(3, 50, 'легинсы', '599.9', '1000'), $filter);
    }

    public function testEqualPriceBoundsAreAllowed(): void
    {
        self::assertSame('900.00', ProductFilter::fromQuery(['priceFrom' => '900', 'priceTo' => '900.00'])->priceTo);
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('invalidQueries')]
    public function testRejectsInvalidValue(array $query, string $field): void
    {
        try {
            ProductFilter::fromQuery($query);
            self::fail('Ожидалась ошибка валидации');
        } catch (ValidationException $e) {
            self::assertArrayHasKey($field, $e->errors);
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidQueries(): iterable
    {
        yield 'нулевая страница' => [['page' => '0'], 'page'];
        yield 'дробная страница' => [['page' => '1.5'], 'page'];
        yield 'слишком далёкая страница' => [['page' => '100001'], 'page'];
        yield 'страница массивом' => [['page' => ['1']], 'page'];
        yield 'лимит больше 100' => [['limit' => '101'], 'limit'];
        yield 'отрицательный лимит' => [['limit' => '-5'], 'limit'];
        yield 'длинное название' => [['name' => str_repeat('я', 256)], 'name'];
        yield 'название массивом' => [['name' => ['a']], 'name'];
        yield 'отрицательная цена' => [['priceFrom' => '-1'], 'priceFrom'];
        yield 'цена с тремя знаками' => [['priceTo' => '10.999'], 'priceTo'];
        yield 'цена текстом' => [['priceTo' => 'дорого'], 'priceTo'];
        yield 'перепутаны границы' => [['priceFrom' => '1000', 'priceTo' => '999.99'], 'priceTo'];
    }
}
