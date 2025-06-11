<?php

namespace App\Entity;

use App\Repository\PreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreferenceRepository::class)]
class Preference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $smoker = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $animals = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customPreferences = null;

    #[ORM\OneToOne(mappedBy: 'preference', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isSmoker(): ?bool
    {
        return $this->smoker;
    }

    public function setSmoker(bool $smoker): static
    {
        $this->smoker = $smoker;

        return $this;
    }

    public function isAnimals(): ?bool
    {
        return $this->animals;
    }

    public function setAnimals(bool $animals): static
    {
        $this->animals = $animals;

        return $this;
    }

    public function getCustomPreferences(): ?string
    {
        return $this->customPreferences;
    }

    public function setCustomPreferences(?string $customPreferences): static
    {
        $this->customPreferences = $customPreferences;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        // set the owning side of the relation if necessary
        if ($user->getPreference() !== $this) {
            $user->setPreference($this);
        }

        $this->user = $user;

        return $this;
    }
}
