<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductImage;

final readonly class ProductTransformer
{
    /**
     * @return array<string, mixed>
     */
    public function item(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'externalCode' => $product->getExternalCode(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'discount' => $product->getDiscount(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Product $product): array
    {
        return $this->item($product) + [
            'description' => $product->getDescription(),
            'attributes' => array_map(
                static fn(ProductAttribute $attribute): array => [
                    'key' => $attribute->getKey(),
                    'value' => $attribute->getValue(),
                ],
                $product->getAttributes(),
            ),
            'images' => array_map(
                static fn(ProductImage $image): array => [
                    'id' => $image->getId(),
                    'url' => $image->getUrl(),
                    'path' => $image->getPath(),
                    'src' => '/media/' . $image->getPath(),
                ],
                $product->getImages(),
            ),
            'createdAt' => $product->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $product->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
