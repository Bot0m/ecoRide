<?php

namespace App\Service;

use App\Entity\Review;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ReviewService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Met à jour la note moyenne d'un utilisateur basée sur ses avis validés
     */
    public function updateUserAverageRating(User $user): void
    {
        $reviews = $this->entityManager->getRepository(Review::class)->findBy([
            'reviewedUser' => $user,
            'isValidated' => true
        ]);

        if (empty($reviews)) {
            // Si aucun avis validé, la note par défaut est 5
            $user->setAverageRating(5.0);
        } else {
            $totalRating = 0;
            foreach ($reviews as $review) {
                $totalRating += $review->getRating();
            }
            $averageRating = round($totalRating / count($reviews), 1);
            $user->setAverageRating($averageRating);
        }

        $this->entityManager->flush();
    }

    /**
     * Valide un avis et met à jour la note moyenne de l'utilisateur évalué
     */
    public function validateReview(Review $review): void
    {
        $review->setIsValidated(true);
        $this->entityManager->flush();

        // Mettre à jour la note moyenne de l'utilisateur évalué
        $this->updateUserAverageRating($review->getReviewedUser());
    }

    /**
     * Marque un avis comme refusé (au lieu de le supprimer pour garder l'historique)
     */
    public function rejectReview(Review $review): void
    {
        $review->setIsValidated(false);
        $this->entityManager->flush();
    }

    /**
     * Crée un nouvel avis
     */
    public function createReview(User $author, User $reviewedUser, int $rating, ?string $comment = null): Review
    {
        $review = new Review();
        $review->setAuthor($author);
        $review->setReviewedUser($reviewedUser);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setIsValidated(false); // Par défaut, l'avis n'est pas validé

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    /**
     * Vérifie si un utilisateur peut laisser un avis pour un autre utilisateur sur un trajet spécifique
     */
    public function canUserReviewUser(User $author, User $reviewedUser, int $rideId): bool
    {
        // Vérifier si l'utilisateur n'a pas déjà laissé un avis pour cet utilisateur sur ce trajet
        $participation = $this->getUserParticipation($author, $rideId);
        $existingReview = $this->entityManager->getRepository(Review::class)->findOneBy([
            'author' => $author,
            'reviewedUser' => $reviewedUser
        ]);

        // Vérifier si l'avis existe déjà pour cette participation
        if ($existingReview && $existingReview->getParticipation() === $participation) {
            return false;
        }

        return true;
    }

    /**
     * Récupère la participation d'un utilisateur pour un trajet
     */
    private function getUserParticipation(User $user, int $rideId): ?\App\Entity\Participation
    {
        return $this->entityManager->getRepository(\App\Entity\Participation::class)
            ->findOneBy(['user' => $user, 'ride' => $rideId]);
    }
} 