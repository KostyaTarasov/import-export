<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\Dto\Page;
use App\Dto\ProductFilter;
use App\Entity\Product;
use App\Exception\NotFoundException;
use App\Repository\ProductRepository;

final readonly class ProductService
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    /**
     * @return Page<Product>
     */
    public function list(ProductFilter $filter): Page
    {
        return $this->products->findPage($filter);
    }

    public function get(int $id): Product
    {
        return $this->products->find($id) ?? throw new NotFoundException('Товар не найден');
    }
}
