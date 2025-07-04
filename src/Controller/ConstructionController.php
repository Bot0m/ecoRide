<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConstructionController extends AbstractController
{
    #[Route('/en-construction', name: 'app_construction')]
    public function index(): Response
    {
        return $this->render('construction/index.html.twig');
    }
} 