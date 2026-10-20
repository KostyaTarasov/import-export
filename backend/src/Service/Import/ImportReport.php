<?php

declare(strict_types=1);

namespace App\Service\Import;

interface ImportReport
{
    public function start(int $totalRows): void;

    public function rowCreated(int $row): void;

    public function rowUpdated(int $row): void;

    /**
     * @param list<string> $errors
     */
    public function rowFailed(int $row, ?string $externalCode, array $errors): void;

    public function warning(int $row, ?string $externalCode, string $message): void;

    public function finish(): void;
}
