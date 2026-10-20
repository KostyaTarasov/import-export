<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMemoryLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnTimeLimitListener;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Worker;

#[AsCommand(name: 'app:import:worker', description: 'Обрабатывать задачи импорта из очереди')]
final class ImportWorkerCommand extends Command
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly MessageBusInterface $bus,
        private readonly EventDispatcher $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Остановиться через указанное число секунд', '3600')
            ->addOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Остановиться при превышении памяти, МБ', '256');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeLimit = (int) $input->getOption('time-limit');
        $memoryLimit = (int) $input->getOption('memory-limit');

        $this->dispatcher->addSubscriber(new StopWorkerOnTimeLimitListener($timeLimit));
        $this->dispatcher->addSubscriber(new StopWorkerOnMemoryLimitListener($memoryLimit * 1024 * 1024));

        $output->writeln(sprintf('Воркер импорта запущен, перезапуск через %d с или при %d МБ памяти', $timeLimit, $memoryLimit));

        try {
            new Worker(['async' => $this->transport], $this->bus, $this->dispatcher)->run(['sleep' => 1_000_000]);
        } catch (TransportException $e) {
            $this->logger->error('Нет связи с очередью, воркер будет перезапущен', ['reason' => $e->getMessage()]);
            sleep(5);

            return Command::FAILURE;
        }

        $output->writeln('Воркер импорта остановлен');

        return Command::SUCCESS;
    }
}
