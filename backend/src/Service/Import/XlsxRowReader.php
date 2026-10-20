<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Exception\ImportFileException;
use DateTimeInterface;
use Generator;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

final readonly class XlsxRowReader
{
    /**
     * @return array<int, string>
     */
    public function headers(string $path): array
    {
        foreach ($this->read($path) as $row) {
            return array_map(
                static fn(string $header): string => (string) preg_replace('/\s+/u', ' ', $header),
                $this->values($row),
            );
        }

        throw new ImportFileException('Файл пустой');
    }

    public function countRows(string $path): int
    {
        $count = 0;

        foreach ($this->read($path) as $number => $row) {
            if ($number > 1 && !$this->isBlank($this->values($row))) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @return Generator<int, array<string, string>>
     */
    public function rows(string $path): Generator
    {
        $headers = array_filter($this->headers($path), static fn(string $header): bool => $header !== '');

        foreach ($this->read($path) as $number => $row) {
            $values = $this->values($row);

            if ($number === 1 || $this->isBlank($values)) {
                continue;
            }

            $cells = [];

            foreach ($headers as $index => $header) {
                $cells[$header] = $values[$index] ?? '';
            }

            yield $number => $cells;
        }
    }

    /**
     * @return Generator<int, Row>
     */
    private function read(string $path): Generator
    {
        $reader = new Reader(new Options(SHOULD_PRESERVE_EMPTY_ROWS: true));

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $number => $row) {
                    yield $number => $row;
                }

                break;
            }
        } catch (OpenSpoutException $e) {
            throw new ImportFileException('Не удалось прочитать xlsx-файл', previous: $e);
        } finally {
            $reader->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function values(Row $row): array
    {
        return array_map(
            static fn(mixed $value): string => match (true) {
                is_string($value) => trim($value),
                is_int($value) => (string) $value,
                is_float($value) => rtrim(rtrim(sprintf('%.6F', $value), '0'), '.'),
                is_bool($value) => $value ? '1' : '0',
                $value instanceof DateTimeInterface => $value->format('Y-m-d'),
                default => '',
            },
            $row->toArray(),
        );
    }

    /**
     * @param array<int, string> $values
     */
    private function isBlank(array $values): bool
    {
        return implode('', $values) === '';
    }
}
