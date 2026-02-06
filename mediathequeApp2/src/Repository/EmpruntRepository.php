<?php

namespace App\Repository;

use App\Entity\Emprunt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Emprunt>
 */
class EmpruntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emprunt::class);
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.idEmprunt)')
            ->andWhere('e.dateRetourReelle IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOverdue(\DateTimeInterface $today): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.idEmprunt)')
            ->andWhere('e.dateRetourReelle IS NULL')
            ->andWhere('e.dateRetourPrevue < :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.idEmprunt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
