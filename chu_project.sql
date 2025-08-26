-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : mysql
-- Généré le : lun. 18 août 2025 à 08:23
-- Version du serveur : 9.3.0
-- Version de PHP : 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `chu_project`
--

-- --------------------------------------------------------

--
-- Structure de la table `Document`
--

CREATE TABLE `Document` (
  `id_document` int NOT NULL,
  `id_souscription` int NOT NULL COMMENT 'Via souscription on accède à utilisateur et admin',
  `id_type_document` int NOT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du fichier sur le serveur',
  `nom_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom original du fichier',
  `chemin_fichier` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chemin complet du fichier',
  `type_mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taille_fichier` bigint NOT NULL COMMENT 'Taille en octets',
  `description_document` text COLLATE utf8mb4_unicode_ci,
  `version_document` int DEFAULT '1' COMMENT 'Version du document',
  `date_telechargement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_document` enum('actif','archive','supprime') COLLATE utf8mb4_unicode_ci DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Evenement`
--

CREATE TABLE `Evenement` (
  `id_evenement` int NOT NULL,
  `id_souscription` int DEFAULT NULL COMMENT 'Événement spécifique à UNE souscription ou GLOBAL si NULL',
  `id_type_evenement` int NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut_evenement` date NOT NULL,
  `date_fin_evenement` date DEFAULT NULL,
  `date_prevue_fin` date DEFAULT NULL COMMENT 'Date prévue de fin',
  `lieu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordonnees_gps` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_evenement` enum('planifie','en_cours','termine','reporte','annule') COLLATE utf8mb4_unicode_ci DEFAULT 'planifie',
  `niveau_avancement_pourcentage` int DEFAULT '0',
  `etape_actuelle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Étape en cours',
  `cout_estime` decimal(15,2) DEFAULT NULL,
  `cout_reel` decimal(15,2) DEFAULT NULL,
  `entreprise_responsable` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responsable_chantier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priorite` enum('basse','normale','haute','critique') COLLATE utf8mb4_unicode_ci DEFAULT 'normale',
  `est_public` tinyint(1) DEFAULT '1' COMMENT 'Visible par tous les souscripteurs',
  `notification_envoyee` tinyint(1) DEFAULT '0',
  `inscription_requise` tinyint(1) DEFAULT '0',
  `nombre_places_limite` int DEFAULT NULL,
  `nombre_inscrits` int DEFAULT '0',
  `fichier_joint` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Document principal',
  `images_json` json DEFAULT NULL COMMENT 'URLs des images',
  `videos_json` json DEFAULT NULL COMMENT 'URLs des vidéos',
  `documents_json` json DEFAULT NULL COMMENT 'Documents associés',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  `nombre_vues` int DEFAULT '0'
) ;

-- --------------------------------------------------------

--
-- Structure de la table `LogsActivite`
--

CREATE TABLE `LogsActivite` (
  `id_log` int NOT NULL,
  `id_utilisateur` int DEFAULT NULL COMMENT 'Utilisateur qui a fait l''action',
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_affectee` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_enregistrement` int DEFAULT NULL,
  `details_json` json DEFAULT NULL COMMENT 'Détails de l''action',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_action` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `PlanPaiement`
--

CREATE TABLE `PlanPaiement` (
  `id_plan_paiement` int NOT NULL,
  `id_souscription` int NOT NULL,
  `numero_mensualite` int NOT NULL COMMENT 'Numéro de la mensualité (1 à 64)',
  `montant_versement_prevu` decimal(15,2) NOT NULL,
  `date_limite_versement` date NOT NULL,
  `date_paiement_effectif` date DEFAULT NULL COMMENT 'Date réelle du paiement',
  `montant_paye` decimal(15,2) DEFAULT NULL COMMENT 'Montant réellement payé',
  `mode_paiement` enum('especes','virement','mobile_money','cheque') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_paiement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Référence du paiement',
  `est_paye` tinyint(1) DEFAULT '0',
  `penalite_appliquee` decimal(15,2) DEFAULT '0.00' COMMENT 'Pénalité de retard',
  `statut_versement` enum('en_attente','paye_a_temps','paye_en_retard','non_paye') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `commentaire_paiement` text COLLATE utf8mb4_unicode_ci,
  `date_saisie` timestamp NULL DEFAULT NULL COMMENT 'Date de saisie par admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Reclamation`
--

CREATE TABLE `Reclamation` (
  `id_reclamation` int NOT NULL,
  `id_souscription` int NOT NULL COMMENT 'Via souscription on accède à utilisateur et admin',
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_reclamation` enum('anomalie_paiement','information_erronee','document_manquant','avancement_projet','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_reclamation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_statut_reclamation` int NOT NULL,
  `priorite` enum('basse','normale','haute','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'normale',
  `reponse_admin` text COLLATE utf8mb4_unicode_ci COMMENT 'Réponse de l''administration',
  `date_reponse` timestamp NULL DEFAULT NULL,
  `date_traitement` timestamp NULL DEFAULT NULL COMMENT 'Date prise en charge',
  `date_resolution` timestamp NULL DEFAULT NULL COMMENT 'Date de résolution',
  `satisfaction_client` enum('tres_satisfait','satisfait','peu_satisfait','insatisfait') COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Recompense`
--

CREATE TABLE `Recompense` (
  `id_recompense` int NOT NULL,
  `id_souscription` int NOT NULL COMMENT 'Via souscription on accède à utilisateur et admin',
  `id_type_recompense` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description de la récompense',
  `motif_recompense` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Motif de l''attribution',
  `periode_merite` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Période concernée ex: Mai-Octobre 2024',
  `valeur_recompense` decimal(10,2) DEFAULT NULL COMMENT 'Valeur monétaire si applicable',
  `statut_recompense` enum('due','attribuee','annulee') COLLATE utf8mb4_unicode_ci DEFAULT 'due',
  `date_attribution` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de décision',
  `date_attribution_effective` timestamp NULL DEFAULT NULL COMMENT 'Date effective de remise',
  `commentaire_admin` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Souscription`
--

CREATE TABLE `Souscription` (
  `id_souscription` int NOT NULL,
  `id_utilisateur` int NOT NULL COMMENT 'Souscripteur',
  `id_terrain` int NOT NULL COMMENT 'Terrain principal',
  `id_admin` int NOT NULL COMMENT 'Admin gestionnaire',
  `date_souscription` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre_terrains` int NOT NULL DEFAULT '1',
  `montant_mensuel` decimal(15,2) NOT NULL DEFAULT '64400.00',
  `nombre_mensualites` int NOT NULL DEFAULT '64',
  `montant_total_souscrit` decimal(15,2) GENERATED ALWAYS AS (((`montant_mensuel` * `nombre_mensualites`) * `nombre_terrains`)) STORED,
  `date_debut_paiement` date DEFAULT '2024-05-01',
  `date_fin_prevue` date GENERATED ALWAYS AS ((`date_debut_paiement` + interval `nombre_mensualites` month)) STORED,
  `statut_souscription` enum('active','suspendue','terminee','resillee') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `groupe_souscription` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Groupement pour souscriptions multiples',
  `notes_admin` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes administratives'
) ;

-- --------------------------------------------------------

--
-- Structure de la table `StatutReclamation`
--

CREATE TABLE `StatutReclamation` (
  `id_statut_reclamation` int NOT NULL,
  `libelle_statut_reclamation` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_statut` text COLLATE utf8mb4_unicode_ci,
  `ordre_statut` int DEFAULT '0' COMMENT 'Ordre d''affichage',
  `couleur_statut` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#6c757d' COMMENT 'Couleur hexadécimale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `StatutReclamation`
--

INSERT INTO `StatutReclamation` (`id_statut_reclamation`, `libelle_statut_reclamation`, `description_statut`, `ordre_statut`, `couleur_statut`) VALUES
(1, 'Nouvelle', 'Réclamation nouvellement créée', 1, '#17a2b8'),
(2, 'En cours', 'Réclamation en cours de traitement', 2, '#ffc107'),
(3, 'En attente', 'En attente de complément d\'information', 3, '#fd7e14'),
(4, 'Résolue', 'Réclamation résolue avec succès', 4, '#28a745'),
(5, 'Fermée', 'Réclamation fermée définitivement', 5, '#6c757d'),
(6, 'Rejetée', 'Réclamation rejetée', 6, '#dc3545');

-- --------------------------------------------------------

--
-- Structure de la table `Terrain`
--

CREATE TABLE `Terrain` (
  `id_terrain` int NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localisation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `superficie` decimal(10,2) DEFAULT NULL COMMENT 'En m²',
  `prix_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix par terrain',
  `description` text COLLATE utf8mb4_unicode_ci,
  `statut_terrain` enum('disponible','reserve','vendu','indisponible') COLLATE utf8mb4_unicode_ci DEFAULT 'disponible',
  `coordonnees_gps` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Latitude, Longitude',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `Terrain`
--

INSERT INTO `Terrain` (`id_terrain`, `libelle`, `localisation`, `superficie`, `prix_unitaire`, `description`, `statut_terrain`, `coordonnees_gps`, `date_creation`) VALUES
(1, 'Lot A1 - Zone Résidentielle', 'Secteur Nord - Angré', 250.00, 4121600.00, 'Terrain de 250m² en zone résidentielle calme, proche commodités', 'disponible', '5.3697,-3.9956', '2025-07-16 14:04:55'),
(2, 'Lot A2 - Zone Résidentielle', 'Secteur Nord - Angré', 250.00, 4121600.00, 'Terrain de 250m² avec vue dégagée', 'disponible', '5.3698,-3.9955', '2025-07-16 14:04:55'),
(3, 'Lot B1 - Zone Premium', 'Secteur Sud - Angré', 500.00, 8243200.00, 'Terrain de 500m² en zone premium, accès direct route principale', 'disponible', '5.3695,-3.9958', '2025-07-16 14:04:55'),
(4, 'Lot B2 - Zone Premium', 'Secteur Sud - Angré', 500.00, 8243200.00, 'Terrain de 500m² d\'angle, double façade', 'disponible', '5.3696,-3.9957', '2025-07-16 14:04:55');

-- --------------------------------------------------------

--
-- Structure de la table `TypeDocument`
--

CREATE TABLE `TypeDocument` (
  `id_type_document` int NOT NULL,
  `libelle_type_document` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_type` text COLLATE utf8mb4_unicode_ci,
  `extension_autorisee` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pdf,jpg,jpeg,png' COMMENT 'Extensions autorisées',
  `taille_max_mo` int DEFAULT '5' COMMENT 'Taille maximale en Mo',
  `est_obligatoire` tinyint(1) DEFAULT '0' COMMENT 'Document obligatoire pour la souscription'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `TypeDocument`
--

INSERT INTO `TypeDocument` (`id_type_document`, `libelle_type_document`, `description_type`, `extension_autorisee`, `taille_max_mo`, `est_obligatoire`) VALUES
(1, 'Plan Terrain', 'Plans et schémas des terrains', 'pdf,jpg,jpeg,png', 10, 1),
(2, 'Contrat Souscription', 'Contrats de souscription signés', 'pdf', 5, 1),
(3, 'Acte de Vente', 'Actes de vente officiels', 'pdf', 5, 0),
(4, 'Attestation Paiement', 'Attestations de paiement', 'pdf', 3, 0),
(5, 'Reçu Mensuel', 'Reçus de paiement mensuel', 'pdf,jpg,jpeg', 2, 0),
(6, 'Certificat Conformité', 'Certificats de conformité', 'pdf', 5, 0),
(7, 'Photo Terrain', 'Photos des terrains et travaux', 'jpg,jpeg,png', 5, 0),
(8, 'Rapport Avancement', 'Rapports d\'avancement des travaux', 'pdf', 10, 0),
(9, 'Document Administratif', 'Documents administratifs divers', 'pdf', 5, 0),
(10, 'Titre Foncier', 'Titres fonciers définitifs', 'pdf', 5, 0);

-- --------------------------------------------------------

--
-- Structure de la table `TypeEvenement`
--

CREATE TABLE `TypeEvenement` (
  `id_type_evenement` int NOT NULL,
  `libelle_type_evenement` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_type` text COLLATE utf8mb4_unicode_ci,
  `categorie_type` enum('travaux_terrain','administrative','communication','livraison','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `couleur_affichage` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#007bff' COMMENT 'Couleur hexadécimale',
  `icone_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-calendar' COMMENT 'Classe CSS icône',
  `ordre_affichage` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `TypeEvenement`
--

INSERT INTO `TypeEvenement` (`id_type_evenement`, `libelle_type_evenement`, `description_type`, `categorie_type`, `couleur_affichage`, `icone_type`, `ordre_affichage`) VALUES
(1, 'Bornage Terrain', 'Délimitation et bornage des terrains', 'travaux_terrain', '#28a745', 'fas fa-map-marked-alt', 1),
(2, 'Terrassement', 'Travaux de terrassement', 'travaux_terrain', '#007bff', 'fas fa-truck', 2),
(3, 'Viabilisation', 'Travaux de viabilisation (eau, électricité)', 'travaux_terrain', '#17a2b8', 'fas fa-tools', 3),
(4, 'Construction Infrastructure', 'Construction des infrastructures communes', 'travaux_terrain', '#6f42c1', 'fas fa-building', 4),
(5, 'Aménagement Paysager', 'Aménagement des espaces verts', 'travaux_terrain', '#20c997', 'fas fa-tree', 5),
(6, 'Titre Foncier', 'Procédures administratives foncières', 'administrative', '#ffc107', 'fas fa-file-contract', 10),
(7, 'Permis Construction', 'Obtention des permis de construire', 'administrative', '#fd7e14', 'fas fa-stamp', 11),
(8, 'Inspection Officielle', 'Inspections réglementaires', 'administrative', '#6c757d', 'fas fa-search', 12),
(9, 'Réunion Information', 'Réunions d\'information souscripteurs', 'communication', '#e83e8c', 'fas fa-users', 20),
(10, 'Assemblée Générale', 'Assemblées générales', 'communication', '#6f42c1', 'fas fa-user-tie', 21),
(11, 'Visite Guidée', 'Visites guidées du projet', 'communication', '#20c997', 'fas fa-route', 22),
(12, 'Communication Officielle', 'Communications officielles', 'communication', '#495057', 'fas fa-bullhorn', 23),
(13, 'Pré-livraison', 'Préparation à la livraison', 'livraison', '#17a2b8', 'fas fa-clipboard-check', 30),
(14, 'Livraison Terrain', 'Livraison effective des terrains', 'livraison', '#28a745', 'fas fa-key', 31),
(15, 'Inauguration', 'Cérémonies d\'inauguration', 'livraison', '#fd7e14', 'fas fa-ribbon', 32),
(16, 'Maintenance Préventive', 'Maintenance préventive', 'maintenance', '#6c757d', 'fas fa-wrench', 40),
(17, 'Réparation', 'Travaux de réparation', 'maintenance', '#dc3545', 'fas fa-hammer', 41),
(18, 'Contrôle Qualité', 'Contrôles qualité périodiques', 'maintenance', '#495057', 'fas fa-clipboard-list', 42);

-- --------------------------------------------------------

--
-- Structure de la table `TypeRecompense`
--

CREATE TABLE `TypeRecompense` (
  `id_type_recompense` int NOT NULL,
  `libelle_type_recompense` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_type` text COLLATE utf8mb4_unicode_ci,
  `valeur_monetaire` decimal(10,2) DEFAULT NULL COMMENT 'Valeur monétaire si applicable',
  `est_monetaire` tinyint(1) DEFAULT '0',
  `conditions_attribution` text COLLATE utf8mb4_unicode_ci COMMENT 'Conditions pour obtenir cette récompense'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `TypeRecompense`
--

INSERT INTO `TypeRecompense` (`id_type_recompense`, `libelle_type_recompense`, `description_type`, `valeur_monetaire`, `est_monetaire`, `conditions_attribution`) VALUES
(1, 'Réduction Mensuelle', 'Réduction sur les mensualités suivantes', 10000.00, 1, '6 paiements consécutifs à temps'),
(2, 'Bonus Régularité', 'Bonus pour régularité de paiement', 25000.00, 1, '12 paiements consécutifs à temps'),
(3, 'Priorité Attribution', 'Priorité pour choix de terrain', NULL, 0, 'Aucun retard sur 6 mois'),
(4, 'Accès VIP Événements', 'Accès privilégié aux événements', NULL, 0, 'Souscripteur exemplaire'),
(5, 'Cadeau Symbolique', 'Cadeau de reconnaissance', 5000.00, 1, 'Participation active au projet'),
(6, 'Réduction Finale', 'Réduction sur dernières mensualités', 50000.00, 1, 'Paiement intégral sans retard'),
(7, 'Lettre Félicitations', 'Lettre officielle de félicitations', NULL, 0, 'Respect des engagements'),
(8, 'Accès Exclusif Info', 'Informations privilégiées sur le projet', NULL, 0, 'Souscripteur premium');

-- --------------------------------------------------------

--
-- Structure de la table `Utilisateur`
--

CREATE TABLE `Utilisateur` (
  `id_utilisateur` int NOT NULL,
  `matricule` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Matricule CHU',
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `poste` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Poste au CHU',
  `service` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Service au CHU',
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_inscription` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `est_administrateur` tinyint(1) DEFAULT '0',
  `statut_utilisateur` enum('actif','suspendu','inactif') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `token_reset` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expiration` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `Utilisateur`
--

INSERT INTO `Utilisateur` (`id_utilisateur`, `matricule`, `nom`, `prenom`, `email`, `telephone`, `poste`, `service`, `mot_de_passe`, `date_inscription`, `derniere_connexion`, `est_administrateur`, `statut_utilisateur`, `token_reset`, `token_expiration`) VALUES
(1, 'ADMIN001', 'ADON', 'AMIEPO RAYNOUARD', 'admin@chu-angre-cite.ci', '0759106404', 'Technicien Biologie Médicale', 'Laboratoire', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-16 14:04:55', NULL, 1, 'actif', NULL, NULL),
(2, 'ADMIN002', 'COORDINATION', 'SYNDICALE', 'coordination@chu-angre-cite.ci', '0759106405', 'Coordination', 'Administration', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-07-16 14:04:55', NULL, 1, 'actif', NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `Document`
--
ALTER TABLE `Document`
  ADD PRIMARY KEY (`id_document`),
  ADD KEY `idx_document_souscription` (`id_souscription`),
  ADD KEY `idx_document_type` (`id_type_document`),
  ADD KEY `idx_document_statut` (`statut_document`),
  ADD KEY `idx_document_date` (`date_telechargement`);

--
-- Index pour la table `Evenement`
--
ALTER TABLE `Evenement`
  ADD PRIMARY KEY (`id_evenement`),
  ADD KEY `idx_evenement_souscription` (`id_souscription`),
  ADD KEY `idx_evenement_type` (`id_type_evenement`),
  ADD KEY `idx_evenement_date_debut` (`date_debut_evenement`),
  ADD KEY `idx_evenement_statut` (`statut_evenement`),
  ADD KEY `idx_evenement_public` (`est_public`),
  ADD KEY `idx_evenement_actif` (`actif`),
  ADD KEY `idx_evenement_avancement` (`niveau_avancement_pourcentage`);

--
-- Index pour la table `LogsActivite`
--
ALTER TABLE `LogsActivite`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_logs_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_logs_action` (`action`),
  ADD KEY `idx_logs_table` (`table_affectee`),
  ADD KEY `idx_logs_date` (`date_action`);

--
-- Index pour la table `PlanPaiement`
--
ALTER TABLE `PlanPaiement`
  ADD PRIMARY KEY (`id_plan_paiement`),
  ADD UNIQUE KEY `unique_mensualite` (`id_souscription`,`numero_mensualite`),
  ADD KEY `idx_planpaiement_souscription` (`id_souscription`),
  ADD KEY `idx_planpaiement_datelimite` (`date_limite_versement`),
  ADD KEY `idx_planpaiement_statut` (`statut_versement`),
  ADD KEY `idx_planpaiement_paye` (`est_paye`);

--
-- Index pour la table `Reclamation`
--
ALTER TABLE `Reclamation`
  ADD PRIMARY KEY (`id_reclamation`),
  ADD KEY `idx_reclamation_souscription` (`id_souscription`),
  ADD KEY `idx_reclamation_statut` (`id_statut_reclamation`),
  ADD KEY `idx_reclamation_priorite` (`priorite`),
  ADD KEY `idx_reclamation_date` (`date_reclamation`),
  ADD KEY `idx_reclamation_type` (`type_reclamation`);

--
-- Index pour la table `Recompense`
--
ALTER TABLE `Recompense`
  ADD PRIMARY KEY (`id_recompense`),
  ADD KEY `idx_recompense_souscription` (`id_souscription`),
  ADD KEY `idx_recompense_statut` (`statut_recompense`),
  ADD KEY `idx_recompense_type` (`id_type_recompense`),
  ADD KEY `idx_recompense_date` (`date_attribution`);

--
-- Index pour la table `Souscription`
--
ALTER TABLE `Souscription`
  ADD PRIMARY KEY (`id_souscription`),
  ADD KEY `idx_souscription_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_souscription_terrain` (`id_terrain`),
  ADD KEY `idx_souscription_admin` (`id_admin`),
  ADD KEY `idx_souscription_statut` (`statut_souscription`),
  ADD KEY `idx_souscription_groupe` (`groupe_souscription`);

--
-- Index pour la table `StatutReclamation`
--
ALTER TABLE `StatutReclamation`
  ADD PRIMARY KEY (`id_statut_reclamation`),
  ADD UNIQUE KEY `libelle_statut_reclamation` (`libelle_statut_reclamation`);

--
-- Index pour la table `Terrain`
--
ALTER TABLE `Terrain`
  ADD PRIMARY KEY (`id_terrain`),
  ADD KEY `idx_terrain_statut` (`statut_terrain`),
  ADD KEY `idx_terrain_superficie` (`superficie`);

--
-- Index pour la table `TypeDocument`
--
ALTER TABLE `TypeDocument`
  ADD PRIMARY KEY (`id_type_document`),
  ADD UNIQUE KEY `libelle_type_document` (`libelle_type_document`);

--
-- Index pour la table `TypeEvenement`
--
ALTER TABLE `TypeEvenement`
  ADD PRIMARY KEY (`id_type_evenement`),
  ADD UNIQUE KEY `libelle_type_evenement` (`libelle_type_evenement`);

--
-- Index pour la table `TypeRecompense`
--
ALTER TABLE `TypeRecompense`
  ADD PRIMARY KEY (`id_type_recompense`),
  ADD UNIQUE KEY `libelle_type_recompense` (`libelle_type_recompense`);

--
-- Index pour la table `Utilisateur`
--
ALTER TABLE `Utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `idx_utilisateur_email` (`email`),
  ADD KEY `idx_utilisateur_matricule` (`matricule`),
  ADD KEY `idx_utilisateur_statut` (`statut_utilisateur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `Document`
--
ALTER TABLE `Document`
  MODIFY `id_document` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Evenement`
--
ALTER TABLE `Evenement`
  MODIFY `id_evenement` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `LogsActivite`
--
ALTER TABLE `LogsActivite`
  MODIFY `id_log` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `PlanPaiement`
--
ALTER TABLE `PlanPaiement`
  MODIFY `id_plan_paiement` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Reclamation`
--
ALTER TABLE `Reclamation`
  MODIFY `id_reclamation` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Recompense`
--
ALTER TABLE `Recompense`
  MODIFY `id_recompense` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `Souscription`
--
ALTER TABLE `Souscription`
  MODIFY `id_souscription` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `StatutReclamation`
--
ALTER TABLE `StatutReclamation`
  MODIFY `id_statut_reclamation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `Terrain`
--
ALTER TABLE `Terrain`
  MODIFY `id_terrain` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `TypeDocument`
--
ALTER TABLE `TypeDocument`
  MODIFY `id_type_document` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `TypeEvenement`
--
ALTER TABLE `TypeEvenement`
  MODIFY `id_type_evenement` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `TypeRecompense`
--
ALTER TABLE `TypeRecompense`
  MODIFY `id_type_recompense` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `Utilisateur`
--
ALTER TABLE `Utilisateur`
  MODIFY `id_utilisateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `Document`
--
ALTER TABLE `Document`
  ADD CONSTRAINT `Document_ibfk_1` FOREIGN KEY (`id_souscription`) REFERENCES `Souscription` (`id_souscription`) ON DELETE CASCADE,
  ADD CONSTRAINT `Document_ibfk_2` FOREIGN KEY (`id_type_document`) REFERENCES `TypeDocument` (`id_type_document`) ON DELETE RESTRICT;

--
-- Contraintes pour la table `Evenement`
--
ALTER TABLE `Evenement`
  ADD CONSTRAINT `Evenement_ibfk_1` FOREIGN KEY (`id_souscription`) REFERENCES `Souscription` (`id_souscription`) ON DELETE CASCADE,
  ADD CONSTRAINT `Evenement_ibfk_2` FOREIGN KEY (`id_type_evenement`) REFERENCES `TypeEvenement` (`id_type_evenement`) ON DELETE RESTRICT;

--
-- Contraintes pour la table `LogsActivite`
--
ALTER TABLE `LogsActivite`
  ADD CONSTRAINT `LogsActivite_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `Utilisateur` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `PlanPaiement`
--
ALTER TABLE `PlanPaiement`
  ADD CONSTRAINT `PlanPaiement_ibfk_1` FOREIGN KEY (`id_souscription`) REFERENCES `Souscription` (`id_souscription`) ON DELETE CASCADE;

--
-- Contraintes pour la table `Reclamation`
--
ALTER TABLE `Reclamation`
  ADD CONSTRAINT `Reclamation_ibfk_1` FOREIGN KEY (`id_souscription`) REFERENCES `Souscription` (`id_souscription`) ON DELETE CASCADE,
  ADD CONSTRAINT `Reclamation_ibfk_2` FOREIGN KEY (`id_statut_reclamation`) REFERENCES `StatutReclamation` (`id_statut_reclamation`) ON DELETE RESTRICT;

--
-- Contraintes pour la table `Recompense`
--
ALTER TABLE `Recompense`
  ADD CONSTRAINT `Recompense_ibfk_1` FOREIGN KEY (`id_souscription`) REFERENCES `Souscription` (`id_souscription`) ON DELETE CASCADE,
  ADD CONSTRAINT `Recompense_ibfk_2` FOREIGN KEY (`id_type_recompense`) REFERENCES `TypeRecompense` (`id_type_recompense`) ON DELETE RESTRICT;

--
-- Contraintes pour la table `Souscription`
--
ALTER TABLE `Souscription`
  ADD CONSTRAINT `Souscription_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `Utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `Souscription_ibfk_2` FOREIGN KEY (`id_terrain`) REFERENCES `Terrain` (`id_terrain`) ON DELETE RESTRICT,
  ADD CONSTRAINT `Souscription_ibfk_3` FOREIGN KEY (`id_admin`) REFERENCES `Utilisateur` (`id_utilisateur`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
