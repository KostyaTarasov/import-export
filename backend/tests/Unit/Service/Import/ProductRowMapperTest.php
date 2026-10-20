<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Service\Import\ProductRowMapper;
use PHPUnit\Framework\TestCase;

final class ProductRowMapperTest extends TestCase
{
    public function testMapsColumns(): void
    {
        $row = new ProductRowMapper()->map([
            'Группы' => 'Одежда Collant',
            'Внешний код' => '3UHfAid1jaMiwgBuNvnsf3',
            'Наименование' => 'Бермуды мужские, Grigio/Verde, OMSA, 46(M), РОССИЯ',
            'Описание' => 'Мужские трикотажные домашние бермуды',
            'Цена: Цена продажи' => '1320,00',
            'Закупочная цена' => '880,00',
            'Доп. поле: Размер' => '46(M)',
            'Доп. поле: Цвет' => 'Grigio/Verde',
            'Доп. поле: Кол-во в упаковке' => '',
            'Доп. поле: Ссылка на упаковку' => 'http://catalog.collant.ru/pics/SNL-504038_p.jpg',
            'Доп. поле: Ссылки на фото' => 'http://catalog.collant.ru/pics/SNL-504038_m.jpg, http://catalog.collant.ru/pics/SNL-504038_b2.jpg',
            'Доп. поле: seo h1' => '',
        ]);

        self::assertSame('3UHfAid1jaMiwgBuNvnsf3', $row->externalCode);
        self::assertSame('Бермуды мужские, Grigio/Verde, OMSA, 46(M), РОССИЯ', $row->name);
        self::assertSame('Мужские трикотажные домашние бермуды', $row->description);
        self::assertSame('1320,00', $row->price);
        self::assertSame('880,00', $row->purchasePrice);
        self::assertSame(['Размер' => '46(M)', 'Цвет' => 'Grigio/Verde'], $row->attributes);
        self::assertSame(
            [
                'http://catalog.collant.ru/pics/SNL-504038_m.jpg',
                'http://catalog.collant.ru/pics/SNL-504038_b2.jpg',
                'http://catalog.collant.ru/pics/SNL-504038_p.jpg',
            ],
            $row->imageUrls,
        );
    }

    public function testMissingOptionalColumns(): void
    {
        $row = new ProductRowMapper()->map(['Внешний код' => 'a', 'Наименование' => 'b', 'Цена: Цена продажи' => '1']);

        self::assertSame('', $row->description);
        self::assertSame('', $row->purchasePrice);
        self::assertSame([], $row->attributes);
        self::assertSame([], $row->imageUrls);
    }

    public function testSplitsUrlsByAnySeparatorAndDropsDuplicates(): void
    {
        $row = new ProductRowMapper()->map([
            'Доп. поле: Ссылки на фото' => "http://a.test/1.jpg;http://a.test/2.jpg\nhttp://a.test/1.jpg,,",
            'Доп. поле: Ссылка на упаковку' => 'http://a.test/2.jpg',
        ]);

        self::assertSame(['http://a.test/1.jpg', 'http://a.test/2.jpg'], $row->imageUrls);
    }

    public function testMissingColumns(): void
    {
        $mapper = new ProductRowMapper();

        self::assertSame([], $mapper->missingColumns(['Внешний код', 'Наименование', 'Цена: Цена продажи', 'Описание']));
        self::assertSame(['Внешний код', 'Цена: Цена продажи'], $mapper->missingColumns(['Наименование', 'Цена']));
    }
}
