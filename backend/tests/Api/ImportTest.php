<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\ImportJob;
use App\Message\ImportProductsMessage;
use App\Repository\ImportJobRepository;
use App\Service\Image\ImageDownloader;
use App\Service\Import\ImportFileValidator;
use App\Tests\Support\ApiTestCase;
use App\Tests\Support\FakeImageDownloader;
use App\Tests\Support\XlsxFile;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Worker;

use function DI\get;

final class ImportTest extends ApiTestCase
{
    private const string EXAMPLE = __DIR__ . '/../fixtures/import-example.xlsx';

    private string $storage;

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/api-storage-' . bin2hex(random_bytes(4));

        parent::setUp();

        $this->authenticate();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        new Filesystem()->remove($this->storage);
    }

    protected function definitions(): array
    {
        return [
            'path.storage' => $this->storage,
            ImageDownloader::class => get(FakeImageDownloader::class),
        ];
    }

    public function testUploadQueuesJob(): void
    {
        $response = $this->upload('/api/imports', self::EXAMPLE, 'import example.xlsx');
        $data = $this->decode($response)['data'];

        self::assertSame(202, $response->getStatusCode());
        self::assertIsArray($data);
        self::assertSame('/api/imports/' . $data['id'], $response->getHeaderLine('Location'));
        self::assertSame('pending', $data['status']);
        self::assertSame('import example.xlsx', $data['fileName']);
        self::assertFileExists(sprintf('%s/imports/%s.xlsx', $this->storage, $data['id']));

        $sent = $this->transport()->getSent();
        self::assertCount(1, $sent);
        self::assertEquals(new ImportProductsMessage($data['id']), $sent[0]->getMessage());
    }

    public function testWorkerProcessesUploadedFile(): void
    {
        $id = $this->decode($this->upload('/api/imports', self::EXAMPLE, 'import example.xlsx'))['data']['id'] ?? null;
        self::assertIsString($id);

        $this->runWorker();

        $job = $this->decode($this->request('GET', '/api/imports/' . $id))['data'];
        self::assertIsArray($job);
        self::assertSame('completed', $job['status']);
        self::assertSame([40, 40, 40, 0], [$job['totalRows'], $job['processedRows'], $job['createdCount'], $job['failedCount']]);
        self::assertSame(27, $this->decode($this->request('GET', '/api/products?name=' . urlencode('легинсы')))['meta']['total'] ?? null);
    }

    public function testErrorReport(): void
    {
        $file = XlsxFile::withProducts([
            ['', 'Без кода', '', '100', '', '', '', '', ''],
            ['ok', 'Нормальный', '', '100', '', '', '', '', ''],
            ['cheap', 'Дешевле закупки', '', '100', '200', '', '', '', ''],
            ['bad', 'Цена текстом', '', 'договорная', '', '', '', '', ''],
        ]);
        $id = $this->decode($this->upload('/api/imports', $file, 'ошибки.xlsx'))['data']['id'] ?? null;
        unlink($file);
        self::assertIsString($id);

        $this->runWorker();

        $job = $this->decode($this->request('GET', '/api/imports/' . $id))['data'];
        $firstPage = $this->decode($this->request('GET', '/api/imports/' . $id . '/errors?limit=2'));
        $secondPage = $this->decode($this->request('GET', '/api/imports/' . $id . '/errors?limit=2&page=2'));

        self::assertIsArray($job);
        self::assertSame([2, 2, 1], [$job['createdCount'], $job['failedCount'], $job['warningCount']]);
        self::assertSame(['page' => 1, 'limit' => 2, 'total' => 3, 'totalPages' => 2], $firstPage['meta']);
        self::assertSame(
            [
                ['row' => 2, 'externalCode' => null, 'level' => 'error', 'message' => 'Не заполнен внешний код'],
                ['row' => 4, 'externalCode' => 'cheap', 'level' => 'warning', 'message' => 'Закупочная цена выше цены продажи, скидка не рассчитана'],
            ],
            $firstPage['data'],
        );
        self::assertSame(
            [['row' => 5, 'externalCode' => 'bad', 'level' => 'error', 'message' => 'Цена продажи «договорная» не является числом']],
            $secondPage['data'],
        );
    }

    public function testMissingFile(): void
    {
        $response = $this->request('POST', '/api/imports');

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(['file' => 'Выберите xlsx-файл для загрузки'], $this->decode($response)['error']['fields'] ?? null);
    }

    public function testNotXlsx(): void
    {
        $response = $this->upload('/api/imports', self::EXAMPLE, 'import.csv');

        self::assertSame(415, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'unsupported_file', 'message' => 'Нужен файл в формате .xlsx']],
            $this->decode($response),
        );
        self::assertSame([], $this->transport()->getSent());
    }

    public function testTooLarge(): void
    {
        $this->replaceService(ImportFileValidator::class, new ImportFileValidator(1024 * 1024));

        $response = $this->upload('/api/imports', self::EXAMPLE, 'import.xlsx', size: 5 * 1024 * 1024);

        self::assertSame(413, $response->getStatusCode());
        self::assertSame('Файл больше 1 МБ', $this->decode($response)['error']['message'] ?? null);
    }

    public function testRateLimit(): void
    {
        for ($i = 0; $i < 5; ++$i) {
            self::assertSame(202, $this->upload('/api/imports', self::EXAMPLE, 'import.xlsx')->getStatusCode());
        }

        $response = $this->upload('/api/imports', self::EXAMPLE, 'import.xlsx');

        self::assertSame(429, $response->getStatusCode());
        self::assertGreaterThan(0, (int) $response->getHeaderLine('Retry-After'));
        self::assertStringStartsWith('Слишком много загрузок. Повторите через', $this->decode($response)['error']['message'] ?? '');
    }

    public function testQueueUnavailable(): void
    {
        $this->replaceService(MessageBusInterface::class, new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                throw new TransportException('Connection refused');
            }
        });

        $response = $this->upload('/api/imports', self::EXAMPLE, 'import.xlsx');

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Импорт временно недоступен, попробуйте позже', $this->decode($response)['error']['message'] ?? null);
        self::assertSame(['failed'], $this->em->getConnection()->fetchFirstColumn('SELECT status FROM import_jobs'));
        self::assertSame([], glob($this->storage . '/imports/*.xlsx'));
    }

    public function testJobOfAnotherUserIsHidden(): void
    {
        $job = new ImportJob($this->createUser('other@example.com'), 'чужой.xlsx');
        $this->container->get(ImportJobRepository::class)->save($job);

        $response = $this->request('GET', '/api/imports/' . $job->getId());

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Задача импорта не найдена', $this->decode($response)['error']['message'] ?? null);
    }

    public function testUploadRequiresToken(): void
    {
        $this->useToken(null);

        self::assertSame(401, $this->upload('/api/imports', self::EXAMPLE, 'import.xlsx')->getStatusCode());
    }

    private function transport(): InMemoryTransport
    {
        $transport = $this->container->get(TransportInterface::class);
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }

    private function runWorker(): void
    {
        $dispatcher = $this->container->get(EventDispatcher::class);
        $dispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(1));

        new Worker(['async' => $this->transport()], $this->container->get(MessageBusInterface::class), $dispatcher)->run(['sleep' => 0]);

        $this->em->clear();
    }
}
