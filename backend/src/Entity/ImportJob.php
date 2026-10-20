<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ImportStatus;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'import_jobs')]
#[ORM\Index(name: 'import_jobs_user_id_created_at_idx', columns: ['user_id', 'created_at'])]
final class ImportJob
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 255)]
    private string $filePath;

    #[ORM\Column(length: 16, enumType: ImportStatus::class)]
    private ImportStatus $status = ImportStatus::Pending;

    #[ORM\Column(options: ['default' => 0])]
    private int $totalRows = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $processedRows = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $createdCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $updatedCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $failedCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $warningCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    public function __construct(User $user, string $originalName)
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->user = $user;
        $this->originalName = mb_substr($originalName, 0, 255);
        $this->filePath = sprintf('imports/%s.xlsx', $this->id);
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function belongsTo(User $user): bool
    {
        return $this->user?->getId() === $user->getId();
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getStatus(): ImportStatus
    {
        return $this->status;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function getProcessedRows(): int
    {
        return $this->processedRows;
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function getWarningCount(): int
    {
        return $this->warningCount;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }
}
