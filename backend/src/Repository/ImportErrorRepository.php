<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Page;
use App\Dto\PageRequest;
use App\Entity\ImportError;
use App\Entity\ImportJob;
use App\Service\Import\ImportIssue;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ImportErrorRepository
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @param list<ImportIssue> $issues
     */
    public function add(string $jobId, array $issues): void
    {
        $connection = $this->em->getConnection();

        foreach ($issues as $issue) {
            $connection->insert('import_errors', [
                'import_job_id' => $jobId,
                'row_number' => $issue->row,
                'external_code' => $issue->externalCode === null ? null : mb_substr($issue->externalCode, 0, 255),
                'level' => $issue->level->value,
                'message' => $issue->message,
            ]);
        }
    }

    public function deleteByJob(string $jobId): void
    {
        $this->em->getConnection()->delete('import_errors', ['import_job_id' => $jobId]);
    }

    /**
     * @return Page<ImportError>
     */
    public function findPageByJob(ImportJob $job, PageRequest $request): Page
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(ImportError::class, 'e')
            ->where('e.importJob = :job')
            ->setParameter('job', $job->getId());

        $total = (int) (clone $qb)->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();

        $items = $qb->orderBy('e.rowNumber', 'ASC')
            ->addOrderBy('e.id', 'ASC')
            ->setFirstResult($request->offset())
            ->setMaxResults($request->limit)
            ->getQuery()
            ->getResult();

        return new Page($items, $total, $request->page, $request->limit);
    }
}
