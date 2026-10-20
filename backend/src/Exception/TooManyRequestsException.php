<?php

declare(strict_types=1);

namespace App\Exception;

final class TooManyRequestsException extends ApiException
{
    public function __construct(
        public readonly int $retryAfter,
    ) {
        parent::__construct(sprintf('Слишком много загрузок. Повторите через %d с', $retryAfter));
    }

    public function statusCode(): int
    {
        return 429;
    }

    public function errorCode(): string
    {
        return 'too_many_requests';
    }
}
