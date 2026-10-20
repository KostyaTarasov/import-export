<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Image;

use App\Service\Image\ImageStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ImageStorageTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/storage-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->root);
    }

    public function testPathDependsOnlyOnUrl(): void
    {
        $storage = new ImageStorage($this->root);
        $url = 'http://catalog.collant.ru/pics/SNL-504038_m.jpg';
        $hash = sha1($url);

        self::assertSame(sprintf('images/%s/%s.jpg', substr($hash, 0, 2), $hash), $storage->pathFor($url, 'jpg'));
        self::assertSame($storage->pathFor($url, 'jpg'), $storage->pathFor($url, 'jpg'));
        self::assertNotSame($storage->pathFor($url, 'jpg'), $storage->pathFor('http://catalog.collant.ru/pics/SNL-504038_b1.jpg', 'jpg'));
    }

    public function testCopyCreatesDirectories(): void
    {
        $storage = new ImageStorage($this->root);
        $path = $storage->pathFor('http://catalog.collant.ru/pics/SNL-504038_m.jpg', 'jpg');

        self::assertFalse($storage->exists($path));

        $storage->copy(dirname(__DIR__, 4) . '/fixtures/images/bermudas.jpg', $path);

        self::assertTrue($storage->exists($path));
        self::assertFileEquals(dirname(__DIR__, 4) . '/fixtures/images/bermudas.jpg', $this->root . '/' . $path);
    }
}
