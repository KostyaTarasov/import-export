<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\TooManyRequestsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final readonly class ImportRateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RateLimiterFactoryInterface $limiter,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $limit = $this->limiter->create('user-' . JwtAuthMiddleware::user($request)->getId())->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsException(max(1, $limit->getRetryAfter()->getTimestamp() - time()));
        }

        return $handler->handle($request);
    }
}
