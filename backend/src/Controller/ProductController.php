<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ProductFilter;
use App\Http\JsonResponder;
use App\Http\ProductTransformer;
use App\Service\Product\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ProductController
{
    public function __construct(
        private ProductService $products,
        private ProductTransformer $transformer,
        private JsonResponder $responder,
    ) {}

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $page = $this->products->list(ProductFilter::fromQuery($request->getQueryParams()));

        return $this->responder->json([
            'data' => array_map($this->transformer->item(...), $page->items),
            'meta' => [
                'page' => $page->page,
                'limit' => $page->limit,
                'total' => $page->total,
                'totalPages' => $page->totalPages(),
            ],
        ]);
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $product = $this->products->get((int) $request->getAttribute('id'));

        return $this->responder->json(['data' => $this->transformer->detail($product)]);
    }
}
