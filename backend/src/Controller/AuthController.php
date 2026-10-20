<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Credentials;
use App\Http\JsonResponder;
use App\Middleware\JwtAuthMiddleware;
use App\Service\Auth\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AuthController
{
    public function __construct(
        private AuthService $auth,
        private JsonResponder $responder,
    ) {}

    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->auth->login(Credentials::fromRequestBody($request->getParsedBody()));

        return $this->responder->json([
            'data' => [
                'token' => $token->token,
                'expiresAt' => $token->expiresAt->format(DATE_ATOM),
            ],
        ]);
    }

    public function me(ServerRequestInterface $request): ResponseInterface
    {
        $user = JwtAuthMiddleware::user($request);

        return $this->responder->json([
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ],
        ]);
    }
}
