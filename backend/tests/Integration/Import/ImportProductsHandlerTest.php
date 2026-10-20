<?php

declare(strict_types=1);

namespace App\Tests\Integration\Import;

use App\Entity\ImportJob;
use App\Enum\ImportStatus;
use App\Message\ImportProductsMessage;
use App\MessageHandler\ImportProductsHandler;
use App\Repository\ImportJobRepository;
use App\Service\Image\ImageDownloader;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\FakeImageDownloader;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Uid\Uuid;

use function DI\get;

final class ImportProductsHandlerTest extends DatabaseTestCase
{
    private const string EXAMPLE = __DIR__ . '/../../fixtures/import-example.xlsx';

    private string $storage;

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/handler-storage-' . bin2hex(random_bytes(4));

        parent::setUp();
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

    public function testCompletesJobAndRemovesFile(): void
    {
        $job = $this->createJob(self::EXAMPLE);

        $this->handle($job);
        $job = $this->reload($job);

        self::assertSame(ImportStatus::Completed, $job->getStatus());
        self::assertSame(
            [40, 40, 40, 0, 0, 0],
            [$job->getTotalRows(), $job->getProcessedRows(), $job->getCreatedCount(), $job->getUpdatedCount(), $job->getFailedCount(), $job->getWarningCount()],
        );
        self::assertNotNull($job->getStartedAt());
        self::assertNotNull($job->getFinishedAt());
        self::assertNull($job->getErrorMessage());
        self::assertFileDoesNotExist($this->storage . '/' . $job->getFilePath());
        self::assertSame(40, $this->countRows('products'));
    }

    public function testSavesIssuesToReport(): void
    {
        $this->container->get(FakeImageDownloader::class)->breakUrl('http://catalog.collant.ru/pics/SNL-504038_b1.jpg');
        $job = $this->createJob(self::EXAMPLE);

        $this->handle($job);

        self::assertSame(1, $this->reload($job)->getWarningCount());
        self::assertSame(
            [['row_number' => 2, 'external_code' => '3UHfAid1jaMiwgBuNvnsf3', 'level' => 'warning']],
            $this->em->getConnection()->fetchAllAssociative('SELECT row_number, external_code, level FROM import_errors'),
        );
    }

    public function testUnreadableFileFailsJob(): void
    {
        $source = (string) tempnam(sys_get_temp_dir(), 'broken');
        file_put_contents($source, 'не xlsx');
        $job = $this->createJob($source);
        unlink($source);

        $this->handle($job);
        $job = $this->reload($job);

        self::assertSame(ImportStatus::Failed, $job->getStatus());
        self::assertSame('Не удалось прочитать xlsx-файл', $job->getErrorMessage());
        self::assertFileDoesNotExist($this->storage . '/' . $job->getFilePath());
    }

    public function testRetryStartsFromScratch(): void
    {
        $job = $this->createJob(self::EXAMPLE);
        $jobs = $this->container->get(ImportJobRepository::class);
        $jobs->markProcessing($job->getId());
        $this->em->getConnection()->insert('import_errors', [
            'import_job_id' => $job->getId(),
            'row_number' => 5,
            'level' => 'error',
            'message' => 'Ошибка прошлой попытки',
        ]);

        $this->handle($job);

        self::assertSame(ImportStatus::Completed, $this->reload($job)->getStatus());
        self::assertSame(0, $this->countRows('import_errors'));
    }

    public function testFinishedJobIsNotImportedAgain(): void
    {
        $job = $this->createJob(self::EXAMPLE);
        $this->container->get(ImportJobRepository::class)->markCompleted($job->getId());

        $this->handle($job);

        self::assertSame(0, $this->countRows('products'));
    }

    public function testUnknownJobIsIgnored(): void
    {
        $this->expectNotToPerformAssertions();

        $this->container->get(ImportProductsHandler::class)(new ImportProductsMessage(Uuid::v7()->toRfc4122()));
    }

    public function testFailedAttemptIsRetried(): void
    {
        $job = $this->createJob(self::EXAMPLE);
        $event = new WorkerMessageFailedEvent(new Envelope(new ImportProductsMessage($job->getId())), 'async', new RuntimeException('База недоступна'));

        $this->container->get(EventDispatcher::class)->dispatch($event);

        self::assertTrue($event->willRetry());
        self::assertSame(ImportStatus::Pending, $this->reload($job)->getStatus());
        self::assertCount(1, $this->transport()->getSent());
    }

    public function testJobFailsWhenRetriesAreExhausted(): void
    {
        $job = $this->createJob(self::EXAMPLE);
        $envelope = new Envelope(new ImportProductsMessage($job->getId()), [new RedeliveryStamp(3)]);
        $event = new WorkerMessageFailedEvent($envelope, 'async', new RuntimeException('База недоступна'));

        $this->container->get(EventDispatcher::class)->dispatch($event);
        $job = $this->reload($job);

        self::assertFalse($event->willRetry());
        self::assertSame(ImportStatus::Failed, $job->getStatus());
        self::assertSame('Импорт прервался из-за внутренней ошибки. Загрузите файл ещё раз', $job->getErrorMessage());
    }

    private function createJob(string $source): ImportJob
    {
        $job = new ImportJob($this->createUser(), 'import example.xlsx');
        new Filesystem()->copy($source, $this->storage . '/' . $job->getFilePath());
        $this->container->get(ImportJobRepository::class)->save($job);

        return $job;
    }

    private function handle(ImportJob $job): void
    {
        $this->container->get(ImportProductsHandler::class)(new ImportProductsMessage($job->getId()));
    }

    private function transport(): InMemoryTransport
    {
        $transport = $this->container->get(TransportInterface::class);
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }

    private function reload(ImportJob $job): ImportJob
    {
        $this->em->clear();

        return $this->container->get(ImportJobRepository::class)->find($job->getId())
            ?? throw new RuntimeException('Задача пропала из базы');
    }
}
