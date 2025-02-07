-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : ven. 07 fév. 2025 à 15:59
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `depotmemo`
--

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `parent_id`) VALUES
(1, 'Livres', NULL),
(2, 'Type Bac', NULL),
(3, 'Fascicule', NULL),
(4, 'Cours', NULL),
(5, 'Sujets', NULL),
(6, 'Série A', 2),
(7, 'Série C', 2),
(8, 'Série D', 2),
(9, 'Série G', 2),
(13, 'Mathématiques', 6),
(14, 'Mathématiques', 7),
(15, 'Mathématiques', 8),
(16, 'Mathématiques', 9),
(17, 'Physique-Chimie', 7),
(18, 'Physique-Chimie', 8),
(19, 'SVT', 8),
(20, 'Arabe', 6),
(21, 'Arabe', 7),
(22, 'Arabe', 8),
(23, 'Arabe', 9);

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `auteur` varchar(100) NOT NULL,
  `universite` varchar(100) DEFAULT NULL,
  `annee` int(11) NOT NULL,
  `type_document` enum('livre','type_bac','fascicule','cours','sujet') NOT NULL,
  `serie` enum('A','C','D','G') DEFAULT NULL,
  `niveau` enum('A','F','autre') DEFAULT NULL,
  `matiere` enum('math','pc','svt','arabe') DEFAULT NULL,
  `fichier` varchar(255) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut` enum('en_attente','approuve','rejete') DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `documents`
--

INSERT INTO `documents` (`id`, `titre`, `auteur`, `universite`, `annee`, `type_document`, `serie`, `niveau`, `matiere`, `fichier`, `date_creation`, `statut`) VALUES
(2, 'Système de gestion d&#39;une usine', 'Ateib Abdoulaye', NULL, 2000, 'type_bac', 'D', NULL, 'svt', '67a5c7cddaa03.pdf', '2025-02-07 08:43:57', 'en_attente'),
(3, 'test', 'test', NULL, 2014, 'livre', NULL, NULL, NULL, '67a5c7fe05924.docx', '2025-02-07 08:44:46', 'en_attente'),
(4, 'aaa', 'aaa', NULL, 2000, 'fascicule', NULL, NULL, 'arabe', '67a5ca1c806ae.pdf', '2025-02-07 08:53:48', 'en_attente'),
(6, 'Sujet Mathématique', 'Fadoul', NULL, 2000, 'type_bac', 'D', NULL, 'pc', '67a5d7541e8f2.docx', '2025-02-07 09:50:12', 'en_attente'),
(8, 'Système de gestion d&#39;une parc', 'Hassan Ahmat Fadoul', NULL, 2015, 'livre', NULL, NULL, NULL, '67a60d6cadcd0.pdf', '2025-02-07 13:41:00', 'en_attente'),
(9, 'Sujet SVT', 'Djimnangar Joseph', NULL, 2000, 'cours', NULL, NULL, 'svt', '67a61091c0bbf.pdf', '2025-02-07 13:54:25', 'en_attente'),
(10, 'Concours Aviation Civile', 'Hassan Ahmat Fadoul', NULL, 2022, 'sujet', 'C', NULL, NULL, '67a610cf6d82c.pdf', '2025-02-07 13:55:27', 'en_attente'),
(11, 'systeme de bibliothèque', 'Fadoul', NULL, 2021, 'livre', NULL, NULL, NULL, '67a622857ccf3.pdf', '2025-02-07 15:11:01', 'en_attente');

-- --------------------------------------------------------

--
-- Structure de la table `document_categories`
--

CREATE TABLE `document_categories` (
  `document_id` int(11) NOT NULL,
  `categorie_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`email`, `password`) VALUES
('mhtalikore@gmail.com', '102030');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`) VALUES
(1, 'Hassan', 'Ahmat Fadoul', 'hassanahmat41@gmail.com', '$2y$10$kRSu1OOEQpNjHUtH7hRFWOgyL9lJBtN/mA3pvB5NmuChsKsPar4Aa'),
(2, 'Idriss', 'Mht', 'idriss@gmail.com', '$2y$10$sxUzXuLftHIevbCXVyC9C.cFSI.sEelcjq7Jvsq7g5gKZDy3yJuSm');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Index pour la table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `document_categories`
--
ALTER TABLE `document_categories`
  ADD PRIMARY KEY (`document_id`,`categorie_id`),
  ADD KEY `categorie_id` (`categorie_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`);

--
-- Contraintes pour la table `document_categories`
--
ALTER TABLE `document_categories`
  ADD CONSTRAINT `document_categories_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`),
  ADD CONSTRAINT `document_categories_ibfk_2` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
