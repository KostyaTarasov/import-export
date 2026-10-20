<?php

declare(strict_types=1);

namespace App\Service\Image;

interface ImageDownloader
{
    /**
     * @param list<string> $urls
     */
    public function download(array $urls): ImageDownloadResult;
}
