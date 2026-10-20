<?php

declare(strict_types=1);

namespace App\Service\Image;

final readonly class ImageDownloadResult
{
    /**
     * @param array<string, string> $paths
     * @param array<string, string> $errors
     */
    public function __construct(
        public array $paths = [],
        public array $errors = [],
    ) {}
}
