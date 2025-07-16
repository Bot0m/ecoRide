<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\LoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                // encode the plain password
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
                
                // S'assurer que le compte est actif par défaut
                $user->setIsActive(true);

                $entityManager->persist($user);
                $entityManager->flush();

                // Connexion automatique
                return $security->login($user, LoginAuthenticator::class, 'main');
                
            } catch (\Exception $e) {
                // Log l'erreur
                error_log('Erreur inscription: ' . $e->getMessage());
                
                // Message d'erreur générique pour l'utilisateur
                $this->addFlash('error', 'Une erreur est survenue lors de la création de votre compte. Veuillez réessayer.');
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            // Le formulaire a été soumis mais contient des erreurs
            $this->addFlash('error', 'Veuillez corriger les erreurs ci-dessous.');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
