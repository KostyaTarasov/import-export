<?php

declare(strict_types=1);

namespace App\Service\Image;

use App\Exception\ImageDownloadException;
use finfo;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class HttpImageDownloader implements ImageDownloader
{
    private const array EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(
        private HttpClientInterface $client,
        private ImageStorage $storage,
        private LoggerInterface $logger,
        private int $timeout,
        private int $maxSize,
    ) {}

    public function download(array $urls): ImageDownloadResult
    {
        $paths = [];
        $errors = [];
        $responses = [];

        foreach ($urls as $url) {
            $stored = $this->storage->find($url);

            if ($stored !== null) {
                $paths[$url] = $stored;

                continue;
            }

            $responses[$url] = $this->client->request('GET', $url, [
                'timeout' => $this->timeout,
                'max_duration' => $this->timeout * 4,
                'max_redirects' => 3,
                'on_progress' => function (int $downloaded, int $total): void {
                    if ($downloaded > $this->maxSize || $total > $this->maxSize) {
                        throw new ImageDownloadException($this->tooLargeMessage());
                    }
                },
            ]);
        }

        foreach ($responses as $url => $response) {
            try {
                $paths[$url] = $this->save($url, $response);
            } catch (ImageDownloadException $e) {
                $errors[$url] = $e->getMessage();
            } catch (TransportExceptionInterface $e) {
                if ($e->getPrevious() instanceof ImageDownloadException) {
                    $errors[$url] = $e->getPrevious()->getMessage();

                    continue;
                }

                $this->logger->warning('Не удалось скачать изображение', ['url' => $url, 'reason' => $e->getMessage()]);
                $errors[$url] = 'сервер недоступен или не ответил вовремя';
            }
        }

        return new ImageDownloadResult($paths, $errors);
    }

    private function save(string $url, ResponseInterface $response): string
    {
        $status = $response->getStatusCode();

        if ($status !== 200) {
            throw new ImageDownloadException(sprintf('сервер ответил кодом %d', $status));
        }

        $content = $response->getContent();

        if (strlen($content) > $this->maxSize) {
            throw new ImageDownloadException($this->tooLargeMessage());
        }

        $extension = self::EXTENSIONS[new finfo(FILEINFO_MIME_TYPE)->buffer($content)] ?? null;

        if ($extension === null || getimagesizefromstring($content) === false) {
            throw new ImageDownloadException('по ссылке не изображение JPEG, PNG, WebP или GIF');
        }

        return $this->storage->store($url, $content, $extension);
    }

    private function tooLargeMessage(): string
    {
        return sprintf('файл больше %d МБ', intdiv($this->maxSize, 1024 * 1024));
    }
}
