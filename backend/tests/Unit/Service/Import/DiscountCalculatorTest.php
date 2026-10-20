<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Service\Import\DiscountCalculator;
use Brick\Math\BigDecimal;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DiscountCalculatorTest extends TestCase
{
    #[DataProvider('cases')]
    public function testCalculate(string $price, string $purchasePrice, string $expected): void
    {
        $discount = new DiscountCalculator()->calculate(BigDecimal::of($price), BigDecimal::of($purchasePrice));

        self::assertSame($expected, (string) $discount);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function cases(): iterable
    {
        yield 'бермуды из файла' => ['1320.00', '880.00', '33.33'];
        yield 'боди из файла' => ['1899.00', '1266.00', '33.33'];
        yield 'без наценки' => ['100.00', '100.00', '0.00'];
        yield 'бесплатная закупка' => ['100.00', '0.00', '100.00'];
        yield 'округление вверх' => ['3.00', '1.00', '66.67'];
    }

    public function testZeroPriceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DiscountCalculator()->calculate(BigDecimal::zero(), BigDecimal::zero());
    }
}
