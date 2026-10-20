<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Dto\Page;
use App\Dto\PageRequest;
use App\Entity\ImportError;
use App\Entity\ImportJob;
use App\Entity\User;
use App\Exception\NotFoundException;
use App\Exception\ServiceUnavailableException;
use App\Message\ImportProductsMessage;
use App\Repository\ImportErrorRepository;
use App\Repository\ImportJobRepository;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ImportService
{
    public function __construct(
        private ImportFileValidator $validator,
        private ImportJobRepository $jobs,
        private ImportErrorRepository $errors,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
        private string $storagePath,
    ) {}

    public function queue(?UploadedFileInterface $file, User $user): ImportJob
    {
        $file = $this->validator->validate($file);

        $job = new ImportJob($user, $file->getClientFilename() ?? 'import.xlsx');
        $target = $this->storagePath . '/' . $job->getFilePath();

        if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0o775, true) && !is_dir(dirname($target))) {
            throw new RuntimeException(sprintf('Не удалось создать каталог %s', dirname($target)));
        }

        $file->moveTo($target);
        $this->jobs->save($job);

        try {
            $this->bus->dispatch(new ImportProductsMessage($job->getId()));
        } catch (TransportException $e) {
            $this->jobs->markFailed($job->getId(), 'Очередь импорта недоступна');
            unlink($target);
            $this->logger->error('Не удалось поставить импорт {job} в очередь', ['job' => $job->getId(), 'exception' => $e]);

            throw new ServiceUnavailableException('Импорт временно недоступен, попробуйте позже');
        }

        $this->logger->info('Файл {file} поставлен в очередь импорта', [
            'file' => $job->getOriginalName(),
            'job' => $job->getId(),
            'user' => $user->getEmail(),
        ]);

        return $job;
    }

    public function get(string $id, User $user): ImportJob
    {
        $job = $this->jobs->find($id);

        if ($job === null || !$job->belongsTo($user)) {
            throw new NotFoundException('Задача импорта не найдена');
        }

        return $job;
    }

    /**
     * @return Page<ImportError>
     */
    public function errors(ImportJob $job, PageRequest $request): Page
    {
        return $this->errors->findPageByJob($job, $request);
    }
}
