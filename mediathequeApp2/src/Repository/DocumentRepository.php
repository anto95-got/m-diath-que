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

    public function hasAvailableCopyForTitle(string $titre, ?int $excludeDocumentId = null): bool
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.idDoc)')
            ->andWhere('LOWER(d.titre) = :titre')
            ->andWhere('d.disponible = 1')
            ->setParameter('titre', mb_strtolower(trim($titre)));

        if ($excludeDocumentId !== null) {
            $qb->andWhere('d.idDoc != :excludeDocumentId')
                ->setParameter('excludeDocumentId', $excludeDocumentId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function hasAnyCopyForTitle(string $titre): bool
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.idDoc)')
            ->andWhere('LOWER(d.titre) = :titre')
            ->setParameter('titre', mb_strtolower(trim($titre)))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findFirstAvailableByTitle(string $titre, ?int $excludeDocumentId = null): ?Document
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('LOWER(d.titre) = :titre')
            ->andWhere('d.disponible = 1')
            ->setParameter('titre', mb_strtolower(trim($titre)))
            ->orderBy('d.idDoc', 'ASC')
            ->setMaxResults(1);

        if ($excludeDocumentId !== null) {
            $qb->andWhere('d.idDoc != :excludeDocumentId')
                ->setParameter('excludeDocumentId', $excludeDocumentId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function countAvailableByTitle(): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('LOWER(d.titre) AS title_key, COUNT(d.idDoc) AS available_count')
            ->andWhere('d.disponible = 1')
            ->groupBy('title_key')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['title_key']] = (int) $row['available_count'];
        }

        return $counts;
    }

    /**
     * @return array<int, Document>
     */
    public function findSoonByTitle(string $titre, int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('LOWER(d.titre) = :titre')
            ->andWhere('d.bientotDisponible = 1')
            ->setParameter('titre', mb_strtolower(trim($titre)))
            ->orderBy('d.idDoc', 'ASC');

        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
