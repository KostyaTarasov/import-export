<?php

declare(strict_types=1);

namespace App\Tests\Integration\Import;

use App\Dto\ProductFilter;
use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductImage;
use App\Enum\ImportIssueLevel;
use App\Exception\ImportFileException;
use App\Repository\ProductRepository;
use App\Service\Image\ImageDownloader;
use App\Service\Import\ImportIssue;
use App\Service\Import\InMemoryImportReport;
use App\Service\Import\ProductImporter;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\FakeImageDownloader;
use App\Tests\Support\XlsxFile;
use Symfony\Component\Filesystem\Filesystem;

use function DI\get;

final class ProductImporterTest extends DatabaseTestCase
{
    private const string EXAMPLE = __DIR__ . '/../../fixtures/import-example.xlsx';

    private string $storage;

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/import-storage-' . bin2hex(random_bytes(4));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        new Filesystem()->remove([$this->storage, ...$this->files]);
    }

    protected function definitions(): array
    {
        return [
            'path.storage' => $this->storage,
            ImageDownloader::class => get(FakeImageDownloader::class),
        ];
    }

    public function testImportsExampleFile(): void
    {
        $report = $this->import(self::EXAMPLE);

        self::assertTrue($report->finished);
        self::assertSame([40, 40, 0, 0], [$report->total, $report->created, $report->updated, $report->failed]);
        self::assertSame([], $report->issues);
        self::assertSame(40, $this->countRows('products'));
        self::assertSame(615, $this->countRows('product_attributes'));
        self::assertSame(131, $this->countRows('product_images'));
        self::assertSame(['33.33'], $this->em->getConnection()->fetchFirstColumn('SELECT DISTINCT discount FROM products'));

        $products = $this->container->get(ProductRepository::class);
        self::assertSame(27, $products->findPage(new ProductFilter(limit: 100, name: 'легинсы'))->total);
        self::assertSame(23, $products->findPage(new ProductFilter(limit: 100, priceFrom: '800', priceTo: '1000'))->total);

        $bermudas = $products->findByExternalCode('3UHfAid1jaMiwgBuNvnsf3');
        self::assertNotNull($bermudas);
        self::assertSame('Бермуды мужские, Grigio/Verde, OMSA, 46(M), РОССИЯ', $bermudas->getName());
        self::assertSame('1320.00', $bermudas->getPrice());
        self::assertSame('33.33', $bermudas->getDiscount());
        self::assertSame('Grigio/Verde', $this->attributesOf($bermudas)['Цвет']);
        self::assertSame('OMSA', $this->attributesOf($bermudas)['Бренд']);
        self::assertArrayNotHasKey('seo h1', $this->attributesOf($bermudas));
        self::assertArrayNotHasKey('Ссылки на фото', $this->attributesOf($bermudas));
        self::assertSame(
            [
                'http://catalog.collant.ru/pics/SNL-504038_m.jpg',
                'http://catalog.collant.ru/pics/SNL-504038_b2.jpg',
                'http://catalog.collant.ru/pics/SNL-504038_b1.jpg',
                'http://catalog.collant.ru/pics/SNL-504038_p.jpg',
            ],
            array_map(static fn(ProductImage $image): string => $image->getUrl(), $bermudas->getImages()),
        );
        self::assertFileExists($this->storage . '/' . $bermudas->getImages()[0]->getPath());
    }

    public function testRepeatedImportUpdatesInsteadOfDuplicating(): void
    {
        $this->import(self::EXAMPLE);
        $ids = $this->em->getConnection()->fetchFirstColumn('SELECT id FROM products ORDER BY id');

        $report = $this->import(self::EXAMPLE);

        self::assertSame([0, 40, 0], [$report->created, $report->updated, $report->failed]);
        self::assertSame($ids, $this->em->getConnection()->fetchFirstColumn('SELECT id FROM products ORDER BY id'));
        self::assertSame(615, $this->countRows('product_attributes'));
        self::assertSame(131, $this->countRows('product_images'));
    }

    public function testRepeatedImportAppliesChanges(): void
    {
        $this->import($this->file([
            ['Pp7', 'Легинсы, Avorio, 50', 'Старое описание', '1599,00', '1066,00', 'Avorio', '50 (XL)', 'http://a.test/1.jpg, http://a.test/2.jpg', ''],
        ]));

        $report = $this->import($this->file([
            ['Pp7', 'Легинсы, Avorio, 52', '', '1499,00', '', 'Bianco', '', 'http://a.test/2.jpg', 'http://a.test/3.jpg'],
        ]));

        $product = $this->container->get(ProductRepository::class)->findByExternalCode('Pp7');

        self::assertSame(1, $report->updated);
        self::assertNotNull($product);
        self::assertSame('Легинсы, Avorio, 52', $product->getName());
        self::assertNull($product->getDescription());
        self::assertSame('1499.00', $product->getPrice());
        self::assertNull($product->getDiscount());
        self::assertSame(['Цвет' => 'Bianco'], $this->attributesOf($product));
        self::assertSame(
            ['http://a.test/2.jpg' => 0, 'http://a.test/3.jpg' => 1],
            array_column(array_map(static fn(ProductImage $image): array => [$image->getUrl(), $image->getPosition()], $product->getImages()), 1, 0),
        );
    }

    public function testInvalidRowsDoNotStopImport(): void
    {
        $report = $this->import($this->file([
            ['ok-1', 'Боди женское, Nero', '', '1899,00', '1266,00', 'Nero', '', '', ''],
            ['', 'Без кода', '', '100', '', '', '', '', ''],
            ['bad-price', 'Цена текстом', '', 'договорная', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', ''],
            ['cheap', 'Ниже закупки', '', '500', '640', '', '', 'ftp://a.test/1.jpg', ''],
            ['ok-1', 'Боди женское, Nero, повтор', '', '1899,00', '1266,00', '', '', '', ''],
        ]));

        self::assertSame([5, 2, 1, 2], [$report->total, $report->created, $report->updated, $report->failed]);
        self::assertEquals(
            [
                new ImportIssue(3, null, ImportIssueLevel::Error, 'Не заполнен внешний код'),
                new ImportIssue(4, 'bad-price', ImportIssueLevel::Error, 'Цена продажи «договорная» не является числом'),
                new ImportIssue(6, 'cheap', ImportIssueLevel::Warning, 'Закупочная цена выше цены продажи, скидка не рассчитана'),
                new ImportIssue(6, 'cheap', ImportIssueLevel::Warning, 'Некорректная ссылка на изображение: ftp://a.test/1.jpg'),
                new ImportIssue(7, 'ok-1', ImportIssueLevel::Warning, 'Внешний код уже был в строке 2, товар перезаписан данными этой строки'),
            ],
            $report->issues,
        );
        self::assertSame(2, $this->countRows('products'));
        self::assertSame(
            'Боди женское, Nero, повтор',
            $this->container->get(ProductRepository::class)->findByExternalCode('ok-1')?->getName(),
        );
    }

    public function testBrokenImageLinkKeepsProduct(): void
    {
        $this->container->get(FakeImageDownloader::class)->breakUrl('http://a.test/missing.jpg');

        $report = $this->import($this->file([
            ['p1', 'Первый', '', '100', '', '', '', 'http://a.test/missing.jpg, http://a.test/ok.jpg', ''],
            ['p2', 'Второй', '', '100', '', '', '', 'http://a.test/missing.jpg', ''],
        ]));

        self::assertSame(2, $report->created);
        self::assertSame(
            [
                'Не удалось скачать изображение http://a.test/missing.jpg: сервер ответил кодом 404',
                'Не удалось скачать изображение http://a.test/missing.jpg: сервер ответил кодом 404',
            ],
            array_map(static fn(ImportIssue $issue): string => $issue->message, $report->issues),
        );
        self::assertSame(1, $this->countRows('product_images'));
    }

    public function testImageThatStoppedDownloadingStaysAttached(): void
    {
        $file = $this->file([['p1', 'Первый', '', '100', '', '', '', 'http://a.test/1.jpg', '']]);
        $this->import($file);
        new Filesystem()->remove($this->storage);
        $this->container->get(FakeImageDownloader::class)->breakUrl('http://a.test/1.jpg');

        $report = $this->import($file);

        self::assertSame(1, $report->updated);
        self::assertCount(1, $report->issues);
        self::assertSame(1, $this->countRows('product_images'));
    }

    public function testDatabaseErrorSkipsOnlyThatRow(): void
    {
        $connection = $this->em->getConnection();
        $connection->executeStatement(<<<'SQL'
            CREATE FUNCTION reject_test_product() RETURNS trigger AS $$
            BEGIN
                IF NEW.external_code = 'rejected' THEN
                    RAISE EXCEPTION 'отклонено триггером';
                END IF;
                RETURN NEW;
            END
            $$ LANGUAGE plpgsql
            SQL);
        $connection->executeStatement('CREATE TRIGGER reject_test_product BEFORE INSERT ON products FOR EACH ROW EXECUTE FUNCTION reject_test_product()');

        $report = $this->import($this->file([
            ['before', 'До', '', '100', '', 'Nero', '', '', ''],
            ['rejected', 'Отклонённый', '', '100', '', 'Nero', '', '', ''],
            ['after', 'После', '', '100', '', 'Nero', '', '', ''],
        ]));

        self::assertSame([2, 1], [$report->created, $report->failed]);
        self::assertEquals(
            [new ImportIssue(3, 'rejected', ImportIssueLevel::Error, 'Не удалось сохранить товар в базу данных')],
            $report->issues,
        );
        self::assertEqualsCanonicalizing(['before', 'after'], $connection->fetchFirstColumn('SELECT external_code FROM products'));
        self::assertSame(2, $this->countRows('product_attributes'));
    }

    public function testMissingRequiredColumns(): void
    {
        $path = $this->files[] = XlsxFile::create([['Внешний код', 'Наименование', 'Цена'], ['a', 'b', '1']]);

        $this->expectException(ImportFileException::class);
        $this->expectExceptionMessage('В файле нет обязательных колонок: Цена: Цена продажи');

        $this->import($path);
    }

    private function import(string $path): InMemoryImportReport
    {
        $report = new InMemoryImportReport();
        $this->container->get(ProductImporter::class)->import($path, $report);
        $this->em->clear();

        return $report;
    }

    /**
     * @param list<list<string>> $products
     */
    private function file(array $products): string
    {
        return $this->files[] = XlsxFile::withProducts($products);
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
