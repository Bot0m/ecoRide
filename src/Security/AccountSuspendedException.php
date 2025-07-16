<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AccountSuspendedException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Votre compte a été suspendu. Veuillez contacter l\'administrateur.';
    }
} 