<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Exception\FileTooLargeException;
use App\Exception\UnsupportedFileException;
use App\Exception\ValidationException;
use finfo;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use ZipArchive;

final readonly class ImportFileValidator
{
    private const array MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
    ];

    public function __construct(
        private int $maxSize,
    ) {}

    public function validate(?UploadedFileInterface $file): UploadedFileInterface
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(['file' => 'Выберите xlsx-файл для загрузки']);
        }

        match ($file->getError()) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => throw $this->tooLarge(),
            UPLOAD_ERR_PARTIAL => throw new ValidationException(['file' => 'Файл загрузился не полностью, попробуйте ещё раз']),
            default => throw new RuntimeException(sprintf('Не удалось принять загруженный файл, код ошибки %d', $file->getError())),
        };

        if (($file->getSize() ?? 0) > $this->maxSize) {
            throw $this->tooLarge();
        }

        if (strtolower(pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new UnsupportedFileException('Нужен файл в формате .xlsx');
        }

        $path = $file->getStream()->getMetadata('uri');

        if (!is_string($path) || !in_array(new finfo(FILEINFO_MIME_TYPE)->file($path), self::MIME_TYPES, true) || !$this->isWorkbook($path)) {
            throw new UnsupportedFileException('Файл повреждён или это не xlsx');
        }

        return $file;
    }

    private function isWorkbook(string $path): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        $found = $zip->locateName('xl/workbook.xml') !== false;
        $zip->close();

        return $found;
    }

    private function tooLarge(): FileTooLargeException
    {
        return new FileTooLargeException(sprintf('Файл больше %d МБ', intdiv($this->maxSize, 1024 * 1024)));
    }
}
