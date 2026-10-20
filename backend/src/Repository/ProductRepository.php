<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Page;
use App\Dto\ProductFilter;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductRepository
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function find(int $id): ?Product
    {
        return $this->em->find(Product::class, $id);
    }

    public function findByExternalCode(string $externalCode): ?Product
    {
        return $this->em->getRepository(Product::class)->findOneBy(['externalCode' => $externalCode]);
    }

    /**
     * @return Page<Product>
     */
    public function findPage(ProductFilter $filter): Page
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p');

        if ($filter->name !== null) {
            $qb->andWhere('LOWER(p.name) LIKE :name')
                ->setParameter('name', '%' . addcslashes(mb_strtolower($filter->name), '%_\\') . '%');
        }

        if ($filter->priceFrom !== null) {
            $qb->andWhere('p.price >= :priceFrom')->setParameter('priceFrom', $filter->priceFrom);
        }

        if ($filter->priceTo !== null) {
            $qb->andWhere('p.price <= :priceTo')->setParameter('priceTo', $filter->priceTo);
        }

        $total = (int) (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        $items = $qb->orderBy('p.name', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->setFirstResult(($filter->page - 1) * $filter->limit)
            ->setMaxResults($filter->limit)
            ->getQuery()
            ->getResult();

        return new Page($items, $total, $filter->page, $filter->limit);
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
        $this->em->flush();
    }

    public function remove(Product $product): void
    {
        $this->em->remove($product);
        $this->em->flush();
    }
}
