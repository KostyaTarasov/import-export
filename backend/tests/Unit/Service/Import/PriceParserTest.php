<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Service\Import\PriceParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PriceParserTest extends TestCase
{
    #[DataProvider('prices')]
    public function testParse(string $value, ?string $expected): void
    {
        $price = new PriceParser()->parse($value);

        self::assertSame($expected, $price === null ? null : (string) $price);
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function prices(): iterable
    {
        yield 'запятая из файла' => ['1320,00', '1320.00'];
        yield 'точка' => ['960.5', '960.50'];
        yield 'целое' => ['780', '780.00'];
        yield 'пробел между разрядами' => ['1 899,00', '1899.00'];
        yield 'неразрывный пробел' => ["1\u{00A0}599,00", '1599.00'];
        yield 'округление' => ['1066,005', '1066.01'];
        yield 'ноль' => ['0', '0.00'];
        yield 'отрицательное' => ['-5', '-5.00'];
        yield 'пусто' => ['', null];
        yield 'текст' => ['бесплатно', null];
        yield 'две запятые' => ['12,5,0', null];
        yield 'экспонента' => ['1e3', null];
        yield 'валюта' => ['1320 руб', null];
    }
}
