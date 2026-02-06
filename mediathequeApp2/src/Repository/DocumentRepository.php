<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.idDoc)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAvailable(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.idDoc)')
            ->andWhere('d.disponible = 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.idDoc', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneByCodeBarres(string $code): ?Document
    {
        return $this->findOneBy(['codeBarres' => $code]);
    }
}
