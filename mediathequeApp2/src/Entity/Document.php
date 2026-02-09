<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'document')]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_doc')]
    private ?int $idDoc = null;

    #[ORM\Column(name: 'titre', length: 255)]
    private ?string $titre = null;

    #[ORM\Column(name: 'code_barres', length: 50, unique: true)]
    private ?string $codeBarres = null;

    #[ORM\Column(name: 'disponible', type: 'boolean', options: ['default' => true])]
    private ?bool $disponible = true;

    #[ORM\Column(name: 'bientot_disponible', type: 'boolean', options: ['default' => false])]
    private ?bool $bientotDisponible = false;

    #[ORM\ManyToOne(targetEntity: Etat::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'id_etat', referencedColumnName: 'id_etat', nullable: false)]
    private ?Etat $idEtat = null;

    #[ORM\ManyToOne(targetEntity: SousCategorie::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'id_sous_categorie', referencedColumnName: 'id_sous_categorie', nullable: false)]
    private ?SousCategorie $idSousCategorie = null;

    /**
     * @var Collection<int, Auteur>
     */
    #[ORM\ManyToMany(targetEntity: Auteur::class, inversedBy: 'documents')]
    #[ORM\JoinTable(name: 'ecrire')]
    #[ORM\JoinColumn(name: 'id_doc', referencedColumnName: 'id_doc')]
    #[ORM\InverseJoinColumn(name: 'id_auteur', referencedColumnName: 'id_auteur')]
    private Collection $auteurs;

    /**
     * @var Collection<int, Emprunt>
     */
    #[ORM\OneToMany(targetEntity: Emprunt::class, mappedBy: 'idDoc')]
    private Collection $emprunts;

    public function __construct()
    {
        $this->auteurs = new ArrayCollection();
        $this->emprunts = new ArrayCollection();
    }

    public function getIdDoc(): ?int
    {
        return $this->idDoc;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getCodeBarres(): ?string
    {
        return $this->codeBarres;
    }

    public function setCodeBarres(string $codeBarres): static
    {
        $this->codeBarres = $codeBarres;

        return $this;
    }

    public function getDisponible(): ?bool
    {
        return $this->disponible;
    }

    public function setDisponible(bool $disponible): static
    {
        $this->disponible = $disponible;

        return $this;
    }

    public function getBientotDisponible(): ?bool
    {
        return $this->bientotDisponible;
    }

    public function isBientotDisponible(): ?bool
    {
        return $this->bientotDisponible;
    }

    public function setBientotDisponible(bool $bientotDisponible): static
    {
        $this->bientotDisponible = $bientotDisponible;

        return $this;
    }

    public function getIdEtat(): ?Etat
    {
        return $this->idEtat;
    }

    public function setIdEtat(?Etat $idEtat): static
    {
        $this->idEtat = $idEtat;

        return $this;
    }

    public function getIdSousCategorie(): ?SousCategorie
    {
        return $this->idSousCategorie;
    }

    public function setIdSousCategorie(?SousCategorie $idSousCategorie): static
    {
        $this->idSousCategorie = $idSousCategorie;

        return $this;
    }

    /**
     * @return Collection<int, Auteur>
     */
    public function getAuteurs(): Collection
    {
        return $this->auteurs;
    }

    public function addAuteur(Auteur $auteur): static
    {
        if (!$this->auteurs->contains($auteur)) {
            $this->auteurs->add($auteur);
        }

        return $this;
    }

    public function removeAuteur(Auteur $auteur): static
    {
        $this->auteurs->removeElement($auteur);

        return $this;
    }

    /**
     * @return Collection<int, Emprunt>
     */
    public function getEmprunts(): Collection
    {
        return $this->emprunts;
    }

    public function addEmprunt(Emprunt $emprunt): static
    {
        if (!$this->emprunts->contains($emprunt)) {
            $this->emprunts->add($emprunt);
            $emprunt->setIdDoc($this);
        }

        return $this;
    }

    public function removeEmprunt(Emprunt $emprunt): static
    {
        if ($this->emprunts->removeElement($emprunt)) {
            if ($emprunt->getIdDoc() === $this) {
                $emprunt->setIdDoc(null);
            }
        }

        return $this;
    }
}
