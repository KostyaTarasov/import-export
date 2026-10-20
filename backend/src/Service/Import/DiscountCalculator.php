<?php

declare(strict_types=1);

namespace App\Service\Import;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final readonly class DiscountCalculator
{
    public function calculate(BigDecimal $price, BigDecimal $purchasePrice): BigDecimal
    {
        if (!$price->isPositive()) {
            throw new InvalidArgumentException('Цена продажи должна быть больше нуля');
        }

        return $price->minus($purchasePrice)
            ->multipliedBy(100)
            ->dividedBy($price, 2, RoundingMode::HalfUp);
    }
}
