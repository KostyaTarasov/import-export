<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Import;

use App\Exception\ApiException;
use App\Exception\FileTooLargeException;
use App\Exception\UnsupportedFileException;
use App\Exception\ValidationException;
use App\Service\Import\ImportFileValidator;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\UploadedFile;
use ZipArchive;

final class ImportFileValidatorTest extends TestCase
{
    private const string EXAMPLE = __DIR__ . '/../../../fixtures/import-example.xlsx';
    private const int MAX_SIZE = 10 * 1024 * 1024;

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->files, is_file(...)));
    }

    public function testAcceptsXlsx(): void
    {
        $file = $this->upload(self::EXAMPLE, 'Товары.XLSX');

        self::assertSame($file, $this->validator()->validate($file));
    }

    public function testMissingFile(): void
    {
        $this->assertRejected(null, ValidationException::class, 'Проверьте параметры запроса');
        $this->assertRejected(new UploadedFile('', null, null, 0, UPLOAD_ERR_NO_FILE), ValidationException::class, 'Проверьте параметры запроса');
    }

    public function testPartialUpload(): void
    {
        $this->assertRejected($this->upload(self::EXAMPLE, 'import.xlsx', error: UPLOAD_ERR_PARTIAL), ValidationException::class, 'Проверьте параметры запроса');
    }

    public function testTooLarge(): void
    {
        $this->assertRejected($this->upload(self::EXAMPLE, 'import.xlsx', size: self::MAX_SIZE + 1), FileTooLargeException::class, 'Файл больше 10 МБ');
        $this->assertRejected($this->upload(self::EXAMPLE, 'import.xlsx', error: UPLOAD_ERR_INI_SIZE), FileTooLargeException::class, 'Файл больше 10 МБ');
    }

    public function testWrongExtension(): void
    {
        $this->assertRejected($this->upload(self::EXAMPLE, 'import.xls'), UnsupportedFileException::class, 'Нужен файл в формате .xlsx');
    }

    public function testRenamedTextFile(): void
    {
        $path = $this->temporary('Внешний код;Наименование');

        $this->assertRejected($this->upload($path, 'import.xlsx'), UnsupportedFileException::class, 'Файл повреждён или это не xlsx');
    }

    public function testZipWithoutWorkbook(): void
    {
        $path = $this->temporary('');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'не таблица');
        $zip->close();

        $this->assertRejected($this->upload($path, 'import.xlsx'), UnsupportedFileException::class, 'Файл повреждён или это не xlsx');
    }

    /**
     * @param class-string<ApiException> $exception
     */
    private function assertRejected(?UploadedFile $file, string $exception, string $message): void
    {
        try {
            $this->validator()->validate($file);
            self::fail('Файл должен был быть отклонён');
        } catch (ApiException $e) {
            self::assertInstanceOf($exception, $e);
            self::assertSame($message, $e->getMessage());
        }
    }

    private function upload(string $path, string $name, ?int $size = null, int $error = UPLOAD_ERR_OK): UploadedFile
    {
        return new UploadedFile($path, $name, 'application/octet-stream', $size ?? (int) filesize($path), $error);
    }

    private function temporary(string $content): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);

        return $this->files[] = $path;
    }

    private function validator(): ImportFileValidator
    {
        return new ImportFileValidator(self::MAX_SIZE);
    }
}
