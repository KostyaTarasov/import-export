<?php

declare(strict_types=1);

namespace App\Exception;

final class FileTooLargeException extends ApiException
{
    public function statusCode(): int
    {
        return 413;
    }

    public function errorCode(): string
    {
        return 'file_too_large';
    }
}
