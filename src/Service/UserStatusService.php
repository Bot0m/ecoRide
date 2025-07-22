<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserStatusService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Met à jour le statut utilisateur basé sur ses véhicules et participations
     */
    public function updateUserStatus(User $user): void
    {
        $hasVehicles = !$user->getVehicles()->isEmpty();
        $hasDrivenRides = !$user->getDrivenRides()->isEmpty();
        $hasRidesAsPassenger = !$user->getRidesAsPassenger()->isEmpty();
        
        // Vérifier aussi les participations en attente ou acceptées
        $hasParticipations = false;
        foreach ($user->getParticipations() as $participation) {
            if (in_array($participation->getStatus(), ['en_attente', 'acceptee'])) {
                $hasParticipations = true;
                break;
            }
        }
        
        $isDriver = $hasVehicles || $hasDrivenRides;
        $isPassenger = $hasRidesAsPassenger || $hasParticipations;
        
        if ($isDriver && $isPassenger) {
            $user->setUserType('Conducteur et Passager');
        } elseif ($isDriver) {
            $user->setUserType('Conducteur');
        } elseif ($isPassenger) {
            $user->setUserType('Passager');
        } else {
            $user->setUserType('Nouveau membre');
        }
        
        $this->entityManager->flush();
    }

    /**
     * Met à jour le statut utilisateur quand il ajoute un véhicule
     */
    public function updateStatusOnVehicleAdded(User $user): void
    {
        $this->updateUserStatus($user);
    }

    /**
     * Met à jour le statut utilisateur quand il supprime un véhicule
     */
    public function updateStatusOnVehicleRemoved(User $user): void
    {
        $this->updateUserStatus($user);
    }

    /**
     * Met à jour le statut utilisateur quand il réserve un trajet
     */
    public function updateStatusOnRideReserved(User $user): void
    {
        $this->updateUserStatus($user);
    }

    /**
     * Met à jour le statut utilisateur quand il crée un trajet
     */
    public function updateStatusOnRideCreated(User $user): void
    {
        $this->updateUserStatus($user);
    }
} 