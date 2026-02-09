<?php

namespace App\Repository;

use App\Entity\DemandeDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeDocument>
 */
class DemandeDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeDocument::class);
    }

    public function findPending(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statutDemande = :st')
            ->setParameter('st', 'En attente')
            ->orderBy('d.idDemande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findForAdminQueue(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statutDemande IN (:statuses)')
            ->setParameter('statuses', ['En attente', 'Réservé'])
            ->addSelect("CASE WHEN d.statutDemande = 'En attente' THEN 0 ELSE 1 END AS HIDDEN statusOrder")
            ->orderBy('statusOrder', 'ASC')
            ->addOrderBy('d.idDemande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findReservationQueue(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.statutDemande IN (:statuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('statuses', ['En attente', 'Réservé'])
            ->addSelect("CASE WHEN d.statutDemande = 'En attente' THEN 0 ELSE 1 END AS HIDDEN statusOrder")
            ->orderBy('statusOrder', 'ASC')
            ->addOrderBy('d.idDemande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPurchaseQueue(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.statutDemande IN (:statuses)')
            ->setParameter('type', 'proposition')
            ->setParameter('statuses', ['En attente', 'Commandé'])
            ->addSelect("CASE WHEN d.statutDemande = 'En attente' THEN 0 ELSE 1 END AS HIDDEN statusOrder")
            ->orderBy('statusOrder', 'ASC')
            ->addOrderBy('d.idDemande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countForAdminQueue(): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.idDemande)')
            ->andWhere('d.statutDemande IN (:statuses)')
            ->setParameter('statuses', ['En attente', 'Réservé'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Cherche une demande active identique pour un utilisateur (évite doublons).
     * Une demande refusée n'est pas considérée comme active.
     */
    public function findActiveDuplicate($user, string $titre): ?DemandeDocument
    {
        $normalizedTitle = mb_strtolower(trim($titre));

        return $this->createQueryBuilder('d')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('LOWER(d.titreDemande) = :titre')
            ->andWhere('d.statutDemande NOT IN (:refusedStatuses)')
            ->setParameter('user', $user)
            ->setParameter('titre', $normalizedTitle)
            ->setParameter('refusedStatuses', ['Refusé', 'Refusée'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActivePropositionDuplicate($user, string $titre): ?DemandeDocument
    {
        $normalizedTitle = mb_strtolower(trim($titre));

        return $this->createQueryBuilder('d')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('LOWER(d.titreDemande) = :titre')
            ->andWhere('d.statutDemande NOT IN (:inactiveStatuses)')
            ->setParameter('user', $user)
            ->setParameter('type', 'proposition')
            ->setParameter('titre', $normalizedTitle)
            ->setParameter('inactiveStatuses', ['Refusé', 'Refusée'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Indique si une réservation est déjà verrouillée (acceptée ou empruntée)
     * pour ce titre.
     */
    public function hasReservationLockForTitle(string $titre, ?int $excludeDemandeId = null): bool
    {
        $normalizedTitle = mb_strtolower(trim($titre));

        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.idDemande)')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('LOWER(d.titreDemande) = :titre')
            ->andWhere('d.statutDemande IN (:lockedStatuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('titre', $normalizedTitle)
            ->setParameter('lockedStatuses', ['Réservé', 'Emprunté']);

        if ($excludeDemandeId !== null) {
            $qb->andWhere('d.idDemande != :excludeId')
               ->setParameter('excludeId', $excludeDemandeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function userAlreadyRequestedReservation($user, string $titre, ?int $excludeDemandeId = null): bool
    {
        $normalizedTitle = mb_strtolower(trim($titre));

        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.idDemande)')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('LOWER(d.titreDemande) = :titre')
            ->andWhere('d.statutDemande IN (:activeStatuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('user', $user)
            ->setParameter('titre', $normalizedTitle)
            ->setParameter('activeStatuses', ['En attente', 'Réservé', 'Emprunté']);

        if ($excludeDemandeId !== null) {
            $qb->andWhere('d.idDemande != :excludeId')
               ->setParameter('excludeId', $excludeDemandeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function userAlreadyRequestedReservationForDocument($user, int $documentId, ?int $excludeDemandeId = null): bool
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.idDemande)')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('d.idDocDemande = :documentId')
            ->andWhere('d.statutDemande IN (:activeStatuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('user', $user)
            ->setParameter('documentId', $documentId)
            ->setParameter('activeStatuses', ['En attente', 'Réservé', 'Emprunté']);

        if ($excludeDemandeId !== null) {
            $qb->andWhere('d.idDemande != :excludeId')
                ->setParameter('excludeId', $excludeDemandeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function userHasActiveReservationForTitle($user, string $titre, ?int $excludeDemandeId = null): bool
    {
        $normalizedTitle = mb_strtolower(trim($titre));

        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.idDemande)')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('LOWER(d.titreDemande) = :titre')
            ->andWhere('d.statutDemande IN (:activeStatuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('user', $user)
            ->setParameter('titre', $normalizedTitle)
            ->setParameter('activeStatuses', ['En attente', 'Réservé', 'Emprunté']);

        if ($excludeDemandeId !== null) {
            $qb->andWhere('d.idDemande != :excludeId')
                ->setParameter('excludeId', $excludeDemandeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function findReservationTitlesRequestedByUser($user): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('LOWER(d.titreDemande) AS title_key')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('d.statutDemande IN (:activeStatuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['En attente', 'Réservé', 'Emprunté'])
            ->groupBy('title_key')
            ->getQuery()
            ->getArrayResult();

        $titles = [];
        foreach ($rows as $row) {
            $titles[(string) $row['title_key']] = true;
        }

        return $titles;
    }

    public function findPendingReservationIdsByDocumentForUser($user): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('IDENTITY(d.idDocDemande) AS doc_id, MAX(d.idDemande) AS demande_id')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('d.statutDemande = :status')
            ->andWhere('d.idDocDemande IS NOT NULL')
            ->setParameter('type', 'reservation')
            ->setParameter('user', $user)
            ->setParameter('status', 'En attente')
            ->groupBy('doc_id')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $docId = (int) ($row['doc_id'] ?? 0);
            if ($docId > 0) {
                $result[$docId] = (int) $row['demande_id'];
            }
        }

        return $result;
    }

    public function findPendingReservationIdsByTitleForUser($user): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('LOWER(d.titreDemande) AS title_key, MAX(d.idDemande) AS demande_id')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('d.statutDemande = :status')
            ->setParameter('type', 'reservation')
            ->setParameter('user', $user)
            ->setParameter('status', 'En attente')
            ->groupBy('title_key')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['title_key']] = (int) $row['demande_id'];
        }

        return $result;
    }

    public function findReservationDocumentIdsRequestedByUser($user): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('IDENTITY(d.idDocDemande) AS doc_id')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('d.idDocDemande IS NOT NULL')
            ->andWhere('d.statutDemande IN (:activeStatuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['En attente', 'Réservé', 'Emprunté'])
            ->groupBy('doc_id')
            ->getQuery()
            ->getArrayResult();

        $ids = [];
        foreach ($rows as $row) {
            $docId = (int) ($row['doc_id'] ?? 0);
            if ($docId > 0) {
                $ids[$docId] = true;
            }
        }

        return $ids;
    }

    public function hasActiveReservationByOtherUser($user, string $titre): bool
    {
        $normalizedTitle = mb_strtolower(trim($titre));

        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.idDemande)')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('LOWER(d.titreDemande) = :titre')
            ->andWhere('d.idUtilisateur != :user')
            ->andWhere('d.statutDemande IN (:activeStatuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('titre', $normalizedTitle)
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['En attente', 'Réservé', 'Emprunté'])
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @return array<int, DemandeDocument>
     */
    public function findConflictingReservationDemandesForDocument(int $documentId, int $excludeDemandeId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idDocDemande = :documentId')
            ->andWhere('d.idDemande != :excludeDemandeId')
            ->andWhere('d.statutDemande IN (:statuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('documentId', $documentId)
            ->setParameter('excludeDemandeId', $excludeDemandeId)
            ->setParameter('statuses', ['En attente', 'Réservé'])
            ->orderBy('d.idDemande', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveReservationTitlesRequestedByOthers($user): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('LOWER(d.titreDemande) AS title_key')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.idUtilisateur != :user')
            ->andWhere('d.statutDemande IN (:activeStatuses)')
            ->setParameter('type', 'reservation')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', ['En attente', 'Réservé', 'Emprunté'])
            ->groupBy('title_key')
            ->getQuery()
            ->getArrayResult();

        $titles = [];
        foreach ($rows as $row) {
            $titles[(string) $row['title_key']] = true;
        }

        return $titles;
    }
}
