<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Doctrine\ResettableEntityManager;
use App\Exception\ImportFileException;
use App\Exception\InvalidRowException;
use App\Service\Image\ImageDownloader;
use App\Service\Image\ImageDownloadResult;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\ConnectionException;
use Psr\Log\LoggerInterface;

final readonly class ProductImporter
{
    public function __construct(
        private XlsxRowReader $reader,
        private ProductRowMapper $mapper,
        private ProductRowValidator $validator,
        private ImageDownloader $downloader,
        private ProductWriter $writer,
        private ResettableEntityManager $em,
        private LoggerInterface $logger,
    ) {}

    public function import(string $path, ImportReport $report): void
    {
        $missing = $this->mapper->missingColumns($this->reader->headers($path));

        if ($missing !== []) {
            throw new ImportFileException(sprintf('В файле нет обязательных колонок: %s', implode(', ', $missing)));
        }

        $report->start($this->reader->countRows($path));

        $rowsByCode = [];
        $failedImages = [];

        foreach ($this->reader->rows($path) as $number => $cells) {
            $row = $this->mapper->map($cells);
            $code = $row->externalCode === '' ? null : $row->externalCode;

            try {
                $data = $this->validator->validate($row);
            } catch (InvalidRowException $e) {
                $report->rowFailed($number, $code, $e->errors);

                continue;
            }

            foreach ($data->warnings as $warning) {
                $report->warning($number, $code, $warning);
            }

            if (isset($rowsByCode[$data->externalCode])) {
                $report->warning($number, $code, sprintf('Внешний код уже был в строке %d, товар перезаписан данными этой строки', $rowsByCode[$data->externalCode]));
            }

            $rowsByCode[$data->externalCode] = $number;

            $images = $this->downloadImages($data->imageUrls, $failedImages);

            foreach ($images->errors as $url => $reason) {
                $report->warning($number, $code, sprintf('Не удалось скачать изображение %s: %s', $url, $reason));
            }

            try {
                $created = $this->writer->upsert($data, $images->paths);
            } catch (ConnectionException $e) {
                throw $e;
            } catch (DbalException $e) {
                $this->em->reset();
                $this->logger->error('Не удалось сохранить товар', ['row' => $number, 'code' => $code, 'exception' => $e]);
                $report->rowFailed($number, $code, ['Не удалось сохранить товар в базу данных']);

                continue;
            }

            if ($created) {
                $report->rowCreated($number);
            } else {
                $report->rowUpdated($number);
            }

            $this->em->clear();
        }

        $report->finish();
    }

    /**
     * @param list<string> $urls
     * @param array<string, string> $failed
     */
    private function downloadImages(array $urls, array &$failed): ImageDownloadResult
    {
        $result = $this->downloader->download(array_values(array_diff($urls, array_keys($failed))));
        $failed += $result->errors;

        return new ImageDownloadResult($result->paths, array_intersect_key($failed, array_flip($urls)));
    }
}
