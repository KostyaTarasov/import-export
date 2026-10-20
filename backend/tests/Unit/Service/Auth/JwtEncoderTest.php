<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Auth;

use App\Entity\User;
use App\Exception\UnauthorizedException;
use App\Service\Auth\JwtEncoder;
use Firebase\JWT\JWT;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class JwtEncoderTest extends TestCase
{
    private const string SECRET = 'unit-test-secret-unit-test-secret';

    public function testRejectsShortSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new JwtEncoder('short', 3600);
    }

    public function testIssuedTokenPointsToUser(): void
    {
        $encoder = new JwtEncoder(self::SECRET, 600);

        $token = $encoder->issue($this->user(42));

        self::assertSame(42, $encoder->userId($token->token));
        self::assertEqualsWithDelta(time() + 600, $token->expiresAt->getTimestamp(), 2);
    }

    public function testRejectsTokenWithoutNumericSubject(): void
    {
        $encoder = new JwtEncoder(self::SECRET, 600);

        $this->expectException(UnauthorizedException::class);

        $encoder->userId(JWT::encode(['sub' => 'admin', 'exp' => time() + 60], self::SECRET, 'HS256'));
    }

    public function testRejectsGarbage(): void
    {
        $this->expectException(UnauthorizedException::class);

        new JwtEncoder(self::SECRET, 600)->userId('abc.def.ghi');
    }

    private function user(int $id): User
    {
        $user = new User('manager@example.com', 'hash');
        new ReflectionProperty(User::class, 'id')->setValue($user, $id);

        return $user;
    }
}
