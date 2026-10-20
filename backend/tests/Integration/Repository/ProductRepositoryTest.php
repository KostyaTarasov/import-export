<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Tests\Support\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class ProductRepositoryTest extends DatabaseTestCase
{
    private ProductRepository $products;

    protected function setUp(): void
    {
        parent::setUp();

        $this->products = $this->container->get(ProductRepository::class);
    }

    public function testCreate(): void
    {
        $product = $this->product('3UHfAid1jaMiwgBuNvnsf3');
        $product->replaceAttributes(['Размер' => '46(M)', 'Цвет' => 'Grigio/Verde']);
        $product->addImage('http://catalog.collant.ru/pics/SNL-504038_m.jpg', 'images/aa/first.jpg');
        $product->addImage('http://catalog.collant.ru/pics/SNL-504038_b1.jpg', 'images/bb/second.jpg');

        $this->products->save($product);
        $this->em->clear();

        $found = $this->products->find($product->getId());

        self::assertNotNull($found);
        self::assertSame('3UHfAid1jaMiwgBuNvnsf3', $found->getExternalCode());
        self::assertSame('Бермуды мужские, Grigio, OMSA, 46(M), РОССИЯ', $found->getName());
        self::assertSame('Домашние бермуды', $found->getDescription());
        self::assertSame('1320.00', $found->getPrice());
        self::assertSame('33.33', $found->getDiscount());
        self::assertSame(['Размер' => '46(M)', 'Цвет' => 'Grigio/Verde'], $this->attributesOf($found));
        self::assertSame(
            [
                'http://catalog.collant.ru/pics/SNL-504038_m.jpg' => 'images/aa/first.jpg',
                'http://catalog.collant.ru/pics/SNL-504038_b1.jpg' => 'images/bb/second.jpg',
            ],
            $this->imagesOf($found),
        );
    }

    public function testFindByExternalCodeIsCaseSensitive(): void
    {
        $this->products->save($this->product('kN9cA5DqiR0-E9RRdsmta3'));

        self::assertNotNull($this->products->findByExternalCode('kN9cA5DqiR0-E9RRdsmta3'));
        self::assertNull($this->products->findByExternalCode('kn9ca5dqir0-e9rrdsmta3'));
    }

    public function testUpdate(): void
    {
        $product = $this->product('-MldwuujjgirmLnbyoUSG0');
        $product->replaceAttributes(['Размер' => '46 (M)', 'Цвет' => 'Bordo']);
        $this->products->save($product);
        $this->em->clear();

        $product = $this->products->findByExternalCode('-MldwuujjgirmLnbyoUSG0');
        self::assertNotNull($product);

        $product->update('Брюки модель "Легинсы" женские, Bordo, MINIMI, 46 (M), КИТАЙ', null, '900.00', null);
        $product->replaceAttributes(['Цвет' => 'Nero', 'Бренд' => 'MINIMI']);
        $this->products->save($product);
        $this->em->clear();

        $updated = $this->products->find($product->getId());

        self::assertNotNull($updated);
        self::assertSame('Брюки модель "Легинсы" женские, Bordo, MINIMI, 46 (M), КИТАЙ', $updated->getName());
        self::assertNull($updated->getDescription());
        self::assertSame('900.00', $updated->getPrice());
        self::assertNull($updated->getDiscount());
        self::assertSame(['Цвет' => 'Nero', 'Бренд' => 'MINIMI'], $this->attributesOf($updated));
        self::assertSame(2, $this->countRows('product_attributes'));
    }

    public function testRemoveDeletesAttributesAndImages(): void
    {
        $product = $this->product('WzxClIoTjhDJ1BN85U1Pw1');
        $product->replaceAttributes(['Размер' => '6(XXL)']);
        $product->addImage('http://catalog.collant.ru/pics/SNL-456097_m.jpg', 'images/cc/photo.jpg');
        $this->products->save($product);
        $id = $product->getId();

        $this->products->remove($product);
        $this->em->clear();

        self::assertNull($this->products->find($id));
        self::assertSame(0, $this->countRows('product_attributes'));
        self::assertSame(0, $this->countRows('product_images'));
    }

    public function testExternalCodeIsUnique(): void
    {
        $this->products->save($this->product('zLIYcKxHgGByt6Y2io7ep2'));

        $this->expectException(UniqueConstraintViolationException::class);

        $this->products->save($this->product('zLIYcKxHgGByt6Y2io7ep2'));
    }

    /**
     * @return array<string, string>
     */
    private function attributesOf(Product $product): array
    {
        $result = [];

        foreach ($product->getAttributes() as $attribute) {
            $result[$attribute->getKey()] = $attribute->getValue();
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function imagesOf(Product $product): array
    {
        $result = [];

        foreach ($product->getImages() as $image) {
            $result[$image->getUrl()] = $image->getPath();
        }

        return $result;
    }
}
