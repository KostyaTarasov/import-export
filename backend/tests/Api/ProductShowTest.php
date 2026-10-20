<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tests\Support\ApiTestCase;

final class ProductShowTest extends ApiTestCase
{
    public function testShowsAttributesAndImages(): void
    {
        $product = new Product('3UHfAid1jaMiwgBuNvnsf3', 'Бермуды мужские, Grigio/Verde, OMSA, 46(M), РОССИЯ', 'Мужские трикотажные домашние бермуды', '1320.00', '33.33');
        $product->replaceAttributes(['Размер' => '46(M)', 'Цвет' => 'Grigio/Verde', 'Бренд' => 'OMSA']);
        $product->addImage('http://catalog.collant.ru/pics/SNL-504038_m.jpg', 'images/3f/first.jpg');
        $product->addImage('http://catalog.collant.ru/pics/SNL-504038_p.jpg', 'images/a1/package.jpg');
        $this->container->get(ProductRepository::class)->save($product);
        $this->em->clear();

        $response = $this->request('GET', '/api/products/' . $product->getId());
        $data = $this->decode($response)['data'];

        self::assertSame(200, $response->getStatusCode());
        self::assertIsArray($data);
        self::assertSame($product->getId(), $data['id']);
        self::assertSame('3UHfAid1jaMiwgBuNvnsf3', $data['externalCode']);
        self::assertSame('Мужские трикотажные домашние бермуды', $data['description']);
        self::assertSame('1320.00', $data['price']);
        self::assertSame('33.33', $data['discount']);
        self::assertSame(
            [
                ['key' => 'Размер', 'value' => '46(M)'],
                ['key' => 'Цвет', 'value' => 'Grigio/Verde'],
                ['key' => 'Бренд', 'value' => 'OMSA'],
            ],
            $data['attributes'],
        );
        self::assertIsArray($data['images']);
        self::assertSame(
            [
                ['http://catalog.collant.ru/pics/SNL-504038_m.jpg', 'images/3f/first.jpg', '/media/images/3f/first.jpg'],
                ['http://catalog.collant.ru/pics/SNL-504038_p.jpg', 'images/a1/package.jpg', '/media/images/a1/package.jpg'],
            ],
            array_map(static fn(array $image): array => [$image['url'], $image['path'], $image['src']], $data['images']),
        );
    }

    public function testUnknownProduct(): void
    {
        $response = $this->request('GET', '/api/products/999999');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'not_found', 'message' => 'Товар не найден']],
            $this->decode($response),
        );
    }

    public function testNonNumericId(): void
    {
        $response = $this->request('GET', '/api/products/abc');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Ресурс не найден', $this->decode($response)['error']['message'] ?? null);
    }
}
