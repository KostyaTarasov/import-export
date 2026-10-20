<?php

declare(strict_types=1);

namespace App\Dto;

use App\Exception\ValidationException;
use Brick\Math\BigDecimal;

final readonly class ProductFilter
{
    private const int MAX_PAGE = 100000;
    private const int MAX_LIMIT = 100;

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
        $errors = [];

        $page = self::integer($query, 'page', 1, self::MAX_PAGE, $errors, 'Номер страницы должен быть целым числом от 1 до 100000');
        $limit = self::integer($query, 'limit', 20, self::MAX_LIMIT, $errors, 'Размер страницы должен быть целым числом от 1 до 100');
        $name = self::text($query, 'name', $errors);
        $priceFrom = self::price($query, 'priceFrom', $errors);
        $priceTo = self::price($query, 'priceTo', $errors);

        if ($priceFrom !== null && $priceTo !== null && BigDecimal::of($priceFrom)->isGreaterThan($priceTo)) {
            $errors['priceTo'] = 'Верхняя граница цены меньше нижней';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self($page, $limit, $name, $priceFrom, $priceTo);
    }

    /**
     * @param array<array-key, mixed> $query
     * @param array<string, string> $errors
     */
    private static function integer(array $query, string $key, int $default, int $max, array &$errors, string $message): int
    {
        $value = $query[$key] ?? '';

        if ($value === '') {
            return $default;
        }

        if (!is_string($value) || preg_match('/^[1-9]\d{0,5}$/', $value) !== 1 || (int) $value > $max) {
            $errors[$key] = $message;

            return $default;
        }

        return (int) $value;
    }

    /**
     * @param array<array-key, mixed> $query
     * @param array<string, string> $errors
     */
    private static function text(array $query, string $key, array &$errors): ?string
    {
        $value = $query[$key] ?? '';

        if (!is_string($value)) {
            $errors[$key] = 'Ожидается строка';

            return null;
        }

        $value = trim($value);

        if (mb_strlen($value) > 255) {
            $errors[$key] = 'Не длиннее 255 символов';

            return null;
        }

        return $value === '' ? null : $value;
    }

    /**
     * @param array<array-key, mixed> $query
     * @param array<string, string> $errors
     */
    private static function price(array $query, string $key, array &$errors): ?string
    {
        $value = $query[$key] ?? '';

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return null;
        }

        if (!is_string($value) || preg_match('/^\d{1,10}(?:[.,]\d{1,2})?$/', $value) !== 1) {
            $errors[$key] = 'Цена должна быть неотрицательным числом, например 900 или 1599,90';

            return null;
        }

        return str_replace(',', '.', $value);
    }
}
