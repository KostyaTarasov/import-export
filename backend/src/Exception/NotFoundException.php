<?php

declare(strict_types=1);

namespace App\Exception;

final class NotFoundException extends ApiException
{
    public function statusCode(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'not_found';
    }
}
