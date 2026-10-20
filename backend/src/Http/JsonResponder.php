<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class JsonResponder
{
    private const int FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function json(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $response->getBody()->write(json_encode($data, self::FLAGS));

        return $response;
    }
}
