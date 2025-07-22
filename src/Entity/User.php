<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité User représentant un utilisateur de l'application
 * 
 * Cette entité gère les utilisateurs de la plateforme de covoiturage,
 * incluant les informations de base, les préférences, les véhicules,
 * les trajets et les évaluations.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $pseudo = null;

    #[ORM\Column(type: 'string', length: 180)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'integer')]
    private ?int $credits = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $userType = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 1, options: ['default' => 5.0])]
    private float $averageRating = 5.0;

    /**
     * @var Collection<int, Vehicle>
     */
    #[ORM\OneToMany(targetEntity: Vehicle::class, mappedBy: 'owner')]
    private Collection $vehicles;

    #[ORM\OneToOne(inversedBy: 'user', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Preference $preference = null;

    /**
     * @var Collection<int, Ride>
     */
    #[ORM\OneToMany(mappedBy: 'driver', targetEntity: Ride::class, orphanRemoval: true)]
    private Collection $drivenRides;

    /**
     * @var Collection<int, Ride>
     */
    #[ORM\ManyToMany(targetEntity: Ride::class, inversedBy: 'passengers')]
    private Collection $ridesAsPassenger;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'author')]
    private Collection $reviewsGiven;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'reviewedUser')]
    private Collection $reviewsReceived;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'user')]
    private Collection $participations;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
        $this->drivenRides = new ArrayCollection();
        $this->ridesAsPassenger = new ArrayCollection();
        $this->reviewsGiven = new ArrayCollection();
        $this->reviewsReceived = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->credits = 20;
        $this->createdAt = new \DateTime();
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

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

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function setUserType(?string $userType): static
    {
        $this->userType = $userType;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getAverageRating(): float
    {
        return $this->averageRating;
    }

    public function setAverageRating(float $averageRating): static
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): static
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles->add($vehicle);
            $vehicle->setOwner($this);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): static
    {
        if ($this->vehicles->removeElement($vehicle)) {
            // set the owning side to null (unless already changed)
            if ($vehicle->getOwner() === $this) {
                $vehicle->setOwner(null);
            }
        }

        return $this;
    }

    public function getPreference(): ?Preference
    {
        return $this->preference;
    }

    public function setPreference(Preference $preference): static
    {
        $this->preference = $preference;

        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getDrivenRides(): Collection
    {
        return $this->drivenRides;
    }

    public function addDrivenRide(Ride $drivenRide): static
    {
        if (!$this->drivenRides->contains($drivenRide)) {
            $this->drivenRides->add($drivenRide);
            $drivenRide->setDriver($this);
        }

        return $this;
    }

    public function removeDrivenRide(Ride $drivenRide): static
    {
        if ($this->drivenRides->removeElement($drivenRide)) {
            // set the owning side to null (unless already changed)
            if ($drivenRide->getDriver() === $this) {
                $drivenRide->setDriver(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getRidesAsPassenger(): Collection
    {
        return $this->ridesAsPassenger;
    }

    public function addRidesAsPassenger(Ride $ridesAsPassenger): static
    {
        if (!$this->ridesAsPassenger->contains($ridesAsPassenger)) {
            $this->ridesAsPassenger->add($ridesAsPassenger);
        }

        return $this;
    }

    public function removeRidesAsPassenger(Ride $ridesAsPassenger): static
    {
        $this->ridesAsPassenger->removeElement($ridesAsPassenger);

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviewsGiven(): Collection
    {
        return $this->reviewsGiven;
    }

    public function addReviewsGiven(Review $reviewsGiven): static
    {
        if (!$this->reviewsGiven->contains($reviewsGiven)) {
            $this->reviewsGiven->add($reviewsGiven);
            $reviewsGiven->setAuthor($this);
        }

        return $this;
    }

    public function removeReviewsGiven(Review $reviewsGiven): static
    {
        if ($this->reviewsGiven->removeElement($reviewsGiven)) {
            // set the owning side to null (unless already changed)
            if ($reviewsGiven->getAuthor() === $this) {
                $reviewsGiven->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviewsReceived(): Collection
    {
        return $this->reviewsReceived;
    }

    public function addReviewsReceived(Review $reviewsReceived): static
    {
        if (!$this->reviewsReceived->contains($reviewsReceived)) {
            $this->reviewsReceived->add($reviewsReceived);
            $reviewsReceived->setReviewedUser($this);
        }

        return $this;
    }

    public function removeReviewsReceived(Review $reviewsReceived): static
    {
        if ($this->reviewsReceived->removeElement($reviewsReceived)) {
            // set the owning side to null (unless already changed)
            if ($reviewsReceived->getReviewedUser() === $this) {
                $reviewsReceived->setReviewedUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setUser($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getUser() === $this) {
                $participation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getUserRole(): string
    {
        // Si un type d'utilisateur a été défini, l'utiliser
        if ($this->userType !== null) {
            return $this->userType;
        }
        
        // Sinon, calculer le statut basé sur les actions actuelles
        $hasVehicles = !$this->vehicles->isEmpty();
        $hasDrivenRides = !$this->drivenRides->isEmpty();
        $hasRidesAsPassenger = !$this->ridesAsPassenger->isEmpty();
        
        $isDriver = $hasVehicles || $hasDrivenRides;
        $isPassenger = $hasRidesAsPassenger;
        
        if ($isDriver && $isPassenger) {
            return 'Conducteur et Passager';
        } elseif ($isDriver) {
            return 'Conducteur';
        } elseif ($isPassenger) {
            return 'Passager';
        } else {
            return 'Nouveau membre';
        }
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Ici nous n'avons pas besoin d'effacer les credentials car nous utilisons un hashage
    }
}
