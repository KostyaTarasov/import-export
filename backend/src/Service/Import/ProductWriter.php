<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductWriter
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductRepository $products,
    ) {}

    /**
     * @param array<string, string> $downloadedImages
     */
    public function upsert(ProductData $data, array $downloadedImages): bool
    {
        return $this->em->wrapInTransaction(function () use ($data, $downloadedImages): bool {
            $product = $this->products->findByExternalCode($data->externalCode);
            $created = $product === null;

            if ($product === null) {
                $product = new Product($data->externalCode, $data->name, $data->description, $data->price, $data->discount);
                $this->em->persist($product);
            } else {
                $product->update($data->name, $data->description, $data->price, $data->discount);
            }

            $product->replaceAttributes($data->attributes);

            $images = [];

            foreach ($data->imageUrls as $url) {
                $path = $downloadedImages[$url] ?? $product->findImage($url)?->getPath();

                if ($path !== null) {
                    $images[$url] = $path;
                }
            }

            $product->replaceImages($images);

            return $created;
        });
    }
}
