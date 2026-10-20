<?php

declare(strict_types=1);

namespace App\Tests\Integration\Import;

use App\Exception\ImportFileException;
use App\Service\Import\XlsxRowReader;
use App\Tests\Support\XlsxFile;
use PHPUnit\Framework\TestCase;

final class XlsxRowReaderTest extends TestCase
{
    private const string EXAMPLE = __DIR__ . '/../../fixtures/import-example.xlsx';

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        array_map(unlink(...), $this->files);
    }

    public function testReadsExampleFile(): void
    {
        $reader = new XlsxRowReader();

        $headers = $reader->headers(self::EXAMPLE);
        $rows = iterator_to_array($reader->rows(self::EXAMPLE));

        self::assertCount(50, $headers);
        self::assertSame('Группы', $headers[0]);
        self::assertSame('Доп. поле: Категория товара', $headers[49]);
        self::assertSame(40, $reader->countRows(self::EXAMPLE));
        self::assertSame(range(2, 41), array_keys($rows));
        self::assertSame('3UHfAid1jaMiwgBuNvnsf3', $rows[2]['Внешний код']);
        self::assertSame('1320,00', $rows[2]['Цена: Цена продажи']);
        self::assertSame('', $rows[2]['Артикул']);
        self::assertSame('v0G7YnK6jnZZopGF7ARV61', $rows[41]['Внешний код']);
    }

    public function testKeepsSpreadsheetRowNumbersAcrossBlankRows(): void
    {
        $path = $this->file([
            ['Внешний код', 'Наименование'],
            ['first', 'Первый'],
            ['', ''],
            ['second', 'Второй'],
        ]);
        $reader = new XlsxRowReader();

        self::assertSame(
            [2 => ['Внешний код' => 'first', 'Наименование' => 'Первый'], 4 => ['Внешний код' => 'second', 'Наименование' => 'Второй']],
            iterator_to_array($reader->rows($path)),
        );
        self::assertSame(2, $reader->countRows($path));
    }

    public function testConvertsNumericCellsAndShortRows(): void
    {
        $path = $this->file([
            ['  Внешний   код ', 'Цена: Цена продажи', 'Описание'],
            [12345, 1320.5],
        ]);

        self::assertSame(
            [2 => ['Внешний код' => '12345', 'Цена: Цена продажи' => '1320.5', 'Описание' => '']],
            iterator_to_array(new XlsxRowReader()->rows($path)),
        );
    }

    public function testBrokenFile(): void
    {
        $path = sys_get_temp_dir() . '/broken-' . bin2hex(random_bytes(4)) . '.xlsx';
        file_put_contents($path, 'это не xlsx');
        $this->files[] = $path;

        $this->expectException(ImportFileException::class);
        $this->expectExceptionMessage('Не удалось прочитать xlsx-файл');

        new XlsxRowReader()->headers($path);
    }

    /**
     * @param list<list<float|int|string|null>> $rows
     */
    private function file(array $rows): string
    {
        return $this->files[] = XlsxFile::create($rows);
    }
}
