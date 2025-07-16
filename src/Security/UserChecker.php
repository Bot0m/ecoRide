<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si le compte est suspendu
        if (!$user->getIsActive()) {
            throw new AccountSuspendedException();
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérification post-authentification si nécessaire
        if (!$user->getIsActive()) {
            throw new AccountSuspendedException();
        }
    }
} 