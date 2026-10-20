<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tests\Support\ApiTestCase;

final class ProductListTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $products = $this->container->get(ProductRepository::class);

        foreach (
            [
                ['7qONO1j8jsj6agLnYaLWZ3', 'Брюки модель "Легинсы" женские, Fumo, MINIMI, 42 (XS), КИТАЙ', '900.00'],
                ['v0G7YnK6jnZZopGF7ARV61', 'Брюки модель "легинсы" женские, Barbie, MINIMI, 48 (L), КИТАЙ', '1599.00'],
                ['zLIYcKxHgGByt6Y2io7ep2', 'Бермуды мужские, Grigio, OMSA, 46(M), РОССИЯ', '960.00'],
                ['AN-AZxf2i24obi-6ehzeU0', 'Бандалетки жен., Caramello, MINIMI, 3 (M), КИТАЙ', '780.00'],
                ['nmZawbT0ijlF-y0nh-QyG3', 'Боди женское, Nero, MINIMI, 48 (L), КИТАЙ', '1899.00'],
                ['socks-100-cotton', 'Носки мужские, 100% хлопок, Bianco', '300.00'],
            ] as [$code, $name, $price]
        ) {
            $products->save(new Product($code, $name, null, $price, null));
        }

        $this->em->clear();
    }

    public function testDefaultPage(): void
    {
        $response = $this->request('GET', '/api/products');
        $body = $this->decode($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['page' => 1, 'limit' => 20, 'total' => 6, 'totalPages' => 1], $body['meta']);
        self::assertCount(6, $this->names($body));
    }

    public function testItemFields(): void
    {
        $body = $this->decode($this->request('GET', '/api/products?name=бермуды'));

        self::assertSame(
            [
                'externalCode' => 'zLIYcKxHgGByt6Y2io7ep2',
                'name' => 'Бермуды мужские, Grigio, OMSA, 46(M), РОССИЯ',
                'price' => '960.00',
                'discount' => null,
            ],
            array_diff_key($this->items($body)[0], ['id' => true]),
        );
    }

    public function testPagination(): void
    {
        $all = $this->names($this->decode($this->request('GET', '/api/products?limit=100')));
        $body = $this->decode($this->request('GET', '/api/products?page=2&limit=2'));

        self::assertSame(array_slice($all, 2, 2), $this->names($body));
        self::assertSame(['page' => 2, 'limit' => 2, 'total' => 6, 'totalPages' => 3], $body['meta']);
    }

    public function testPageAfterLastIsEmpty(): void
    {
        $body = $this->decode($this->request('GET', '/api/products?page=5&limit=2'));

        self::assertSame([], $body['data']);
        self::assertSame(['page' => 5, 'limit' => 2, 'total' => 6, 'totalPages' => 3], $body['meta']);
    }

    public function testSearchIgnoresCase(): void
    {
        $lower = $this->names($this->decode($this->request('GET', '/api/products?name=' . urlencode('легинсы'))));
        $upper = $this->names($this->decode($this->request('GET', '/api/products?name=' . urlencode('ЛЕГИНСЫ'))));

        self::assertCount(2, $lower);
        self::assertSame($lower, $upper);
    }

    public function testSearchTreatsWildcardsLiterally(): void
    {
        $body = $this->decode($this->request('GET', '/api/products?name=' . urlencode('%')));

        self::assertSame(['Носки мужские, 100% хлопок, Bianco'], $this->names($body));
    }

    public function testPriceRange(): void
    {
        $body = $this->decode($this->request('GET', '/api/products?priceFrom=800&priceTo=1000'));

        self::assertEqualsCanonicalizing(
            ['Брюки модель "Легинсы" женские, Fumo, MINIMI, 42 (XS), КИТАЙ', 'Бермуды мужские, Grigio, OMSA, 46(M), РОССИЯ'],
            $this->names($body),
        );
    }

    public function testPriceAcceptsComma(): void
    {
        $body = $this->decode($this->request('GET', '/api/products?priceFrom=1599,00'));

        self::assertSame(2, $body['meta']['total'] ?? null);
    }

    public function testInvalidParameters(): void
    {
        $response = $this->request('GET', '/api/products?page=0&limit=1000&priceFrom=abc');

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(
            [
                'code' => 'validation_failed',
                'message' => 'Проверьте параметры запроса',
                'fields' => [
                    'page' => 'Номер страницы должен быть целым числом от 1 до 100000',
                    'limit' => 'Размер страницы должен быть целым числом от 1 до 100',
                    'priceFrom' => 'Цена должна быть неотрицательным числом, например 900 или 1599,90',
                ],
            ],
            $this->decode($response)['error'],
        );
    }

    public function testPriceFromAbovePriceTo(): void
    {
        $response = $this->request('GET', '/api/products?priceFrom=1000&priceTo=900');

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(['priceTo' => 'Верхняя граница цены меньше нижней'], $this->decode($response)['error']['fields'] ?? null);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return list<array<string, mixed>>
     */
    private function items(array $body): array
    {
        self::assertIsArray($body['data']);

        return array_values($body['data']);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return list<string>
     */
    private function names(array $body): array
    {
        return array_map(static fn(array $item): string => $item['name'], $this->items($body));
    }
}
