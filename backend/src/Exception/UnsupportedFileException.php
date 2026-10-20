<?php

declare(strict_types=1);

namespace App\Exception;

final class UnsupportedFileException extends ApiException
{
    public function statusCode(): int
    {
        return 415;
    }

    public function errorCode(): string
    {
        return 'unsupported_file';
    }
}
