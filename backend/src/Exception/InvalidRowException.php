<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class InvalidRowException extends RuntimeException
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public readonly array $errors,
    ) {
        parent::__construct(implode('; ', $errors));
    }
}
