<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Enum\ImportIssueLevel;

final readonly class ImportIssue
{
    public function __construct(
        public int $row,
        public ?string $externalCode,
        public ImportIssueLevel $level,
        public string $message,
    ) {}
}
