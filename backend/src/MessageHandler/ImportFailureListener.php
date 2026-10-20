<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ImportProductsMessage;
use App\Repository\ImportJobRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

final readonly class ImportFailureListener implements EventSubscriberInterface
{
    public function __construct(
        private ImportJobRepository $jobs,
        private LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [WorkerMessageFailedEvent::class => 'onMessageFailed'];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if (!$message instanceof ImportProductsMessage) {
            return;
        }

        $error = $event->getThrowable()->getPrevious() ?? $event->getThrowable();

        if ($event->willRetry()) {
            $this->logger->warning('Импорт {job} прерван ошибкой, будет повторная попытка', ['job' => $message->jobId, 'exception' => $error]);

            return;
        }

        $this->jobs->markFailed($message->jobId, 'Импорт прервался из-за внутренней ошибки. Загрузите файл ещё раз');
        $this->logger->error('Импорт {job} не удался после всех попыток', ['job' => $message->jobId, 'exception' => $error]);
    }
}
