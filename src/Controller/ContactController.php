<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\ContactMessageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['POST'])]
    public function handle(Request $request, ContactMessageService $mongo): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $mongo->save($data['email'], $data['sujet'], $data['message']);
            $this->addFlash('success', 'Message envoyé !');
        }

        return $this->redirectToRoute('homepage');
    }
}