<?php

declare(strict_types=1);

namespace App\Exception;

final class ValidationException extends ApiException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        public readonly array $errors,
        string $message = 'Проверьте параметры запроса',
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'validation_failed';
    }
}
