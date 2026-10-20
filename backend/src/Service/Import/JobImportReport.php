<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Enum\ImportIssueLevel;
use App\Repository\ImportErrorRepository;
use App\Repository\ImportJobRepository;

final class JobImportReport implements ImportReport
{
    private const int FLUSH_EVERY_ROWS = 50;
    private const float FLUSH_EVERY_SECONDS = 1.0;

    public private(set) int $total = 0;
    public private(set) int $created = 0;
    public private(set) int $updated = 0;
    public private(set) int $failed = 0;
    public private(set) int $warnings = 0;

    /**
     * @var list<ImportIssue>
     */
    private array $pendingIssues = [];

    private float $flushedAt = 0.0;

    public function __construct(
        private readonly string $jobId,
        private readonly ImportJobRepository $jobs,
        private readonly ImportErrorRepository $errors,
    ) {}

    public function start(int $totalRows): void
    {
        $this->total = $totalRows;
        $this->flush();
    }

    public function rowCreated(int $row): void
    {
        ++$this->created;
        $this->flushIfDue();
    }

    public function rowUpdated(int $row): void
    {
        ++$this->updated;
        $this->flushIfDue();
    }

    public function rowFailed(int $row, ?string $externalCode, array $errors): void
    {
        ++$this->failed;

        foreach ($errors as $error) {
            $this->pendingIssues[] = new ImportIssue($row, $externalCode, ImportIssueLevel::Error, $error);
        }

        $this->flushIfDue();
    }

    public function warning(int $row, ?string $externalCode, string $message): void
    {
        ++$this->warnings;
        $this->pendingIssues[] = new ImportIssue($row, $externalCode, ImportIssueLevel::Warning, $message);
    }

    public function finish(): void
    {
        $this->flush();
        $this->jobs->markCompleted($this->jobId);
    }

    public function processed(): int
    {
        return $this->created + $this->updated + $this->failed;
    }

    private function flushIfDue(): void
    {
        if ($this->processed() % self::FLUSH_EVERY_ROWS === 0 || microtime(true) - $this->flushedAt >= self::FLUSH_EVERY_SECONDS) {
            $this->flush();
        }
    }

    private function flush(): void
    {
        $this->errors->add($this->jobId, $this->pendingIssues);
        $this->pendingIssues = [];

        $this->jobs->saveProgress($this->jobId, new ImportProgress(
            $this->total,
            $this->processed(),
            $this->created,
            $this->updated,
            $this->failed,
            $this->warnings,
        ));

        $this->flushedAt = microtime(true);
    }
}
