<?php

declare(strict_types=1);

namespace App\Exception;

final class UnauthorizedException extends ApiException
{
    public function __construct(string $message = 'Требуется авторизация')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 401;
    }

    public function errorCode(): string
    {
        return 'unauthorized';
    }
}
