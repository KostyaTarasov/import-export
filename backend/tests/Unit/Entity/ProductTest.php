<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductImage;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testSetAttributeUpdatesExistingKey(): void
    {
        $product = $this->product();
        $product->setAttribute('Цвет', 'Nero');
        $product->setAttribute('Цвет', 'Bordo');

        self::assertSame(['Цвет' => 'Bordo'], $this->attributesOf($product));
    }

    public function testReplaceAttributesDropsMissingKeys(): void
    {
        $product = $this->product();
        $product->replaceAttributes(['Размер' => '44 (S)', 'Цвет' => 'Nero', 'seo h1' => 'Легинсы']);
        $product->replaceAttributes(['Цвет' => 'Fumo', 'Бренд' => 'MINIMI']);

        self::assertSame(['Цвет' => 'Fumo', 'Бренд' => 'MINIMI'], $this->attributesOf($product));
    }

    public function testReplaceImagesKeepsFileOrder(): void
    {
        $product = $this->product();
        $product->replaceImages([
            'http://catalog.collant.ru/pics/SNL-558148_m.jpg' => 'images/a.jpg',
            'http://catalog.collant.ru/pics/SNL-558148_b1.jpg' => 'images/b.jpg',
        ]);
        $kept = $product->findImage('http://catalog.collant.ru/pics/SNL-558148_b1.jpg');

        $product->replaceImages([
            'http://catalog.collant.ru/pics/SNL-558148_b1.jpg' => 'images/b2.jpg',
            'http://catalog.collant.ru/pics/SNL-558148_p.jpg' => 'images/c.jpg',
        ]);

        self::assertSame(
            [
                ['http://catalog.collant.ru/pics/SNL-558148_b1.jpg', 'images/b2.jpg', 0],
                ['http://catalog.collant.ru/pics/SNL-558148_p.jpg', 'images/c.jpg', 1],
            ],
            array_map(
                static fn(ProductImage $image): array => [$image->getUrl(), $image->getPath(), $image->getPosition()],
                $product->getImages(),
            ),
        );
        self::assertSame($kept, $product->findImage('http://catalog.collant.ru/pics/SNL-558148_b1.jpg'));
        self::assertNull($product->findImage('http://catalog.collant.ru/pics/SNL-558148_m.jpg'));
    }

    public function testAddImageAppendsToEnd(): void
    {
        $product = $this->product();
        $product->replaceImages(['http://catalog.collant.ru/pics/SNL-561514_m.jpg' => 'images/a.jpg']);
        $product->addImage('http://catalog.collant.ru/pics/SNL-561514_p.jpg', 'images/b.jpg');

        self::assertSame(1, $product->findImage('http://catalog.collant.ru/pics/SNL-561514_p.jpg')?->getPosition());
    }

    public function testUpdateTouchesUpdatedAt(): void
    {
        $product = $this->product();
        $createdAt = $product->getCreatedAt();

        usleep(1000);
        $product->update('Бермуды мужские, Grigio, OMSA, 48(L), РОССИЯ', null, '960.00', '33.33');

        self::assertSame($createdAt, $product->getCreatedAt());
        self::assertGreaterThan($createdAt, $product->getUpdatedAt());
        self::assertSame('960.00', $product->getPrice());
        self::assertNull($product->getDescription());
    }

    private function product(): Product
    {
        return new Product('zLIYcKxHgGByt6Y2io7ep2', 'Бермуды мужские, Grigio, OMSA, 46(M), РОССИЯ', 'Домашние бермуды', '960.00', '33.33');
    }

    /**
     * @return array<string, string>
     */
    private function attributesOf(Product $product): array
    {
        return array_column(
            array_map(static fn(ProductAttribute $attribute): array => [$attribute->getKey(), $attribute->getValue()], $product->getAttributes()),
            1,
            0,
        );
    }
}
