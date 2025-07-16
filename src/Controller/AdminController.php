<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\Ride;
use App\Entity\Review;
use App\Form\EmployeType;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Date limite : lundi 23 juin 2024
        $minDate = new \DateTime('2024-06-23'); // Lundi 23 juin 2024
        $minDate->setTime(0, 0, 0);
        
        // Récupérer les semaines sélectionnées depuis la requête (séparées pour users et rides)
        $selectedUserWeekParam = $request->query->get('user_week');
        $selectedRideWeekParam = $request->query->get('ride_week');
        
        $today = new \DateTime();
        $currentDayOfWeek = (int)$today->format('N');
        $currentMonday = clone $today;
        $currentMonday->modify('-' . ($currentDayOfWeek - 1) . ' days');
        
        // Vérifier que la semaine courante n'est pas antérieure au 23 juin 2024
        if ($currentMonday < $minDate) {
            $currentMonday = clone $minDate;
        }
        
        // Fonction helper pour traiter une semaine sélectionnée
        $processSelectedWeek = function($weekParam) use ($minDate, $currentMonday) {
            $selectedWeek = null;
            
            if ($weekParam) {
                try {
                    $selectedWeek = new \DateTime($weekParam);
                    // S'assurer que c'est un lundi
                    $dayOfWeek = (int)$selectedWeek->format('N');
                    if ($dayOfWeek !== 1) {
                        $selectedWeek->modify('-' . ($dayOfWeek - 1) . ' days');
                    }
                    // Vérifier que la semaine n'est pas antérieure au 23 juin 2024
                    if ($selectedWeek < $minDate) {
                        $selectedWeek = clone $minDate;
                    }
                } catch (\Exception $e) {
                    $selectedWeek = null;
                }
            }
            
            // Si pas de semaine sélectionnée ou invalide, utiliser la semaine courante
            if (!$selectedWeek) {
                $selectedWeek = clone $currentMonday;
            }
            
            $selectedWeek->setTime(0, 0, 0);
            return $selectedWeek;
        };
        
        // Statistiques utilisateurs (seulement les vrais utilisateurs, pas admin/employé)
        $totalUsers = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles NOT LIKE :admin AND u.roles NOT LIKE :employe')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('employe', '%ROLE_EMPLOYE%')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Statistiques trajets
        $totalRides = $entityManager->getRepository(Ride::class)->count([]);

        $ecoRides = $entityManager->getRepository(Ride::class)->count(['isEcological' => true]);

        // Statistiques crédits (seulement les vrais utilisateurs)
        $totalCredits = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('SUM(u.credits)')
            ->where('u.roles NOT LIKE :admin AND u.roles NOT LIKE :employe')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('employe', '%ROLE_EMPLOYE%')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $avgCredits = $totalUsers > 0 ? round($totalCredits / $totalUsers) : 0;

        // Statistiques avis
        $totalReviews = $entityManager->getRepository(Review::class)->count([]);
        $avgRating = $entityManager->getRepository(Review::class)
            ->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Fonction helper pour générer les données d'une semaine
        $generateWeekData = function($selectedWeek, $entityType) use ($entityManager, $today) {
            $weeklyData = [0, 0, 0, 0, 0, 0, 0];
            $weekLabels = [];
            $frenchDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
            
            for ($i = 0; $i < 7; $i++) {
                $day = clone $selectedWeek;
                $day->modify('+' . $i . ' days');
                $weekLabels[] = $frenchDays[$i] . ' ' . $day->format('d/m');
                
                $dayStart = clone $day;
                $dayStart->setTime(0, 0, 0);
                $dayEnd = clone $day;
                $dayEnd->setTime(23, 59, 59);
                
                // Si c'est un jour futur par rapport à aujourd'hui, mettre 0
                if ($day > $today) {
                    $weeklyData[$i] = 0;
                } else {
                    if ($entityType === 'user') {
                        $count = $entityManager->getRepository(User::class)
                            ->createQueryBuilder('u')
                            ->select('COUNT(u.id)')
                            ->where('u.roles NOT LIKE :admin AND u.roles NOT LIKE :employe')
                            ->andWhere('u.createdAt BETWEEN :dayStart AND :dayEnd')
                            ->setParameter('admin', '%ROLE_ADMIN%')
                            ->setParameter('employe', '%ROLE_EMPLOYE%')
                            ->setParameter('dayStart', $dayStart)
                            ->setParameter('dayEnd', $dayEnd)
                            ->getQuery()
                            ->getSingleScalarResult();
                    } else { // ride
                        $count = $entityManager->getRepository(Ride::class)
                            ->createQueryBuilder('r')
                            ->select('COUNT(r.id)')
                            ->where('r.createdAt BETWEEN :dayStart AND :dayEnd')
                            ->setParameter('dayStart', $dayStart)
                            ->setParameter('dayEnd', $dayEnd)
                            ->getQuery()
                            ->getSingleScalarResult();
                    }
                    
                    $weeklyData[$i] = (int)$count;
                }
            }
            
            return [$weeklyData, $weekLabels];
        };
        
        // Fonction helper pour générer les infos de navigation
        $generateNavigation = function($selectedWeek, $minDate, $today) {
            $previousWeek = clone $selectedWeek;
            $previousWeek->modify('-7 days');
            $nextWeek = clone $selectedWeek;
            $nextWeek->modify('+7 days');
            
            $weekStart = $selectedWeek->format('d/m/Y');
            $weekEnd = clone $selectedWeek;
            $weekEnd->modify('+6 days');
            $weekEnd = $weekEnd->format('d/m/Y');
            
            return [
                'selectedWeek' => $selectedWeek->format('Y-m-d'),
                'previousWeek' => $previousWeek->format('Y-m-d'),
                'nextWeek' => $nextWeek->format('Y-m-d'),
                'canGoPrevious' => $previousWeek >= $minDate,
                'canGoNext' => $nextWeek <= $today,
                'weekPeriod' => "Semaine du $weekStart au $weekEnd"
            ];
        };

        // Traiter les deux semaines séparément
        $selectedUserWeek = $processSelectedWeek($selectedUserWeekParam);
        $selectedRideWeek = $processSelectedWeek($selectedRideWeekParam);

        // Générer les données pour les utilisateurs
        [$weeklyUserData, $userWeekLabels] = $generateWeekData($selectedUserWeek, 'user');
        $userNavigation = $generateNavigation($selectedUserWeek, $minDate, $today);
        
        // Générer les données pour les trajets
        [$weeklyRideData, $rideWeekLabels] = $generateWeekData($selectedRideWeek, 'ride');
        $rideNavigation = $generateNavigation($selectedRideWeek, $minDate, $today);

        // Calculs cohérents basés sur les VRAIES données
        $newUsersThisWeek = array_sum($weeklyUserData);
        $newRidesThisWeek = array_sum($weeklyRideData);
        
        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
            'stats' => [
                'totalUsers' => $totalUsers,
                'newUsersThisWeek' => $newUsersThisWeek,
                'totalRides' => $totalRides,
                'ridesThisWeek' => $newRidesThisWeek,
                'ecoRides' => $ecoRides,
                'totalCredits' => $totalCredits,
                'avgCredits' => $avgCredits,
                'totalReviews' => $totalReviews,
                'avgRating' => round($avgRating, 1),
                'weeklyUserData' => $weeklyUserData,
                'weeklyRideData' => $weeklyRideData,
                'userWeekLabels' => $userWeekLabels,
                'rideWeekLabels' => $rideWeekLabels,
                'userGrowth' => $totalUsers > 0 ? round(($newUsersThisWeek / $totalUsers) * 100, 1) : 0,
                'rideGrowth' => $totalRides > 0 ? round(($newRidesThisWeek / $totalRides) * 100, 1) : 0,
                'ecoPercentage' => $totalRides > 0 ? round(($ecoRides / $totalRides) * 100, 1) : 0,
                'minDateFormatted' => $minDate->format('d/m/Y'),
                // Navigation séparée pour les utilisateurs
                'userNavigation' => $userNavigation,
                // Navigation séparée pour les trajets
                'rideNavigation' => $rideNavigation,
            ]
        ]);
    }

    #[Route('/create-employe', name: 'admin_create_employe')]
    public function createEmploye(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(EmployeType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles(['ROLE_EMPLOYE']);
            $user->setCredits(0);

            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Employé créé avec succès !');
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/create_employe.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
