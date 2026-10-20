<?php

declare(strict_types=1);

namespace App\Tests\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

final class XlsxFile
{
    public const array HEADERS = [
        'Внешний код',
        'Наименование',
        'Описание',
        'Цена: Цена продажи',
        'Закупочная цена',
        'Доп. поле: Цвет',
        'Доп. поле: Размер',
        'Доп. поле: Ссылки на фото',
        'Доп. поле: Ссылка на упаковку',
    ];

    /**
     * @param list<list<float|int|string|null>> $rows
     */
    public static function create(array $rows): string
    {
        $path = sprintf('%s/import-%s.xlsx', sys_get_temp_dir(), bin2hex(random_bytes(6)));

        $writer = new Writer();
        $writer->openToFile($path);

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    /**
     * @param list<list<float|int|string|null>> $products
     */
    public static function withProducts(array $products): string
    {
        return self::create([self::HEADERS, ...$products]);
    }
}
