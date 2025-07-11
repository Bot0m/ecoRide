<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class VehicleController extends AbstractController
{
    #[Route('/mes-vehicules', name: 'app_vehicles', methods: ['GET', 'POST'])]
    public function index(Request $request, VehicleRepository $vehicleRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        // Création du formulaire d'ajout
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        // Traitement de l'ajout si le formulaire est soumis
        if ($form->isSubmitted() && $form->isValid()) {
            $vehicle->setOwner($user);
            $entityManager->persist($vehicle);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicles');
        }

        // Récupération des véhicules
        $vehicles = $vehicleRepository->findBy(['owner' => $user]);

        return $this->render('user/vehicles.html.twig', [
            'vehicles' => $vehicles,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-vehicules/{id}/supprimer', name: 'app_vehicles_delete')]
    public function delete(Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        if ($vehicle->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($vehicle);
        $entityManager->flush();

        return $this->redirectToRoute('app_vehicles');
    }
} 