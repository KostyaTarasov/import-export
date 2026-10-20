<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Image;

use App\Service\Image\ImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ImageStorageTest extends TestCase
{
    private const string URL = 'http://catalog.collant.ru/pics/SNL-504038_m.jpg';

    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/storage-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->root);
    }

    public function testStoresByUrlHash(): void
    {
        $storage = new ImageStorage($this->root);
        $hash = sha1(self::URL);

        $path = $storage->store(self::URL, 'jpeg-bytes', 'jpg');

        self::assertSame(sprintf('images/%s/%s.jpg', substr($hash, 0, 2), $hash), $path);
        self::assertStringEqualsFile($this->root . '/' . $path, 'jpeg-bytes');
        self::assertSame([], glob($this->root . '/images/*/*.tmp'));
    }

    public function testFindsStoredFileWithAnyExtension(): void
    {
        $storage = new ImageStorage($this->root);

        self::assertNull($storage->find(self::URL));

        $path = $storage->store(self::URL, 'png-bytes', 'png');

        self::assertSame($path, $storage->find(self::URL));
        self::assertNull($storage->find('http://catalog.collant.ru/pics/SNL-504038_b1.jpg'));
    }

    public function testOverwritesExistingFile(): void
    {
        $storage = new ImageStorage($this->root);
        $storage->store(self::URL, 'old', 'jpg');

        $path = $storage->store(self::URL, 'new', 'jpg');

        self::assertStringEqualsFile($this->root . '/' . $path, 'new');
    }
}
