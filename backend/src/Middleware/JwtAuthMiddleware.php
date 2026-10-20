<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Entity\User;
use App\Exception\UnauthorizedException;
use App\Service\Auth\AuthService;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthService $auth,
    ) {}

    public static function user(ServerRequestInterface $request): User
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            throw new LogicException('Маршрут не защищён JwtAuthMiddleware');
        }

        return $user;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (preg_match('/^Bearer\s+(\S+)$/i', $request->getHeaderLine('Authorization'), $matches) !== 1) {
            throw new UnauthorizedException();
        }

        return $handler->handle($request->withAttribute('user', $this->auth->authenticate($matches[1])));
    }
}
