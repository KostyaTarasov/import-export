<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

abstract class ApiException extends RuntimeException
{
    abstract public function statusCode(): int;

    abstract public function errorCode(): string;
}
