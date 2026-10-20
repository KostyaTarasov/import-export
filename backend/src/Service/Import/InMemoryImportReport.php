<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Enum\ImportIssueLevel;

final class InMemoryImportReport implements ImportReport
{
    public private(set) int $total = 0;
    public private(set) int $created = 0;
    public private(set) int $updated = 0;
    public private(set) int $failed = 0;
    public private(set) bool $finished = false;

    /**
     * @var list<ImportIssue>
     */
    public private(set) array $issues = [];

    public function start(int $totalRows): void
    {
        $this->total = $totalRows;
    }

    public function rowCreated(int $row): void
    {
        ++$this->created;
    }

    public function rowUpdated(int $row): void
    {
        ++$this->updated;
    }

    public function rowFailed(int $row, ?string $externalCode, array $errors): void
    {
        ++$this->failed;

        foreach ($errors as $error) {
            $this->issues[] = new ImportIssue($row, $externalCode, ImportIssueLevel::Error, $error);
        }
    }

    public function warning(int $row, ?string $externalCode, string $message): void
    {
        $this->issues[] = new ImportIssue($row, $externalCode, ImportIssueLevel::Warning, $message);
    }

    public function finish(): void
    {
        $this->finished = true;
    }

    public function processed(): int
    {
        return $this->created + $this->updated + $this->failed;
    }

    public function warnings(): int
    {
        return count(array_filter($this->issues, static fn(ImportIssue $issue): bool => $issue->level === ImportIssueLevel::Warning));
    }
}
