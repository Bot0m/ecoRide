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

/**
 * Contrôleur pour la gestion administrative
 * 
 * Ce contrôleur gère toutes les fonctionnalités d'administration :
 * - Tableau de bord avec statistiques
 * - Gestion des employés
 * - Gestion des utilisateurs
 * - Validation des avis
 * - Statistiques de la plateforme
 */
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
        
        // Récupérer les semaines sélectionnées depuis la requête (séparées pour users, rides et credits)
        $selectedUserWeekParam = $request->query->get('user_week');
        $selectedRideWeekParam = $request->query->get('ride_week');
        $selectedCreditsWeekParam = $request->query->get('credits_week');
        
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

        // Statistiques crédits de la plateforme (2 crédits par participation)
        $totalParticipations = $entityManager->getRepository(\App\Entity\Participation::class)->count([]);
        $totalCreditsPlatform = $totalParticipations * 2; // 2 crédits par participation

        $avgCreditsPerParticipation = $totalParticipations > 0 ? round($totalCreditsPlatform / $totalParticipations) : 0;

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
                    } elseif ($entityType === 'ride') {
                        $count = $entityManager->getRepository(Ride::class)
                            ->createQueryBuilder('r')
                            ->select('COUNT(r.id)')
                            ->where('r.createdAt BETWEEN :dayStart AND :dayEnd')
                            ->setParameter('dayStart', $dayStart)
                            ->setParameter('dayEnd', $dayEnd)
                            ->getQuery()
                            ->getSingleScalarResult();
                    } else { // credits
                        // Compter les participations créées ce jour et multiplier par 2 (commission plateforme)
                        $count = $entityManager->getRepository(\App\Entity\Participation::class)
                            ->createQueryBuilder('p')
                            ->select('COUNT(p.id)')
                            ->where('p.createdAt BETWEEN :dayStart AND :dayEnd')
                            ->setParameter('dayStart', $dayStart)
                            ->setParameter('dayEnd', $dayEnd)
                            ->getQuery()
                            ->getSingleScalarResult();
                        $count = $count * 2; // 2 crédits par participation
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

        // Traiter les trois semaines séparément
        $selectedUserWeek = $processSelectedWeek($selectedUserWeekParam);
        $selectedRideWeek = $processSelectedWeek($selectedRideWeekParam);
        $selectedCreditsWeek = $processSelectedWeek($selectedCreditsWeekParam);

        // Générer les données pour les utilisateurs
        [$weeklyUserData, $userWeekLabels] = $generateWeekData($selectedUserWeek, 'user');
        $userNavigation = $generateNavigation($selectedUserWeek, $minDate, $today);
        
        // Générer les données pour les trajets
        [$weeklyRideData, $rideWeekLabels] = $generateWeekData($selectedRideWeek, 'ride');
        $rideNavigation = $generateNavigation($selectedRideWeek, $minDate, $today);

        // Générer les données pour les crédits (semaine séparée)
        [$weeklyCreditsData, $creditsWeekLabels] = $generateWeekData($selectedCreditsWeek, 'credits');
        $creditsNavigation = $generateNavigation($selectedCreditsWeek, $minDate, $today);
        
        // Calculs cohérents basés sur les VRAIES données
        $newUsersThisWeek = array_sum($weeklyUserData);
        $newRidesThisWeek = array_sum($weeklyRideData);
        $newCreditsThisWeek = array_sum($weeklyCreditsData);
        
        return $this->render('admin/index.html.twig', [
            'user' => $this->getUser(),
            'stats' => [
                'totalUsers' => $totalUsers,
                'newUsersThisWeek' => $newUsersThisWeek,
                'totalRides' => $totalRides,
                'ridesThisWeek' => $newRidesThisWeek,
                'ecoRides' => $ecoRides,
                'totalCredits' => $totalCreditsPlatform,
                'avgCredits' => $avgCreditsPerParticipation,
                'creditsThisWeek' => $newCreditsThisWeek,
                'totalReviews' => $totalReviews,
                'avgRating' => round($avgRating, 1),
                'weeklyUserData' => $weeklyUserData,
                'weeklyRideData' => $weeklyRideData,
                'weeklyCreditsData' => $weeklyCreditsData,
                'userWeekLabels' => $userWeekLabels,
                'rideWeekLabels' => $rideWeekLabels,
                'creditsWeekLabels' => $creditsWeekLabels,
                'userGrowth' => $totalUsers > 0 ? round(($newUsersThisWeek / $totalUsers) * 100, 1) : 0,
                'rideGrowth' => $totalRides > 0 ? round(($newRidesThisWeek / $totalRides) * 100, 1) : 0,
                'ecoPercentage' => $totalRides > 0 ? round(($ecoRides / $totalRides) * 100, 1) : 0,
                'minDateFormatted' => $minDate->format('d/m/Y'),
                // Navigation séparée pour les utilisateurs
                'userNavigation' => $userNavigation,
                // Navigation séparée pour les trajets
                'rideNavigation' => $rideNavigation,
                // Navigation séparée pour les crédits
                'creditsNavigation' => $creditsNavigation,
            ]
        ]);
    }

    #[Route('/create-employe', name: 'admin_create_employe')]
    public function createEmploye(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(EmployeType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles(['ROLE_EMPLOYE']);
            $user->setCredits(0);
            $user->setIsActive(true);

            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('admin_create_employe');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Compter le total d'employés
        $totalEmployees = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_EMPLOYE%')
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les employés actifs
        $activeEmployees = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->andWhere('u.isActive = :isActive')
            ->setParameter('role', '%ROLE_EMPLOYE%')
            ->setParameter('isActive', true)
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les employés suspendus
        $suspendedEmployees = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->andWhere('u.isActive = :isActive')
            ->setParameter('role', '%ROLE_EMPLOYE%')
            ->setParameter('isActive', false)
            ->getQuery()
            ->getSingleScalarResult();

        // Récupérer les employés paginés
        $employees = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_EMPLOYE%')
            ->orderBy('u.pseudo', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        $totalPages = ceil($totalEmployees / $limit);

        // Formulaire d'édition pour chaque employé
        $editForms = [];
        foreach ($employees as $employee) {
            $editForms[$employee->getId()] = $this->createForm(EmployeType::class, $employee)->createView();
        }

        return $this->render('admin/create_employe.html.twig', [
            'form' => $form->createView(),
            'employees' => $employees,
            'editForms' => $editForms,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'suspendedEmployees' => $suspendedEmployees,
        ]);
    }

    #[Route('/employe/{id}/toggle-status', name: 'admin_toggle_employe_status')]
    public function toggleEmployeStatus(User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérifier que c'est bien un employé
        if (!in_array('ROLE_EMPLOYE', $user->getRoles())) {
            throw $this->createNotFoundException('Employé non trouvé');
        }

        $user->setIsActive(!$user->getIsActive());
        $entityManager->flush();

        return $this->redirectToRoute('admin_create_employe');
    }

    #[Route('/employe/{id}/edit', name: 'admin_edit_employe')]
    public function editEmploye(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérifier que c'est bien un employé
        if (!in_array('ROLE_EMPLOYE', $user->getRoles())) {
            throw $this->createNotFoundException('Employé non trouvé');
        }

        $form = $this->createForm(EmployeType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe a été fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $hasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();
            return $this->redirectToRoute('admin_create_employe');
        }

        // Si le formulaire a des erreurs, rediriger vers la page principale avec l'ID de l'employé à éditer
        return $this->redirectToRoute('admin_create_employe', ['edit' => $user->getId()]);
    }

    #[Route('/users', name: 'admin_users')]
    public function manageUsers(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Compter le total d'utilisateurs
        $totalUsers = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles NOT LIKE :admin AND u.roles NOT LIKE :employe')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('employe', '%ROLE_EMPLOYE%')
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les utilisateurs actifs
        $activeUsers = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles NOT LIKE :admin AND u.roles NOT LIKE :employe')
            ->andWhere('u.isActive = :isActive')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('employe', '%ROLE_EMPLOYE%')
            ->setParameter('isActive', true)
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les utilisateurs suspendus
        $suspendedUsers = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles NOT LIKE :admin AND u.roles NOT LIKE :employe')
            ->andWhere('u.isActive = :isActive')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('employe', '%ROLE_EMPLOYE%')
            ->setParameter('isActive', false)
            ->getQuery()
            ->getSingleScalarResult();

        // Récupérer les utilisateurs paginés
        $users = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :admin AND u.roles NOT LIKE :employe')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('employe', '%ROLE_EMPLOYE%')
            ->orderBy('u.pseudo', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        $totalPages = ceil($totalUsers / $limit);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'suspendedUsers' => $suspendedUsers,
        ]);
    }

    #[Route('/user/{id}/toggle-status', name: 'admin_toggle_user_status')]
    public function toggleUserStatus(User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérifier que ce n'est pas un admin ou employé
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_EMPLOYE', $user->getRoles())) {
            throw $this->createNotFoundException('Impossible de modifier ce type de compte');
        }

        $user->setIsActive(!$user->getIsActive());
        $entityManager->flush();

        return $this->redirectToRoute('admin_users');
    }
}
