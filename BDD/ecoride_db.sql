-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 22 juil. 2025 à 18:06
-- Version du serveur : 9.2.0
-- Version de PHP : 8.4.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ecoride_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `access_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20250611090939', '2025-06-11 09:15:08', 280),
('DoctrineMigrations\\Version20250612075511', '2025-06-12 07:55:23', 57),
('DoctrineMigrations\\Version20250710080017', '2025-07-10 08:00:25', 46),
('DoctrineMigrations\\Version20250710094253', '2025-07-10 09:43:25', 47),
('DoctrineMigrations\\Version20250711174545', '2025-07-11 17:50:43', 36),
('DoctrineMigrations\\Version20250714143935', '2025-07-14 14:43:46', 70),
('DoctrineMigrations\\Version20250716083833', '2025-07-16 08:41:10', 168),
('DoctrineMigrations\\Version20250716115754', '2025-07-16 11:58:08', 53),
('DoctrineMigrations\\Version20250718081823', '2025-07-18 08:18:51', 41),
('DoctrineMigrations\\Version20250718084059', '2025-07-18 08:41:09', 46),
('DoctrineMigrations\\Version20250718085156', '2025-07-18 08:52:01', 96),
('DoctrineMigrations\\Version20250718093858', '2025-07-18 09:39:01', 25),
('DoctrineMigrations\\Version20250721100641', '2025-07-21 10:06:48', 33),
('DoctrineMigrations\\Version20250721132010', '2025-07-21 13:20:14', 60),
('DoctrineMigrations\\Version20250721164953_fix_reviews', '2025-07-21 16:53:10', 50),
('DoctrineMigrations\\Version20250722121500', '2025-07-22 12:15:38', 116),
('DoctrineMigrations\\Version20250722170546', '2025-07-22 17:05:54', 71);

-- --------------------------------------------------------

--
-- Structure de la table `employee`
--

CREATE TABLE `employee` (
  `id` int NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `ride_id` int DEFAULT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `participation`
--

CREATE TABLE `participation` (
  `id` int NOT NULL,
  `ride_id` int NOT NULL,
  `user_id` int NOT NULL,
  `review_id` int DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `has_given_review` tinyint(1) NOT NULL,
  `rating` int DEFAULT NULL,
  `trip_validated` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `seats_count` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `participation`
--

INSERT INTO `participation` (`id`, `ride_id`, `user_id`, `review_id`, `status`, `has_given_review`, `rating`, `trip_validated`, `created_at`, `seats_count`) VALUES
(1, 2, 4, 2, 'acceptee', 1, NULL, 0, '2025-07-22 16:57:31', 1),
(2, 2, 3, 1, 'acceptee', 1, NULL, 1, '2025-07-22 16:58:50', 1);

-- --------------------------------------------------------

--
-- Structure de la table `preference`
--

CREATE TABLE `preference` (
  `id` int NOT NULL,
  `smoker` tinyint(1) NOT NULL,
  `animals` tinyint(1) NOT NULL,
  `custom_preferences` longtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `preference`
--

INSERT INTO `preference` (`id`, `smoker`, `animals`, `custom_preferences`) VALUES
(1, 0, 1, '');

-- --------------------------------------------------------

--
-- Structure de la table `review`
--

CREATE TABLE `review` (
  `id` int NOT NULL,
  `author_id` int NOT NULL,
  `reviewed_user_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  `is_validated` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `review`
--

INSERT INTO `review` (`id`, `author_id`, `reviewed_user_id`, `rating`, `comment`, `is_validated`) VALUES
(1, 3, 4, 4, 'Super moment passé l\'ors de ce voyage', 1),
(2, 4, 3, 4, 'Super voyage !', 1);

-- --------------------------------------------------------

--
-- Structure de la table `ride`
--

CREATE TABLE `ride` (
  `id` int NOT NULL,
  `driver_id` int NOT NULL,
  `vehicle_id` int NOT NULL,
  `departure` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arrival` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time NOT NULL,
  `price` int NOT NULL,
  `available_seats` int NOT NULL,
  `is_ecological` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'actif',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ride`
--

INSERT INTO `ride` (`id`, `driver_id`, `vehicle_id`, `departure`, `arrival`, `date`, `departure_time`, `arrival_time`, `price`, `available_seats`, `is_ecological`, `created_at`, `status`, `started_at`, `completed_at`) VALUES
(1, 3, 1, 'Nantes', 'La Baule-Escoublac', '2025-07-21', '19:00:00', '20:00:00', 12, 3, 1, '2025-07-22 16:49:53', 'actif', NULL, NULL),
(2, 3, 1, 'Nantes', 'La Baule-Escoublac', '2025-07-22', '19:00:00', '20:00:00', 12, 2, 1, '2025-07-22 16:52:45', 'termine', '2025-07-22 16:58:21', '2025-07-22 16:58:25');

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `preference_id` int DEFAULT NULL,
  `pseudo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `credits` int NOT NULL,
  `bio` longtext COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `average_rating` double NOT NULL DEFAULT '5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `preference_id`, `pseudo`, `email`, `password`, `roles`, `credits`, `bio`, `avatar`, `user_type`, `created_at`, `is_active`, `average_rating`) VALUES
(1, NULL, 'admin', 'admin@ecoride.com', '$2y$12$krzJxuAGiNBdf4x/Ka7UbONezhw7grjLP/aLVZMVuGiTcKxRhuDzq', '[\"ROLE_ADMIN\"]', 0, 'Compte administrateur', NULL, NULL, '2025-07-22 16:15:28', 1, 0),
(2, NULL, 'Tom', 'tom@ecoride.com', '$2y$13$lZsBDNOOXryURIXHlejMCubFTrWIyXb7vXyTfQwAURVdNORs.A3rm', '[\"ROLE_EMPLOYE\"]', 0, NULL, NULL, NULL, '2025-07-22 14:39:47', 1, 5),
(3, 1, 'Bob', 'bob@ecoride.com', '$2y$13$p3mAAccWrWZtBPSicwsmleR/tTyisc6wq8zzgu3LhBF2941HZWzdO', '[]', 32, 'Je suis Bob et j\'adore partager mes voyages', NULL, 'Conducteur', '2025-07-22 14:40:43', 1, 4),
(4, NULL, 'Pitou', 'pitou@ecoride.com', '$2y$13$kZrLQ/n/0v1m3nxM5RrU5eFQp8AB.gmMQTxEbxiciiSvHOp6FmjvW', '[]', 6, NULL, NULL, 'Conducteur et Passager', '2025-07-22 16:53:54', 1, 4);

-- --------------------------------------------------------

--
-- Structure de la table `user_ride`
--

CREATE TABLE `user_ride` (
  `user_id` int NOT NULL,
  `ride_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_ride`
--

INSERT INTO `user_ride` (`user_id`, `ride_id`) VALUES
(4, 2);

-- --------------------------------------------------------

--
-- Structure de la table `vehicle`
--

CREATE TABLE `vehicle` (
  `id` int NOT NULL,
  `owner_id` int NOT NULL,
  `plate` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_date` date NOT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `energy` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seats` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `vehicle`
--

INSERT INTO `vehicle` (`id`, `owner_id`, `plate`, `registration_date`, `model`, `brand`, `energy`, `color`, `seats`) VALUES
(1, 3, 'AB-123-CD', '2025-07-16', '500e', 'Fiat', 'Électrique', 'Vert', 3),
(2, 3, 'AR-456-ET', '2025-07-23', '208', 'peugeot', 'Diesel', 'Gris', 5),
(3, 4, 'TG-254-HD', '2025-07-12', 'Model3', 'tesla', 'Électrique', 'Rouge', 5);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  ADD KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  ADD KEY `IDX_75EA56E016BA31DB` (`delivered_at`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_BF5476CAA76ED395` (`user_id`),
  ADD KEY `IDX_BF5476CA302A8A70` (`ride_id`);

--
-- Index pour la table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_AB55E24F3E2E969B` (`review_id`),
  ADD KEY `IDX_AB55E24F302A8A70` (`ride_id`),
  ADD KEY `IDX_AB55E24FA76ED395` (`user_id`);

--
-- Index pour la table `preference`
--
ALTER TABLE `preference`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_794381C6F675F31B` (`author_id`),
  ADD KEY `IDX_794381C6B9A2A077` (`reviewed_user_id`);

--
-- Index pour la table `ride`
--
ALTER TABLE `ride`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_9B3D7CD0C3423909` (`driver_id`),
  ADD KEY `IDX_9B3D7CD0545317D1` (`vehicle_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649D81022C0` (`preference_id`);

--
-- Index pour la table `user_ride`
--
ALTER TABLE `user_ride`
  ADD PRIMARY KEY (`user_id`,`ride_id`),
  ADD KEY `IDX_E1BC3019A76ED395` (`user_id`),
  ADD KEY `IDX_E1BC3019302A8A70` (`ride_id`);

--
-- Index pour la table `vehicle`
--
ALTER TABLE `vehicle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1B80E4867E3C61F9` (`owner_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `participation`
--
ALTER TABLE `participation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `preference`
--
ALTER TABLE `preference`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `review`
--
ALTER TABLE `review`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `ride`
--
ALTER TABLE `ride`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `vehicle`
--
ALTER TABLE `vehicle`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `FK_BF5476CA302A8A70` FOREIGN KEY (`ride_id`) REFERENCES `ride` (`id`),
  ADD CONSTRAINT `FK_BF5476CAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `FK_AB55E24F302A8A70` FOREIGN KEY (`ride_id`) REFERENCES `ride` (`id`),
  ADD CONSTRAINT `FK_AB55E24F3E2E969B` FOREIGN KEY (`review_id`) REFERENCES `review` (`id`),
  ADD CONSTRAINT `FK_AB55E24FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `FK_794381C6B9A2A077` FOREIGN KEY (`reviewed_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_794381C6F675F31B` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `ride`
--
ALTER TABLE `ride`
  ADD CONSTRAINT `FK_9B3D7CD0545317D1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicle` (`id`),
  ADD CONSTRAINT `FK_9B3D7CD0C3423909` FOREIGN KEY (`driver_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `FK_8D93D649D81022C0` FOREIGN KEY (`preference_id`) REFERENCES `preference` (`id`);

--
-- Contraintes pour la table `user_ride`
--
ALTER TABLE `user_ride`
  ADD CONSTRAINT `FK_E1BC3019302A8A70` FOREIGN KEY (`ride_id`) REFERENCES `ride` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_E1BC3019A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `vehicle`
--
ALTER TABLE `vehicle`
  ADD CONSTRAINT `FK_1B80E4867E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
