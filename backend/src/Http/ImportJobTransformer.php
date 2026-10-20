<?php

declare(strict_types=1);

namespace App\Http;

use App\Entity\ImportError;
use App\Entity\ImportJob;

final readonly class ImportJobTransformer
{
    /**
     * @return array<string, mixed>
     */
    public function job(ImportJob $job): array
    {
        return [
            'id' => $job->getId(),
            'status' => $job->getStatus()->value,
            'fileName' => $job->getOriginalName(),
            'totalRows' => $job->getTotalRows(),
            'processedRows' => $job->getProcessedRows(),
            'createdCount' => $job->getCreatedCount(),
            'updatedCount' => $job->getUpdatedCount(),
            'failedCount' => $job->getFailedCount(),
            'warningCount' => $job->getWarningCount(),
            'error' => $job->getErrorMessage(),
            'createdAt' => $job->getCreatedAt()->format(DATE_ATOM),
            'startedAt' => $job->getStartedAt()?->format(DATE_ATOM),
            'finishedAt' => $job->getFinishedAt()?->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function error(ImportError $error): array
    {
        return [
            'row' => $error->getRowNumber(),
            'externalCode' => $error->getExternalCode(),
            'level' => $error->getLevel()->value,
            'message' => $error->getMessage(),
        ];
    }
}
