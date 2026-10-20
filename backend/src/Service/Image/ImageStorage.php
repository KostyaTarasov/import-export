<?php

declare(strict_types=1);

namespace App\Service\Image;

use RuntimeException;

final readonly class ImageStorage
{
    public function __construct(
        private string $root,
    ) {}

    public function pathFor(string $url, string $extension): string
    {
        $hash = sha1($url);

        return sprintf('images/%s/%s.%s', substr($hash, 0, 2), $hash, $extension);
    }

    public function exists(string $path): bool
    {
        return is_file($this->absolute($path));
    }

    public function copy(string $source, string $path): void
    {
        $target = $this->absolute($path);
        $directory = dirname($target);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Не удалось создать каталог %s', $directory));
        }

        if (!copy($source, $target)) {
            throw new RuntimeException(sprintf('Не удалось скопировать %s в %s', $source, $target));
        }
    }

    private function absolute(string $path): string
    {
        return $this->root . '/' . $path;
    }
}
