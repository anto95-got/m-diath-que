<?php

namespace App\Entity;

use App\Repository\DemandeDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DemandeDocumentRepository::class)]
#[ORM\Table(name: 'demande_document')]
class DemandeDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_demande')]
    private ?int $idDemande = null;

    #[ORM\Column(name: 'titre_demande', length: 255)]
    private ?string $titreDemande = null;

    #[ORM\Column(name: 'auteur_demande', length: 150)]
    private ?string $auteurDemande = null;

    #[ORM\Column(name: 'statut_demande', length: 50)]
    private ?string $statutDemande = 'En attente';

    #[ORM\Column(name: 'type_demande', length: 20, options: ['default' => 'reservation'])]
    private ?string $typeDemande = 'reservation';

    #[ORM\Column(name: 'motif_refus', type: 'text', nullable: true)]
    private ?string $motifRefus = null;

    #[ORM\Column(name: 'quantite_demandee', options: ['default' => 1])]
    private ?int $quantiteDemandee = 1;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'demandes')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false)]
    private ?Utilisateur $idUtilisateur = null;

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(name: 'id_doc_demande', referencedColumnName: 'id_doc', nullable: true, onDelete: 'SET NULL')]
    private ?Document $idDocDemande = null;

    public function getIdDemande(): ?int
    {
        return $this->idDemande;
    }

    public function getTitreDemande(): ?string
    {
        return $this->titreDemande;
    }

    public function setTitreDemande(string $titreDemande): static
    {
        $this->titreDemande = $titreDemande;

        return $this;
    }

    public function getAuteurDemande(): ?string
    {
        return $this->auteurDemande;
    }

    public function setAuteurDemande(string $auteurDemande): static
    {
        $this->auteurDemande = $auteurDemande;

        return $this;
    }

    public function getStatutDemande(): ?string
    {
        return $this->statutDemande;
    }

    public function setStatutDemande(string $statutDemande): static
    {
        $this->statutDemande = $statutDemande;

        return $this;
    }

    public function getTypeDemande(): ?string
    {
        return $this->typeDemande;
    }

    public function setTypeDemande(string $typeDemande): static
    {
        $this->typeDemande = $typeDemande;
        return $this;
    }

    public function getMotifRefus(): ?string
    {
        return $this->motifRefus;
    }

    public function setMotifRefus(?string $motifRefus): static
    {
        $this->motifRefus = $motifRefus;
        return $this;
    }

    public function getQuantiteDemandee(): ?int
    {
        return $this->quantiteDemandee;
    }

    public function setQuantiteDemandee(int $quantiteDemandee): static
    {
        $this->quantiteDemandee = max(1, $quantiteDemandee);

        return $this;
    }

    public function getIdUtilisateur(): ?Utilisateur
    {
        return $this->idUtilisateur;
    }

    public function setIdUtilisateur(?Utilisateur $idUtilisateur): static
    {
        $this->idUtilisateur = $idUtilisateur;

        return $this;
    }

    public function getIdDocDemande(): ?Document
    {
        return $this->idDocDemande;
    }

    public function setIdDocDemande(?Document $idDocDemande): static
    {
        $this->idDocDemande = $idDocDemande;

        return $this;
    }

    // Backwards compatibility: alias using legacy 'matricule' naming
    public function getMatricule(): ?Utilisateur
    {
        return $this->idUtilisateur;
    }

    public function setMatricule(?Utilisateur $utilisateur): static
    {
        $this->idUtilisateur = $utilisateur;

        return $this;
    }
}
