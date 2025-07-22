# EcoRide - Plateforme de Covoiturage Écologique

## À propos du projet

EcoRide est une plateforme de covoiturage moderne développée avec Symfony 7.3, conçue pour faciliter le partage de trajets tout en promouvant la mobilité durable. Le projet met l'accent sur l'expérience utilisateur, la sécurité et l'impact environnemental positif.

### Fonctionnalités principales

- **Gestion multi-rôles** : Utilisateurs, Employés et Administrateurs
- **Système de covoiturage complet** : Création, recherche et réservation de trajets
- **Système d'évaluation** : Avis et notes entre utilisateurs
- **Gestion des véhicules** : Enregistrement et gestion des véhicules personnels
- **Système de crédits** : Économie virtuelle pour les trajets
- **Notifications en temps réel** : Système de notifications intégré
- **Interface responsive** : Design moderne avec Tailwind CSS et Flowbite

### Technologies utilisées

- **Backend** : Symfony 7.3, PHP 8.2+
- **Base de données** : MySQL avec Doctrine ORM et MongoDB
- **Frontend** : Twig, Tailwind CSS, Flowbite
- **JavaScript**
- **Outils** : Composer, Asset Mapper, Doctrine Migrations

### Architecture

Le projet suit une architecture MVC classique avec :
- **Entités** : User, Ride, Vehicle, Review, Participation, Notification, etc.
- **Contrôleurs** : Gestion des différentes sections (Admin, User, Employee)
- **Services** : Logique métier centralisée
- **Formulaires** : Validation et traitement des données
- **Sécurité** : Authentification et autorisation personnalisées

---

## Docker et Perte de Données

### Le défi Docker

Dans le cadre du développement, j'ai tenté d'implémenter Docker pour faciliter le déploiement et la collaboration. Cependant, cette étape s'est révélée plus complexe que prévu et a malheureusement conduit à un incident majeur.

### L'incident de la base de données

Lors de la configuration Docker, j'ai accidentellement écrasé toutes les données de ma base de données MySQL locale. Cet incident a eu plusieurs conséquences :

- **Perte de données de test** : Tous les utilisateurs, trajets et évaluations de test ont été supprimés
- **Retard dans le développement** : Temps nécessaire pour reconstituer les données essentielles
- **Leçon apprise** : Importance des sauvegardes régulières et de la séparation des environnements

### Impact sur le projet

Malgré cette perte de données, le projet a pu continuer grâce à :
- La structure de base de données préservée via les migrations Doctrine
- Les exports de données sauvegardés dans le dossier `BDD/`
- La possibilité de recréer les données de test

---

## Prochaines étapes

### 1. Dockerisation et Déploiement

**Objectif** : Mettre en place un environnement Docker robuste et sécurisé

- Créer un `Dockerfile` optimisé pour Symfony
- Configurer `docker-compose.yml` avec MySQL, mongoDB, PHP-FPM et Nginx
- Implémenter des volumes persistants pour éviter la perte de données
- Créer un environnement de développement isolé

### 2. Tests et Améliorations

**Objectif** : Permettre le test de la plateforme et identifier les améliorations

- Déployer sur un serveur de test accessible
- Organiser des sessions de test utilisateur
- Collecter les retours et suggestions d'amélioration
- Optimiser les performances et l'expérience utilisateur
- Implémenter les fonctionnalités manquantes identifiées

### 3. Fonctionnalités à développer au dela du projet ECF

- Système de paiement intégré
- Application mobile (React Native ou Flutter)
- Système de géolocalisation en temps réel
- Intégration avec les transports en commun

---

## Structure du projet

### Base de données
- **`BDD/ecoride_db.sql`** : Export complet de la structure et des données de la base de données
- **`BDD/ecoride.contact_messages.json`** : Messages de contact exportés

### Maquettes et Design
- **`Figma/`** : Toutes les maquettes Figma du projet
  - Maquettes pour chaque type d'utilisateur (visiteur, utilisateur, employé, administrateur)
  - Workflows utilisateur complets
  - Charte graphique et éléments de design
  - Maquettes de base et wireframes

### Code source
- **`src/`** : Code PHP Symfony (entités, contrôleurs, services)
- **`templates/`** : Templates Twig organisés par section
- **`assets/`** : Assets frontend (CSS, JavaScript)
- **`migrations/`** : Migrations Doctrine pour la base de données

---

## Développement continu

En attendant le retour de la correction, je continuerai le développement sur une **branche secondaire** pour :

- Implémenter les améliorations identifiées
- Corriger les bugs potentiels
- Ajouter de nouvelles fonctionnalités
- Optimiser les performances
- Préparer la version de production

---

## Contact

Pour toute question ou suggestion concernant le projet EcoRide, n'hésitez pas à me contacter.

**Développeur** : Perrocheau Tom  
**Email** : perrocheautom@icloud.com  
**GitHub** : Bot0m

---

*EcoRide - Ensemble pour une mobilité plus durable* 