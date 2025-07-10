<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class UserController extends AbstractController
{
    #[Route('/credit', name: 'credit')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('user/credit.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/credit/add', name: 'credit_add', methods: ['POST'])]
    public function addCredits(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $creditsToAdd = $data['credits'] ?? 0;

        // Validation des montants autorisés
        $allowedAmounts = [10, 15, 20, 30];
        if (!in_array($creditsToAdd, $allowedAmounts)) {
            return new JsonResponse(['error' => 'Montant de crédits non autorisé'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();
        $currentCredits = $user->getCredits();
        $newCredits = $currentCredits + $creditsToAdd;

        $user->setCredits($newCredits);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'newCredits' => $newCredits,
            'addedCredits' => $creditsToAdd,
            'message' => "Félicitations ! Vous avez reçu {$creditsToAdd} crédits gratuits."
        ]);
    }
}