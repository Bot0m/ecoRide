<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'app_notifications')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        $notifications = $notificationRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
        
        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(Notification $notification, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if ($notification->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Non autorisé'], 403);
        }
        
        $notification->setIsRead(true);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/notifications/unread-count', name: 'app_notifications_unread_count')]
    public function getUnreadCount(NotificationRepository $notificationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        $count = $notificationRepository->count([
            'user' => $user,
            'isRead' => false
        ]);
        
        return new JsonResponse(['count' => $count]);
    }
} 