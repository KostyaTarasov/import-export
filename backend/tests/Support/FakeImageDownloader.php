<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Service\Image\ImageDownloader;
use App\Service\Image\ImageDownloadResult;
use App\Service\Image\ImageStorage;

final class FakeImageDownloader implements ImageDownloader
{
    /**
     * @var array<string, true>
     */
    private array $broken = [];

    public function __construct(
        private readonly ImageStorage $storage,
    ) {}

    public function breakUrl(string $url): void
    {
        $this->broken[$url] = true;
    }

    public function download(array $urls): ImageDownloadResult
    {
        $paths = [];
        $errors = [];

        foreach ($urls as $url) {
            if (isset($this->broken[$url])) {
                $errors[$url] = 'сервер ответил кодом 404';

                continue;
            }

            $paths[$url] = $this->storage->find($url)
                ?? $this->storage->store($url, (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/images/leggings.jpg'), 'jpg');
        }

        return new ImageDownloadResult($paths, $errors);
    }
}
