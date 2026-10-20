<?php

declare(strict_types=1);

namespace App\Service\Import;

final readonly class ImportProgress
{
    public function __construct(
        public int $total,
        public int $processed,
        public int $created,
        public int $updated,
        public int $failed,
        public int $warnings,
    ) {}
}
