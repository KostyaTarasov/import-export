<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\User;
use App\Exception\UnauthorizedException;
use DateTimeImmutable;
use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use UnexpectedValueException;

final readonly class JwtEncoder
{
    private const string ALGORITHM = 'HS256';

    public function __construct(
        private string $secret,
        private int $ttl,
    ) {
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('JWT_SECRET должен быть не короче 32 символов');
        }
    }

    public function issue(User $user): AccessToken
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->ttl;

        $token = JWT::encode(
            [
                'sub' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'iat' => $issuedAt,
                'exp' => $expiresAt,
            ],
            $this->secret,
            self::ALGORITHM,
        );

        return new AccessToken($token, new DateTimeImmutable('@' . $expiresAt));
    }

    public function userId(string $token): int
    {
        try {
            $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (UnexpectedValueException|DomainException) {
            throw new UnauthorizedException('Сессия истекла, войдите заново');
        }

        $subject = $payload->sub ?? null;

        if (!is_string($subject) || !ctype_digit($subject)) {
            throw new UnauthorizedException('Сессия истекла, войдите заново');
        }

        return (int) $subject;
    }
}
