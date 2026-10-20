<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Image;

use App\Service\Image\HttpImageDownloader;
use App\Service\Image\ImageStorage;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HttpImageDownloaderTest extends TestCase
{
    private const string URL = 'http://catalog.collant.ru/pics/SNL-504038_m.jpg';

    private string $root;
    private ImageStorage $storage;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/downloads-' . bin2hex(random_bytes(4));
        $this->storage = new ImageStorage($this->root);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->root);
    }

    public function testStoresImage(): void
    {
        $jpeg = $this->jpeg();
        $client = new MockHttpClient(new MockResponse($jpeg, ['response_headers' => ['Content-Type' => 'image/jpeg']]));

        $result = $this->downloader($client)->download([self::URL]);

        self::assertSame([], $result->errors);
        self::assertArrayHasKey(self::URL, $result->paths);
        self::assertStringEndsWith('.jpg', $result->paths[self::URL]);
        self::assertStringEqualsFile($this->root . '/' . $result->paths[self::URL], $jpeg);
    }

    public function testSkipsAlreadyStoredImage(): void
    {
        $path = $this->storage->store(self::URL, $this->jpeg(), 'jpg');
        $client = new MockHttpClient([]);

        $result = $this->downloader($client)->download([self::URL]);

        self::assertSame([self::URL => $path], $result->paths);
        self::assertSame(0, $client->getRequestsCount());
    }

    public function testErrorStatus(): void
    {
        $client = new MockHttpClient(new MockResponse('Forbidden', ['http_code' => 403]));

        $result = $this->downloader($client)->download([self::URL]);

        self::assertSame([self::URL => 'сервер ответил кодом 403'], $result->errors);
        self::assertSame([], $result->paths);
    }

    public function testNotAnImage(): void
    {
        $client = new MockHttpClient(new MockResponse('<html>Страница не найдена</html>', ['response_headers' => ['Content-Type' => 'image/jpeg']]));

        $result = $this->downloader($client)->download([self::URL]);

        self::assertSame([self::URL => 'по ссылке не изображение JPEG, PNG, WebP или GIF'], $result->errors);
        self::assertNull($this->storage->find(self::URL));
    }

    public function testTooLarge(): void
    {
        $client = new MockHttpClient(new MockResponse(str_repeat('x', 1024 * 1024 + 1)));

        $result = $this->downloader($client, maxSize: 1024 * 1024)->download([self::URL]);

        self::assertSame([self::URL => 'файл больше 1 МБ'], $result->errors);
    }

    public function testNetworkError(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['error' => 'Could not resolve host: catalog.collant.ru']));

        $result = $this->downloader($client)->download([self::URL]);

        self::assertSame([self::URL => 'сервер недоступен или не ответил вовремя'], $result->errors);
    }

    public function testOneFailureDoesNotAffectOthers(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
            new MockResponse($this->jpeg()),
        ]);

        $result = $this->downloader($client)->download([self::URL, 'http://catalog.collant.ru/pics/SNL-504038_b1.jpg']);

        self::assertSame([self::URL => 'сервер ответил кодом 404'], $result->errors);
        self::assertArrayHasKey('http://catalog.collant.ru/pics/SNL-504038_b1.jpg', $result->paths);
    }

    private function downloader(MockHttpClient $client, int $maxSize = 10 * 1024 * 1024): HttpImageDownloader
    {
        return new HttpImageDownloader($client, $this->storage, new NullLogger(), 5, $maxSize);
    }

    private function jpeg(): string
    {
        return (string) file_get_contents(dirname(__DIR__, 4) . '/fixtures/images/bodysuit.jpg');
    }
}
