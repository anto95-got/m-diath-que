<?php

namespace App\Entity;

use App\Repository\EtatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtatRepository::class)]
#[ORM\Table(name: 'etat')]
class Etat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_etat')]
    private ?int $idEtat = null;

    #[ORM\Column(name: 'libelle_etat', length: 50, unique: true)]
    private ?string $libelleEtat = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'idEtat')]
    private Collection $documents;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    public function getIdEtat(): ?int
    {
        return $this->idEtat;
    }

    public function getLibelleEtat(): ?string
    {
        return $this->libelleEtat;
    }

    public function setLibelleEtat(string $libelleEtat): static
    {
        $this->libelleEtat = $libelleEtat;

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
            $document->setIdEtat($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getIdEtat() === $this) {
                $document->setIdEtat(null);
            }
        }

        return $this;
    }
}
