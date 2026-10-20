<?php

declare(strict_types=1);

namespace App\Service\Auth;

use DateTimeImmutable;

final readonly class AccessToken
{
    public function __construct(
        public string $token,
        public DateTimeImmutable $expiresAt,
    ) {}
}
