<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\ImportFileException;
use App\Service\Import\ImportIssue;
use App\Service\Import\InMemoryImportReport;
use App\Service\Import\ProductImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:products:import', description: 'Импортировать товары из xlsx-файла')]
final class ImportProductsCommand extends Command
{
    public function __construct(
        private readonly ProductImporter $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Путь к xlsx-файлу');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');

        if (!is_file($file)) {
            $io->error(sprintf('Файл %s не найден', $file));

            return Command::FAILURE;
        }

        $report = new InMemoryImportReport();

        try {
            $this->importer->import($file, $report);
        } catch (ImportFileException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->definitionList(
            ['Строк в файле' => $report->total],
            ['Создано' => $report->created],
            ['Обновлено' => $report->updated],
            ['С ошибками' => $report->failed],
            ['Предупреждений' => $report->warnings()],
        );

        if ($report->issues !== []) {
            $io->table(
                ['Строка', 'Внешний код', 'Тип', 'Сообщение'],
                array_map(
                    static fn(ImportIssue $issue): array => [$issue->row, $issue->externalCode ?? '—', $issue->level->label(), $issue->message],
                    $report->issues,
                ),
            );
        }

        $io->success('Импорт завершён');

        return Command::SUCCESS;
    }
}
