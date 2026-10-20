<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PageRequest;
use App\Http\ImportJobTransformer;
use App\Http\JsonResponder;
use App\Middleware\JwtAuthMiddleware;
use App\Service\Import\ImportService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ImportController
{
    public function __construct(
        private ImportService $imports,
        private ImportJobTransformer $transformer,
        private JsonResponder $responder,
    ) {}

    public function upload(ServerRequestInterface $request): ResponseInterface
    {
        $job = $this->imports->queue(
            $request->getUploadedFiles()['file'] ?? null,
            JwtAuthMiddleware::user($request),
        );

        return $this->responder
            ->json(['data' => $this->transformer->job($job)], 202)
            ->withHeader('Location', '/api/imports/' . $job->getId());
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $job = $this->imports->get((string) $request->getAttribute('id'), JwtAuthMiddleware::user($request));

        return $this->responder->json(['data' => $this->transformer->job($job)]);
    }

    public function errors(ServerRequestInterface $request): ResponseInterface
    {
        $job = $this->imports->get((string) $request->getAttribute('id'), JwtAuthMiddleware::user($request));
        $page = $this->imports->errors($job, PageRequest::fromQuery($request->getQueryParams()));

        return $this->responder->json([
            'data' => array_map($this->transformer->error(...), $page->items),
            'meta' => [
                'page' => $page->page,
                'limit' => $page->limit,
                'total' => $page->total,
                'totalPages' => $page->totalPages(),
            ],
        ]);
    }
}
