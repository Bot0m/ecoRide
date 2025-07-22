<?php

namespace App\Command;

use App\Entity\User;
use App\Service\UserStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-user-status',
    description: 'Met à jour le statut de tous les utilisateurs basé sur leurs actions',
)]
class UpdateUserStatusCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserStatusService $userStatusService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour du statut des utilisateurs');

        // Récupérer tous les utilisateurs
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        $io->progressStart(count($users));
        
        $updatedCount = 0;
        
        foreach ($users as $user) {
            $oldStatus = $user->getUserType();
            
            // Mettre à jour le statut
            $this->userStatusService->updateUserStatus($user);
            
            $newStatus = $user->getUserType();
            
            if ($oldStatus !== $newStatus) {
                $updatedCount++;
                $io->text("Utilisateur {$user->getPseudo()}: {$oldStatus} → {$newStatus}");
            }
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        $io->success("Mise à jour terminée ! {$updatedCount} utilisateurs mis à jour sur " . count($users) . " total.");

        return Command::SUCCESS;
    }
} 