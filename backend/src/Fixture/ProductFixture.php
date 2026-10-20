<?php

declare(strict_types=1);

namespace App\Fixture;

use App\Entity\Product;
use App\Service\Image\ImageStorage;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

final readonly class ProductFixture implements FixtureInterface
{
    private const array IMAGES = [
        'bermudas' => 'http://catalog.collant.ru/pics/SNL-504038_m.jpg',
        'bandalettes' => 'http://catalog.collant.ru/pics/SNL-456097_m.jpg',
        'leggings' => 'http://catalog.collant.ru/pics/SNL-558148_m.jpg',
        'bodysuit' => 'http://catalog.collant.ru/pics/SNL-561514_m.jpg',
    ];

    private const string BERMUDAS = 'Домашние бермуды из мягкого трикотажа. Пояс на резинке со шнурком, два боковых кармана, длина чуть ниже колена.';
    private const string BANDALETTES = 'Бандалетки из гладкой эластичной микрофибры с силиконовой полосой по краю. Защищают от натирания и не видны под платьем.';
    private const string LEGGINGS = 'Легинсы с высокой посадкой из плотного трикотажа, не просвечивают. Подходят для спорта и на каждый день.';
    private const string BODYSUIT = 'Боди из эластичной сетки с регулируемыми бретелями и застёжкой на кнопках.';

    public function __construct(
        private ImageStorage $storage,
        private string $imagesDir,
    ) {}

    public function load(ObjectManager $manager): void
    {
        foreach ($this->products() as $data) {
            $product = new Product($data['code'], $data['name'], $data['description'], $data['price'], $data['discount']);
            $product->replaceAttributes($data['attributes']);
            $product->addImage(self::IMAGES[$data['image']], $this->storeImage($data['image']));

            $manager->persist($product);
        }

        $manager->flush();
    }

    private function storeImage(string $name): string
    {
        $path = $this->storage->pathFor(self::IMAGES[$name], 'jpg');

        if (!$this->storage->exists($path)) {
            $this->storage->copy(sprintf('%s/%s.jpg', $this->imagesDir, $name), $path);
        }

        return $path;
    }

    /**
     * @return list<array{code: string, name: string, description: string, price: string, discount: ?string, image: key-of<self::IMAGES>, attributes: array<string, string>}>
     */
    private function products(): array
    {
        return [
            [
                'code' => 'demo-bermudas-gv-48',
                'name' => 'Бермуды мужские, Grigio/Verde, OMSA, 48(L), РОССИЯ',
                'description' => self::BERMUDAS,
                'price' => '1320.00',
                'discount' => '33.33',
                'image' => 'bermudas',
                'attributes' => $this->attributes('48(L)', 'Grigio/Verde', 'OMSA', '100% Хлопок', 'Мужчинам_Одежда для дома'),
            ],
            [
                'code' => 'demo-bermudas-gv-50',
                'name' => 'Бермуды мужские, Grigio/Verde, OMSA, 50(XL), РОССИЯ',
                'description' => self::BERMUDAS,
                'price' => '1320.00',
                'discount' => '33.33',
                'image' => 'bermudas',
                'attributes' => $this->attributes('50(XL)', 'Grigio/Verde', 'OMSA', '100% Хлопок', 'Мужчинам_Одежда для дома'),
            ],
            [
                'code' => 'demo-bermudas-g-52',
                'name' => 'Бермуды мужские, Grigio, OMSA, 52(XXL), РОССИЯ',
                'description' => self::BERMUDAS,
                'price' => '960.00',
                'discount' => '33.33',
                'image' => 'bermudas',
                'attributes' => $this->attributes('52(XXL)', 'Grigio', 'OMSA', '100% Хлопок', 'Мужчинам_Одежда для дома'),
            ],
            [
                'code' => 'demo-bandalettes-nero-2',
                'name' => 'Бандалетки жен., Nero, MINIMI, 2 (S), КИТАЙ',
                'description' => self::BANDALETTES,
                'price' => '780.00',
                'discount' => '33.33',
                'image' => 'bandalettes',
                'attributes' => $this->attributes('2 (S)', 'Nero', 'MINIMI', '86% Полиамид, 14% Эластан', 'Женщинам_Белье'),
            ],
            [
                'code' => 'demo-bandalettes-caramello-3',
                'name' => 'Бандалетки жен., Caramello, MINIMI, 3 (M), КИТАЙ',
                'description' => self::BANDALETTES,
                'price' => '780.00',
                'discount' => '33.33',
                'image' => 'bandalettes',
                'attributes' => $this->attributes('3 (M)', 'Caramello', 'MINIMI', '86% Полиамид, 14% Эластан', 'Женщинам_Белье'),
            ],
            [
                'code' => 'demo-bandalettes-daino-4',
                'name' => 'Бандалетки жен., Daino, MINIMI, 4 (L), КИТАЙ',
                'description' => self::BANDALETTES,
                'price' => '780.00',
                'discount' => null,
                'image' => 'bandalettes',
                'attributes' => $this->attributes('4 (L)', 'Daino', 'MINIMI', '90% Полиамид, 10% Эластан', 'Женщинам_Белье'),
            ],
            [
                'code' => 'demo-leggings-nero-44',
                'name' => 'Брюки модель "Легинсы" женские, Nero, MINIMI, 44 (S), КИТАЙ',
                'description' => self::LEGGINGS,
                'price' => '900.00',
                'discount' => '33.33',
                'image' => 'leggings',
                'attributes' => $this->attributes('44 (S)', 'Nero', 'MINIMI', '92% Полиэстер, 8% Эластан', 'Женщинам_Брюки'),
            ],
            [
                'code' => 'demo-leggings-fumo-46',
                'name' => 'Брюки модель "Легинсы" женские, Fumo, MINIMI, 46 (M), КИТАЙ',
                'description' => self::LEGGINGS,
                'price' => '900.00',
                'discount' => '33.33',
                'image' => 'leggings',
                'attributes' => $this->attributes('46 (M)', 'Fumo', 'MINIMI', '92% Полиэстер, 8% Эластан', 'Женщинам_Брюки'),
            ],
            [
                'code' => 'demo-leggings-bordo-48',
                'name' => 'Брюки модель "Легинсы" женские, Bordo, MINIMI, 48 (L), КИТАЙ',
                'description' => self::LEGGINGS,
                'price' => '900.00',
                'discount' => '33.33',
                'image' => 'leggings',
                'attributes' => $this->attributes('48 (L)', 'Bordo', 'MINIMI', '92% Полиэстер, 8% Эластан', 'Женщинам_Брюки'),
            ],
            [
                'code' => 'demo-leggings-militari-50',
                'name' => 'Брюки модель "Легинсы" женские, Militari, MINIMI, 50 (XL), КИТАЙ',
                'description' => self::LEGGINGS,
                'price' => '990.00',
                'discount' => '25.00',
                'image' => 'leggings',
                'attributes' => $this->attributes('50 (XL)', 'Militari', 'MINIMI', '92% Полиэстер, 8% Эластан', 'Женщинам_Брюки'),
            ],
            [
                'code' => 'demo-bodysuit-nero-44',
                'name' => 'Боди женское, Nero, MINIMI, 44 (S), КИТАЙ',
                'description' => self::BODYSUIT,
                'price' => '1899.00',
                'discount' => '33.33',
                'image' => 'bodysuit',
                'attributes' => $this->attributes('44 (S)', 'Nero', 'MINIMI', '83% Полиамид, 17% Эластан', 'Женщинам_Белье'),
            ],
            [
                'code' => 'demo-bodysuit-bianco-46',
                'name' => 'Боди женское, Bianco, MINIMI, 46 (M), КИТАЙ',
                'description' => self::BODYSUIT,
                'price' => '1899.00',
                'discount' => '33.33',
                'image' => 'bodysuit',
                'attributes' => $this->attributes('46 (M)', 'Bianco', 'MINIMI', '83% Полиамид, 17% Эластан', 'Женщинам_Белье'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(string $size, string $color, string $brand, string $composition, string $category): array
    {
        return [
            'Размер' => $size,
            'Цвет' => $color,
            'Бренд' => $brand,
            'Состав' => $composition,
            'Категория товара' => $category,
        ];
    }
}
