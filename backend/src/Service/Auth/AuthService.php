<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Dto\Credentials;
use App\Entity\User;
use App\Exception\UnauthorizedException;
use App\Repository\UserRepository;

final readonly class AuthService
{
    private const string TIMING_HASH = '$2y$12$w8NhX/IALMDf5ZmZztArLOK1vhtlnS0Au4sDRTRtbDmHOrYlaWe9S';

    public function __construct(
        private UserRepository $users,
        private JwtEncoder $jwt,
    ) {}

    public function login(Credentials $credentials): AccessToken
    {
        $user = $this->users->findByEmail($credentials->email);

        if ($user === null) {
            password_verify($credentials->password, self::TIMING_HASH);

            throw new UnauthorizedException('Неверный email или пароль');
        }

        if (!$user->verifyPassword($credentials->password)) {
            throw new UnauthorizedException('Неверный email или пароль');
        }

        return $this->jwt->issue($user);
    }

    public function authenticate(string $token): User
    {
        return $this->users->find($this->jwt->userId($token))
            ?? throw new UnauthorizedException('Сессия истекла, войдите заново');
    }
}
