<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Table(name: 'categorie')]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_categorie')]
    private ?int $idCategorie = null;

    #[ORM\Column(name: 'nom_categorie', length: 100, unique: true)]
    private ?string $nomCategorie = null;

    /**
     * @var Collection<int, SousCategorie>
     */
    #[ORM\OneToMany(targetEntity: SousCategorie::class, mappedBy: 'idCategorie')]
    private Collection $sousCategories;

    public function __construct()
    {
        $this->sousCategories = new ArrayCollection();
    }

    public function getIdCategorie(): ?int
    {
        return $this->idCategorie;
    }

    public function getNomCategorie(): ?string
    {
        return $this->nomCategorie;
    }

    public function setNomCategorie(string $nomCategorie): static
    {
        $this->nomCategorie = $nomCategorie;

        return $this;
    }

    /**
     * @return Collection<int, SousCategorie>
     */
    public function getSousCategories(): Collection
    {
        return $this->sousCategories;
    }

    public function addSousCategorie(SousCategorie $sousCategorie): static
    {
        if (!$this->sousCategories->contains($sousCategorie)) {
            $this->sousCategories->add($sousCategorie);
            $sousCategorie->setIdCategorie($this);
        }

        return $this;
    }

    public function removeSousCategorie(SousCategorie $sousCategorie): static
    {
        if ($this->sousCategories->removeElement($sousCategorie)) {
            if ($sousCategorie->getIdCategorie() === $this) {
                $sousCategorie->setIdCategorie(null);
            }
        }

        return $this;
    }
}
