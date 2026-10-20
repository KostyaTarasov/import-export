<?php

declare(strict_types=1);

namespace App\Service\Import;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class PriceParser
{
    public function parse(string $value): ?BigDecimal
    {
        $normalized = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $value);
        $normalized = str_replace(',', '.', $normalized);

        if (preg_match('/^-?\d+(\.\d+)?$/', $normalized) !== 1) {
            return null;
        }

        return BigDecimal::of($normalized)->toScale(2, RoundingMode::HalfUp);
    }
}
