<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Doctrine\ResettableEntityManager;
use App\Exception\ImportFileException;
use App\Message\ImportProductsMessage;
use App\Repository\ImportErrorRepository;
use App\Repository\ImportJobRepository;
use App\Service\Import\JobImportReport;
use App\Service\Import\ProductImporter;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Log\LoggerInterface;

final readonly class ImportProductsHandler
{
    public function __construct(
        private ImportJobRepository $jobs,
        private ImportErrorRepository $errors,
        private ProductImporter $importer,
        private ResettableEntityManager $em,
        private LoggerInterface $logger,
        private string $storagePath,
    ) {}

    public function __invoke(ImportProductsMessage $message): void
    {
        $this->reconnectIfNeeded();
        $this->em->reset();

        $job = $this->jobs->find($message->jobId);

        if ($job === null) {
            $this->logger->warning('Задача импорта {job} не найдена', ['job' => $message->jobId]);

            return;
        }

        if ($job->getStatus()->isFinished()) {
            return;
        }

        $this->jobs->markProcessing($job->getId());
        $this->errors->deleteByJob($job->getId());
        $this->logger->info('Импорт {job} начат', ['job' => $job->getId(), 'file' => $job->getOriginalName()]);

        $path = $this->storagePath . '/' . $job->getFilePath();
        $report = new JobImportReport($job->getId(), $this->jobs, $this->errors);

        try {
            $this->importer->import($path, $report);
        } catch (ImportFileException $e) {
            $this->jobs->markFailed($job->getId(), $e->getMessage());
            $this->removeFile($path);
            $this->logger->warning('Импорт {job} отклонён: {reason}', ['job' => $job->getId(), 'reason' => $e->getMessage()]);

            return;
        }

        $this->removeFile($path);
        $this->logger->info('Импорт {job} завершён', [
            'job' => $job->getId(),
            'rows' => $report->total,
            'created' => $report->created,
            'updated' => $report->updated,
            'failed' => $report->failed,
            'warnings' => $report->warnings,
        ]);
    }

    private function reconnectIfNeeded(): void
    {
        $connection = $this->em->getConnection();

        try {
            $connection->executeQuery('SELECT 1');
        } catch (DbalException) {
            $connection->close();
        }
    }

    private function removeFile(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
