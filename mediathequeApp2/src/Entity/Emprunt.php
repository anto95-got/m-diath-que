<?php

namespace App\Entity;

use App\Repository\EmpruntRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmpruntRepository::class)]
#[ORM\Table(name: 'emprunt')]
class Emprunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_emprunt')]
    private ?int $idEmprunt = null;

    #[ORM\Column(name: 'date_emprunt', type: 'datetime')]
    private ?\DateTimeInterface $dateEmprunt = null;

    #[ORM\Column(name: 'date_retour_prevue', type: 'date')]
    private ?\DateTimeInterface $dateRetourPrevue = null;

    #[ORM\Column(name: 'date_retour_effectif', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateRetourEffectif = null;

    #[ORM\Column(name: 'statut', length: 50, options: ['default' => 'en_cours'])]
    private ?string $statut = 'en_cours';

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'emprunts')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false)]
    private ?Utilisateur $idUtilisateur = null;

    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'emprunts')]
    #[ORM\JoinColumn(name: 'id_doc', referencedColumnName: 'id_doc', nullable: false)]
    private ?Document $idDoc = null;

    public function __construct()
    {
        $this->dateEmprunt = new \DateTime();
        $this->dateRetourPrevue = (new \DateTime())->modify('+30 days');
    }

    public function getIdEmprunt(): ?int
    {
        return $this->idEmprunt;
    }

    public function getDateEmprunt(): ?\DateTimeInterface
    {
        return $this->dateEmprunt;
    }

    public function setDateEmprunt(\DateTimeInterface $dateEmprunt): static
    {
        $this->dateEmprunt = $dateEmprunt;
        return $this;
    }

    public function getDateRetourPrevue(): ?\DateTimeInterface
    {
        return $this->dateRetourPrevue;
    }

    public function setDateRetourPrevue(\DateTimeInterface $dateRetourPrevue): static
    {
        $this->dateRetourPrevue = $dateRetourPrevue;
        return $this;
    }

    public function getDateRetourEffectif(): ?\DateTimeInterface
    {
        return $this->dateRetourEffectif;
    }

    public function setDateRetourEffectif(?\DateTimeInterface $dateRetourEffectif): static
    {
        $this->dateRetourEffectif = $dateRetourEffectif;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
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

    // Backwards compatibility: alias methods using legacy "matricule" naming
    public function getMatricule(): ?Utilisateur
    {
        return $this->idUtilisateur;
    }

    public function setMatricule(?Utilisateur $utilisateur): static
    {
        $this->idUtilisateur = $utilisateur;
        return $this;
    }

    public function getIdDoc(): ?Document
    {
        return $this->idDoc;
    }

    public function setIdDoc(?Document $idDoc): static
    {
        $this->idDoc = $idDoc;
        return $this;
    }

    public function isRetourne(): bool
    {
        return $this->dateRetourEffectif !== null || $this->statut === 'retourne';
    }

    public function isEnRetard(): bool
    {
        if ($this->isRetourne()) {
            return false;
        }
        return new \DateTime() > $this->dateRetourPrevue;
    }
}
