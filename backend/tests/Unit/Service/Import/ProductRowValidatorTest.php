<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Exception\InvalidRowException;
use App\Service\Import\DiscountCalculator;
use App\Service\Import\PriceParser;
use App\Service\Import\ProductRow;
use App\Service\Import\ProductRowValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductRowValidatorTest extends TestCase
{
    public function testValidRow(): void
    {
        $data = $this->validator()->validate(self::row());

        self::assertSame('zLIYcKxHgGByt6Y2io7ep2', $data->externalCode);
        self::assertSame('Бермуды мужские, Grigio, OMSA, 46(M), РОССИЯ', $data->name);
        self::assertSame('Мужские трикотажные домашние бермуды', $data->description);
        self::assertSame('960.00', $data->price);
        self::assertSame('33.33', $data->discount);
        self::assertSame(['Цвет' => 'Grigio'], $data->attributes);
        self::assertSame(['http://catalog.collant.ru/pics/SNL-504039_m.jpg'], $data->imageUrls);
        self::assertSame([], $data->warnings);
    }

    public function testEmptyDescriptionBecomesNull(): void
    {
        self::assertNull($this->validator()->validate(self::row(description: ''))->description);
    }

    public function testWithoutPurchasePriceThereIsNoDiscount(): void
    {
        $data = $this->validator()->validate(self::row(purchasePrice: ''));

        self::assertNull($data->discount);
        self::assertSame([], $data->warnings);
    }

    public function testPurchasePriceAbovePrice(): void
    {
        $data = $this->validator()->validate(self::row(price: '500', purchasePrice: '640,00'));

        self::assertNull($data->discount);
        self::assertSame(['Закупочная цена выше цены продажи, скидка не рассчитана'], $data->warnings);
    }

    public function testInvalidImageUrlsAreSkippedWithWarning(): void
    {
        $data = $this->validator()->validate(self::row(imageUrls: [
            'http://catalog.collant.ru/pics/SNL-504039_m.jpg',
            'ftp://catalog.collant.ru/pics/SNL-504039_b1.jpg',
            'javascript:alert(1)',
            'pics/SNL-504039_b2.jpg',
        ]));

        self::assertSame(['http://catalog.collant.ru/pics/SNL-504039_m.jpg'], $data->imageUrls);
        self::assertSame(
            [
                'Некорректная ссылка на изображение: ftp://catalog.collant.ru/pics/SNL-504039_b1.jpg',
                'Некорректная ссылка на изображение: javascript:alert(1)',
                'Некорректная ссылка на изображение: pics/SNL-504039_b2.jpg',
            ],
            $data->warnings,
        );
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('invalidRows')]
    public function testInvalidRow(ProductRow $row, array $expected): void
    {
        try {
            $this->validator()->validate($row);
            self::fail('Ожидалась ошибка в строке');
        } catch (InvalidRowException $e) {
            self::assertSame($expected, $e->errors);
        }
    }

    /**
     * @return iterable<string, array{ProductRow, list<string>}>
     */
    public static function invalidRows(): iterable
    {
        yield 'нет внешнего кода' => [self::row(externalCode: ''), ['Не заполнен внешний код']];
        yield 'длинный код' => [self::row(externalCode: str_repeat('x', 65)), ['Внешний код длиннее 64 символов']];
        yield 'нет наименования' => [self::row(name: ''), ['Не заполнено наименование']];
        yield 'нет цены' => [self::row(price: ''), ['Не заполнена цена продажи']];
        yield 'цена текстом' => [self::row(price: 'договорная'), ['Цена продажи «договорная» не является числом']];
        yield 'нулевая цена' => [self::row(price: '0,00'), ['Цена продажи должна быть больше нуля']];
        yield 'огромная цена' => [self::row(price: '10000000000'), ['Цена продажи «10000000000» слишком большая']];
        yield 'закупочная текстом' => [self::row(purchasePrice: 'n/a'), ['Закупочная цена «n/a» не является числом']];
        yield 'отрицательная закупочная' => [self::row(purchasePrice: '-1'), ['Закупочная цена не может быть отрицательной']];
        yield 'несколько ошибок сразу' => [
            self::row(externalCode: '', name: '', price: 'abc'),
            ['Не заполнен внешний код', 'Не заполнено наименование', 'Цена продажи «abc» не является числом'],
        ];
    }

    private function validator(): ProductRowValidator
    {
        return new ProductRowValidator(new PriceParser(), new DiscountCalculator());
    }

    /**
     * @param list<string> $imageUrls
     */
    private static function row(
        string $externalCode = 'zLIYcKxHgGByt6Y2io7ep2',
        string $name = 'Бермуды мужские, Grigio, OMSA, 46(M), РОССИЯ',
        string $description = 'Мужские трикотажные домашние бермуды',
        string $price = '960,00',
        string $purchasePrice = '640,00',
        array $imageUrls = ['http://catalog.collant.ru/pics/SNL-504039_m.jpg'],
    ): ProductRow {
        return new ProductRow($externalCode, $name, $description, $price, $purchasePrice, ['Цвет' => 'Grigio'], $imageUrls);
    }
}
