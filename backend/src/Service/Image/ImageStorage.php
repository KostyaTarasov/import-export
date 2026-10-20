<?php

declare(strict_types=1);

namespace App\Service\Image;

use RuntimeException;

final readonly class ImageStorage
{
    private const array EXTENSIONS = ['jpg', 'png', 'webp', 'gif'];

    public function __construct(
        private string $root,
    ) {}

    public function find(string $url): ?string
    {
        foreach (self::EXTENSIONS as $extension) {
            $path = $this->pathFor($url, $extension);

            if (is_file($this->absolute($path))) {
                return $path;
            }
        }

        return null;
    }

    public function store(string $url, string $content, string $extension): string
    {
        $path = $this->pathFor($url, $extension);
        $this->put($path, $content);

        return $path;
    }

    public function put(string $path, string $content): void
    {
        $target = $this->absolute($path);
        $directory = dirname($target);

        if (!is_dir($directory) && !@mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Не удалось создать каталог %s', $directory));
        }

        $temporary = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (file_put_contents($temporary, $content) === false || !rename($temporary, $target)) {
            @unlink($temporary);

            throw new RuntimeException(sprintf('Не удалось сохранить файл %s', $target));
        }
    }

    private function pathFor(string $url, string $extension): string
    {
        $hash = sha1($url);

        return sprintf('images/%s/%s.%s', substr($hash, 0, 2), $hash, $extension);
    }

    private function absolute(string $path): string
    {
        return $this->root . '/' . $path;
    }
}
