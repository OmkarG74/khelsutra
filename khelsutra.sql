-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 20, 2026 at 03:49 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `khelsutra`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodations`
--

DROP TABLE IF EXISTS `accommodations`;
CREATE TABLE IF NOT EXISTS `accommodations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accommodation_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `total_rooms` int UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_accommodation_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_allocations`
--

DROP TABLE IF EXISTS `accommodation_allocations`;
CREATE TABLE IF NOT EXISTS `accommodation_allocations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `accommodation_id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED DEFAULT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date DEFAULT NULL,
  `status` enum('reserved','checked_in','checked_out','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reserved',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_aa_accommodation` (`accommodation_id`),
  KEY `fk_aa_room` (`room_id`),
  KEY `fk_aa_event` (`event_id`),
  KEY `fk_aa_athlete` (`athlete_id`),
  KEY `fk_aa_employee` (`employee_id`),
  KEY `fk_aa_coach` (`coach_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_rooms`
--

DROP TABLE IF EXISTS `accommodation_rooms`;
CREATE TABLE IF NOT EXISTS `accommodation_rooms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `accommodation_id` bigint UNSIGNED NOT NULL,
  `room_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int UNSIGNED NOT NULL DEFAULT '1',
  `floor_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','occupied','maintenance','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_accommodation_room` (`accommodation_id`,`room_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

DROP TABLE IF EXISTS `achievements`;
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `tournament_id` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `achievement_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position_or_medal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `achievement_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `certificate_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_achievement_org` (`organization_id`),
  KEY `fk_achievement_athlete` (`athlete_id`),
  KEY `fk_achievement_tournament` (`tournament_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athletes`
--

DROP TABLE IF EXISTS `athletes`;
CREATE TABLE IF NOT EXISTS `athletes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `athlete_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other','not_specified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_specified',
  `blood_group` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `government_id_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `government_id_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `current_sport_id` bigint UNSIGNED DEFAULT NULL,
  `current_category_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive','injured','suspended','retired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_athlete_org_code` (`organization_id`,`athlete_code`),
  KEY `idx_athlete_org_status` (`organization_id`,`status`),
  KEY `fk_athlete_user` (`user_id`),
  KEY `fk_athlete_sport` (`current_sport_id`),
  KEY `fk_athlete_category` (`current_category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `athletes`
--

INSERT INTO `athletes` (`id`, `organization_id`, `user_id`, `athlete_code`, `first_name`, `middle_name`, `last_name`, `photo_path`, `date_of_birth`, `gender`, `blood_group`, `nationality`, `phone`, `email`, `government_id_type`, `government_id_number`, `address_line1`, `address_line2`, `city`, `state`, `country`, `postal_code`, `latitude`, `longitude`, `registration_date`, `joining_date`, `current_sport_id`, `current_category_id`, `status`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, NULL, 'ATH-E4ED5F', 'Aarav', NULL, 'Patel', NULL, '2008-05-14', 'male', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, NULL, NULL, 1, NULL, 'active', NULL, '2026-09-20 14:12:30', '2026-09-20 14:12:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `athlete_awards`
--

DROP TABLE IF EXISTS `athlete_awards`;
CREATE TABLE IF NOT EXISTS `athlete_awards` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `award_id` bigint UNSIGNED NOT NULL,
  `award_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `certificate_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_athlete_award_org` (`organization_id`),
  KEY `fk_athlete_award_athlete` (`athlete_id`),
  KEY `fk_athlete_award_award` (`award_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athlete_documents`
--

DROP TABLE IF EXISTS `athlete_documents`;
CREATE TABLE IF NOT EXISTS `athlete_documents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `document_type` enum('id_proof','birth_certificate','medical_certificate','insurance','sports_certificate','consent_form','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_athlete_document` (`athlete_id`,`document_type`),
  KEY `fk_ath_doc_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athlete_guardians`
--

DROP TABLE IF EXISTS `athlete_guardians`;
CREATE TABLE IF NOT EXISTS `athlete_guardians` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `guardian_type` enum('father','mother','guardian','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guardian',
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alternate_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `is_emergency_contact` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_guardian_athlete` (`athlete_id`),
  KEY `fk_guardian_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athlete_injuries`
--

DROP TABLE IF EXISTS `athlete_injuries`;
CREATE TABLE IF NOT EXISTS `athlete_injuries` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `injury_date` date NOT NULL,
  `injury_type` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_part` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `severity` enum('minor','moderate','major','critical') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `treatment` text COLLATE utf8mb4_unicode_ci,
  `expected_recovery_date` date DEFAULT NULL,
  `actual_recovery_date` date DEFAULT NULL,
  `status` enum('active','recovering','recovered','chronic') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_injury_org` (`organization_id`),
  KEY `fk_injury_athlete` (`athlete_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athlete_medical_clearances`
--

DROP TABLE IF EXISTS `athlete_medical_clearances`;
CREATE TABLE IF NOT EXISTS `athlete_medical_clearances` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `clearance_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `doctor_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clearance_status` enum('fit','fit_with_restrictions','unfit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `restrictions` text COLLATE utf8mb4_unicode_ci,
  `certificate_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_clearance_org` (`organization_id`),
  KEY `fk_clearance_athlete` (`athlete_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athlete_medical_profiles`
--

DROP TABLE IF EXISTS `athlete_medical_profiles`;
CREATE TABLE IF NOT EXISTS `athlete_medical_profiles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `chronic_conditions` text COLLATE utf8mb4_unicode_ci,
  `current_medications` text COLLATE utf8mb4_unicode_ci,
  `medical_notes` text COLLATE utf8mb4_unicode_ci,
  `emergency_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_medical_profile_athlete` (`athlete_id`),
  KEY `fk_medical_profile_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athlete_performance`
--

DROP TABLE IF EXISTS `athlete_performance`;
CREATE TABLE IF NOT EXISTS `athlete_performance` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `sport_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED DEFAULT NULL,
  `training_session_id` bigint UNSIGNED DEFAULT NULL,
  `match_id` bigint UNSIGNED DEFAULT NULL,
  `evaluation_date` date NOT NULL,
  `overall_rating` decimal(5,2) DEFAULT NULL,
  `coach_remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_performance_athlete_date` (`athlete_id`,`evaluation_date`),
  KEY `fk_perf_org` (`organization_id`),
  KEY `fk_perf_sport` (`sport_id`),
  KEY `fk_perf_team` (`team_id`),
  KEY `fk_perf_training` (`training_session_id`),
  KEY `fk_perf_match` (`match_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athlete_performance_values`
--

DROP TABLE IF EXISTS `athlete_performance_values`;
CREATE TABLE IF NOT EXISTS `athlete_performance_values` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `performance_id` bigint UNSIGNED NOT NULL,
  `metric_id` bigint UNSIGNED NOT NULL,
  `numeric_value` decimal(14,4) DEFAULT NULL,
  `text_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_performance_metric` (`performance_id`,`metric_id`),
  KEY `fk_pv_metric` (`metric_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `athlete_sport_history`
--

DROP TABLE IF EXISTS `athlete_sport_history`;
CREATE TABLE IF NOT EXISTS `athlete_sport_history` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `sport_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_athlete_sport_history` (`athlete_id`,`start_date`),
  KEY `fk_ash_org` (`organization_id`),
  KEY `fk_ash_sport` (`sport_id`),
  KEY `fk_ash_category` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` bigint UNSIGNED DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_org_date` (`organization_id`,`created_at`),
  KEY `idx_audit_user_date` (`user_id`,`created_at`),
  KEY `idx_audit_record` (`table_name`,`record_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `awards`
--

DROP TABLE IF EXISTS `awards`;
CREATE TABLE IF NOT EXISTS `awards` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `award_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_award_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
CREATE TABLE IF NOT EXISTS `budgets` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `budget_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `financial_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_budget` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','active','closed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_budget_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_items`
--

DROP TABLE IF EXISTS `budget_items`;
CREATE TABLE IF NOT EXISTS `budget_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` bigint UNSIGNED NOT NULL,
  `finance_category_id` bigint UNSIGNED DEFAULT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_budget_item_budget` (`budget_id`),
  KEY `fk_budget_item_category` (`finance_category_id`),
  KEY `fk_budget_item_department` (`department_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coach_profiles`
--

DROP TABLE IF EXISTS `coach_profiles`;
CREATE TABLE IF NOT EXISTS `coach_profiles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `coach_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualification` text COLLATE utf8mb4_unicode_ci,
  `certifications` text COLLATE utf8mb4_unicode_ci,
  `experience_years` decimal(5,2) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coach_org_code` (`organization_id`,`coach_code`),
  UNIQUE KEY `uq_coach_employee` (`employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_department_org_name` (`organization_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `employee_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','not_specified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_specified',
  `blood_group` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `employee_category_id` bigint UNSIGNED DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','temporary','intern','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full_time',
  `employment_status` enum('active','inactive','on_leave','terminated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `emergency_contact_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_ifsc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employee_org_code` (`organization_id`,`employee_code`),
  KEY `idx_employee_org_status` (`organization_id`,`employment_status`),
  KEY `fk_employee_user` (`user_id`),
  KEY `fk_employee_department` (`department_id`),
  KEY `fk_employee_category` (`employee_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_categories`
--

DROP TABLE IF EXISTS `employee_categories`;
CREATE TABLE IF NOT EXISTS `employee_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employee_category_org_name` (`organization_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

DROP TABLE IF EXISTS `employee_documents`;
CREATE TABLE IF NOT EXISTS `employee_documents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `document_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_document` (`employee_id`),
  KEY `fk_emp_doc_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE IF NOT EXISTS `equipment` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `inventory_item_id` bigint UNSIGNED DEFAULT NULL,
  `asset_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(14,2) DEFAULT NULL,
  `warranty_expiry_date` date DEFAULT NULL,
  `condition_status` enum('new','good','damaged','under_maintenance','lost','disposed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `current_location` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','assigned','maintenance','lost','disposed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_equipment_asset_code` (`organization_id`,`asset_code`),
  UNIQUE KEY `uq_equipment_serial` (`organization_id`,`serial_number`),
  KEY `fk_equipment_item` (`inventory_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_assignments`
--

DROP TABLE IF EXISTS `equipment_assignments`;
CREATE TABLE IF NOT EXISTS `equipment_assignments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `equipment_id` bigint UNSIGNED NOT NULL,
  `assignee_type` enum('athlete','coach','employee','team','venue') COLLATE utf8mb4_unicode_ci NOT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `team_id` bigint UNSIGNED DEFAULT NULL,
  `venue_id` bigint UNSIGNED DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `returned_date` date DEFAULT NULL,
  `condition_on_issue` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_on_return` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('assigned','returned','lost','damaged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `issued_by` bigint UNSIGNED DEFAULT NULL,
  `received_by` bigint UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ea_org` (`organization_id`),
  KEY `fk_ea_equipment` (`equipment_id`),
  KEY `fk_ea_athlete` (`athlete_id`),
  KEY `fk_ea_coach` (`coach_id`),
  KEY `fk_ea_employee` (`employee_id`),
  KEY `fk_ea_team` (`team_id`),
  KEY `fk_ea_venue` (`venue_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `event_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue_id` bigint UNSIGNED DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `organizer_employee_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('draft','planned','ongoing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_reference` (`organization_id`,`event_reference`),
  KEY `fk_event_venue` (`venue_id`),
  KEY `fk_event_organizer` (`organizer_employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_participants`
--

DROP TABLE IF EXISTS `event_participants`;
CREATE TABLE IF NOT EXISTS `event_participants` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` bigint UNSIGNED NOT NULL,
  `participant_type` enum('athlete','employee','coach','team') COLLATE utf8mb4_unicode_ci NOT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `team_id` bigint UNSIGNED DEFAULT NULL,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('invited','confirmed','attended','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invited',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ep_event` (`event_id`),
  KEY `fk_ep_athlete` (`athlete_id`),
  KEY `fk_ep_employee` (`employee_id`),
  KEY `fk_ep_coach` (`coach_id`),
  KEY `fk_ep_team` (`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `expense_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `finance_category_id` bigint UNSIGNED DEFAULT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_id` bigint UNSIGNED DEFAULT NULL,
  `event_id` bigint UNSIGNED DEFAULT NULL,
  `tournament_id` bigint UNSIGNED DEFAULT NULL,
  `expense_date` date NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(14,2) NOT NULL,
  `payment_status` enum('pending','approved','paid','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `receipt_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_expense_reference` (`organization_id`,`expense_reference`),
  KEY `fk_expense_category` (`finance_category_id`),
  KEY `fk_expense_department` (`department_id`),
  KEY `fk_expense_vendor` (`vendor_id`),
  KEY `fk_expense_event` (`event_id`),
  KEY `fk_expense_tournament` (`tournament_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_categories`
--

DROP TABLE IF EXISTS `finance_categories`;
CREATE TABLE IF NOT EXISTS `finance_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_type` enum('income','expense','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'expense',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_finance_category` (`organization_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_payments`
--

DROP TABLE IF EXISTS `finance_payments`;
CREATE TABLE IF NOT EXISTS `finance_payments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `payment_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_type` enum('expense','vendor_invoice','payroll','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `expense_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_invoice_id` bigint UNSIGNED DEFAULT NULL,
  `payroll_id` bigint UNSIGNED DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_reference` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_finance_payment_reference` (`organization_id`,`payment_reference`),
  KEY `fk_fp_expense` (`expense_id`),
  KEY `fk_fp_vendor_invoice` (`vendor_invoice_id`),
  KEY `fk_fp_payroll` (`payroll_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixtures`
--

DROP TABLE IF EXISTS `fixtures`;
CREATE TABLE IF NOT EXISTS `fixtures` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `fixture_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `round_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `home_team_id` bigint UNSIGNED NOT NULL,
  `away_team_id` bigint UNSIGNED NOT NULL,
  `venue_id` bigint UNSIGNED DEFAULT NULL,
  `facility_id` bigint UNSIGNED DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_start_time` time NOT NULL,
  `scheduled_end_time` time DEFAULT NULL,
  `status` enum('scheduled','postponed','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fixture_reference` (`organization_id`,`fixture_reference`),
  KEY `idx_fixture_schedule` (`scheduled_date`,`scheduled_start_time`),
  KEY `fk_fixture_tournament` (`tournament_id`),
  KEY `fk_fixture_home_team` (`home_team_id`),
  KEY `fk_fixture_away_team` (`away_team_id`),
  KEY `fk_fixture_venue` (`venue_id`),
  KEY `fk_fixture_facility` (`facility_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

DROP TABLE IF EXISTS `goods_receipts`;
CREATE TABLE IF NOT EXISTS `goods_receipts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `purchase_order_id` bigint UNSIGNED NOT NULL,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receipt_date` date NOT NULL,
  `received_by` bigint UNSIGNED DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_goods_receipt` (`organization_id`,`receipt_number`),
  KEY `fk_gr_po` (`purchase_order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_tasks`
--

DROP TABLE IF EXISTS `housekeeping_tasks`;
CREATE TABLE IF NOT EXISTS `housekeeping_tasks` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `venue_id` bigint UNSIGNED NOT NULL,
  `facility_id` bigint UNSIGNED DEFAULT NULL,
  `task_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `assigned_employee_id` bigint UNSIGNED DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_start_time` time DEFAULT NULL,
  `scheduled_end_time` time DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','assigned','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_housekeeping_reference` (`organization_id`,`task_reference`),
  KEY `fk_housekeeping_venue` (`venue_id`),
  KEY `fk_housekeeping_facility` (`facility_id`),
  KEY `fk_housekeeping_employee` (`assigned_employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `income_transactions`
--

DROP TABLE IF EXISTS `income_transactions`;
CREATE TABLE IF NOT EXISTS `income_transactions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `income_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `finance_category_id` bigint UNSIGNED DEFAULT NULL,
  `income_date` date NOT NULL,
  `source_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_income_reference` (`organization_id`,`income_reference`),
  KEY `fk_income_category` (`finance_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
CREATE TABLE IF NOT EXISTS `inventory_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_category` (`organization_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `item_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'piece',
  `quantity` decimal(14,2) NOT NULL DEFAULT '0.00',
  `minimum_stock_level` decimal(14,2) NOT NULL DEFAULT '0.00',
  `reorder_level` decimal(14,2) NOT NULL DEFAULT '0.00',
  `unit_cost` decimal(14,2) NOT NULL DEFAULT '0.00',
  `location_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','discontinued') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_item_code` (`organization_id`,`item_code`),
  KEY `fk_inventory_item_category` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `applicant_type` enum('employee','athlete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `leave_type_id` bigint UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(6,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leave_org_status` (`organization_id`,`status`),
  KEY `fk_leave_employee` (`employee_id`),
  KEY `fk_leave_athlete` (`athlete_id`),
  KEY `fk_leave_type` (`leave_type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_days_per_year` decimal(6,2) DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_leave_type_org_name` (`organization_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
CREATE TABLE IF NOT EXISTS `matches` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `fixture_id` bigint UNSIGNED NOT NULL,
  `match_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actual_start_time` datetime DEFAULT NULL,
  `actual_end_time` datetime DEFAULT NULL,
  `home_score` decimal(10,2) DEFAULT NULL,
  `away_score` decimal(10,2) DEFAULT NULL,
  `winner_team_id` bigint UNSIGNED DEFAULT NULL,
  `result_type` enum('home_win','away_win','draw','tie','no_result','abandoned') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referee_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `officials_notes` text COLLATE utf8mb4_unicode_ci,
  `match_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('scheduled','live','completed','abandoned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_match_reference` (`organization_id`,`match_reference`),
  UNIQUE KEY `uq_match_fixture` (`fixture_id`),
  KEY `fk_match_winner` (`winner_team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_attendance`
--

DROP TABLE IF EXISTS `match_attendance`;
CREATE TABLE IF NOT EXISTS `match_attendance` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `match_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `attendance_status` enum('present','absent','late','excused') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_match_attendee` (`match_id`,`athlete_id`,`coach_id`,`employee_id`),
  KEY `fk_ma_org` (`organization_id`),
  KEY `fk_ma_athlete` (`athlete_id`),
  KEY `fk_ma_coach` (`coach_id`),
  KEY `fk_ma_employee` (`employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_visits`
--

DROP TABLE IF EXISTS `medical_visits`;
CREATE TABLE IF NOT EXISTS `medical_visits` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `visit_date` date NOT NULL,
  `doctor_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medical_facility` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `diagnosis` text COLLATE utf8mb4_unicode_ci,
  `treatment` text COLLATE utf8mb4_unicode_ci,
  `medications` text COLLATE utf8mb4_unicode_ci,
  `follow_up_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_med_visit_org` (`organization_id`),
  KEY `fk_med_visit_athlete` (`athlete_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `channel` enum('in_app','push','email') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_app',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notification_user` (`user_id`,`is_read`,`created_at`),
  KEY `fk_notification_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `logo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','active','suspended','expired','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `access_start_date` date DEFAULT NULL,
  `access_end_date` date DEFAULT NULL,
  `plan_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_code` (`organization_code`),
  KEY `idx_org_status` (`status`),
  KEY `idx_org_access` (`access_start_date`,`access_end_date`),
  KEY `fk_org_created_by` (`created_by`),
  KEY `fk_org_updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `organization_code`, `name`, `legal_name`, `email`, `phone`, `website`, `address_line1`, `address_line2`, `city`, `state`, `country`, `postal_code`, `latitude`, `longitude`, `logo_path`, `status`, `access_start_date`, `access_end_date`, `plan_name`, `notes`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'ORG-DEMO', 'Apex Sports Academy', 'Apex Sports Foundation Ltd', 'contact@apexsports.org', '+919876543210', NULL, NULL, NULL, 'Pune', 'Maharashtra', 'India', NULL, NULL, NULL, NULL, 'active', NULL, NULL, 'Enterprise', NULL, NULL, NULL, '2026-09-20 14:09:03', '2026-09-20 14:09:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `organization_access_logs`
--

DROP TABLE IF EXISTS `organization_access_logs`;
CREATE TABLE IF NOT EXISTS `organization_access_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `action` enum('created','activated','suspended','expired','renewed','deactivated') COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_start_date` date DEFAULT NULL,
  `access_end_date` date DEFAULT NULL,
  `performed_by` bigint UNSIGNED DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org_access_log` (`organization_id`,`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization_settings`
--

DROP TABLE IF EXISTS `organization_settings`;
CREATE TABLE IF NOT EXISTS `organization_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `setting_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('string','integer','decimal','boolean','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_setting` (`organization_id`,`setting_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization_users`
--

DROP TABLE IF EXISTS `organization_users`;
CREATE TABLE IF NOT EXISTS `organization_users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `access_status` enum('active','inactive','suspended','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `assigned_by` bigint UNSIGNED DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_user` (`organization_id`,`user_id`),
  KEY `idx_org_role` (`organization_id`,`role_id`),
  KEY `idx_org_access` (`organization_id`,`access_status`),
  KEY `fk_ou_user` (`user_id`),
  KEY `fk_ou_role` (`role_id`),
  KEY `fk_ou_assigned_by` (`assigned_by`),
  KEY `fk_ou_employee` (`employee_id`),
  KEY `fk_ou_athlete` (`athlete_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organization_users`
--

INSERT INTO `organization_users` (`id`, `organization_id`, `user_id`, `role_id`, `employee_id`, `athlete_id`, `access_status`, `assigned_by`, `assigned_at`, `revoked_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, NULL, NULL, 'active', NULL, '2026-09-20 14:09:03', NULL, '2026-09-20 14:09:03', '2026-09-20 14:09:03'),
(2, 1, 2, 4, NULL, NULL, 'active', NULL, '2026-09-20 14:09:03', NULL, '2026-09-20 14:09:03', '2026-09-20 14:09:03'),
(3, 1, 3, 5, NULL, NULL, 'active', NULL, '2026-09-20 14:09:03', NULL, '2026-09-20 14:09:03', '2026-09-20 14:09:03');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
CREATE TABLE IF NOT EXISTS `payroll` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `payroll_period_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `basic_salary` decimal(14,2) NOT NULL DEFAULT '0.00',
  `allowances` decimal(14,2) NOT NULL DEFAULT '0.00',
  `overtime_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `overtime_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `bonus` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(14,2) NOT NULL DEFAULT '0.00',
  `deductions` decimal(14,2) NOT NULL DEFAULT '0.00',
  `other_deductions` decimal(14,2) NOT NULL DEFAULT '0.00',
  `gross_salary` decimal(14,2) NOT NULL DEFAULT '0.00',
  `net_salary` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('pending','processed','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payroll_employee_period` (`payroll_period_id`,`employee_id`),
  KEY `fk_payroll_org` (`organization_id`),
  KEY `fk_payroll_employee` (`employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
CREATE TABLE IF NOT EXISTS `payroll_periods` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `period_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('draft','processing','processed','locked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `processed_by` bigint UNSIGNED DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payroll_period` (`organization_id`,`start_date`,`end_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_metrics`
--

DROP TABLE IF EXISTS `performance_metrics`;
CREATE TABLE IF NOT EXISTS `performance_metrics` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sport_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_type` enum('number','decimal','percentage','time','distance','rating','text') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'number',
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_value` decimal(12,4) DEFAULT NULL,
  `max_value` decimal(12,4) DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_metric_sport_code` (`sport_id`,`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `uq_permission_module_action` (`module`,`action`)
) ENGINE=MyISAM AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `module`, `action`, `description`, `created_at`, `updated_at`) VALUES
(1, 'organization.view', 'organization', 'view', 'View organization details', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(2, 'organization.create', 'organization', 'create', 'Create organisations', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(3, 'organization.update', 'organization', 'update', 'Update organisations', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(4, 'organization.suspend', 'organization', 'suspend', 'Suspend organisation access', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(5, 'user.view', 'user', 'view', 'View users', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(6, 'user.create', 'user', 'create', 'Create users', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(7, 'user.update', 'user', 'update', 'Update users', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(8, 'user.deactivate', 'user', 'deactivate', 'Deactivate users', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(9, 'athlete.view', 'athlete', 'view', 'View athlete records and profiles', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(10, 'athlete.create', 'athlete', 'create', 'Register new athletes', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(11, 'athlete.update', 'athlete', 'update', 'Update athletes', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(12, 'athlete.delete', 'athlete', 'delete', 'Archive or remove athletes', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(13, 'athlete.medical.view', 'athlete_medical', 'view', 'View athlete medical records', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(14, 'athlete.medical.manage', 'athlete_medical', 'manage', 'Manage athlete medical records', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(15, 'athlete.performance.view', 'athlete_performance', 'view', 'View athlete performance', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(16, 'athlete.performance.manage', 'athlete_performance', 'manage', 'Manage athlete performance', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(17, 'coach.view', 'coach', 'view', 'View coach profiles and specialties', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(18, 'coach.create', 'coach', 'create', 'Create coaches', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(19, 'coach.update', 'coach', 'update', 'Update coaches', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(20, 'team.view', 'team', 'view', 'View teams and rosters', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(21, 'team.create', 'team', 'create', 'Create teams', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(22, 'team.update', 'team', 'update', 'Update teams', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(23, 'team.members.manage', 'team', 'members_manage', 'Manage team members', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(24, 'team.coaches.manage', 'team', 'coaches_manage', 'Manage team coaches', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(25, 'training.view', 'training', 'view', 'View training sessions and camps', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(26, 'training.create', 'training', 'create', 'Create training', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(27, 'training.update', 'training', 'update', 'Update training', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(28, 'training.attendance.manage', 'training', 'attendance_manage', 'Manage training attendance', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(29, 'tournament.view', 'tournament', 'view', 'View tournaments and standings', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(30, 'tournament.create', 'tournament', 'create', 'Create tournaments', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(31, 'tournament.update', 'tournament', 'update', 'Update tournaments', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(32, 'fixture.manage', 'fixture', 'manage', 'Create fixtures and record match results', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(33, 'match.manage', 'match', 'manage', 'Manage matches and results', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(34, 'venue.view', 'venue', 'view', 'View venues and facilities', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(35, 'venue.create', 'venue', 'create', 'Create venues', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(36, 'venue.update', 'venue', 'update', 'Update venues', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(37, 'venue.booking.manage', 'venue_booking', 'manage', 'Manage venue bookings', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(38, 'venue.maintenance.manage', 'venue_maintenance', 'manage', 'Manage venue maintenance', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(39, 'housekeeping.manage', 'housekeeping', 'manage', 'Manage housekeeping tasks', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(40, 'employee.view', 'employee', 'view', 'View staff and employee records', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(41, 'employee.create', 'employee', 'create', 'Create employees', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(42, 'employee.update', 'employee', 'update', 'Update employees', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(43, 'leave.view', 'leave', 'view', 'View leave', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(44, 'leave.create', 'leave', 'create', 'Create leave requests', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(45, 'leave.approve', 'leave', 'approve', 'Approve or reject leave applications', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(46, 'payroll.view', 'payroll', 'view', 'View payroll', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(47, 'payroll.manage', 'payroll', 'manage', 'Manage salary structures and process payroll', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(48, 'inventory.view', 'inventory', 'view', 'View inventory levels and equipment', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(49, 'inventory.manage', 'inventory', 'manage', 'Manage inventory stock and equipment assignments', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(50, 'equipment.manage', 'equipment', 'manage', 'Manage equipment', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(51, 'vendor.manage', 'vendor', 'manage', 'Manage vendors', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(52, 'purchase.manage', 'purchase', 'manage', 'Create purchase orders and manage vendor invoices', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(53, 'event.view', 'event', 'view', 'View events and school activities', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(54, 'event.manage', 'event', 'manage', 'Organize events, transport, and accommodation', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(55, 'transport.manage', 'transport', 'manage', 'Manage transport', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(56, 'accommodation.manage', 'accommodation', 'manage', 'Manage accommodation', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(57, 'finance.view', 'finance', 'view', 'View financial budgets and reports', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(58, 'finance.manage', 'finance', 'manage', 'Manage budgets, record expenses and income', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(59, 'report.view', 'report', 'view', 'Generate and view analytical reports', '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(60, 'notification.view', 'notification', 'view', 'View notifications', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(61, 'notification.manage', 'notification', 'manage', 'Manage notifications', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(62, 'settings.manage', 'settings', 'manage', 'Manage organisation settings', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(63, 'audit.view', 'audit', 'view', 'View audit logs', '2026-09-20 13:42:35', '2026-09-20 13:42:35'),
(64, 'organization.manage', 'organization', 'manage', 'Manage organization settings and details', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(65, 'user.manage', 'user', 'manage', 'Manage organization users and role assignments', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(66, 'sport.view', 'sport', 'view', 'View sports and categories', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(67, 'sport.manage', 'sport', 'manage', 'Create and edit sports and categories', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(68, 'athlete.edit', 'athlete', 'edit', 'Update athlete details', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(69, 'coach.manage', 'coach', 'manage', 'Manage coach profiles and assignments', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(70, 'team.manage', 'team', 'manage', 'Create and modify teams and rosters', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(71, 'training.manage', 'training', 'manage', 'Schedule training sessions and camps', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(72, 'attendance.record', 'attendance', 'record', 'Mark training and match attendance', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(73, 'performance.view', 'performance', 'view', 'View performance metrics and evaluation', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(74, 'performance.manage', 'performance', 'manage', 'Record and update athlete performance', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(75, 'medical.view', 'medical', 'view', 'View medical records and clearances', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(76, 'medical.manage', 'medical', 'manage', 'Manage medical consultations and injury records', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(77, 'tournament.manage', 'tournament', 'manage', 'Create and administer tournaments', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(78, 'venue.manage', 'venue', 'manage', 'Manage venues and maintenance', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(79, 'booking.create', 'booking', 'create', 'Book venue facilities', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(80, 'employee.manage', 'employee', 'manage', 'Manage employee records and documents', '2026-09-20 13:48:33', '2026-09-20 13:48:33'),
(81, 'leave.request', 'leave', 'request', 'Submit leave applications', '2026-09-20 13:48:33', '2026-09-20 13:48:33');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `purchase_request_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_id` bigint UNSIGNED NOT NULL,
  `po_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('draft','sent','confirmed','partially_received','received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_po_number` (`organization_id`,`po_number`),
  KEY `fk_po_request` (`purchase_request_id`),
  KEY `fk_po_vendor` (`vendor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint UNSIGNED NOT NULL,
  `inventory_item_id` bigint UNSIGNED DEFAULT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ordered_quantity` decimal(14,2) NOT NULL,
  `received_quantity` decimal(14,2) NOT NULL DEFAULT '0.00',
  `unit_cost` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_poi_po` (`purchase_order_id`),
  KEY `fk_poi_item` (`inventory_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

DROP TABLE IF EXISTS `purchase_requests`;
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `request_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by` bigint UNSIGNED NOT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `request_date` date NOT NULL,
  `required_date` date DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','submitted','approved','rejected','converted','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_purchase_request_ref` (`organization_id`,`request_reference`),
  KEY `fk_pr_department` (`department_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_items`
--

DROP TABLE IF EXISTS `purchase_request_items`;
CREATE TABLE IF NOT EXISTS `purchase_request_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint UNSIGNED NOT NULL,
  `inventory_item_id` bigint UNSIGNED DEFAULT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `quantity` decimal(14,2) NOT NULL,
  `estimated_unit_cost` decimal(14,2) NOT NULL DEFAULT '0.00',
  `estimated_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_pri_request` (`purchase_request_id`),
  KEY `fk_pri_item` (`inventory_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system_role` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `is_system_role`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'Platform provider administrator with global multi-organization access', 1, '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(2, 'Sports Administrator', 'Full administrative control over a single sports organization', 1, '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(3, 'HR & Finance', 'Manages staff, coaches, payroll, leave, budgets, expenses, and accounts', 1, '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(4, 'Coach', 'Manages assigned teams, athletes, training sessions, attendance, and match tactics', 1, '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(5, 'Athlete', 'Accesses personal training schedules, performance stats, attendance, and leave requests', 1, '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(6, 'Venue & Tournament Manager', 'Oversees ground/facility bookings, maintenance, housekeeping, and tournament operations', 1, '2026-09-20 13:42:35', '2026-09-20 13:48:33'),
(7, 'Inventory Manager', 'Manages equipment, physical inventory, vendor relations, and purchase orders', 1, '2026-09-20 13:42:35', '2026-09-20 13:48:33');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` bigint UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 1, '2026-09-20 13:48:33'),
(1, 2, '2026-09-20 13:48:33'),
(1, 3, '2026-09-20 13:48:33'),
(1, 4, '2026-09-20 13:48:33'),
(1, 5, '2026-09-20 13:48:33'),
(1, 6, '2026-09-20 13:48:33'),
(1, 7, '2026-09-20 13:48:33'),
(1, 8, '2026-09-20 13:48:33'),
(1, 9, '2026-09-20 13:48:33'),
(1, 10, '2026-09-20 13:48:33'),
(1, 11, '2026-09-20 13:48:33'),
(1, 12, '2026-09-20 13:48:33'),
(1, 13, '2026-09-20 13:48:33'),
(1, 14, '2026-09-20 13:48:33'),
(1, 15, '2026-09-20 13:48:33'),
(1, 16, '2026-09-20 13:48:33'),
(1, 17, '2026-09-20 13:48:33'),
(1, 18, '2026-09-20 13:48:33'),
(1, 19, '2026-09-20 13:48:33'),
(1, 20, '2026-09-20 13:48:33'),
(1, 21, '2026-09-20 13:48:33'),
(1, 22, '2026-09-20 13:48:33'),
(1, 23, '2026-09-20 13:48:33'),
(1, 24, '2026-09-20 13:48:33'),
(1, 25, '2026-09-20 13:48:33'),
(1, 26, '2026-09-20 13:48:33'),
(1, 27, '2026-09-20 13:48:33'),
(1, 28, '2026-09-20 13:48:33'),
(1, 29, '2026-09-20 13:48:33'),
(1, 30, '2026-09-20 13:48:33'),
(1, 31, '2026-09-20 13:48:33'),
(1, 32, '2026-09-20 13:48:33'),
(1, 33, '2026-09-20 13:48:33'),
(1, 34, '2026-09-20 13:48:33'),
(1, 35, '2026-09-20 13:48:33'),
(1, 36, '2026-09-20 13:48:33'),
(1, 37, '2026-09-20 13:48:33'),
(1, 38, '2026-09-20 13:48:33'),
(1, 39, '2026-09-20 13:48:33'),
(1, 40, '2026-09-20 13:48:33'),
(1, 41, '2026-09-20 13:48:33'),
(1, 42, '2026-09-20 13:48:33'),
(1, 43, '2026-09-20 13:48:33'),
(1, 44, '2026-09-20 13:48:33'),
(1, 45, '2026-09-20 13:48:33'),
(1, 46, '2026-09-20 13:48:33'),
(1, 47, '2026-09-20 13:48:33'),
(1, 48, '2026-09-20 13:48:33'),
(1, 49, '2026-09-20 13:48:33'),
(1, 50, '2026-09-20 13:48:33'),
(1, 51, '2026-09-20 13:48:33'),
(1, 52, '2026-09-20 13:48:33'),
(1, 53, '2026-09-20 13:48:33'),
(1, 54, '2026-09-20 13:48:33'),
(1, 55, '2026-09-20 13:48:33'),
(1, 56, '2026-09-20 13:48:33'),
(1, 57, '2026-09-20 13:48:33'),
(1, 58, '2026-09-20 13:48:33'),
(1, 59, '2026-09-20 13:48:33'),
(1, 60, '2026-09-20 13:48:33'),
(1, 61, '2026-09-20 13:48:33'),
(1, 62, '2026-09-20 13:48:33'),
(1, 63, '2026-09-20 13:48:33'),
(1, 64, '2026-09-20 13:48:33'),
(1, 65, '2026-09-20 13:48:33'),
(1, 66, '2026-09-20 13:48:33'),
(1, 67, '2026-09-20 13:48:33'),
(1, 68, '2026-09-20 13:48:33'),
(1, 69, '2026-09-20 13:48:33'),
(1, 70, '2026-09-20 13:48:33'),
(1, 71, '2026-09-20 13:48:33'),
(1, 72, '2026-09-20 13:48:33'),
(1, 73, '2026-09-20 13:48:33'),
(1, 74, '2026-09-20 13:48:33'),
(1, 75, '2026-09-20 13:48:33'),
(1, 76, '2026-09-20 13:48:33'),
(1, 77, '2026-09-20 13:48:33'),
(1, 78, '2026-09-20 13:48:33'),
(1, 79, '2026-09-20 13:48:33'),
(1, 80, '2026-09-20 13:48:33'),
(1, 81, '2026-09-20 13:48:33'),
(2, 1, '2026-09-20 13:48:33'),
(2, 2, '2026-09-20 13:48:33'),
(2, 3, '2026-09-20 13:48:33'),
(2, 4, '2026-09-20 13:48:33'),
(2, 5, '2026-09-20 13:48:33'),
(2, 6, '2026-09-20 13:48:33'),
(2, 7, '2026-09-20 13:48:33'),
(2, 8, '2026-09-20 13:48:33'),
(2, 9, '2026-09-20 13:48:33'),
(2, 10, '2026-09-20 13:48:33'),
(2, 11, '2026-09-20 13:48:33'),
(2, 12, '2026-09-20 13:48:33'),
(2, 13, '2026-09-20 13:48:33'),
(2, 14, '2026-09-20 13:48:33'),
(2, 15, '2026-09-20 13:48:33'),
(2, 16, '2026-09-20 13:48:33'),
(2, 17, '2026-09-20 13:48:33'),
(2, 18, '2026-09-20 13:48:33'),
(2, 19, '2026-09-20 13:48:33'),
(2, 20, '2026-09-20 13:48:33'),
(2, 21, '2026-09-20 13:48:33'),
(2, 22, '2026-09-20 13:48:33'),
(2, 23, '2026-09-20 13:48:33'),
(2, 24, '2026-09-20 13:48:33'),
(2, 25, '2026-09-20 13:48:33'),
(2, 26, '2026-09-20 13:48:33'),
(2, 27, '2026-09-20 13:48:33'),
(2, 28, '2026-09-20 13:48:33'),
(2, 29, '2026-09-20 13:48:33'),
(2, 30, '2026-09-20 13:48:33'),
(2, 31, '2026-09-20 13:48:33'),
(2, 32, '2026-09-20 13:48:33'),
(2, 33, '2026-09-20 13:48:33'),
(2, 34, '2026-09-20 13:48:33'),
(2, 35, '2026-09-20 13:48:33'),
(2, 36, '2026-09-20 13:48:33'),
(2, 37, '2026-09-20 13:48:33'),
(2, 38, '2026-09-20 13:48:33'),
(2, 39, '2026-09-20 13:48:33'),
(2, 40, '2026-09-20 13:48:33'),
(2, 41, '2026-09-20 13:48:33'),
(2, 42, '2026-09-20 13:48:33'),
(2, 43, '2026-09-20 13:48:33'),
(2, 44, '2026-09-20 13:48:33'),
(2, 45, '2026-09-20 13:48:33'),
(2, 46, '2026-09-20 13:48:33'),
(2, 47, '2026-09-20 13:48:33'),
(2, 48, '2026-09-20 13:48:33'),
(2, 49, '2026-09-20 13:48:33'),
(2, 50, '2026-09-20 13:48:33'),
(2, 51, '2026-09-20 13:48:33'),
(2, 52, '2026-09-20 13:48:33'),
(2, 53, '2026-09-20 13:48:33'),
(2, 54, '2026-09-20 13:48:33'),
(2, 55, '2026-09-20 13:48:33'),
(2, 56, '2026-09-20 13:48:33'),
(2, 57, '2026-09-20 13:48:33'),
(2, 58, '2026-09-20 13:48:33'),
(2, 59, '2026-09-20 13:48:33'),
(2, 60, '2026-09-20 13:48:33'),
(2, 61, '2026-09-20 13:48:33'),
(2, 62, '2026-09-20 13:48:33'),
(2, 63, '2026-09-20 13:48:33'),
(2, 64, '2026-09-20 13:48:33'),
(2, 65, '2026-09-20 13:48:33'),
(2, 66, '2026-09-20 13:48:33'),
(2, 67, '2026-09-20 13:48:33'),
(2, 68, '2026-09-20 13:48:33'),
(2, 69, '2026-09-20 13:48:33'),
(2, 70, '2026-09-20 13:48:33'),
(2, 71, '2026-09-20 13:48:33'),
(2, 72, '2026-09-20 13:48:33'),
(2, 73, '2026-09-20 13:48:33'),
(2, 74, '2026-09-20 13:48:33'),
(2, 75, '2026-09-20 13:48:33'),
(2, 76, '2026-09-20 13:48:33'),
(2, 77, '2026-09-20 13:48:33'),
(2, 78, '2026-09-20 13:48:33'),
(2, 79, '2026-09-20 13:48:33'),
(2, 80, '2026-09-20 13:48:33'),
(2, 81, '2026-09-20 13:48:33'),
(3, 41, '2026-09-20 13:48:33'),
(3, 80, '2026-09-20 13:48:33'),
(3, 42, '2026-09-20 13:48:33'),
(3, 40, '2026-09-20 13:48:33'),
(3, 58, '2026-09-20 13:48:33'),
(3, 57, '2026-09-20 13:48:33'),
(3, 45, '2026-09-20 13:48:33'),
(3, 44, '2026-09-20 13:48:33'),
(3, 81, '2026-09-20 13:48:33'),
(3, 43, '2026-09-20 13:48:33'),
(3, 47, '2026-09-20 13:48:33'),
(3, 46, '2026-09-20 13:48:33'),
(3, 59, '2026-09-20 13:48:33'),
(4, 9, '2026-09-20 13:48:33'),
(4, 72, '2026-09-20 13:48:33'),
(4, 32, '2026-09-20 13:48:33'),
(4, 81, '2026-09-20 13:48:33'),
(4, 74, '2026-09-20 13:48:33'),
(4, 73, '2026-09-20 13:48:33'),
(4, 20, '2026-09-20 13:48:33'),
(4, 29, '2026-09-20 13:48:33'),
(4, 71, '2026-09-20 13:48:33'),
(4, 25, '2026-09-20 13:48:33'),
(5, 81, '2026-09-20 13:48:33'),
(5, 73, '2026-09-20 13:48:33'),
(5, 20, '2026-09-20 13:48:33'),
(5, 29, '2026-09-20 13:48:33'),
(5, 25, '2026-09-20 13:48:33'),
(6, 79, '2026-09-20 13:48:33'),
(6, 32, '2026-09-20 13:48:33'),
(6, 39, '2026-09-20 13:48:33'),
(6, 30, '2026-09-20 13:48:33'),
(6, 77, '2026-09-20 13:48:33'),
(6, 31, '2026-09-20 13:48:33'),
(6, 29, '2026-09-20 13:48:33'),
(6, 35, '2026-09-20 13:48:33'),
(6, 78, '2026-09-20 13:48:33'),
(6, 36, '2026-09-20 13:48:33'),
(6, 34, '2026-09-20 13:48:33'),
(7, 49, '2026-09-20 13:48:33'),
(7, 48, '2026-09-20 13:48:33'),
(7, 52, '2026-09-20 13:48:33');

-- --------------------------------------------------------

--
-- Table structure for table `salary_structures`
--

DROP TABLE IF EXISTS `salary_structures`;
CREATE TABLE IF NOT EXISTS `salary_structures` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `effective_from` date NOT NULL,
  `basic_salary` decimal(14,2) NOT NULL DEFAULT '0.00',
  `allowances` decimal(14,2) NOT NULL DEFAULT '0.00',
  `deduction` decimal(14,2) NOT NULL DEFAULT '0.00',
  `overtime_rate` decimal(14,2) NOT NULL DEFAULT '0.00',
  `bonus_default` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_default` decimal(14,2) NOT NULL DEFAULT '0.00',
  `other_deductions_default` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_salary_employee_effective` (`employee_id`,`effective_from`),
  KEY `fk_salary_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_activities`
--

DROP TABLE IF EXISTS `school_activities`;
CREATE TABLE IF NOT EXISTS `school_activities` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED DEFAULT NULL,
  `school_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sport_id` bigint UNSIGNED DEFAULT NULL,
  `activity_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_date` date NOT NULL,
  `venue_id` bigint UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `participant_count` int UNSIGNED DEFAULT NULL,
  `status` enum('planned','ongoing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_school_activity_org` (`organization_id`),
  KEY `fk_school_activity_event` (`event_id`),
  KEY `fk_school_activity_sport` (`sport_id`),
  KEY `fk_school_activity_venue` (`venue_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sports`
--

DROP TABLE IF EXISTS `sports`;
CREATE TABLE IF NOT EXISTS `sports` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_global` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sport_org_name` (`organization_id`,`name`),
  KEY `idx_sport_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sports`
--

INSERT INTO `sports` (`id`, `organization_id`, `name`, `code`, `description`, `status`, `is_global`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'Football', 'FTB', 'Association Football', 'active', 1, '2026-09-20 13:48:33', '2026-09-20 13:48:33', NULL),
(2, NULL, 'Cricket', 'CRK', 'Cricket (T20, One-day, Multi-day)', 'active', 1, '2026-09-20 13:48:33', '2026-09-20 13:48:33', NULL),
(3, NULL, 'Basketball', 'BSK', '5v5 and 3x3 Basketball', 'active', 1, '2026-09-20 13:48:33', '2026-09-20 13:48:33', NULL),
(4, NULL, 'Badminton', 'BDM', 'Singles and Doubles Badminton', 'active', 1, '2026-09-20 13:48:33', '2026-09-20 13:48:33', NULL),
(5, NULL, 'Athletics', 'ATH', 'Track and Field disciplines', 'active', 1, '2026-09-20 13:48:33', '2026-09-20 13:48:33', NULL),
(6, NULL, 'Swimming', 'SWM', 'Competitive Aquatics and Swimming', 'active', 1, '2026-09-20 13:48:33', '2026-09-20 13:48:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sport_categories`
--

DROP TABLE IF EXISTS `sport_categories`;
CREATE TABLE IF NOT EXISTS `sport_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED DEFAULT NULL,
  `sport_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('male','female','mixed','open','not_specified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `min_age` smallint UNSIGNED DEFAULT NULL,
  `max_age` smallint UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sport_category` (`organization_id`,`sport_id`,`name`),
  KEY `fk_sport_cat_sport` (`sport_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `inventory_item_id` bigint UNSIGNED NOT NULL,
  `transaction_type` enum('opening','purchase','issue','return','adjustment','damage','loss','disposal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,2) NOT NULL,
  `unit_cost` decimal(14,2) DEFAULT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `performed_by` bigint UNSIGNED DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_item_date` (`inventory_item_id`,`transaction_date`),
  KEY `fk_stock_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
CREATE TABLE IF NOT EXISTS `teams` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `team_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sport_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `gender` enum('male','female','mixed','open','not_specified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `age_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `formation_or_level` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_org_code` (`organization_id`,`team_code`),
  KEY `fk_team_sport` (`sport_id`),
  KEY `fk_team_category` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_coaches`
--

DROP TABLE IF EXISTS `team_coaches`;
CREATE TABLE IF NOT EXISTS `team_coaches` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `coach_id` bigint UNSIGNED NOT NULL,
  `coach_role` enum('head_coach','assistant_coach','fitness_coach','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'head_coach',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_team_coach` (`team_id`,`coach_id`),
  KEY `fk_team_coach_org` (`organization_id`),
  KEY `fk_team_coach_coach` (`coach_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

DROP TABLE IF EXISTS `team_members`;
CREATE TABLE IF NOT EXISTS `team_members` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED NOT NULL,
  `jersey_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `member_role` enum('player','captain','vice_captain','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'player',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_team_member_current` (`team_id`,`is_current`),
  KEY `idx_athlete_team_history` (`athlete_id`,`start_date`),
  KEY `fk_team_member_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

DROP TABLE IF EXISTS `tournaments`;
CREATE TABLE IF NOT EXISTS `tournaments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `tournament_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sport_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `tournament_level_id` bigint UNSIGNED DEFAULT NULL,
  `tournament_format_id` bigint UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `organizer_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `rules` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','registration_open','registration_closed','ongoing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tournament_reference` (`organization_id`,`tournament_reference`),
  KEY `idx_tournament_dates` (`organization_id`,`start_date`,`end_date`),
  KEY `fk_tournament_sport` (`sport_id`),
  KEY `fk_tournament_category` (`category_id`),
  KEY `fk_tournament_level` (`tournament_level_id`),
  KEY `fk_tournament_format` (`tournament_format_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_formats`
--

DROP TABLE IF EXISTS `tournament_formats`;
CREATE TABLE IF NOT EXISTS `tournament_formats` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournament_formats`
--

INSERT INTO `tournament_formats` (`id`, `name`, `description`, `status`) VALUES
(1, 'Knockout', 'Single elimination tournament bracket', 'active'),
(2, 'League', 'Double round-robin or single league table', 'active'),
(3, 'Round Robin', 'All teams play every other team in group', 'active'),
(4, 'Group + Knockout', 'Group stage followed by playoff knockouts', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_levels`
--

DROP TABLE IF EXISTS `tournament_levels`;
CREATE TABLE IF NOT EXISTS `tournament_levels` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tournament_levels`
--

INSERT INTO `tournament_levels` (`id`, `name`, `description`, `status`) VALUES
(1, 'School', 'Inter-school or intra-school competitions', 'active'),
(2, 'District', 'District level championships', 'active'),
(3, 'State', 'State level tournaments and trials', 'active'),
(4, 'National', 'National federation championships', 'active'),
(5, 'International', 'International invitationals and series', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_standings`
--

DROP TABLE IF EXISTS `tournament_standings`;
CREATE TABLE IF NOT EXISTS `tournament_standings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `played` int UNSIGNED NOT NULL DEFAULT '0',
  `won` int UNSIGNED NOT NULL DEFAULT '0',
  `drawn` int UNSIGNED NOT NULL DEFAULT '0',
  `lost` int UNSIGNED NOT NULL DEFAULT '0',
  `points` decimal(10,2) NOT NULL DEFAULT '0.00',
  `scored` decimal(12,2) NOT NULL DEFAULT '0.00',
  `conceded` decimal(12,2) NOT NULL DEFAULT '0.00',
  `difference` decimal(12,2) NOT NULL DEFAULT '0.00',
  `rank_position` int UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_standing_team` (`tournament_id`,`team_id`),
  KEY `fk_standing_team` (`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_teams`
--

DROP TABLE IF EXISTS `tournament_teams`;
CREATE TABLE IF NOT EXISTS `tournament_teams` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seed_number` int UNSIGNED DEFAULT NULL,
  `group_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('registered','approved','withdrawn','eliminated','qualified','winner','runner_up') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `registered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tournament_team` (`tournament_id`,`team_id`),
  KEY `fk_tt_team` (`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_venues`
--

DROP TABLE IF EXISTS `tournament_venues`;
CREATE TABLE IF NOT EXISTS `tournament_venues` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` bigint UNSIGNED NOT NULL,
  `venue_id` bigint UNSIGNED NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tournament_venue` (`tournament_id`,`venue_id`),
  KEY `fk_tv_venue` (`venue_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_attendance`
--

DROP TABLE IF EXISTS `training_attendance`;
CREATE TABLE IF NOT EXISTS `training_attendance` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `training_session_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `attendance_status` enum('present','absent','late','excused') COLLATE utf8mb4_unicode_ci NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_training_attendee` (`training_session_id`,`athlete_id`,`coach_id`,`employee_id`),
  KEY `idx_training_attendance_athlete` (`athlete_id`),
  KEY `fk_ta_org` (`organization_id`),
  KEY `fk_ta_coach` (`coach_id`),
  KEY `fk_ta_employee` (`employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_camps`
--

DROP TABLE IF EXISTS `training_camps`;
CREATE TABLE IF NOT EXISTS `training_camps` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `camp_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sport_id` bigint UNSIGNED NOT NULL,
  `team_id` bigint UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `objectives` text COLLATE utf8mb4_unicode_ci,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('planned','active','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_camp_reference` (`organization_id`,`camp_reference`),
  KEY `fk_camp_sport` (`sport_id`),
  KEY `fk_camp_team` (`team_id`),
  KEY `fk_camp_coach` (`coach_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_camp_participants`
--

DROP TABLE IF EXISTS `training_camp_participants`;
CREATE TABLE IF NOT EXISTS `training_camp_participants` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `training_camp_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `participant_role` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('registered','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_camp_participant` (`training_camp_id`,`athlete_id`,`employee_id`,`coach_id`),
  KEY `fk_tcp_athlete` (`athlete_id`),
  KEY `fk_tcp_employee` (`employee_id`),
  KEY `fk_tcp_coach` (`coach_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_sessions`
--

DROP TABLE IF EXISTS `training_sessions`;
CREATE TABLE IF NOT EXISTS `training_sessions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `training_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `team_id` bigint UNSIGNED NOT NULL,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `venue_id` bigint UNSIGNED DEFAULT NULL,
  `facility_id` bigint UNSIGNED DEFAULT NULL,
  `training_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `objectives` text COLLATE utf8mb4_unicode_ci,
  `training_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_training_reference` (`organization_id`,`training_reference`),
  KEY `fk_training_team` (`team_id`),
  KEY `fk_training_coach` (`coach_id`),
  KEY `fk_training_venue` (`venue_id`),
  KEY `fk_training_facility` (`facility_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_passengers`
--

DROP TABLE IF EXISTS `transport_passengers`;
CREATE TABLE IF NOT EXISTS `transport_passengers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transport_trip_id` bigint UNSIGNED NOT NULL,
  `athlete_id` bigint UNSIGNED DEFAULT NULL,
  `employee_id` bigint UNSIGNED DEFAULT NULL,
  `coach_id` bigint UNSIGNED DEFAULT NULL,
  `passenger_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drop_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('planned','boarded','dropped','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tp_trip` (`transport_trip_id`),
  KEY `fk_tp_athlete` (`athlete_id`),
  KEY `fk_tp_employee` (`employee_id`),
  KEY `fk_tp_coach` (`coach_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_trips`
--

DROP TABLE IF EXISTS `transport_trips`;
CREATE TABLE IF NOT EXISTS `transport_trips` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `event_id` bigint UNSIGNED DEFAULT NULL,
  `vehicle_id` bigint UNSIGNED NOT NULL,
  `trip_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trip_date` date NOT NULL,
  `departure_time` time DEFAULT NULL,
  `return_time` time DEFAULT NULL,
  `origin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_employee_id` bigint UNSIGNED DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transport_trip` (`organization_id`,`trip_reference`),
  KEY `fk_trip_event` (`event_id`),
  KEY `fk_trip_vehicle` (`vehicle_id`),
  KEY `fk_trip_driver` (`driver_employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','locked','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_user_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `uuid`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `profile_photo_path`, `status`, `last_login_at`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'd5b053ec-b4fc-11f1-afc2-345a608d6f9e', 'admin_demo', 'admin@khelsutra.com', '$2y$12$e0NZB7.O0gVv9bZ.hN8KTuE0t7N.e9W3oX1zM5yK9p7r8q0s1t2u3', 'Sports', 'Admin', '+919876543211', NULL, 'active', NULL, NULL, NULL, '2026-09-20 14:09:03', '2026-09-20 14:09:03', NULL),
(2, 'd5b05e9c-b4fc-11f1-afc2-345a608d6f9e', 'coach_rajesh', 'coach@khelsutra.com', '$2y$12$e0NZB7.O0gVv9bZ.hN8KTuE0t7N.e9W3oX1zM5yK9p7r8q0s1t2u3', 'Rajesh', 'Sharma', '+919876543212', NULL, 'active', NULL, NULL, NULL, '2026-09-20 14:09:03', '2026-09-20 14:09:03', NULL),
(3, 'd5b06099-b4fc-11f1-afc2-345a608d6f9e', 'athlete_aarav', 'athlete@khelsutra.com', '$2y$12$e0NZB7.O0gVv9bZ.hN8KTuE0t7N.e9W3oX1zM5yK9p7r8q0s1t2u3', 'Aarav', 'Patel', '+919876543213', NULL, 'active', NULL, NULL, NULL, '2026-09-20 14:09:03', '2026-09-20 14:09:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_permission_overrides`
--

DROP TABLE IF EXISTS `user_permission_overrides`;
CREATE TABLE IF NOT EXISTS `user_permission_overrides` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL,
  `override_type` enum('grant','deny') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_permission_override` (`organization_id`,`user_id`,`permission_id`),
  KEY `fk_upo_user` (`user_id`),
  KEY `fk_upo_permission` (`permission_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `vehicle_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `make` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` smallint UNSIGNED DEFAULT NULL,
  `capacity` int UNSIGNED DEFAULT NULL,
  `driver_employee_id` bigint UNSIGNED DEFAULT NULL,
  `insurance_expiry_date` date DEFAULT NULL,
  `registration_expiry_date` date DEFAULT NULL,
  `status` enum('available','assigned','maintenance','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vehicle_number` (`organization_id`,`vehicle_number`),
  KEY `fk_vehicle_driver` (`driver_employee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
CREATE TABLE IF NOT EXISTS `vendors` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `vendor_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alternate_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pan_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `bank_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_ifsc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','blacklisted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendor_code` (`organization_id`,`vendor_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_invoices`
--

DROP TABLE IF EXISTS `vendor_invoices`;
CREATE TABLE IF NOT EXISTS `vendor_invoices` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `vendor_id` bigint UNSIGNED NOT NULL,
  `purchase_order_id` bigint UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('unpaid','partially_paid','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendor_invoice` (`organization_id`,`vendor_id`,`invoice_number`),
  KEY `fk_vi_vendor` (`vendor_id`),
  KEY `fk_vi_po` (`purchase_order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

DROP TABLE IF EXISTS `venues`;
CREATE TABLE IF NOT EXISTS `venues` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `venue_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `venue_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `capacity` int UNSIGNED DEFAULT NULL,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `status` enum('active','inactive','under_maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venue_org_code` (`organization_id`,`venue_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venue_bookings`
--

DROP TABLE IF EXISTS `venue_bookings`;
CREATE TABLE IF NOT EXISTS `venue_bookings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `venue_id` bigint UNSIGNED NOT NULL,
  `facility_id` bigint UNSIGNED DEFAULT NULL,
  `booking_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booked_by_user_id` bigint UNSIGNED NOT NULL,
  `booking_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `team_id` bigint UNSIGNED DEFAULT NULL,
  `event_id` bigint UNSIGNED DEFAULT NULL,
  `tournament_id` bigint UNSIGNED DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_reference` (`organization_id`,`booking_reference`),
  KEY `idx_booking_schedule` (`venue_id`,`facility_id`,`booking_date`,`start_time`,`end_time`),
  KEY `fk_booking_facility` (`facility_id`),
  KEY `fk_booking_user` (`booked_by_user_id`),
  KEY `fk_booking_team` (`team_id`),
  KEY `fk_booking_event` (`event_id`),
  KEY `fk_booking_tournament` (`tournament_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venue_facilities`
--

DROP TABLE IF EXISTS `venue_facilities`;
CREATE TABLE IF NOT EXISTS `venue_facilities` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `venue_id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `facility_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `capacity` int UNSIGNED DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('active','inactive','under_maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_facility_venue` (`venue_id`),
  KEY `fk_facility_org` (`organization_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venue_maintenance`
--

DROP TABLE IF EXISTS `venue_maintenance`;
CREATE TABLE IF NOT EXISTS `venue_maintenance` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` bigint UNSIGNED NOT NULL,
  `venue_id` bigint UNSIGNED NOT NULL,
  `facility_id` bigint UNSIGNED DEFAULT NULL,
  `maintenance_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_description` text COLLATE utf8mb4_unicode_ci,
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `reported_by` bigint UNSIGNED DEFAULT NULL,
  `assigned_employee_id` bigint UNSIGNED DEFAULT NULL,
  `assigned_vendor_id` bigint UNSIGNED DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `estimated_cost` decimal(14,2) DEFAULT '0.00',
  `actual_cost` decimal(14,2) DEFAULT '0.00',
  `status` enum('reported','assigned','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reported',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_maintenance_reference` (`organization_id`,`maintenance_reference`),
  KEY `fk_vm_venue` (`venue_id`),
  KEY `fk_vm_facility` (`facility_id`),
  KEY `fk_vm_employee` (`assigned_employee_id`),
  KEY `fk_vm_vendor` (`assigned_vendor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
