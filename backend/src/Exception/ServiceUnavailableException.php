<?php

declare(strict_types=1);

namespace App\Exception;

final class ServiceUnavailableException extends ApiException
{
    public function statusCode(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'service_unavailable';
    }
}
