-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 01, 2025 at 11:41 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `base_donne_web`
--

-- --------------------------------------------------------

--
-- Table structure for table `adresse`
--

DROP TABLE IF EXISTS `adresse`;
CREATE TABLE IF NOT EXISTS `adresse` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Adresse` varchar(255) NOT NULL,
  `Ville` varchar(255) NOT NULL,
  `CodePostal` int NOT NULL,
  `InfosComplementaires` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=288 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `adresse`
--

INSERT INTO `adresse` (`ID`, `Adresse`, `Ville`, `CodePostal`, `InfosComplementaires`) VALUES
(233, '30 Avenue Charles de Gaulle', 'Neuilly-sur-Seine', 92200, 'Centre médical, 1er étage avec ascenseur'),
(232, '25 Rue Jean-Pierre Timbaud', 'Paris', 75011, 'Cabinet de groupe, 2ème étage'),
(231, '20 Rue du Maréchal Foch', 'Versailles', 78000, 'Clinique des Yvelines, bâtiment principal'),
(230, '55 Rue de Paris', 'Boulogne-Billancourt', 92100, 'Cabinet équipé de matériel de pointe'),
(229, '45 Rue de Bezons', 'Courbevoie', 92400, 'Bâtiment moderne, accès PMR'),
(228, '12 Rue de la Liberté', 'Vincennes', 94300, 'Cabinet adapté aux enfants, accessible en poussette'),
(227, '30 Avenue Charles de Gaulle', 'Neuilly-sur-Seine', 92200, 'Centre médical, 1er étage avec ascenseur'),
(226, '25 Rue Jean-Pierre Timbaud', 'Paris', 75011, 'Cabinet de groupe, 2ème étage'),
(225, '42 Rue des Acacias', 'Paris', 75017, 'Appartement 5B'),
(224, '10 Rue de Paris', 'Nanterre', 92000, 'Au rez-de-chaussée, entrée sur cour'),
(223, '9 Rue des Vignes', 'Créteil', 94000, 'Près du centre commercial, immeuble moderne'),
(222, '8 Rue de la Victoire', 'Versailles', 78000, 'Centre-ville, porte jaune'),
(221, '7 Boulevard Saint-Michel', 'Paris', 75005, 'Près de l\'université, au 4ème étage'),
(220, '6 Rue du Faubourg Saint-Honoré', 'Paris', 75008, 'Cabinet moderne, 3ème étage'),
(219, '5 Place de la Bastille', 'Paris', 75004, 'Immeuble haussmannien, porte cochère'),
(218, '4 Avenue de la République', 'Saint-Denis', 93200, 'En face de la mairie, au fond de la cour'),
(217, '3 Impasse des Roses', 'Boulogne-Billancourt', 92100, 'Bâtiment B, 2ème étage'),
(216, '22 Avenue des Lilas', 'Paris', 75012, 'RDC droite, accès facile'),
(215, '15 Rue des Fleurs', 'Paris', 75010, 'Cabinet médical au 1er étage, interphone Dr. Dupont'),
(214, '10 Rue de la Paix', 'Paris', 75002, 'Laboratoire central de référence'),
(2, 'Teest', 'test', 23232, 'TEsttt'),
(213, '26 rue d Estienne d Orves', 'Montrouge', 92120, 'Test'),
(234, '12 Rue de la Liberté', 'Vincennes', 94300, 'Cabinet adapté aux enfants, accessible en poussette'),
(235, '45 Rue de Bezons', 'Courbevoie', 92400, 'Bâtiment moderne, accès PMR'),
(236, '55 Rue de Paris', 'Boulogne-Billancourt', 92100, 'Cabinet équipé de matériel de pointe'),
(237, '20 Rue du Maréchal Foch', 'Versailles', 78000, 'Clinique des Yvelines, bâtiment principal'),
(238, '7 Rue de Passy', 'Paris', 75016, 'Cabinet médical moderne, 1er étage'),
(239, '18 Rue Gallieni', 'Boulogne-Billancourt', 92100, 'Centre de santé, 2ème étage'),
(240, '3 Boulevard Jean Monnet', 'Créteil', 94000, 'Hôpital privé, pavillon des consultations'),
(241, '42 Avenue Vladimir Ilitch Lénine', 'Nanterre', 92000, 'Clinique des Acacias, bâtiment principal'),
(242, '14 Rue Jeanne d\'Arc', 'Paris', 75013, 'Service d\'oncologie, rez-de-chaussée'),
(243, '10 Rue de la Légion d\'Honneur', 'Saint-Denis', 93200, 'Hôpital privé, 3ème étage'),
(244, '1 Rue de la Médecine Libre', 'Paris', 75001, 'Siège social virtuel des cabinets indépendants'),
(245, '17 Rue des Entrepreneurs', 'Paris', 75015, 'Cabinet au 3ème étage, code B345'),
(246, '21 Rue du Pont Neuf', 'Puteaux', 92800, 'Centre de consultations, RDC'),
(247, '33 Avenue de Paris', 'Vincennes', 94300, 'Cabinet médical au 1er étage'),
(248, '44 Rue de l\'Hôpital', 'Suresnes', 92150, 'Consultations externes, 4ème étage'),
(249, '50 Rue de la Pompe', 'Paris', 75116, 'Cabinet au sein d\'une clinique privée'),
(250, '66 Rue du Chemin Vert', 'Paris', 75011, 'Cabinet au sein d\'un pôle de santé'),
(251, '77 Rue du 11 Novembre 1918', 'Montrouge', 92120, 'Centre de rééducation, 2ème étage'),
(252, '8 Place Denfert-Rochereau', 'Paris', 75014, 'Cabinet spécialisé enfants, accès facile'),
(253, '92 Boulevard Raspail', 'Paris', 75006, 'Cabinet au RDC, accessible'),
(254, '10 Rue du Colonel Pierre Avia', 'Paris', 75015, 'Centre de soins en addictologie'),
(255, '2 Rue du 4 Septembre', 'Paris', 75002, 'Cabinet de consultations, 5ème étage avec ascenseur'),
(256, '28 Rue du Louvre', 'Paris', 75001, 'Cabinet de spécialistes, RDC'),
(257, '35 Rue de Rivoli', 'Paris', 75004, 'Cabinet au 2ème étage, sur cour'),
(258, '55 Avenue Kléber', 'Paris', 75116, 'Centre de la douleur, 1er étage'),
(259, '77 Boulevard du Montparnasse', 'Paris', 75014, 'Centre d\'imagerie médicale, 2ème étage'),
(260, '80 Rue de la Convention', 'Paris', 75015, 'Clinique des Ternes, service chirurgie'),
(261, '2bis Rue de Sèvres', 'Boulogne-Billancourt', 92100, 'Clinique de la Source, aile chirurgie'),
(262, '12 Rue Vavin', 'Paris', 75006, 'Cabinet au RDC, adapté aux familles'),
(263, '33 Rue du Docteur Roux', 'Paris', 75015, 'Centre de consultations spécialisées'),
(264, '7 Rue de Prony', 'Paris', 75017, 'Cabinet privé de chirurgie esthétique'),
(265, '50 Avenue du Président Wilson', 'Saint-Cloud', 92210, 'Hôpital privé du Val d\'Or, consultations spécialisées'),
(266, '60 Rue Saint-Jacques', 'Paris', 75005, 'Centre de génétique humaine, 3ème étage'),
(267, '5 Rue de la Victoire', 'Clichy', 92110, 'Centre de consultations pédiatriques'),
(268, '18 Rue du Faubourg Saint-Denis', 'Paris', 75010, 'Centre de la douleur, 2ème étage'),
(269, '22 Avenue Victor Hugo', 'Paris', 75116, 'Cabinet de médecine esthétique'),
(270, '4 Place de la Comédie Française', 'Paris', 75001, 'Centre de pédiatrie spécialisée'),
(271, '75 Rue de la Croix Nivert', 'Paris', 75015, 'Cabinet médical, 1er étage'),
(272, '88 Rue de l\'Université', 'Paris', 75007, 'Clinique du Palais, service orthopédie'),
(273, '10 Rue de la Paix', 'Versailles', 78000, 'Cabinet spacieux et accueillant'),
(274, '33 Rue de Maubeuge', 'Paris', 75009, 'Service de santé au travail'),
(275, '99 Rue du Cherche-Midi', 'Paris', 75006, 'Cabinet au RDC, entrée discrète'),
(276, '22 Rue de Vaugirard', 'Paris', 75006, 'Clinique Saint-Luc, service urologie'),
(277, '15 Rue du Coq Français', 'Les Lilas', 93260, 'Face à la pharmacie, RDC'),
(278, '1 Place du Dôme', 'Puteaux', 92800, 'Tour Atlantique, 5ème étage'),
(279, '26 Avenue Aristide Briand', 'Montrouge', 92120, 'Près de la place Jean Jaurès'),
(280, '7 Rue de la Varenne', 'Saint-Maur-des-Fossés', 94100, 'Près du marché, 1er étage'),
(281, '30 Avenue de France', 'Paris', 75013, 'Quartier Olympiades, accessible PMR'),
(282, '1 Avenue de Paris', 'Versailles', 78000, 'Proche du Château, entrée discrète'),
(283, '5 Rue de la République', 'Bobigny', 93000, 'Pôle de santé, 1er étage'),
(284, '2 Boulevard du 11 Novembre 1918', 'Gonesse', 95500, 'Hôpital de Gonesse, annexe'),
(285, '44 Rue de la Fédération', 'Paris', 75015, 'Dédié aux professionnels de la santé animale'),
(286, '25 Rue de la Fontaine au Roi', 'Paris', 75011, 'Près du métro Couronnes'),
(287, '26 rue d Estienne d Orves', 'Montrouge', 92120, 'B1');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
CREATE TABLE IF NOT EXISTS `conversations` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `user1_id` int NOT NULL,
  `user2_id` int NOT NULL,
  `last_message_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `user1_unread_count` int DEFAULT '0',
  `user2_unread_count` int DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `idx_unique_conversation` (`user1_id`,`user2_id`),
  KEY `user2_id` (`user2_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`ID`, `user1_id`, `user2_id`, `last_message_at`, `user1_unread_count`, `user2_unread_count`) VALUES
(1, 1, 73, '2025-05-29 21:49:29', 0, 0),
(2, 1, 72, '2025-05-30 19:37:43', 0, 10),
(3, 61, 63, '2025-05-30 00:03:01', 0, 3),
(4, 63, 73, '2025-05-29 21:54:47', 0, 1),
(5, 61, 105, '2025-05-30 00:03:06', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `cv`
--

DROP TABLE IF EXISTS `cv`;
CREATE TABLE IF NOT EXISTS `cv` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ID_Personnel` int NOT NULL,
  `ContenuXML` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispo`
--

DROP TABLE IF EXISTS `dispo`;
CREATE TABLE IF NOT EXISTS `dispo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Date` date NOT NULL,
  `HeureDebut` time NOT NULL,
  `HeureFin` time NOT NULL,
  `IdPersonnel` int NOT NULL,
  `IdServiceLabo` int NOT NULL,
  `Prix` float NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=363 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dispo`
--

INSERT INTO `dispo` (`ID`, `Date`, `HeureDebut`, `HeureFin`, `IdPersonnel`, `IdServiceLabo`, `Prix`) VALUES
(37, '2025-05-30', '09:00:00', '12:00:00', 66, 9999, 25),
(33, '2025-05-29', '09:00:00', '12:00:00', 65, 9999, 25),
(32, '2025-05-27', '09:00:00', '12:00:00', 65, 9999, 25),
(31, '2025-05-26', '10:00:00', '13:00:00', 65, 9999, 25),
(30, '2025-05-29', '14:00:00', '17:00:00', 64, 9999, 25),
(29, '2025-05-28', '09:00:00', '12:00:00', 64, 9999, 25),
(28, '2025-05-27', '14:00:00', '17:00:00', 64, 9999, 25),
(34, '2025-05-30', '14:00:00', '17:00:00', 65, 9999, 25),
(36, '2025-05-28', '09:00:00', '13:00:00', 66, 9999, 25),
(35, '2025-05-26', '14:00:00', '18:00:00', 66, 9999, 25),
(27, '2025-05-26', '09:00:00', '12:00:00', 64, 9999, 25),
(38, '2025-05-27', '09:00:00', '13:00:00', 67, 9999, 25),
(39, '2025-05-28', '14:00:00', '18:00:00', 67, 9999, 25),
(40, '2025-05-31', '09:00:00', '12:00:00', 67, 9999, 25),
(41, '2025-05-26', '09:00:00', '13:00:00', 68, 9999, 25),
(42, '2025-05-28', '09:00:00', '13:00:00', 68, 9999, 25),
(43, '2025-05-30', '09:00:00', '13:00:00', 68, 9999, 25),
(44, '2025-05-27', '14:00:00', '18:00:00', 69, 9999, 25),
(45, '2025-05-29', '09:00:00', '13:00:00', 69, 9999, 25),
(46, '2025-05-31', '14:00:00', '17:00:00', 69, 9999, 25),
(47, '2025-05-26', '14:00:00', '17:00:00', 70, 9999, 25),
(48, '2025-05-28', '14:00:00', '17:00:00', 70, 9999, 25),
(49, '2025-05-30', '14:00:00', '17:00:00', 70, 9999, 25),
(50, '2025-05-27', '09:00:00', '12:00:00', 71, 9999, 25),
(51, '2025-05-29', '14:00:00', '17:00:00', 71, 9999, 25),
(53, '2025-05-26', '09:00:00', '13:00:00', 72, 9999, 25),
(54, '2025-05-27', '14:00:00', '18:00:00', 72, 9999, 25),
(55, '2025-05-29', '09:00:00', '13:00:00', 72, 9999, 25),
(57, '2025-05-30', '09:00:00', '12:00:00', 73, 9999, 25),
(209, '2025-05-31', '09:00:00', '10:00:00', 73, 9999, 0),
(59, '2025-05-26', '09:00:00', '13:00:00', 75, 9999, 60),
(60, '2025-05-28', '14:00:00', '18:00:00', 75, 9999, 60),
(61, '2025-05-27', '09:30:00', '13:30:00', 76, 9999, 55),
(62, '2025-05-29', '14:00:00', '18:00:00', 76, 9999, 55),
(63, '2025-05-28', '09:00:00', '12:00:00', 77, 9999, 50),
(64, '2025-05-30', '14:00:00', '17:00:00', 77, 9999, 50),
(65, '2025-05-26', '10:00:00', '14:00:00', 78, 9999, 70),
(66, '2025-05-29', '09:00:00', '13:00:00', 78, 9999, 70),
(67, '2025-05-27', '14:00:00', '18:00:00', 79, 9999, 65),
(68, '2025-05-30', '09:00:00', '13:00:00', 79, 9999, 65),
(69, '2025-05-28', '09:30:00', '13:30:00', 80, 9999, 75),
(70, '2025-05-31', '09:00:00', '12:00:00', 80, 9999, 75),
(71, '2025-05-26', '09:00:00', '13:00:00', 81, 9999, 60),
(72, '2025-05-28', '14:00:00', '18:00:00', 81, 9999, 60),
(73, '2025-05-27', '09:30:00', '13:30:00', 82, 9999, 55),
(74, '2025-05-29', '14:00:00', '18:00:00', 82, 9999, 55),
(75, '2025-05-28', '09:00:00', '12:00:00', 83, 9999, 50),
(76, '2025-05-30', '14:00:00', '17:00:00', 83, 9999, 50),
(77, '2025-05-26', '10:00:00', '14:00:00', 84, 9999, 70),
(78, '2025-05-29', '09:00:00', '13:00:00', 84, 9999, 70),
(79, '2025-05-27', '14:00:00', '18:00:00', 85, 9999, 65),
(80, '2025-05-30', '09:00:00', '13:00:00', 85, 9999, 65),
(81, '2025-05-28', '09:30:00', '13:30:00', 86, 9999, 75),
(82, '2025-05-31', '09:00:00', '12:00:00', 86, 9999, 75),
(83, '2025-05-26', '09:00:00', '13:00:00', 87, 9999, 70),
(84, '2025-05-28', '14:00:00', '18:00:00', 87, 9999, 70),
(85, '2025-05-27', '09:30:00', '13:30:00', 88, 9999, 65),
(86, '2025-05-29', '14:00:00', '18:00:00', 88, 9999, 65),
(87, '2025-05-28', '09:00:00', '13:00:00', 89, 9999, 70),
(88, '2025-05-30', '14:00:00', '17:00:00', 89, 9999, 70),
(89, '2025-05-26', '10:00:00', '14:00:00', 90, 9999, 60),
(90, '2025-05-29', '09:00:00', '13:00:00', 90, 9999, 60),
(91, '2025-05-27', '14:00:00', '18:00:00', 91, 9999, 80),
(92, '2025-05-30', '09:00:00', '13:00:00', 91, 9999, 80),
(93, '2025-05-28', '09:30:00', '13:30:00', 92, 9999, 65),
(94, '2025-05-31', '09:00:00', '12:00:00', 92, 9999, 65),
(95, '2025-05-26', '09:00:00', '13:00:00', 93, 9999, 70),
(96, '2025-05-28', '14:00:00', '18:00:00', 93, 9999, 70),
(97, '2025-05-27', '09:30:00', '13:30:00', 94, 9999, 60),
(98, '2025-05-29', '14:00:00', '18:00:00', 94, 9999, 60),
(99, '2025-05-28', '09:00:00', '12:00:00', 95, 9999, 65),
(100, '2025-05-30', '14:00:00', '17:00:00', 95, 9999, 65),
(101, '2025-05-26', '10:00:00', '14:00:00', 96, 9999, 100),
(102, '2025-05-29', '09:00:00', '13:00:00', 96, 9999, 100),
(103, '2025-05-27', '14:00:00', '18:00:00', 97, 9999, 75),
(104, '2025-05-30', '09:00:00', '13:00:00', 97, 9999, 75),
(105, '2025-05-28', '09:30:00', '13:30:00', 98, 9999, 70),
(106, '2025-05-31', '09:00:00', '12:00:00', 98, 9999, 70),
(107, '2025-05-27', '09:00:00', '12:00:00', 99, 9999, 60),
(108, '2025-05-29', '14:00:00', '17:00:00', 99, 9999, 60),
(109, '2025-05-26', '14:00:00', '17:00:00', 100, 9999, 65),
(110, '2025-05-28', '14:00:00', '17:00:00', 100, 9999, 65),
(111, '2025-05-27', '10:00:00', '14:00:00', 101, 9999, 70),
(112, '2025-05-30', '14:00:00', '18:00:00', 101, 9999, 70),
(113, '2025-05-28', '14:00:00', '18:00:00', 102, 9999, 80),
(114, '2025-05-31', '09:00:00', '13:00:00', 102, 9999, 80),
(115, '2025-05-26', '09:00:00', '13:00:00', 103, 9999, 75),
(116, '2025-05-28', '14:00:00', '18:00:00', 103, 9999, 75),
(117, '2025-05-27', '09:30:00', '13:30:00', 104, 9999, 70),
(119, '2025-05-28', '09:00:00', '12:00:00', 105, 9999, 60),
(120, '2025-05-30', '14:00:00', '17:00:00', 105, 9999, 60),
(121, '2025-05-26', '10:00:00', '14:00:00', 106, 9999, 80),
(122, '2025-05-29', '09:00:00', '13:00:00', 106, 9999, 80),
(123, '2025-05-27', '14:00:00', '18:00:00', 107, 9999, 50),
(124, '2025-05-30', '09:00:00', '13:00:00', 107, 9999, 50),
(125, '2025-05-28', '09:30:00', '13:30:00', 108, 9999, 90),
(126, '2025-05-31', '09:00:00', '12:00:00', 108, 9999, 90),
(127, '2025-05-26', '09:00:00', '13:00:00', 109, 9999, 85),
(128, '2025-05-28', '14:00:00', '18:00:00', 109, 9999, 85),
(129, '2025-05-27', '09:30:00', '13:30:00', 110, 9999, 70),
(130, '2025-05-29', '14:00:00', '18:00:00', 110, 9999, 70),
(131, '2025-05-28', '09:00:00', '12:00:00', 111, 9999, 80),
(132, '2025-05-30', '14:00:00', '17:00:00', 111, 9999, 80),
(133, '2025-05-26', '10:00:00', '14:00:00', 112, 9999, 120),
(134, '2025-05-29', '09:00:00', '13:00:00', 112, 9999, 120),
(135, '2025-05-27', '14:00:00', '18:00:00', 113, 9999, 70),
(136, '2025-05-30', '09:00:00', '13:00:00', 113, 9999, 70),
(137, '2025-05-28', '09:30:00', '13:30:00', 114, 9999, 80),
(138, '2025-05-31', '09:00:00', '12:00:00', 114, 9999, 80),
(139, '2025-05-26', '09:00:00', '13:00:00', 200, 9999, 75),
(140, '2025-05-28', '14:00:00', '18:00:00', 200, 9999, 75),
(141, '2025-05-27', '09:30:00', '13:30:00', 201, 9999, 85),
(142, '2025-05-29', '14:00:00', '18:00:00', 201, 9999, 85),
(143, '2025-05-28', '09:00:00', '12:00:00', 202, 9999, 90),
(144, '2025-05-30', '14:00:00', '17:00:00', 202, 9999, 90),
(145, '2025-05-26', '10:00:00', '14:00:00', 203, 9999, 80),
(146, '2025-05-29', '09:00:00', '13:00:00', 203, 9999, 80),
(147, '2025-05-27', '14:00:00', '18:00:00', 204, 9999, 65),
(148, '2025-05-30', '09:00:00', '13:00:00', 204, 9999, 65),
(149, '2025-05-28', '09:30:00', '13:30:00', 205, 9999, 90),
(150, '2025-05-31', '09:00:00', '12:00:00', 205, 9999, 90),
(151, '2025-05-27', '09:00:00', '12:00:00', 206, 9999, 90),
(152, '2025-05-29', '14:00:00', '17:00:00', 206, 9999, 90),
(153, '2025-05-26', '14:00:00', '17:00:00', 207, 9999, 70),
(154, '2025-05-28', '14:00:00', '17:00:00', 207, 9999, 70),
(155, '2025-05-27', '10:00:00', '14:00:00', 208, 9999, 100),
(156, '2025-05-30', '14:00:00', '18:00:00', 208, 9999, 100),
(157, '2025-05-28', '14:00:00', '18:00:00', 209, 9999, 95),
(158, '2025-05-31', '09:00:00', '13:00:00', 209, 9999, 95),
(159, '2025-05-26', '08:00:00', '09:00:00', 0, 2000, 10),
(160, '2025-05-26', '09:00:00', '10:00:00', 0, 2001, 8),
(161, '2025-05-27', '10:00:00', '11:00:00', 0, 2000, 10),
(162, '2025-05-27', '11:00:00', '12:00:00', 0, 2002, 30),
(163, '2025-05-28', '14:00:00', '15:00:00', 0, 2001, 8),
(164, '2025-05-26', '14:30:00', '16:00:00', 0, 2003, 120),
(165, '2025-05-27', '09:00:00', '10:30:00', 0, 2004, 90),
(166, '2025-05-28', '10:00:00', '11:00:00', 0, 2005, 30),
(167, '2025-05-29', '15:00:00', '16:30:00', 0, 2003, 120),
(168, '2025-05-30', '09:30:00', '11:00:00', 0, 2004, 90),
(169, '2025-05-27', '08:30:00', '09:30:00', 0, 2006, 250),
(170, '2025-05-27', '09:30:00', '10:30:00', 0, 2007, 75),
(172, '2025-05-29', '09:00:00', '10:00:00', 0, 2009, 80),
(173, '2025-05-30', '10:00:00', '11:00:00', 0, 2006, 250),
(174, '2025-05-26', '10:00:00', '11:00:00', 0, 2010, 60),
(175, '2025-05-27', '14:00:00', '15:00:00', 0, 2011, 50),
(176, '2025-05-28', '09:00:00', '10:00:00', 0, 2012, 80),
(177, '2025-05-29', '11:00:00', '12:00:00', 0, 2010, 60),
(178, '2025-05-30', '15:00:00', '16:00:00', 0, 2011, 50),
(179, '2025-05-26', '13:00:00', '14:00:00', 0, 2013, 25),
(180, '2025-05-27', '10:00:00', '11:00:00', 0, 2014, 35),
(181, '2025-05-28', '11:00:00', '12:00:00', 0, 2015, 40),
(182, '2025-05-29', '14:00:00', '15:00:00', 0, 2013, 25),
(183, '2025-05-30', '10:00:00', '11:00:00', 0, 2014, 35),
(184, '2025-05-26', '09:00:00', '10:00:00', 0, 2016, 60),
(185, '2025-05-27', '14:00:00', '15:00:00', 0, 2017, 45),
(186, '2025-05-28', '10:30:00', '11:00:00', 0, 2018, 20),
(187, '2025-05-29', '09:00:00', '10:00:00', 0, 2016, 60),
(188, '2025-05-30', '14:00:00', '15:00:00', 0, 2017, 45),
(189, '2025-05-27', '09:00:00', '10:30:00', 0, 2019, 300),
(190, '2025-05-28', '14:00:00', '15:30:00', 0, 2020, 400),
(191, '2025-05-29', '10:00:00', '11:30:00', 0, 2019, 300),
(192, '2025-05-30', '09:00:00', '10:30:00', 0, 2020, 400),
(193, '2025-05-26', '10:00:00', '11:30:00', 0, 2021, 450),
(194, '2025-05-27', '14:00:00', '15:00:00', 0, 2022, 180),
(195, '2025-05-28', '11:00:00', '12:00:00', 0, 2023, 150),
(196, '2025-05-29', '10:00:00', '11:30:00', 0, 2021, 450),
(197, '2025-05-30', '14:00:00', '15:00:00', 0, 2022, 180),
(198, '2025-05-26', '09:00:00', '10:00:00', 0, 2024, 40),
(199, '2025-05-27', '13:00:00', '14:00:00', 0, 2025, 30),
(200, '2025-05-28', '10:00:00', '11:00:00', 0, 2026, 50),
(201, '2025-05-29', '09:00:00', '10:00:00', 0, 2024, 40),
(202, '2025-05-30', '13:00:00', '14:00:00', 0, 2025, 30),
(203, '2025-05-27', '09:00:00', '10:00:00', 0, 2027, 150),
(204, '2025-05-28', '14:00:00', '15:00:00', 0, 2028, 80),
(205, '2025-05-29', '10:00:00', '11:00:00', 0, 2029, 100),
(206, '2025-05-30', '09:00:00', '10:00:00', 0, 2027, 150),
(207, '2025-05-31', '14:00:00', '15:00:00', 0, 2028, 80),
(210, '2025-06-02', '09:00:00', '09:30:00', 64, 9999, 25),
(211, '2025-06-03', '14:00:00', '14:30:00', 64, 9999, 25),
(212, '2025-06-04', '09:30:00', '10:00:00', 64, 9999, 25),
(213, '2025-06-05', '14:30:00', '15:00:00', 64, 9999, 25),
(214, '2025-06-06', '10:00:00', '10:30:00', 64, 9999, 25),
(215, '2025-06-02', '09:30:00', '10:00:00', 65, 9999, 25),
(216, '2025-06-03', '14:30:00', '15:00:00', 65, 9999, 25),
(217, '2025-06-04', '10:00:00', '10:30:00', 65, 9999, 25),
(218, '2025-06-05', '15:00:00', '15:30:00', 65, 9999, 25),
(219, '2025-06-06', '10:30:00', '11:00:00', 65, 9999, 25),
(220, '2025-06-02', '10:00:00', '10:30:00', 66, 9999, 25),
(221, '2025-06-03', '15:00:00', '15:30:00', 66, 9999, 25),
(222, '2025-06-04', '10:30:00', '11:00:00', 66, 9999, 25),
(223, '2025-06-05', '15:30:00', '16:00:00', 66, 9999, 25),
(224, '2025-06-06', '11:00:00', '11:30:00', 66, 9999, 25),
(225, '2025-06-02', '10:30:00', '11:00:00', 67, 9999, 25),
(226, '2025-06-03', '15:30:00', '16:00:00', 67, 9999, 25),
(227, '2025-06-04', '11:00:00', '11:30:00', 67, 9999, 25),
(228, '2025-06-05', '16:00:00', '16:30:00', 67, 9999, 25),
(229, '2025-06-06', '11:30:00', '12:00:00', 67, 9999, 25),
(230, '2025-06-02', '11:00:00', '11:30:00', 68, 9999, 25),
(231, '2025-06-03', '16:00:00', '16:30:00', 68, 9999, 25),
(232, '2025-06-04', '11:30:00', '12:00:00', 68, 9999, 25),
(233, '2025-06-05', '16:30:00', '17:00:00', 68, 9999, 25),
(234, '2025-06-06', '14:00:00', '14:30:00', 68, 9999, 25),
(235, '2025-06-02', '11:30:00', '12:00:00', 69, 9999, 25),
(236, '2025-06-03', '16:30:00', '17:00:00', 69, 9999, 25),
(237, '2025-06-04', '14:00:00', '14:30:00', 69, 9999, 25),
(238, '2025-06-05', '09:00:00', '09:30:00', 69, 9999, 25),
(239, '2025-06-06', '14:30:00', '15:00:00', 69, 9999, 25),
(240, '2025-06-02', '14:00:00', '14:30:00', 70, 9999, 25),
(241, '2025-06-03', '09:00:00', '09:30:00', 70, 9999, 25),
(242, '2025-06-04', '14:30:00', '15:00:00', 70, 9999, 25),
(243, '2025-06-05', '09:30:00', '10:00:00', 70, 9999, 25),
(244, '2025-06-06', '15:00:00', '15:30:00', 70, 9999, 25),
(245, '2025-06-02', '14:30:00', '15:00:00', 71, 9999, 25),
(246, '2025-06-03', '09:30:00', '10:00:00', 71, 9999, 25),
(247, '2025-06-04', '15:00:00', '15:30:00', 71, 9999, 25),
(248, '2025-06-05', '10:00:00', '10:30:00', 71, 9999, 25),
(249, '2025-06-06', '15:30:00', '16:00:00', 71, 9999, 25),
(250, '2025-06-02', '15:00:00', '15:30:00', 72, 9999, 25),
(251, '2025-06-03', '10:00:00', '10:30:00', 72, 9999, 25),
(252, '2025-06-04', '15:30:00', '16:00:00', 72, 9999, 25),
(253, '2025-06-05', '10:30:00', '11:00:00', 72, 9999, 25),
(254, '2025-06-06', '16:00:00', '16:30:00', 72, 9999, 25),
(255, '2025-06-02', '15:30:00', '16:00:00', 73, 9999, 25),
(256, '2025-06-03', '10:30:00', '11:00:00', 73, 9999, 25),
(257, '2025-06-04', '16:00:00', '16:30:00', 73, 9999, 25),
(258, '2025-06-05', '11:00:00', '11:30:00', 73, 9999, 25),
(259, '2025-06-06', '16:30:00', '17:00:00', 73, 9999, 25),
(260, '2025-06-02', '09:00:00', '09:30:00', 85, 9999, 65),
(261, '2025-06-04', '14:00:00', '14:30:00', 85, 9999, 65),
(262, '2025-06-06', '10:00:00', '10:30:00', 85, 9999, 65),
(263, '2025-06-02', '10:00:00', '10:30:00', 86, 9999, 75),
(264, '2025-06-03', '15:00:00', '15:30:00', 86, 9999, 75),
(265, '2025-06-05', '09:30:00', '10:00:00', 86, 9999, 75),
(266, '2025-06-02', '11:00:00', '11:30:00', 87, 9999, 70),
(267, '2025-06-04', '16:00:00', '16:30:00', 87, 9999, 70),
(268, '2025-06-06', '11:00:00', '11:30:00', 87, 9999, 70),
(269, '2025-06-03', '09:00:00', '09:30:00', 88, 9999, 65),
(270, '2025-06-05', '14:00:00', '14:30:00', 88, 9999, 65),
(271, '2025-06-06', '14:00:00', '14:30:00', 88, 9999, 65),
(272, '2025-06-02', '14:30:00', '15:00:00', 89, 9999, 70),
(273, '2025-06-04', '10:30:00', '11:00:00', 89, 9999, 70),
(274, '2025-06-05', '15:30:00', '16:00:00', 89, 9999, 70),
(275, '2025-06-03', '10:00:00', '10:30:00', 90, 9999, 60),
(276, '2025-06-05', '11:00:00', '11:30:00', 90, 9999, 60),
(277, '2025-06-06', '09:00:00', '09:30:00', 90, 9999, 60),
(278, '2025-06-02', '09:30:00', '10:00:00', 91, 9999, 80),
(279, '2025-06-03', '14:30:00', '15:00:00', 91, 9999, 80),
(280, '2025-06-04', '09:00:00', '09:30:00', 91, 9999, 80),
(281, '2025-06-05', '16:30:00', '17:00:00', 91, 9999, 80),
(282, '2025-06-02', '15:30:00', '16:00:00', 92, 9999, 65),
(283, '2025-06-04', '11:30:00', '12:00:00', 92, 9999, 65),
(284, '2025-06-06', '15:00:00', '15:30:00', 92, 9999, 65),
(285, '2025-06-03', '11:00:00', '11:30:00', 93, 9999, 70),
(286, '2025-06-04', '15:00:00', '15:30:00', 93, 9999, 70),
(287, '2025-06-05', '10:30:00', '11:00:00', 93, 9999, 70),
(288, '2025-06-06', '10:30:00', '11:00:00', 93, 9999, 70),
(289, '2025-06-02', '16:30:00', '17:00:00', 94, 9999, 60),
(290, '2025-06-03', '16:30:00', '17:00:00', 94, 9999, 60),
(291, '2025-06-05', '12:00:00', '12:30:00', 94, 9999, 60),
(292, '2025-06-02', '10:30:00', '11:00:00', 95, 9999, 65),
(293, '2025-06-03', '09:30:00', '10:00:00', 95, 9999, 65),
(294, '2025-06-04', '14:30:00', '15:00:00', 95, 9999, 65),
(295, '2025-06-06', '16:00:00', '16:30:00', 95, 9999, 65),
(296, '2025-06-03', '14:00:00', '14:30:00', 96, 9999, 100),
(297, '2025-06-05', '09:00:00', '09:30:00', 96, 9999, 100),
(298, '2025-06-06', '11:30:00', '12:00:00', 96, 9999, 100),
(299, '2025-06-02', '11:30:00', '12:00:00', 97, 9999, 75),
(300, '2025-06-04', '10:00:00', '10:30:00', 97, 9999, 75),
(301, '2025-06-05', '15:00:00', '15:30:00', 97, 9999, 75),
(302, '2025-06-06', '09:30:00', '10:00:00', 97, 9999, 75),
(303, '2025-06-02', '16:00:00', '16:30:00', 98, 9999, 70),
(304, '2025-06-04', '09:30:00', '10:00:00', 98, 9999, 70),
(305, '2025-06-05', '14:30:00', '15:00:00', 98, 9999, 70),
(306, '2025-06-03', '10:30:00', '11:00:00', 99, 9999, 60),
(307, '2025-06-04', '15:30:00', '16:00:00', 99, 9999, 60),
(308, '2025-06-05', '11:30:00', '12:00:00', 99, 9999, 60),
(309, '2025-06-06', '14:30:00', '15:00:00', 99, 9999, 60),
(310, '2025-06-02', '09:00:00', '10:30:00', 0, 2021, 450),
(311, '2025-06-04', '14:00:00', '15:30:00', 0, 2021, 450),
(312, '2025-06-03', '10:00:00', '11:30:00', 0, 2019, 300),
(313, '2025-06-05', '09:00:00', '10:30:00', 0, 2019, 300),
(314, '2025-06-02', '11:00:00', '12:00:00', 0, 2006, 250),
(315, '2025-06-06', '09:00:00', '10:00:00', 0, 2006, 250),
(316, '2025-06-03', '09:00:00', '10:00:00', 0, 2027, 150),
(317, '2025-06-05', '14:00:00', '15:00:00', 0, 2027, 150),
(318, '2025-06-03', '14:00:00', '15:00:00', 0, 2003, 120),
(319, '2025-06-05', '11:00:00', '12:00:00', 0, 2003, 120),
(320, '2025-06-06', '14:00:00', '15:00:00', 0, 2003, 120),
(321, '2025-06-02', '14:00:00', '15:00:00', 0, 2004, 90),
(322, '2025-06-04', '09:00:00', '10:00:00', 0, 2004, 90),
(323, '2025-06-06', '10:30:00', '11:30:00', 0, 2004, 90),
(324, '2025-06-02', '15:30:00', '16:00:00', 0, 2010, 60),
(325, '2025-06-03', '15:30:00', '16:00:00', 0, 2010, 60),
(326, '2025-06-04', '10:30:00', '11:00:00', 0, 2010, 60),
(327, '2025-06-05', '15:30:00', '16:00:00', 0, 2010, 60),
(328, '2025-06-02', '16:00:00', '16:30:00', 0, 2016, 60),
(329, '2025-06-03', '16:00:00', '16:30:00', 0, 2016, 60),
(330, '2025-06-04', '11:00:00', '11:30:00', 0, 2016, 60),
(331, '2025-06-05', '16:00:00', '16:30:00', 0, 2016, 60),
(332, '2025-06-02', '09:00:00', '09:30:00', 0, 2005, 30),
(333, '2025-06-03', '09:00:00', '09:30:00', 0, 2005, 30),
(334, '2025-06-04', '11:30:00', '12:00:00', 0, 2005, 30),
(335, '2025-06-05', '09:00:00', '09:30:00', 0, 2005, 30),
(336, '2025-06-06', '12:00:00', '12:30:00', 0, 2005, 30),
(337, '2025-06-02', '09:30:00', '10:00:00', 0, 2002, 30),
(338, '2025-06-03', '09:30:00', '10:00:00', 0, 2002, 30),
(339, '2025-06-04', '14:00:00', '14:30:00', 0, 2002, 30),
(340, '2025-06-05', '09:30:00', '10:00:00', 0, 2002, 30),
(341, '2025-06-06', '12:30:00', '13:00:00', 0, 2002, 30),
(342, '2025-06-02', '10:00:00', '10:30:00', 0, 2000, 10),
(343, '2025-06-03', '10:00:00', '10:30:00', 0, 2000, 10),
(344, '2025-06-04', '14:30:00', '15:00:00', 0, 2000, 10),
(345, '2025-06-05', '10:00:00', '10:30:00', 0, 2000, 10),
(346, '2025-06-06', '15:00:00', '15:30:00', 0, 2000, 10),
(347, '2025-06-02', '10:30:00', '11:00:00', 0, 2001, 8),
(348, '2025-06-03', '10:30:00', '11:00:00', 0, 2001, 8),
(349, '2025-06-04', '15:00:00', '15:30:00', 0, 2001, 8),
(350, '2025-06-05', '10:30:00', '11:00:00', 0, 2001, 8),
(351, '2025-06-06', '15:30:00', '16:00:00', 0, 2001, 8),
(352, '2025-06-02', '11:00:00', '11:30:00', 0, 2013, 25),
(353, '2025-06-03', '11:30:00', '12:00:00', 0, 2013, 25),
(354, '2025-06-04', '15:30:00', '16:00:00', 0, 2013, 25),
(355, '2025-06-05', '11:30:00', '12:00:00', 0, 2013, 25),
(356, '2025-06-06', '16:00:00', '16:30:00', 0, 2013, 25),
(357, '2025-06-02', '12:00:00', '12:30:00', 0, 2024, 40),
(358, '2025-06-03', '12:00:00', '12:30:00', 0, 2024, 40),
(359, '2025-06-04', '16:00:00', '16:30:00', 0, 2024, 40),
(360, '2025-06-05', '12:00:00', '12:30:00', 0, 2024, 40),
(361, '2025-06-06', '09:30:00', '10:00:00', 0, 2024, 40),
(362, '2025-06-06', '10:00:00', '10:30:00', 0, 2024, 40);

-- --------------------------------------------------------

--
-- Table structure for table `id_paiement`
--

DROP TABLE IF EXISTS `id_paiement`;
CREATE TABLE IF NOT EXISTS `id_paiement` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TypeCarte` varchar(255) NOT NULL,
  `NumeroCarte` bigint NOT NULL,
  `NomCarte` varchar(255) NOT NULL,
  `DateExpiration` date NOT NULL,
  `CCV` int NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `id_paiement`
--

INSERT INTO `id_paiement` (`ID`, `TypeCarte`, `NumeroCarte`, `NomCarte`, `DateExpiration`, `CCV`) VALUES
(18, 'Mastercard', 874392834234234, 'sdfdg', '2026-03-01', 3323),
(17, 'Mastercard', 9999999999999999, 'kgoigu', '2027-05-01', 999),
(16, 'Mastercard', 12312312312313, '3123', '2028-02-01', 444),
(15, 'American Express', 43433434343433434, 'Ethfd', '2033-03-01', 433),
(14, 'Visa', 1111111111111111, 'sdfsdf', '2030-02-01', 432),
(13, 'Visa', 1234567890123456, 'MARC DUBOIS', '2028-12-31', 123);

-- --------------------------------------------------------

--
-- Table structure for table `laboratoire`
--

DROP TABLE IF EXISTS `laboratoire`;
CREATE TABLE IF NOT EXISTS `laboratoire` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Telephone` bigint NOT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ID_Adresse` int NOT NULL,
  `Photos` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `laboratoire`
--

INSERT INTO `laboratoire` (`ID`, `Nom`, `Telephone`, `Email`, `Description`, `ID_Adresse`, `Photos`) VALUES
(103, 'Centre d\'Échographie et Doppler Saint-Maur', 1432109876, 'contact@echosaintmaur.fr', 'Spécialisé en échographies et examens Doppler, incluant échographies abdominales, thyroïdiennes et vasculaires.', 280, 'labo4.png'),
(102, 'Laboratoire de Biologie Spécialisée Montrouge', 1463578901, 'labo.specialise.m@email.com', 'Axé sur la génétique, l\'immunologie et les tests hormonaux avancés. Réalise également des analyses de fertilité.', 279, 'labo3.png'),
(101, 'Imagerie Médicale de la Défense', 1478523690, 'accueil@imageriedefense.fr', 'Centre d\'imagerie de pointe, offrant IRM, Scanner et radiographies pour un diagnostic précis.', 278, 'labo2.png'),
(100, 'Laboratoire d\'Analyses Médicales Les Lilas', 1489765432, 'contact@labolilas.fr', 'Votre partenaire de santé pour des analyses biologiques fiables et rapides. Nous proposons des prélèvements sanguins, analyses urinaires et tests de dépistage courants.', 277, 'labo1.png'),
(104, 'Laboratoire de Microbiologie Paris 13', 1456789012, 'microbioparis13@email.com', 'Expertise en bactériologie, mycologie et virologie. Tests d\'identification de germes et de sensibilité aux antibiotiques.', 281, 'labo5.png'),
(105, 'Centre d\'Analyses Toxicologiques Versailles', 1300098765, 'contact@toxicologie78.fr', 'Spécialisé dans les analyses toxicologiques et les tests de dépistage de drogues et de médicaments.', 282, 'labo6.png'),
(106, 'Laboratoire de Cytogénétique et Biologie Moléculaire Bobigny', 1412345678, 'labo.cytomol@email.com', 'Recherche de maladies chromosomiques et génétiques par analyse cytogénétique et biologie moléculaire.', 283, 'labo7.png'),
(107, 'Centre de Médecine Nucléaire et TEP-Scanner Gonesse', 1345678901, 'medecinenucleaire95@email.com', 'Explorations fonctionnelles par imagerie nucléaire, incluant TEP-Scanner pour l\'oncologie et autres spécialités.', 284, 'labo8.png'),
(108, 'Laboratoire d\'Analyses Vétérinaires Paris Ouest', 1478901234, 'contact@laboveto.fr', 'Laboratoire spécialisé dans les analyses pour animaux de compagnie et de rente : hématologie, biochimie, parasitologie.', 285, 'labo9.png'),
(109, 'Institut de Pathologie et Cytologie Paris Est', 1498765432, 'pathoparisest@email.com', 'Analyse histologique et cytologique de biopsies et prélèvements pour le diagnostic des maladies, notamment cancers.', 286, 'labo10.png');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`ID`, `conversation_id`, `sender_id`, `message_text`, `sent_at`) VALUES
(1, 1, 1, 'Hello !', '2025-05-29 21:35:36'),
(2, 1, 1, 'Hello !', '2025-05-29 21:35:39'),
(3, 1, 1, 'Hello !', '2025-05-29 21:35:58'),
(4, 1, 1, 'lol', '2025-05-29 21:38:46'),
(5, 1, 1, 'lol', '2025-05-29 21:39:20'),
(6, 1, 1, 'Oui', '2025-05-29 21:39:35'),
(7, 1, 1, 'Non', '2025-05-29 21:39:41'),
(8, 1, 1, 'dfsdf', '2025-05-29 21:40:07'),
(9, 1, 1, 'Test1212', '2025-05-29 21:42:31'),
(10, 2, 1, 'Test', '2025-05-29 21:43:55'),
(11, 2, 1, 'Tersd', '2025-05-29 21:44:44'),
(12, 2, 1, 'qsdqs', '2025-05-29 21:46:42'),
(13, 2, 1, 'qsdqsd', '2025-05-29 21:47:28'),
(14, 2, 1, 'qsdqsd', '2025-05-29 21:47:30'),
(15, 2, 1, 'qsdqsd', '2025-05-29 21:47:40'),
(16, 2, 1, 'qsdqsd', '2025-05-29 21:47:52'),
(17, 1, 1, 'sqdqsd', '2025-05-29 21:47:57'),
(18, 1, 1, 'sqdqsd', '2025-05-29 21:48:03'),
(19, 1, 1, 'sqdqsd', '2025-05-29 21:48:10'),
(20, 1, 1, 'Oui', '2025-05-29 21:48:17'),
(21, 1, 73, 'Mais nan', '2025-05-29 21:49:29'),
(22, 3, 61, 'Bonjours', '2025-05-29 21:50:35'),
(23, 4, 63, 'test', '2025-05-29 21:54:47'),
(24, 5, 61, 'qsd', '2025-05-30 00:02:10'),
(25, 3, 61, 'qsdqsd', '2025-05-30 00:02:27'),
(26, 3, 61, 'qsdqsd', '2025-05-30 00:02:34'),
(27, 3, 61, 'qsdqsd', '2025-05-30 00:03:01'),
(28, 5, 61, 'dqsdq', '2025-05-30 00:03:06'),
(29, 2, 1, 'dqsdqs', '2025-05-30 19:37:27'),
(30, 2, 1, 'aaaaaaaa', '2025-05-30 19:37:33'),
(31, 2, 1, 'aaaaaaaa', '2025-05-30 19:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `rdv`
--

DROP TABLE IF EXISTS `rdv`;
CREATE TABLE IF NOT EXISTS `rdv` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `DateRDV` date NOT NULL,
  `HeureDebut` time NOT NULL,
  `HeureFin` time NOT NULL,
  `Statut` varchar(255) NOT NULL,
  `InfoComplementaire` varchar(255) NOT NULL,
  `ID_Client` int NOT NULL,
  `ID_Personnel` int NOT NULL,
  `ID_ServiceLabo` int NOT NULL,
  `ID_Paiement` int NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rdv`
--

INSERT INTO `rdv` (`ID`, `DateRDV`, `HeureDebut`, `HeureFin`, `Statut`, `InfoComplementaire`, `ID_Client`, `ID_Personnel`, `ID_ServiceLabo`, `ID_Paiement`) VALUES
(13, '2025-05-29', '15:00:00', '15:30:00', 'Confirmé', 'Bilan de santé enfant (8 ans)', 74, 71, 1001, 13),
(12, '2025-05-28', '11:00:00', '11:30:00', 'Annulé', 'Douleur au dos persistante', 74, 66, 1001, 13),
(11, '2025-05-27', '10:15:00', '10:45:00', 'En attente', 'Vaccin grippe saisonnière', 74, 65, 1001, 13),
(10, '2025-05-26', '09:30:00', '10:00:00', 'Confirmé', 'Contrôle annuel, renouvellement ordonnance', 74, 64, 1001, 13),
(14, '2025-05-29', '10:00:00', '10:30:00', 'Confirmé', 'Suivi tension artérielle', 74, 72, 1001, 13),
(15, '2025-05-28', '13:00:00', '14:00:00', 'A venir', 'RDV Médecin: Dr. Martin Arthur (Rhumatologue Interventionnel)', 63, 201, 2008, 14),
(16, '2025-05-31', '09:00:00', '12:00:00', 'A venir', 'RDV Médecin: Dr. Girard Manon', 63, 73, 9999, 15),
(17, '2025-05-28', '09:00:00', '12:00:00', 'A venir', 'RDV Médecin: Dr. Girard Manon', 64, 73, 9999, 16),
(18, '2025-05-29', '14:00:00', '18:00:00', 'A venir', 'RDV Médecin: Dr. Mercier Laura (Immunologue Clinique)', 61, 104, 9999, 17),
(19, '2025-05-30', '09:00:00', '12:00:00', 'A venir', 'RDV Médecin: Dr. Moreau Clara', 61, 71, 9999, 18);

-- --------------------------------------------------------

--
-- Table structure for table `service_labo`
--

DROP TABLE IF EXISTS `service_labo`;
CREATE TABLE IF NOT EXISTS `service_labo` (
  `ID` int NOT NULL,
  `NomService` varchar(255) DEFAULT NULL,
  `Prix` float NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `ID_Laboratoire` int NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `service_labo`
--

INSERT INTO `service_labo` (`ID`, `NomService`, `Prix`, `Description`, `ID_Laboratoire`) VALUES
(2020, 'Recherche de microdélétions', 400, 'Tests génétiques avancés pour des syndromes spécifiques.', 106),
(2018, 'Alcoolemie', 20, 'Mesure du taux d\'alcool dans le sang.', 105),
(2019, 'Caryotype Constitutionnel', 300, 'Analyse des chromosomes pour détecter des anomalies.', 106),
(2016, 'Dépistage de Drogues', 60, 'Analyse urinaire ou sanguine pour stupéfiants.', 105),
(2017, 'Dosage Médicamenteux', 45, 'Mesure des concentrations de médicaments dans le sang.', 105),
(2014, 'Prélèvement Mycologique', 35, 'Recherche de champignons et levures.', 104),
(2015, 'Antibiogramme', 40, 'Test de sensibilité aux antibiotiques.', 104),
(2012, 'Écho-Doppler Vasculaire', 80, 'Analyse des flux sanguins dans les artères et les veines.', 103),
(2013, 'Coproculture', 25, 'Analyse des selles pour détection bactérienne.', 104),
(2010, 'Échographie Abdominale', 60, 'Examen par ultrasons des organes abdominaux.', 103),
(2011, 'Échographie Thyroïdienne', 50, 'Évaluation de la glande thyroïde.', 103),
(2008, 'Analyses de Fertilité', 150, 'Tests approfondis pour les couples rencontrant des difficultés à concevoir.', 102),
(2009, 'Bilan Immunologique', 80, 'Évaluation du système immunitaire et des maladies auto-immunes.', 102),
(2006, 'Tests Génétiques', 250, 'Analyses ADN pour maladies génétiques ou prédispositions.', 102),
(2007, 'Bilan Hormonal Complet', 75, 'Évaluation des niveaux hormonaux masculins et féminins.', 102),
(2004, 'Scanner (Tomodensitométrie)', 90, 'Examen aux rayons X pour des images en coupe.', 101),
(2005, 'Radiographie standard', 30, 'Clichés radiographiques pour le diagnostic initial.', 101),
(2000, 'Prise de sang', 10, 'Prélèvement sanguin pour analyses diverses.', 100),
(2001, 'Analyses urinaires', 8, 'Tests pour infections urinaires et autres pathologies.', 100),
(2002, 'Tests COVID-19 (PCR/Antigénique)', 30, 'Dépistage rapide et fiable du COVID-19.', 100),
(2003, 'IRM (Imagerie par Résonance Magnétique)', 120, 'Examen d\'imagerie détaillé des tissus mous.', 101),
(2021, 'TEP-Scanner', 450, 'Imagerie métabolique pour le diagnostic oncologique et neurologique.', 107),
(2022, 'Scintigraphie Osseuse', 180, 'Examen pour détecter des anomalies osseuses.', 107),
(2023, 'Scintigraphie Thyroïdienne', 150, 'Évaluation de la fonction thyroïdienne.', 107),
(2024, 'Bilan Sanguin Vétérinaire', 40, 'Hématologie et biochimie pour animaux.', 108),
(2025, 'Analyse Coprologique Vétérinaire', 30, 'Recherche de parasites intestinaux chez les animaux.', 108),
(2026, 'Tests de Dépistage (Lien, FIV)', 50, 'Dépistage de maladies spécifiques aux animaux (ex: Leucose Féline, FIV).', 108),
(2027, 'Examen Histopathologique (Biopsie)', 150, 'Analyse microscopique de tissus.', 109),
(2028, 'Examen Cytologique (Frotti)', 80, 'Analyse de cellules isolées pour dépistage (ex: frottis cervico-utérin).', 109),
(2029, 'Immunohistochimie', 100, 'Tests complémentaires pour caractériser les cellules et les tissus.', 109);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Nom` varchar(255) DEFAULT NULL,
  `Prenom` varchar(255) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `MotDePasse` varchar(255) DEFAULT NULL,
  `TypeCompte` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`ID`, `Nom`, `Prenom`, `Email`, `MotDePasse`, `TypeCompte`) VALUES
(99, 'Bernard', 'Cécile', 'cecile.bernard.mpr@sante.fr', 'mprcecile', 'Personnel'),
(98, 'Martin', 'David', 'david.martin.gastrohepato@sante.fr', 'davidgastro', 'Personnel'),
(97, 'Petit', 'Marion', 'marion.petit.anesth@sante.fr', 'anesthesiemarion', 'Personnel'),
(96, 'Dubois', 'Antoine', 'antoine.dubois.neurochir@sante.fr', 'neurochirantoine', 'Personnel'),
(95, 'Leroy', 'Sophie', 'sophie.leroy.pneumoallerg@sante.fr', 'sophieallerg', 'Personnel'),
(94, 'Garcia', 'Mathieu', 'mathieu.garcia.geriatre@sante.fr', 'geriatreg', 'Personnel'),
(93, 'Fournier', 'Élodie', 'elodie.fournier.endo@sante.fr', 'endofournier', 'Personnel'),
(92, 'Dubois', 'Vincent', 'vincent.dubois.nephro@sante.fr', 'vincentnephro', 'Personnel'),
(91, 'Roussel', 'Clara', 'clara.roussel.onco@sante.fr', 'clarapass', 'Personnel'),
(90, 'Leroy', 'Julien', 'julien.leroy.orl@sante.fr', 'orljulien', 'Personnel'),
(89, 'Moreau', 'Sarah', 'sarah.moreau.uro@sante.fr', 'sarahuro23', 'Personnel'),
(88, 'Lambert', 'Pierre', 'pierre.lambert.rhumato@sante.fr', 'pierrerhumato', 'Personnel'),
(87, 'Gauthier', 'Anna', 'anna.gauthier.gyno@sante.fr', 'annagyno21', 'Personnel'),
(86, 'Leroy', 'Julien', 'julien.leroy.ortho@sante.fr', 'orthojulien', 'Personnel'),
(85, 'Durand', 'Chloé', 'chloe.durand.ophtalmo@sante.fr', 'ophtalmochloe', 'Personnel'),
(84, 'Roux', 'David', 'david.roux.neuro@sante.fr', 'neuroroux', 'Personnel'),
(83, 'Garcia', 'Laura', 'laura.garcia.pediatre@sante.fr', 'pediatrelaura', 'Personnel'),
(82, 'Lefevre', 'Marc', 'marc.lefevre.dermato@sante.fr', 'dermatomarc', 'Personnel'),
(81, 'Dubois', 'Émilie', 'emilie.dubois.cardio@sante.fr', 'cardioemilie', 'Personnel'),
(80, 'Leroy', 'Julien', 'julien.leroy.ortho@sante.fr', 'orthojulien', 'Personnel'),
(79, 'Durand', 'Chloé', 'chloe.durand.ophtalmo@sante.fr', 'ophtalmochloe', 'Personnel'),
(78, 'Roux', 'David', 'david.roux.neuro@sante.fr', 'neuroroux', 'Personnel'),
(77, 'Garcia', 'Laura', 'laura.garcia.pediatre@sante.fr', 'pediatrelaura', 'Personnel'),
(76, 'Lefevre', 'Marc', 'marc.lefevre.dermato@sante.fr', 'dermatomarc', 'Personnel'),
(75, 'Dubois', 'Émilie', 'emilie.dubois.cardio@sante.fr', 'cardioemilie', 'Personnel'),
(74, 'Dubois', 'Marc', 'marc.dubois@email.com', 'clientpass', 'client'),
(73, 'Girard', 'Manon', 'manon.girard@sante.fr', 'manondoc10', 'Personnel'),
(72, 'Fournier', 'Antoine', 'antoine.fournier@sante.fr', 'antomed9', 'Personnel'),
(71, 'Moreau', 'Clara', 'clara.moreau@sante.fr', 'claramed8', 'Personnel'),
(70, 'Roux', 'Nicolas', 'nicolas.roux@sante.fr', 'mednicolas7', 'Personnel'),
(69, 'Leroy', 'Alice', 'alice.leroy@sante.fr', 'alicedoc6', 'Personnel'),
(68, 'Durand', 'Paul', 'paul.durand@sante.fr', 'medpass555', 'Personnel'),
(67, 'Petit', 'Camille', 'camille.petit@sante.fr', 'securemeds', 'Personnel'),
(66, 'Bernard', 'Thomas', 'thomas.bernard@sante.fr', 'docpass789', 'Personnel'),
(65, 'Martin', 'Sophie', 'sophie.martin@sante.fr', 'safepass456', 'Personnel'),
(64, 'Dupont', 'Jean', 'jean.dupont@sante.fr', 'med123pass', 'Personnel'),
(63, 'BAU', 'UmyY', 'Umy@gmail.com', '12345', 'Personnel'),
(1, 'Admin', 'Admin', 'Admin@gmail.com', 'Admin', 'Admin'),
(61, 'BAUDRILLARD', 'Ethan', 'ethan.baudrillard@gmail.com', '1234', 'client'),
(100, 'Rousseau', 'Laura', 'laura.rousseau.rhumato_ped@sante.fr', 'rhumato_ped', 'Personnel'),
(101, 'Faure', 'Nicolas', 'nicolas.faure.vasculaire@sante.fr', 'vasculairefaure', 'Personnel'),
(102, 'Moreau', 'Chloé', 'chloe.moreau.addicto@sante.fr', 'addictochloe', 'Personnel'),
(103, 'Bertrand', 'Olivier', 'olivier.bertrand.hemato@sante.fr', 'hemarand', 'Personnel'),
(104, 'Mercier', 'Laura', 'laura.mercier.immuno@sante.fr', 'immuno42', 'Personnel'),
(105, 'Lacroix', 'Fabien', 'fabien.lacroix.nutri@sante.fr', 'nutrifabien', 'Personnel'),
(106, 'Lefevre', 'Sarah', 'sarah.lefevre.douleur@sante.fr', 'sarahdouleur', 'Personnel'),
(107, 'Dubois', 'Clément', 'clement.dubois.radio@sante.fr', 'radioclement', 'Personnel'),
(108, 'Leclerc', 'Manon', 'manon.leclerc.chir@sante.fr', 'chirmanon', 'Personnel'),
(109, 'Dubois', 'Florian', 'florian.dubois.maxillo@sante.fr', 'maxillodubois', 'Personnel'),
(110, 'Fournier', 'Laura', 'laura.fournier.endo_ped@sante.fr', 'endopedlaura', 'Personnel'),
(111, 'Leclerc', 'Romain', 'romain.leclerc.neuroped@sante.fr', 'neuropedromain', 'Personnel'),
(112, 'Giraud', 'Sarah', 'sarah.giraud.plast@sante.fr', 'plastiquesarah', 'Personnel'),
(113, 'Mercier', 'Olivier', 'olivier.mercier.gastroped@sante.fr', 'gastropedolivier', 'Personnel'),
(114, 'Roux', 'Pauline', 'pauline.roux.genetique@sante.fr', 'genetiquepauline', 'Personnel'),
(200, 'Durand', 'Louise', 'louise.durand.neonato@sante.fr', 'neonatodurand', 'Personnel'),
(201, 'Martin', 'Arthur', 'arthur.martin.rhumato_inter@sante.fr', 'rhumatoarthur', 'Personnel'),
(202, 'Lacroix', 'Clara', 'clara.lacroix.dermato_esthet@sante.fr', 'dermatoesthetclara', 'Personnel'),
(203, 'Bernard', 'Lucas', 'lucas.bernard.ortho_ped@sante.fr', 'orthopedlucas', 'Personnel'),
(204, 'Dubois', 'Emma', 'emma.dubois.gyneco_med@sante.fr', 'gynemedemma', 'Personnel'),
(205, 'Thomas', 'Hugo', 'hugo.thomas.chir_sup@sante.fr', 'chirsuthomas', 'Personnel'),
(206, 'Lefevre', 'Manon', 'manon.lefevre.psy_enfant_ado@sante.fr', 'psylefevremanon', 'Personnel'),
(207, 'Roussel', 'Antoine', 'antoine.roussel.med_travail@sante.fr', 'medtravailantoine', 'Personnel'),
(208, 'Thomas', 'Zoé', 'zoe.thomas.med_esthet@sante.fr', 'esthetiquezoe', 'Personnel'),
(209, 'Petit', 'Gabriel', 'gabriel.petit.chir_uro@sante.fr', 'chiruropetit', 'Personnel'),
(210, 'BAU', 'ETH', 'eth.bau@gmail.com', '12345', 'client');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs_admin`
--

DROP TABLE IF EXISTS `utilisateurs_admin`;
CREATE TABLE IF NOT EXISTS `utilisateurs_admin` (
  `ID` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `utilisateurs_admin`
--

INSERT INTO `utilisateurs_admin` (`ID`) VALUES
(1);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs_client`
--

DROP TABLE IF EXISTS `utilisateurs_client`;
CREATE TABLE IF NOT EXISTS `utilisateurs_client` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Telephone` bigint NOT NULL,
  `CarteVitale` bigint NOT NULL,
  `ID_Adresse` int NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `utilisateurs_client`
--

INSERT INTO `utilisateurs_client` (`ID`, `Telephone`, `CarteVitale`, `ID_Adresse`) VALUES
(210, 785944854, 24323234234234, 287),
(74, 770123456, 123456789012345, 225),
(28, 785944854, 1234, 61);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs_personnel`
--

DROP TABLE IF EXISTS `utilisateurs_personnel`;
CREATE TABLE IF NOT EXISTS `utilisateurs_personnel` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Photo` varchar(255) DEFAULT NULL,
  `Video` varchar(255) DEFAULT NULL,
  `Telephone` bigint NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Type` varchar(255) DEFAULT NULL,
  `ID_Adresse` int NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=210 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `utilisateurs_personnel`
--

INSERT INTO `utilisateurs_personnel` (`ID`, `Photo`, `Video`, `Telephone`, `Description`, `Type`, `ID_Adresse`) VALUES
(104, 'photoPro42.png', 'videoPro42.mp4', 642000042, 'Le Dr. Mercier est une immunologue dédiée aux maladies rares et auto-immunes, offrant un diagnostic précis et des stratégies de traitement adaptées.', 'Immunologue Clinique', 256),
(105, 'photoPro43.png', 'videoPro43.mp4', 643000043, 'Le Dr. Lacroix est un médecin nutritionniste privilégiant une approche personnalisée pour une alimentation saine et adaptée aux besoins de chacun.', 'Médecin Nutritionniste', 257),
(106, 'photoPro44.png', 'videoPro44.mp4', 644000044, 'Le Dr. Lefevre est une algologue experte en thérapies non médicamenteuses et accompagnement psychologique pour mieux vivre avec la douleur.', 'Algologue', 258),
(107, 'photoPro45.png', 'videoPro45.mp4', 645000045, 'Le Dr. Dubois est un radiologue spécialisé en imagerie musculo-squelettique, fournissant des diagnostics précis pour les affections articulaires et osseuses.', 'Radiologue', 259),
(108, 'photoPro46.png', 'videoPro46.mp4', 646000046, 'Le Dr. Leclerc est une chirurgienne viscérale experte en chirurgie mini-invasive du côlon et de l\'estomac, garantissant une récupération rapide.', 'Chirurgien Viscéral et Digestif', 260),
(97, 'photoPro35.png', 'videoPro35.mp4', 635000035, 'Le Dr. Petit assure une prise en charge anesthésique sécurisée et personnalisée, avec une expertise en analgésie post-opératoire.', 'Anesthésiste-Réanimateur', 249),
(98, 'photoPro36.png', 'videoPro36.mp4', 636000036, 'Le Dr. Martin est un expert en maladies inflammatoires intestinales et pathologies hépatiques, proposant une approche diagnostique et thérapeutique de pointe.', 'Gastro-entérologue et Hépatologue', 250),
(99, 'photoPro37.png', 'videoPro37.mp4', 637000037, 'Le Dr. Bernard est une MPR experte en rééducation neurologique et des troubles musculo-squelettiques, visant à restaurer l\'autonomie de ses patients.', 'Médecin Physique et de Réadaptation', 251),
(73, 'photoPro10.png', 'videoPro10.mp4', 610000010, 'Le Dr. Girard est une généraliste attentive aux nouvelles approches thérapeutiques, avec une spécialisation en nutrition.', 'Généraliste', 224),
(72, 'photoPro9.png', 'videoPro9.mp4', 609000009, 'Le Dr. Fournier est un généraliste rigoureux, avec un intérêt marqué pour la cardiologie et les bilans de santé complets.', 'Généraliste', 223),
(71, 'photoPro8.png', 'videoPro8.mp4', 608000008, 'Le Dr. Moreau est appréciée pour son écoute attentive et son approche douce, particulièrement adaptée aux familles et aux enfants.', 'Généraliste', 222),
(70, 'photoPro7.png', 'videoPro7.mp4', 607000007, 'Le Dr. Roux est reconnu pour son expertise en maladies infectieuses et son approche pédagogique auprès de ses patients.', 'Généraliste', 221),
(69, 'photoPro6.png', 'videoPro6.mp4', 606000006, 'Le Dr. Leroy propose une médecine générale axée sur le bien-être et la gestion du stress, avec une approche intégrative.', 'Généraliste', 220),
(68, 'photoPro5.png', 'videoPro5.mp4', 605000005, 'Le Dr. Durand est un généraliste expérimenté, spécialisé en médecine préventive et suivi des adolescents.', 'Généraliste', 219),
(67, 'photoPro4.png', 'videoPro4.mp4', 604000004, 'Le Dr. Petit offre un suivi attentif et personnalisé, avec une prédilection pour la gériatrie et les maladies chroniques.', 'Généraliste', 218),
(66, 'photoPro3.png', 'videoPro3.mp4', 603000003, 'Spécialiste de la médecine du sport, le Dr. Bernard est également un généraliste passionné par la prévention et l\'éducation à la santé.', 'Généraliste', 217),
(65, 'photoPro2.png', 'videoPro2.mp4', 602000002, 'Ancienne interne des hôpitaux de Paris, le Dr. Martin est connue pour son approche bienveillante et sa pédagogie, orientée vers la médecine familiale.', 'Généraliste', 216),
(63, 'umyBAU.png', 'umyBAU.mp4', 798765478, 'Test Test', 'Généraliste', 2),
(64, 'photoPro1.png', 'videoPro1.mp4', 601000001, 'Diplômé de la Faculté de Médecine de Paris, le Dr. Dupont est un généraliste à l\'écoute, privilégiant une approche holistique et préventive.', 'Généraliste', 215),
(100, 'photoPro38.png', 'videoPro38.mp4', 638000038, 'Le Dr. Rousseau est une rhumatologue pédiatrique bienveillante, experte dans les affections articulaires et auto-immunes de l\'enfant, assurant un suivi adapté.', 'Rhumatologue Pédiatrique', 252),
(101, 'photoPro39.png', 'videoPro39.mp4', 639000039, 'Le Dr. Faure est un médecin vasculaire expert en échographie Doppler, spécialisé dans la prévention et le traitement des maladies artérielles et veineuses.', 'Médecin Vasculaire', 253),
(102, 'photoPro40.png', 'videoPro40.mp4', 640000040, 'Le Dr. Moreau est une addictologue empathique et expérimentée, offrant un soutien personnalisé pour aider à surmonter les dépendances et retrouver un équilibre de vie.', 'Addictologue', 254),
(103, 'photoPro41.png', 'videoPro41.mp4', 641000041, 'Le Dr. Bertrand est un hématologue expert en myélome et lymphome, offrant un suivi précis et des traitements de pointe.', 'Hématologue', 255),
(93, 'photoPro31.png', 'videoPro31.mp4', 631000031, 'Le Dr. Fournier est une endocrinologue-diabétologue attentive et pédagogue, axée sur la prévention et le suivi des maladies métaboliques.', 'Endocrinologue-Diabétologue', 245),
(94, 'photoPro32.png', 'videoPro32.mp4', 632000032, 'Le Dr. Garcia est un gériatre dévoué, spécialisé dans l\'accompagnement des seniors, avec une approche respectueuse de l\'autonomie et du bien-être.', 'Gériatre', 246),
(95, 'photoPro33.png', 'videoPro33.mp4', 633000033, 'Le Dr. Leroy est une pneumo-allergologue experte dans le diagnostic et le suivi des patients souffrant d\'allergies et d\'affections pulmonaires chroniques.', 'Pneumo-allergologue', 247),
(96, 'photoPro34.png', 'videoPro34.mp4', 634000034, 'Le Dr. Dubois est un neurochirurgien renommé, spécialisé dans la chirurgie mini-invasive de la colonne vertébrale et les tumeurs cérébrales.', 'Neurochirurgien', 248),
(109, 'photoPro51.png', 'videoPro51.mp4', 651000051, 'Le Dr. Dubois est un chirurgien maxillo-facial expert en reconstruction faciale et implantologie, garantissant des résultats fonctionnels et esthétiques.', 'Chirurgien Maxillo-Facial', 261),
(110, 'photoPro52.png', 'videoPro52.mp4', 652000052, 'Le Dr. Fournier est une endocrinologue pédiatrique dévouée, experte en troubles de la croissance et du développement chez les enfants.', 'Endocrinologue Pédiatrique', 262),
(111, 'photoPro53.png', 'videoPro53.mp4', 653000053, 'Le Dr. Leclerc est un neuro-pédiatre expérimenté, offrant un accompagnement holistique pour les enfants et leurs familles face aux défis neurologiques.', 'Neuro-pédiatre', 263),
(112, 'photoPro54.png', 'videoPro54.mp4', 654000054, 'Le Dr. Giraud est une chirurgienne plasticienne experte en remodelage corporel et rajeunissement facial, avec un souci du naturel et de l\'harmonie.', 'Chirurgien Plasticien et Esthétique', 264),
(113, 'photoPro55.png', 'videoPro55.mp4', 655000055, 'Le Dr. Mercier est un gastro-pédiatre spécialisé dans les intolérances alimentaires et les maladies inflammatoires digestives chez les plus jeunes.', 'Gastro-pédiatre', 265),
(114, 'photoPro56.png', 'videoPro56.mp4', 656000056, 'Le Dr. Roux est une généticienne médicale spécialisée dans le diagnostic des maladies rares et l\'accompagnement des familles, avec bienveillance et expertise.', 'Généticien Médical', 266),
(200, 'photoPro61.png', 'videoPro61.mp4', 661000061, 'Le Dr. Durand est une néonatologue bienveillante, dédiée au bien-être des plus petits et au soutien des jeunes parents.', 'Néonatologue', 267),
(201, 'photoPro62.png', 'videoPro62.mp4', 662000062, 'Le Dr. Martin est un rhumatologue interventionnel qui utilise des techniques de pointe pour soulager les douleurs chroniques et améliorer la mobilité.', 'Rhumatologue Interventionnel', 268),
(202, 'photoPro63.png', 'videoPro63.mp4', 663000063, 'Le Dr. Lacroix est une dermatologue esthétique reconnue pour son approche naturelle et personnalisée, visant à sublimer la beauté de chaque patient.', 'Dermatologue Esthétique', 269),
(203, 'photoPro64.png', 'videoPro64.mp4', 664000064, 'Le Dr. Bernard est un orthopédiste pédiatrique dévoué à la santé osseuse et articulaire des enfants, avec une approche ludique et rassurante.', 'Orthopédiste Pédiatrique', 270),
(204, 'photoPro65.png', 'videoPro65.mp4', 665000065, 'Le Dr. Dubois est une gynécologue médicale à l\'écoute, privilégiant une approche douce et pédagogique pour la santé des femmes à tout âge.', 'Gynécologue Médicale', 271),
(205, 'photoPro66.png', 'videoPro66.mp4', 666000066, 'Le Dr. Thomas est un chirurgien de la main et du membre supérieur expert en microchirurgie et traumatologie sportive, favorisant une récupération optimale.', 'Chirurgien Orthopédique du Membre Supérieur', 272),
(206, 'photoPro67.png', 'videoPro67.mp4', 667000067, 'Le Dr. Lefevre est une psychiatre experte dans le soutien des enfants et adolescents, favorisant un développement sain et équilibré.', 'Psychiatre de l\'Enfant et de l\'Adolescent', 273),
(207, 'photoPro68.png', 'videoPro68.mp4', 668000068, 'Le Dr. Roussel est un médecin du travail engagé dans la promotion de la santé au travail et l\'amélioration des conditions de vie professionnelle.', 'Médecin du Travail', 274),
(208, 'photoPro69.png', 'videoPro69.mp4', 669000069, 'Le Dr. Thomas est une médecin esthétique reconnue pour sa maîtrise des techniques douces et son approche naturelle, pour des résultats subtils et harmonieux.', 'Médecin Esthétique', 275),
(209, 'photoPro70.png', 'videoPro70.mp4', 670000070, 'Le Dr. Petit est un chirurgien urologue expert en chirurgie robot-assistée pour les pathologies complexes de la prostate et des reins.', 'Chirurgien Urologue', 276);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
