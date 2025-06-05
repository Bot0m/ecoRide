<?php

namespace App\Controller;

use App\Service\AvisManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AvisController extends AbstractController
{
    /*
     * ✅ Route temporaire créée pour tester l’initialisation de MongoDB
     * Cette route insère un avis dans la base NoSQL.
     * Elle a permis de valider la connexion et l’insertion via AvisManager.
     * À désactiver ou supprimer une fois l’US correspondante implémentée.
     */
    // #[Route('/ajouter-avis', name: 'ajouter_avis')]
    // public function ajouterAvis(AvisManager $avisManager): Response
    // {
    //     $avisManager->ajouterAvis([
    //         'note' => 5,
    //         'commentaire' => 'Excellent covoiturage !',
    //         'chauffeur' => 'Jean',
    //         'passager' => 'Tom',
    //         'date' => new \DateTime(),
    //     ]);

    //     return new Response('✅ Avis ajouté avec succès');
    // }
}