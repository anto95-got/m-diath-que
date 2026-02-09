<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_utilisateur')]
    private ?int $idUtilisateur = null;

    #[ORM\Column(name: 'nom', length: 100)]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom', length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'email', length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'password', length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'adresse', length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(name: 'telephone', length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'date_inscription', type: 'datetime', nullable: true)]
    private ?\DateTime $dateInscription = null;

    #[ORM\Column(name: 'actif', type: 'boolean', options: ['default' => true])]
    private ?bool $actif = true;

    #[ORM\Column(name: 'verification_code', length: 6, nullable: true)]
    private ?string $verificationCode = null;

    #[ORM\Column(name: 'password_reset_code', length: 6, nullable: true)]
    private ?string $passwordResetCode = null;

    #[ORM\Column(name: 'password_reset_expires_at', type: 'datetime', nullable: true)]
    private ?\DateTime $passwordResetExpiresAt = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'id_role', referencedColumnName: 'id_role', nullable: false)]
    private ?Role $idRole = null;

    /**
     * @var Collection<int, Emprunt>
     */
    #[ORM\OneToMany(targetEntity: Emprunt::class, mappedBy: 'idUtilisateur')]
    private Collection $emprunts;

    /**
     * @var Collection<int, DemandeDocument>
     */
    #[ORM\OneToMany(targetEntity: DemandeDocument::class, mappedBy: 'idUtilisateur')]
    private Collection $demandes;

    public function __construct()
    {
        $this->emprunts = new ArrayCollection();
        $this->demandes = new ArrayCollection();
        $this->dateInscription = new \DateTime();
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->idUtilisateur;
    }

    // Backwards compatibility: expose legacy "matricule" property name
    public function getMatricule(): ?int
    {
        return $this->idUtilisateur;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->dateInscription;
    }

    public function setDateInscription(?\DateTime $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): static
    {
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function getPasswordResetCode(): ?string
    {
        return $this->passwordResetCode;
    }

    public function setPasswordResetCode(?string $passwordResetCode): static
    {
        $this->passwordResetCode = $passwordResetCode;
        return $this;
    }

    public function getPasswordResetExpiresAt(): ?\DateTime
    {
        return $this->passwordResetExpiresAt;
    }

    public function setPasswordResetExpiresAt(?\DateTime $passwordResetExpiresAt): static
    {
        $this->passwordResetExpiresAt = $passwordResetExpiresAt;
        return $this;
    }

    public function getIdRole(): ?Role
    {
        return $this->idRole;
    }

    public function setIdRole(?Role $idRole): static
    {
        $this->idRole = $idRole;
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
            $emprunt->setIdUtilisateur($this);
        }
        return $this;
    }

    public function removeEmprunt(Emprunt $emprunt): static
    {
        if ($this->emprunts->removeElement($emprunt)) {
            if ($emprunt->getIdUtilisateur() === $this) {
                $emprunt->setIdUtilisateur(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, DemandeDocument>
     */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(DemandeDocument $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setIdUtilisateur($this);
        }
        return $this;
    }

    public function removeDemande(DemandeDocument $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            if ($demande->getIdUtilisateur() === $this) {
                $demande->setIdUtilisateur(null);
            }
        }
        return $this;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->idRole && method_exists($this->idRole, 'getNomRole')) {
            $nom = strtoupper((string) $this->idRole->getNomRole());
            if ($nom) {
                if ($nom === 'ADMIN') {
                    $nom = 'ROLE_ADMIN';
                } elseif ($nom === 'USER') {
                    $nom = 'ROLE_USER';
                } elseif ($nom === 'SUPERADMIN' || $nom === 'SUPER_ADMIN') {
                    $nom = 'ROLE_SUPERADMIN';
                } elseif (!str_starts_with($nom, 'ROLE_')) {
                    $nom = 'ROLE_' . $nom;
                }
                $roles[] = $nom;
                if ($nom === 'ROLE_SUPERADMIN') {
                    $roles[] = 'ROLE_ADMIN';
                }
            }
        }
        return array_values(array_unique($roles));
    }

    public function isAdmin(): bool
    {
        $roles = $this->getRoles();
        return in_array('ROLE_ADMIN', $roles, true) || $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        $roles = $this->getRoles();
        return in_array('ROLE_SUPERADMIN', $roles, true) || in_array('ROLE_SUPER_ADMIN', $roles, true);
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}
