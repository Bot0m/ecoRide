<?php

namespace App\Entity;

use App\Repository\RideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RideRepository::class)]
class Ride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $departure = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $arrival = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $departureTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $arrivalTime = null;

    #[ORM\Column(type: 'integer')]
    private ?int $price = null;

    #[ORM\Column(type: 'integer')]
    private ?int $availableSeats = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isEcological = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'actif'])]
    private ?string $status = 'actif';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $startedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $completedAt = null;

    #[ORM\ManyToOne(inversedBy: 'drivenRides')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $driver = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'ridesAsPassenger')]
    private Collection $passengers;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'ride')]
    private Collection $participations;

    public function __construct()
    {
        $this->passengers = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeparture(): ?string
    {
        return $this->departure;
    }

    public function setDeparture(string $departure): static
    {
        $this->departure = $departure;

        return $this;
    }

    public function getArrival(): ?string
    {
        return $this->arrival;
    }

    public function setArrival(string $arrival): static
    {
        $this->arrival = $arrival;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDepartureTime(): ?\DateTime
    {
        return $this->departureTime;
    }

    public function setDepartureTime(\DateTime $departureTime): static
    {
        $this->departureTime = $departureTime;

        return $this;
    }

    public function getArrivalTime(): ?\DateTime
    {
        return $this->arrivalTime;
    }

    public function setArrivalTime(\DateTime $arrivalTime): static
    {
        $this->arrivalTime = $arrivalTime;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getAvailableSeats(): ?int
    {
        return $this->availableSeats;
    }

    public function setAvailableSeats(int $availableSeats): static
    {
        $this->availableSeats = $availableSeats;

        return $this;
    }

    public function isEcological(): ?bool
    {
        return $this->isEcological;
    }

    public function setIsEcological(bool $isEcological): static
    {
        $this->isEcological = $isEcological;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getPassengers(): Collection
    {
        return $this->passengers;
    }

    public function addPassenger(User $passenger): static
    {
        if (!$this->passengers->contains($passenger)) {
            $this->passengers->add($passenger);
            $passenger->addRidesAsPassenger($this);
        }

        return $this;
    }

    public function removePassenger(User $passenger): static
    {
        if ($this->passengers->removeElement($passenger)) {
            $passenger->removeRidesAsPassenger($this);
        }

        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;

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
            $participation->setRide($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getRide() === $this) {
                $participation->setRide(null);
            }
        }

        return $this;
    }
}
