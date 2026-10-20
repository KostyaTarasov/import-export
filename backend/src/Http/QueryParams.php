<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\ValidationException;

final class QueryParams
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * @param array<array-key, mixed> $query
     */
    public function __construct(
        private readonly array $query,
    ) {}

    public function integer(string $key, int $default, int $max, string $message): int
    {
        $value = $this->query[$key] ?? '';

        if ($value === '') {
            return $default;
        }

        if (!is_string($value) || preg_match('/^[1-9]\d{0,8}$/', $value) !== 1 || (int) $value > $max) {
            $this->errors[$key] = $message;

            return $default;
        }

        return (int) $value;
    }

    public function text(string $key, int $maxLength): ?string
    {
        $value = $this->query[$key] ?? '';

        if (!is_string($value)) {
            $this->errors[$key] = 'Ожидается строка';

            return null;
        }

        $value = trim($value);

        if (mb_strlen($value) > $maxLength) {
            $this->errors[$key] = sprintf('Не длиннее %d символов', $maxLength);

            return null;
        }

        return $value === '' ? null : $value;
    }

    public function decimal(string $key, string $message): ?string
    {
        $value = $this->query[$key] ?? '';

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return null;
        }

        if (!is_string($value) || preg_match('/^\d{1,10}(?:[.,]\d{1,2})?$/', $value) !== 1) {
            $this->errors[$key] = $message;

            return null;
        }

        return str_replace(',', '.', $value);
    }

    public function addError(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    public function validate(): void
    {
        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }
    }
}
