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

    /**
     * Cherche une demande active identique pour un utilisateur (évite doublons)
     */
    public function findActiveDuplicate($user, string $titre, string $type): ?DemandeDocument
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.idUtilisateur = :user')
            ->andWhere('d.titreDemande = :titre')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.statutDemande != :refused')
            ->setParameters([
                'user' => $user,
                'titre' => $titre,
                'type' => $type,
                'refused' => 'Refusée',
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
