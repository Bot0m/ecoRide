<?php

namespace App\EventSubscriber;

use App\Form\ContactType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;

class GlobalContactFormSubscriber implements EventSubscriberInterface
{
    private FormFactoryInterface $formFactory;
    private Environment $twig;

    public function __construct(FormFactoryInterface $formFactory, Environment $twig)
    {
        $this->formFactory = $formFactory;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $form = $this->formFactory->create(ContactType::class)->createView();
        $this->twig->addGlobal('contactForm', $form);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}