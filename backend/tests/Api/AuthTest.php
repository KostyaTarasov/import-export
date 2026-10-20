<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Repository\UserRepository;
use App\Tests\Support\ApiTestCase;
use DateTimeImmutable;
use Firebase\JWT\JWT;

final class AuthTest extends ApiTestCase
{
    public function testLoginIssuesWorkingToken(): void
    {
        $this->createUser('manager@example.com', 'secret-password');

        $response = $this->request('POST', '/api/auth/login', ['email' => 'manager@example.com', 'password' => 'secret-password']);
        $data = $this->decode($response)['data'];

        self::assertSame(200, $response->getStatusCode());
        self::assertIsArray($data);
        self::assertIsString($data['token']);
        self::assertIsString($data['expiresAt']);
        self::assertEqualsWithDelta(time() + 3600, new DateTimeImmutable($data['expiresAt'])->getTimestamp(), 5);

        $this->useToken($data['token']);
        $me = $this->request('GET', '/api/auth/me');

        self::assertSame(200, $me->getStatusCode());
        self::assertSame('manager@example.com', $this->decode($me)['data']['email'] ?? null);
    }

    public function testEmailIsNormalized(): void
    {
        $this->createUser('manager@example.com', 'secret-password');

        $response = $this->request('POST', '/api/auth/login', ['email' => '  Manager@Example.COM ', 'password' => 'secret-password']);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testWrongPassword(): void
    {
        $this->createUser('manager@example.com', 'secret-password');

        $response = $this->request('POST', '/api/auth/login', ['email' => 'manager@example.com', 'password' => 'wrong']);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Bearer', $response->getHeaderLine('WWW-Authenticate'));
        self::assertSame(
            ['error' => ['code' => 'unauthorized', 'message' => 'Неверный email или пароль']],
            $this->decode($response),
        );
    }

    public function testUnknownEmail(): void
    {
        $response = $this->request('POST', '/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'secret-password']);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Неверный email или пароль', $this->decode($response)['error']['message'] ?? null);
    }

    public function testLoginRequiresFields(): void
    {
        $response = $this->request('POST', '/api/auth/login', ['email' => ' ', 'password' => 123]);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(
            ['email' => 'Укажите email', 'password' => 'Укажите пароль'],
            $this->decode($response)['error']['fields'] ?? null,
        );
    }

    public function testProtectedRouteWithoutToken(): void
    {
        $response = $this->request('GET', '/api/products');

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'unauthorized', 'message' => 'Требуется авторизация']],
            $this->decode($response),
        );
    }

    public function testExpiredToken(): void
    {
        $user = $this->createUser();
        $this->useToken($this->signedToken(['sub' => (string) $user->getId(), 'exp' => time() - 60]));

        $response = $this->request('GET', '/api/auth/me');

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Сессия истекла, войдите заново', $this->decode($response)['error']['message'] ?? null);
    }

    public function testTokenSignedWithAnotherSecret(): void
    {
        $user = $this->createUser();
        $this->useToken(JWT::encode(['sub' => (string) $user->getId(), 'exp' => time() + 60], str_repeat('x', 32), 'HS256'));

        self::assertSame(401, $this->request('GET', '/api/auth/me')->getStatusCode());
    }

    public function testTokenOfDeletedUser(): void
    {
        $user = $this->authenticate();
        $this->container->get(UserRepository::class)->remove($user);

        self::assertSame(401, $this->request('GET', '/api/auth/me')->getStatusCode());
    }

    public function testMalformedHeader(): void
    {
        $this->useToken('not a token');

        self::assertSame(401, $this->request('GET', '/api/auth/me')->getStatusCode());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function signedToken(array $payload): string
    {
        $secret = getenv('JWT_SECRET');
        self::assertIsString($secret);

        return JWT::encode($payload, $secret, 'HS256');
    }
}
