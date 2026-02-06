<?php

namespace App\Entity;

use App\Repository\SousCategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SousCategorieRepository::class)]
#[ORM\Table(name: 'sous_categorie', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uq_sous_categorie', columns: ['nom_sous_categorie', 'id_categorie'])
])]
class SousCategorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_sous_categorie')]
    private ?int $idSousCategorie = null;

    #[ORM\Column(name: 'nom_sous_categorie', length: 100)]
    private ?string $nomSousCategorie = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'sousCategories')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', nullable: false)]
    private ?Categorie $idCategorie = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'idSousCategorie')]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    public function getIdSousCategorie(): ?int
    {
        return $this->idSousCategorie;
    }

    public function getNomSousCategorie(): ?string
    {
        return $this->nomSousCategorie;
    }

    public function setNomSousCategorie(string $nomSousCategorie): static
    {
        $this->nomSousCategorie = $nomSousCategorie;

        return $this;
    }

    public function getIdCategorie(): ?Categorie
    {
        return $this->idCategorie;
    }

    public function setIdCategorie(?Categorie $idCategorie): static
    {
        $this->idCategorie = $idCategorie;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setIdSousCategorie($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getIdSousCategorie() === $this) {
                $document->setIdSousCategorie(null);
            }
        }

        return $this;
    }
}
