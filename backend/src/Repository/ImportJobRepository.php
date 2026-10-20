<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ImportJob;
use App\Enum\ImportStatus;
use App\Service\Import\ImportProgress;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ImportJobRepository
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function find(string $id): ?ImportJob
    {
        return $this->em->find(ImportJob::class, $id);
    }

    public function save(ImportJob $job): void
    {
        $this->em->persist($job);
        $this->em->flush();
    }

    public function markProcessing(string $id): void
    {
        $this->em->getConnection()->executeStatement(
            'UPDATE import_jobs
             SET status = ?, started_at = now(), finished_at = NULL, error_message = NULL,
                 total_rows = 0, processed_rows = 0, created_count = 0, updated_count = 0, failed_count = 0, warning_count = 0
             WHERE id = ?',
            [ImportStatus::Processing->value, $id],
        );
    }

    public function saveProgress(string $id, ImportProgress $progress): void
    {
        $this->em->getConnection()->update('import_jobs', [
            'total_rows' => $progress->total,
            'processed_rows' => $progress->processed,
            'created_count' => $progress->created,
            'updated_count' => $progress->updated,
            'failed_count' => $progress->failed,
            'warning_count' => $progress->warnings,
        ], ['id' => $id]);
    }

    public function markCompleted(string $id): void
    {
        $this->finish($id, ImportStatus::Completed, null);
    }

    public function markFailed(string $id, string $message): void
    {
        $this->finish($id, ImportStatus::Failed, $message);
    }

    private function finish(string $id, ImportStatus $status, ?string $message): void
    {
        $this->em->getConnection()->executeStatement(
            'UPDATE import_jobs SET status = ?, error_message = ?, finished_at = now() WHERE id = ?',
            [$status->value, $message, $id],
        );
    }
}
