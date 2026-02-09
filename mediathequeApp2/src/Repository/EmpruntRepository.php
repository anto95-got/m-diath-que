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
            ->andWhere('e.dateRetourEffectif IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOverdue(\DateTimeInterface $today): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.idEmprunt)')
            ->andWhere('e.dateRetourEffectif IS NULL')
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

    public function findOpenForAdmin(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateRetourEffectif IS NULL')
            ->andWhere('e.statut IN (:statuses)')
            ->setParameter('statuses', ['reserve', 'en_cours'])
            ->orderBy('e.idEmprunt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findHistoryForAdmin(int $limit = 200): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateRetourEffectif IS NOT NULL OR e.statut = :returned')
            ->setParameter('returned', 'retourne')
            ->orderBy('e.dateRetourEffectif', 'DESC')
            ->addOrderBy('e.idEmprunt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function hasActiveLoanForDocument(int $documentId, ?int $excludeLoanId = null): bool
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.idEmprunt)')
            ->andWhere('e.idDoc = :documentId')
            ->andWhere('e.dateRetourEffectif IS NULL')
            ->setParameter('documentId', $documentId);

        if ($excludeLoanId !== null) {
            $qb->andWhere('e.idEmprunt != :excludeLoanId')
               ->setParameter('excludeLoanId', $excludeLoanId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function findReservedLoanFor(int $documentId, int $userId): ?Emprunt
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.idDoc = :documentId')
            ->andWhere('e.idUtilisateur = :userId')
            ->andWhere('e.dateRetourEffectif IS NULL')
            ->andWhere('e.statut = :status')
            ->setParameter('documentId', $documentId)
            ->setParameter('userId', $userId)
            ->setParameter('status', 'reserve')
            ->orderBy('e.idEmprunt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findReservedLoanByTitleForUser(string $title, int $userId): ?Emprunt
    {
        return $this->createQueryBuilder('e')
            ->join('e.idDoc', 'd')
            ->andWhere('e.idUtilisateur = :userId')
            ->andWhere('e.dateRetourEffectif IS NULL')
            ->andWhere('e.statut = :status')
            ->andWhere('LOWER(d.titre) = :title')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'reserve')
            ->setParameter('title', mb_strtolower(trim($title)))
            ->orderBy('e.idEmprunt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasActiveLoanOrReservationForTitleByUser(int $userId, string $title, ?int $excludeLoanId = null): bool
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.idEmprunt)')
            ->join('e.idDoc', 'd')
            ->andWhere('e.idUtilisateur = :userId')
            ->andWhere('e.dateRetourEffectif IS NULL')
            ->andWhere('e.statut IN (:statuses)')
            ->andWhere('LOWER(d.titre) = :title')
            ->setParameter('userId', $userId)
            ->setParameter('statuses', ['reserve', 'en_cours'])
            ->setParameter('title', mb_strtolower(trim($title)));

        if ($excludeLoanId !== null) {
            $qb->andWhere('e.idEmprunt != :excludeLoanId')
                ->setParameter('excludeLoanId', $excludeLoanId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
