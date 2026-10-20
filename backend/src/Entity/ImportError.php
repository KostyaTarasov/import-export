<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ImportIssueLevel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'import_errors')]
#[ORM\Index(name: 'import_errors_import_job_id_row_number_idx', columns: ['import_job_id', 'row_number'])]
final class ImportError
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImportJob::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ImportJob $importJob;

    #[ORM\Column]
    private int $rowNumber;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalCode;

    #[ORM\Column(length: 16, enumType: ImportIssueLevel::class)]
    private ImportIssueLevel $level;

    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    public function __construct(ImportJob $importJob, int $rowNumber, ?string $externalCode, ImportIssueLevel $level, string $message)
    {
        $this->importJob = $importJob;
        $this->rowNumber = $rowNumber;
        $this->externalCode = $externalCode;
        $this->level = $level;
        $this->message = $message;
    }

    public function getRowNumber(): int
    {
        return $this->rowNumber;
    }

    public function getExternalCode(): ?string
    {
        return $this->externalCode;
    }

    public function getLevel(): ImportIssueLevel
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
