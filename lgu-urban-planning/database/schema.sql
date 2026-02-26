-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 04:29 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lgu_urban_planning`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `parcel_id` varchar(50) DEFAULT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_type` varchar(100) DEFAULT NULL,
  `project_description` text DEFAULT NULL,
  `lot_number` varchar(50) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `block` varchar(50) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('draft','submitted','under_review','for_revision','approved','rejected','cancelled') DEFAULT 'draft',
  `assigned_officer_id` int(11) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_type` enum('online','walk-in') DEFAULT 'online',
  `verified_latitude` decimal(10,8) DEFAULT NULL,
  `verified_longitude` decimal(11,8) DEFAULT NULL,
  `parcel_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `application_number`, `applicant_id`, `parcel_id`, `project_name`, `project_type`, `project_description`, `lot_number`, `barangay`, `street`, `block`, `latitude`, `longitude`, `status`, `assigned_officer_id`, `submitted_at`, `created_at`, `updated_at`, `record_type`, `verified_latitude`, `verified_longitude`, `parcel_details`) VALUES
(3, 'DP-2026-2903', 15, NULL, 'Testing', 'Residential', 'test', '6835', '177', NULL, NULL, 44.00000000, 999.99999999, 'under_review', 1, '2026-02-08 13:15:33', '2026-02-08 13:15:33', '2026-02-25 11:34:40', 'online', NULL, NULL, NULL),
(4, 'DP-2026-9253', 15, NULL, 'blah', 'Commercial', 'blah', '7216', '178', NULL, NULL, 99.99999999, 999.99999999, 'submitted', NULL, '2026-02-11 17:45:26', '2026-02-11 17:45:26', '2026-02-11 17:45:26', 'online', NULL, NULL, NULL),
(35, 'DP-2026-2180', 15, '114-05-002-01-001', 'Small Retail Convenience Store', 'Commercial', 'test', '1', 'Bagong Pag-asa', 'North Avenue', '2', 14.65330000, 121.03330000, 'approved', NULL, '2026-02-19 20:46:44', '2026-02-19 20:46:44', '2026-02-26 03:26:33', 'walk-in', NULL, NULL, NULL),
(37, 'DP-2026-4364', 15, '114-14-001-05-005', 'Proposed 2-Storey Residence', 'Residential', 'try', '5', 'South Triangle', 'Sgt. Esguerra Ave', '10', 14.63940000, 121.03470000, 'approved', NULL, '2026-02-24 02:42:40', '2026-02-24 02:42:40', '2026-02-26 03:19:18', 'online', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `application_documents`
--

CREATE TABLE `application_documents` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_documents`
--

INSERT INTO `application_documents` (`id`, `application_id`, `document_type`, `file_name`, `file_path`, `file_size`, `mime_type`, `version`, `uploaded_by`, `created_at`) VALUES
(3, 3, 'ownership_proof', 'thumb-1920-1355194.jpeg', 'documents/69888c758d19c_1770556533.jpeg', 302872, 'image/jpeg', 1, 15, '2026-02-08 13:15:33'),
(4, 4, 'lot_plan', '#kenma#haikyuu#anime#pfp#icon.jpg', 'documents/698cc036a73d8_1770831926.jpg', 86486, 'image/jpeg', 1, 15, '2026-02-11 17:45:26'),
(6, 37, 'site_plan', 'residential-plan.jpg', 'documents/699d102051d3a_1771900960.jpg', 8873, 'image/jpeg', 1, 15, '2026-02-24 02:42:40');

-- --------------------------------------------------------

--
-- Table structure for table `application_status_history`
--

CREATE TABLE `application_status_history` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `remarks` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_status_history`
--

INSERT INTO `application_status_history` (`id`, `application_id`, `status`, `remarks`, `changed_by`, `created_at`, `created_by`) VALUES
(108, 35, 'zoning_verified', 'GIS Verification: COMPLIANT (Zone: Major Commercial (C-2))', 1, '2026-02-23 05:19:32', NULL),
(109, 37, 'submitted', 'Application submitted by applicant', 15, '2026-02-24 02:42:40', NULL),
(112, 37, 'under_review', 'test1', 1, '2026-02-24 03:47:59', NULL),
(113, 37, 'for_revision', 'test2', 1, '2026-02-24 03:48:36', NULL),
(114, 37, 'zoning_verified', 'GIS Verification: COMPLIANT (Zone: Major Commercial (C-2))', 1, '2026-02-25 05:11:33', NULL),
(115, 3, 'submitted', '.', 1, '2026-02-25 10:18:37', NULL),
(116, 3, 'under_review', '.', 1, '2026-02-25 11:07:42', NULL),
(117, 3, 'for_revision', 'test', 1, '2026-02-25 11:29:29', NULL),
(118, 3, 'under_review', 'testt', 1, '2026-02-25 11:34:40', NULL),
(119, 37, 'for_revision', 'Departmental simulations triggered for Roads & Energy.', 1, '2026-02-25 11:53:45', NULL),
(120, 35, 'zoning_verified', 'GIS Verification: COMPLIANT (Zone: Major Commercial (C-2))', 1, '2026-02-25 11:55:38', NULL),
(121, 37, 'for_revision', 'GIS Verification: COMPLIANT (Zone: Major Commercial (C-2))', 1, '2026-02-25 14:52:00', NULL),
(122, 37, 'approved', 'Monitoring Test', 1, '2026-02-26 00:05:34', NULL),
(123, 37, 'approved', 'test1', 1, '2026-02-26 01:18:48', NULL),
(124, 35, 'approved', 'test101', 1, '2026-02-26 02:07:44', NULL),
(125, 37, 'approved', 'test102', 1, '2026-02-26 02:10:58', NULL),
(126, 35, 'approved', 'test103', 1, '2026-02-26 02:14:19', NULL),
(127, 37, 'approved', 'test104', 1, '2026-02-26 02:22:32', NULL),
(128, 35, 'approved', 'test105', 1, '2026-02-26 02:24:38', NULL),
(129, 37, 'approved', 'test106', 1, '2026-02-26 02:31:00', NULL),
(130, 37, 'approved', '107', 1, '2026-02-26 02:33:44', NULL),
(131, 37, 'approved', '107', 1, '2026-02-26 02:41:18', NULL),
(132, 35, 'approved', '108', 1, '2026-02-26 02:51:13', NULL),
(133, 37, 'approved', '123', 1, '2026-02-26 02:58:03', NULL),
(134, 37, 'approved', '124', 1, '2026-02-26 03:04:55', NULL),
(135, 37, 'approved', '125', 1, '2026-02-26 03:09:30', NULL),
(136, 37, 'approved', '126', 1, '2026-02-26 03:15:24', NULL),
(137, 37, 'approved', '127', 1, '2026-02-26 03:19:18', NULL),
(138, 35, 'approved', '15685', 1, '2026-02-26 03:26:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 19:10:14'),
(2, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 19:31:02'),
(3, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:41:28'),
(4, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:45:30'),
(5, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:45:38'),
(6, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:45:42'),
(7, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:45:47'),
(8, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:49:37'),
(9, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:49:44'),
(10, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:49:48'),
(11, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:50:01'),
(12, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 17:51:07'),
(13, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 19:00:00'),
(14, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 19:00:21'),
(15, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 20:19:29'),
(16, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 20:19:35'),
(17, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 21:07:19'),
(18, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 21:07:31'),
(19, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 13:36:37'),
(20, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 13:37:06'),
(21, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 13:48:16'),
(22, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 13:48:31'),
(23, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 13:56:33'),
(24, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:09:11'),
(25, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:09:22'),
(26, NULL, 'submit_application', 'application', 1, 'Submitted application: DP-2025-2969', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:11:17'),
(27, NULL, 'upload_document', 'application', 1, 'Uploaded document: ownership_proof', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:11:17'),
(28, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:12:35'),
(29, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:12:40'),
(30, 1, 'update_application_status', 'application', 1, 'Updated status to: rejected', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:16:52'),
(31, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:17:45'),
(32, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:17:57'),
(33, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:18:31'),
(34, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-23 14:18:37'),
(35, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 16:08:09'),
(36, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 16:12:09'),
(37, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 16:15:37'),
(38, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 16:16:47'),
(39, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 16:16:53'),
(40, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 16:22:03'),
(41, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:00:02'),
(42, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:00:40'),
(43, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:00:54'),
(44, 1, 'generate_report', 'report', 1, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:11:21'),
(45, 1, 'generate_report', 'report', 2, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:16:56'),
(46, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:32:33'),
(47, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:32:42'),
(48, NULL, 'submit_application', 'application', 2, 'Submitted application: DP-2025-5500', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:33:57'),
(49, NULL, 'upload_document', 'application', 2, 'Uploaded document: ownership_proof', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:33:57'),
(50, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:34:14'),
(51, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 19:34:48'),
(52, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 13:25:01'),
(53, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 13:43:03'),
(54, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 13:43:12'),
(55, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 17:33:35'),
(56, 1, 'update_application_status', 'application', 1, 'Updated status to: rejected', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 17:50:24'),
(57, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 19:03:56'),
(58, NULL, 'login', 'user', 3, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 19:28:40'),
(59, NULL, 'logout', 'user', 3, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 19:28:45'),
(60, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-27 19:28:50'),
(61, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 06:41:03'),
(62, 1, 'deactivate_user', 'user', 3, 'Deactivated user ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:42:14'),
(63, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:42:23'),
(64, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:42:38'),
(65, 1, 'activate_user', 'user', 3, 'Activated user ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:42:41'),
(66, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:42:43'),
(67, NULL, 'login', 'user', 3, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:42:49'),
(68, NULL, 'logout', 'user', 3, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:42:51'),
(69, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 07:42:59'),
(70, 1, 'password_reset_triggered', 'user', 3, 'Reset link generated for: unknownfire01@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:10:39'),
(71, 1, 'password_reset_triggered', 'user', 3, 'Reset link generated for: unknownfire01@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:10:48'),
(72, 1, 'update_user', 'user', 3, 'Updated user ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:26:02'),
(73, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:26:05'),
(74, NULL, 'login', 'user', 3, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:26:20'),
(75, NULL, 'logout', 'user', 3, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:26:24'),
(76, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 08:26:31'),
(77, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 16:04:58'),
(78, 1, 'verify_identity', 'user', 3, 'Rejected Identity: Blurry or Unreadable ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 16:24:04'),
(79, 1, 'verify_identity', 'user', 3, 'Approved Identity', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 16:27:00'),
(80, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 17:14:03'),
(81, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 17:14:15'),
(82, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 17:14:28'),
(83, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 17:14:33'),
(84, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 15:08:41'),
(85, 1, 'generate_report', 'report', 3, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 15:31:21'),
(86, 1, 'generate_report', 'report', 4, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 15:50:18'),
(87, 1, 'generate_report', 'report', 5, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 15:50:39'),
(88, 1, 'generate_report', 'report', 6, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 15:51:46'),
(89, 1, 'generate_report', 'report', 7, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 15:52:00'),
(90, 1, 'generate_report', 'report', 8, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 15:52:22'),
(91, 1, 'generate_report', 'report', 9, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 15:57:11'),
(92, 1, 'generate_report', 'report', 10, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:02:50'),
(93, 1, 'generate_report', 'report', 11, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:03:07'),
(94, 1, 'generate_report', 'report', 12, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:03:26'),
(95, 1, 'generate_report', 'report', 13, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:03:39'),
(96, 1, 'generate_report', 'report', 14, 'Generated report: monthly_analytics', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:03:51'),
(97, 1, 'generate_report', 'report', 15, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:04:11'),
(98, 1, 'generate_report', 'report', 16, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:06:44'),
(99, 1, 'generate_report', 'report', 17, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:11:28'),
(100, 1, 'generate_report', 'report', 18, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:11:35'),
(101, 1, 'generate_report', 'report', 19, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:11:39'),
(102, 1, 'update_application_status', 'application', 1, 'Updated status to: approved', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:14:31'),
(103, 1, 'generate_report', 'report', 20, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:14:47'),
(104, 1, 'generate_report', 'report', 21, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:14:54'),
(105, 1, 'generate_report', 'report', 22, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:16:41'),
(106, 1, 'generate_report', 'report', 23, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:17:39'),
(107, 1, 'generate_report', 'report', 24, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:18:19'),
(108, 1, 'generate_report', 'report', 25, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:18:46'),
(109, 1, 'generate_report', 'report', 26, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:18:54'),
(110, 1, 'generate_report', 'report', 27, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:19:03'),
(111, 1, 'generate_report', 'report', 28, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:19:14'),
(112, 1, 'generate_report', 'report', 29, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:22:40'),
(113, 1, 'generate_report', 'report', 30, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:22:55'),
(114, 1, 'generate_report', 'report', 31, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:23:09'),
(115, 1, 'generate_report', 'report', 32, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:23:14'),
(116, 1, 'generate_report', 'report', 33, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:23:20'),
(117, 1, 'generate_report', 'report', 34, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:23:38'),
(118, 1, 'generate_report', 'report', 35, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:25:15'),
(119, 1, 'generate_report', 'report', 36, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:28:53'),
(120, 1, 'generate_report', 'report', 37, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:29:03'),
(121, 1, 'generate_report', 'report', 38, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:29:06'),
(122, 1, 'generate_report', 'report', 39, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:29:17'),
(123, 1, 'generate_report', 'report', 40, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:29:34'),
(124, 1, 'generate_report', 'report', 41, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:29:46'),
(125, 1, 'generate_report', 'report', 42, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:31:11'),
(126, 1, 'generate_report', 'report', 43, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:31:29'),
(127, 1, 'generate_report', 'report', 44, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:32:38'),
(128, 1, 'generate_report', 'report', 45, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:32:46'),
(129, 1, 'generate_report', 'report', 46, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:32:50'),
(130, 1, 'generate_report', 'report', 47, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:33:18'),
(131, 1, 'generate_report', 'report', 48, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:33:26'),
(132, 1, 'generate_report', 'report', 49, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:33:48'),
(133, 1, 'generate_report', 'report', 50, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:36:34'),
(134, 1, 'generate_report', 'report', 51, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:36:46'),
(135, 1, 'generate_report', 'report', 52, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:36:52'),
(136, 1, 'generate_report', 'report', 53, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:36:54'),
(137, 1, 'generate_report', 'report', 54, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:36:57'),
(138, 1, 'generate_report', 'report', 55, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:38:01'),
(139, 1, 'generate_report', 'report', 56, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:41:11'),
(140, 1, 'generate_report', 'report', 57, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:42:03'),
(141, 1, 'generate_report', 'report', 58, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:42:13'),
(142, 1, 'generate_report', 'report', 59, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:44:34'),
(143, 1, 'generate_report', 'report', 60, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:44:45'),
(144, 1, 'generate_report', 'report', 61, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:44:48'),
(145, 1, 'generate_report', 'report', 62, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:45:36'),
(146, 1, 'generate_report', 'report', 63, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:45:41'),
(147, 1, 'generate_report', 'report', 64, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:46:54'),
(148, 1, 'generate_report', 'report', 65, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:47:10'),
(149, 1, 'generate_report', 'report', 66, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:47:13'),
(150, 1, 'generate_report', 'report', 67, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:47:20'),
(151, 1, 'generate_report', 'report', 68, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:47:23'),
(152, 1, 'generate_report', 'report', 69, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:47:27'),
(153, 1, 'generate_report', 'report', 70, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:48:47'),
(154, 1, 'generate_report', 'report', 71, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:50:08'),
(155, 1, 'generate_report', 'report', 72, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:50:11'),
(156, 1, 'generate_report', 'report', 73, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:51:58'),
(157, 1, 'generate_report', 'report', 74, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:52:07'),
(158, 1, 'generate_report', 'report', 75, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:53:43'),
(159, 1, 'generate_report', 'report', 76, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:53:48'),
(160, 1, 'generate_report', 'report', 77, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:55:50'),
(161, 1, 'generate_report', 'report', 78, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:58:14'),
(162, 1, 'generate_report', 'report', 79, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:58:21'),
(163, 1, 'generate_report', 'report', 80, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:58:24'),
(164, 1, 'generate_report', 'report', 81, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 16:59:13'),
(165, 1, 'generate_report', 'report', 82, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 17:01:32'),
(166, 1, 'generate_report', 'report', 83, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 17:01:48'),
(167, 1, 'generate_report', 'report', 84, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 17:03:19'),
(168, 1, 'generate_report', 'report', 85, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 17:04:08'),
(169, 1, 'generate_report', 'report', 86, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-29 17:06:53'),
(170, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 17:24:46'),
(171, 1, 'generate_report', 'report', 87, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 18:02:53'),
(172, 1, 'generate_report', 'report', 88, 'Generated report: permits_issued', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 18:03:11'),
(173, 1, 'generate_report', 'report', 89, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 18:03:21'),
(174, 1, 'generate_report', 'report', 90, 'Generated report: monthly_analytics', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 18:04:33'),
(175, 1, 'generate_report', 'report', 91, 'Generated report: zoning_compliance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 18:05:50'),
(176, 1, 'generate_report', 'report', 92, 'Generated report: applications_summary', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 18:05:58'),
(177, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 18:57:13'),
(178, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 18:57:20'),
(179, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-01 16:39:09'),
(180, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-01 21:11:25'),
(181, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 07:54:32'),
(182, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:12:25'),
(183, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:12:38'),
(184, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:12:47'),
(185, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:12:53'),
(186, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:19:42'),
(187, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:19:47'),
(188, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:45:10'),
(189, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:45:16'),
(190, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:48:25'),
(191, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:48:30'),
(192, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:48:56'),
(193, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:49:02'),
(194, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:52:25'),
(195, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:52:31'),
(196, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:54:19'),
(197, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 08:57:04'),
(198, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:15:00'),
(199, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:15:08'),
(200, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Missing back part of the ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:23:04'),
(201, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:34:58'),
(202, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:42:10'),
(203, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Missing back part of the ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:46:19'),
(204, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:46:24'),
(205, 1, 'verify_identity', 'user', 2, 'Approved Identity', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:48:19'),
(206, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:48:27'),
(207, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Missing back part of the ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:52:30'),
(208, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:52:44'),
(209, 1, 'verify_identity', 'user', 2, 'Approved Identity', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:54:52'),
(210, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:55:03'),
(211, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Blurry or Unreadable ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:55:34'),
(212, 1, 'verify_identity', 'user', 2, 'Approved Identity', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:56:40'),
(213, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:06:33'),
(214, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:06:41'),
(215, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Missing back part of the ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:17:51'),
(216, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:18:27'),
(217, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:23:23'),
(218, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:29:40'),
(219, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:29:46'),
(220, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:32:06'),
(221, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:32:11'),
(222, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Blurry or Unreadable ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:32:30'),
(223, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:32:39'),
(224, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:32:46'),
(225, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:49:46'),
(226, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:50:00'),
(227, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Blurry or Unreadable ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:52:59'),
(228, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:53:01'),
(229, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:53:05'),
(230, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:53:24'),
(231, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:58:33'),
(232, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:58:40'),
(233, 1, 'verify_identity', 'user', 2, 'Approved Identity', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 12:07:28'),
(234, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Expired Identification Card', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 12:12:12'),
(235, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Blurry or Unreadable ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 12:12:19'),
(236, 1, 'verify_identity', 'user', 2, 'Rejected Identity: Blurry or Unreadable ID', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 12:12:26'),
(237, 1, 'verify_identity', 'user', 2, 'Approved Identity', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 12:13:53'),
(238, 1, 'verify_identity', 'user', 2, 'Approved Identity', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 12:19:11');
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(239, 1, 'verify_identity', 'user', 2, 'Approved Identity', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 12:19:53'),
(240, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 13:26:19'),
(241, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 13:26:23'),
(242, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 13:26:29'),
(243, NULL, 'send_message', 'message', 17, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 13:44:27'),
(244, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 14:15:17'),
(245, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 14:15:23'),
(246, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 14:22:33'),
(247, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 14:22:40'),
(248, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 14:44:56'),
(249, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 14:45:01'),
(250, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 14:53:29'),
(251, NULL, 'send_message', 'message', 22, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:01:52'),
(252, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:17:48'),
(253, NULL, 'login', 'user', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:17:55'),
(254, NULL, 'logout', 'user', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:23:07'),
(255, 1, 'login', 'user', 1, 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 05:17:06'),
(256, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 05:23:04'),
(257, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 05:24:31'),
(258, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 05:30:36'),
(259, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 05:30:40'),
(260, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 05:32:41'),
(261, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 05:36:40'),
(262, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 05:36:48'),
(263, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 07:15:09'),
(264, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 07:18:59'),
(265, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 07:19:17'),
(266, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 07:19:32'),
(267, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 07:19:37'),
(268, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 07:20:05'),
(269, NULL, 'login', 'user', 7, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 14:31:25'),
(270, NULL, 'logout', 'user', 7, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 14:31:33'),
(271, NULL, 'login', 'user', 13, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 15:50:45'),
(272, NULL, 'logout', 'user', 13, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 15:50:48'),
(273, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:45:14'),
(274, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:45:44'),
(275, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:53:04'),
(276, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 12:53:07'),
(277, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:11:35'),
(278, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:11:40'),
(279, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:13:33'),
(280, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:13:44'),
(281, 15, 'submit_application', 'application', 3, 'Submitted application: DP-2026-2903', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:15:33'),
(282, 15, 'upload_document', 'application', 3, 'Uploaded document: ownership_proof', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:15:33'),
(283, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:15:35'),
(284, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 13:15:42'),
(285, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-08 15:59:58'),
(286, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:17:35'),
(287, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:26:06'),
(288, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:26:20'),
(289, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:32:58'),
(290, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:33:03'),
(291, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 03:34:59'),
(292, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 15:27:00'),
(293, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 15:27:12'),
(294, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 15:27:22'),
(295, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 15:27:27'),
(296, 1, 'request_inspection', 'application', 3, 'Sent inspection request to Roads and Energy groups', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 16:20:35'),
(297, 1, 'request_inspection', 'application', 3, 'Sent inspection request to Roads and Energy groups', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 16:20:42'),
(298, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 14:32:41'),
(299, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 17:10:23'),
(300, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 17:25:17'),
(301, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 17:44:35'),
(302, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 17:44:41'),
(303, 15, 'submit_application', 'application', 4, 'Submitted application: DP-2026-9253', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 17:45:26'),
(304, 15, 'upload_document', 'application', 4, 'Uploaded document: lot_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 17:45:26'),
(305, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 17:45:29'),
(306, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 17:45:38'),
(307, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:16:26'),
(308, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 14:00:42'),
(309, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 14:32:28'),
(310, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 17:30:44'),
(311, 1, 'request_inspection', 'application', 6, 'Sent inspection request to Roads and Energy groups', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 18:46:30'),
(312, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 16:07:19'),
(313, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-14 17:25:56'),
(314, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 16:39:13'),
(315, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 19:12:57'),
(316, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-16 19:14:16'),
(317, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-17 12:05:56'),
(318, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-17 13:48:51'),
(319, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 15:16:08'),
(320, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 17:10:54'),
(321, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 20:21:18'),
(322, 1, 'update_application_status', 'application', 3, 'Updated status to: under_review', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 21:27:34'),
(323, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 01:24:27'),
(324, 1, 'update_application_status', 'application', 3, 'Updated status to: under_review', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 01:30:09'),
(325, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 11:03:57'),
(326, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 14:06:46'),
(327, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 20:35:59'),
(328, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 21:33:44'),
(329, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 21:33:55'),
(330, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 21:35:07'),
(331, 15, 'submit_application', 'application', 36, 'Submitted application: DP-2026-2687', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 22:05:13'),
(332, 15, 'upload_document', 'application', 36, 'Uploaded document: lot_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 22:05:13'),
(333, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 16:01:00'),
(334, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 16:04:08'),
(335, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 16:06:25'),
(336, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 16:06:30'),
(337, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-21 16:06:42'),
(338, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 14:10:15'),
(339, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 18:48:45'),
(340, NULL, 'login', 'user', 16, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 19:55:11'),
(341, NULL, 'logout', 'user', 16, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 20:01:11'),
(342, NULL, 'login', 'user', 16, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 20:01:29'),
(343, NULL, 'logout', 'user', 16, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 20:24:53'),
(344, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 20:26:47'),
(345, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:26:52'),
(346, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:28:23'),
(347, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:28:29'),
(348, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:28:53'),
(349, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:29:01'),
(350, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:35:21'),
(351, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:35:31'),
(352, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:37:23'),
(353, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:37:33'),
(354, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:40:30'),
(355, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:40:37'),
(356, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:40:37'),
(357, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:40:40'),
(358, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:40:48'),
(359, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:43:35'),
(360, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 21:43:47'),
(361, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 22:05:13'),
(362, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 22:16:41'),
(363, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 22:16:57'),
(364, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 22:26:07'),
(365, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-22 22:26:38'),
(366, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 02:33:57'),
(367, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 04:11:44'),
(368, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 04:12:35'),
(369, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 04:12:43'),
(370, NULL, 'login', 'user', 18, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 04:16:10'),
(371, NULL, 'logout', 'user', 18, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 04:18:25'),
(372, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 04:18:37'),
(373, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 04:43:37'),
(374, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:10:56'),
(375, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:14:26'),
(376, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:14:30'),
(377, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 02:03:00'),
(378, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 02:13:10'),
(379, 15, 'submit_application', 'applications', 37, 'Submitted application #DP-2026-4364', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 02:42:40'),
(380, 15, 'upload_document', 'application', 37, 'Uploaded document: site_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 02:42:40'),
(381, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 13:25:18'),
(382, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 03:33:38'),
(383, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 03:34:02'),
(384, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 03:34:11'),
(385, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 03:37:02'),
(386, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 03:37:07'),
(387, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:06:37'),
(388, 19, 'login', 'user', 19, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:16:47'),
(389, 19, 'logout', 'user', 19, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:24:28'),
(390, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:24:39'),
(391, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:31:58'),
(392, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:32:29'),
(393, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:33:06'),
(394, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:33:17'),
(395, 15, 'logout', 'user', 15, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:38:27'),
(396, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 04:38:36'),
(397, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 05:01:42'),
(398, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 10:13:42'),
(399, 15, 'login', 'user', 15, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 10:44:30'),
(400, 15, 'send_message', 'message', 34, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 10:57:07'),
(401, 15, 'send_message', 'message', 35, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:03:36'),
(402, 15, 'send_message', 'message', 37, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:14:35'),
(403, 15, 'send_message', 'message', 38, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:14:55'),
(404, 15, 'send_message', 'message', 39, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:22:52'),
(405, 15, 'send_message', 'message', 40, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:24:07'),
(406, 15, 'send_message', 'message', 41, 'Sent message to user ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:25:22'),
(407, 1, 'logout', 'user', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 18:04:08'),
(408, 17, 'login', 'user', 17, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 18:04:18'),
(409, 17, 'logout', 'user', 17, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 18:05:21'),
(410, 1, 'login', 'user', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 19:07:38');

-- --------------------------------------------------------

--
-- Table structure for table `gis_layers`
--

CREATE TABLE `gis_layers` (
  `id` int(11) NOT NULL,
  `layer_name` varchar(100) NOT NULL,
  `layer_type` enum('zoning','land_use','hazard','parcel','other') NOT NULL,
  `layer_data` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `impact_assessments`
--

CREATE TABLE `impact_assessments` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `traffic_score` decimal(10,2) DEFAULT NULL,
  `traffic_flag` enum('ok','high') DEFAULT 'ok',
  `traffic_notes` text DEFAULT NULL,
  `energy_score` decimal(10,2) DEFAULT NULL,
  `energy_flag` enum('ok','high') DEFAULT 'ok',
  `energy_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assessed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `checked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `impact_assessments`
--

INSERT INTO `impact_assessments` (`id`, `application_id`, `traffic_score`, `traffic_flag`, `traffic_notes`, `energy_score`, `energy_flag`, `energy_notes`, `notes`, `assessed_by`, `created_at`, `checked_at`) VALUES
(5, 3, 90.00, 'high', NULL, 78.00, 'high', NULL, 'Auto assessment (mock): Traffic 90, Energy 78', 1, '2026-02-10 15:44:17', NULL),
(6, 3, NULL, '', NULL, NULL, '', NULL, 'Inspection requested. Waiting for departmental reports.', 1, '2026-02-10 16:20:35', NULL),
(7, 3, NULL, '', NULL, NULL, '', NULL, 'Inspection requested. Waiting for departmental reports.', 1, '2026-02-10 16:20:42', NULL),
(9, 37, NULL, 'ok', 'AUTOMATED SIMULATION: Traffic impact study completed. Proposed project entrance meets road safety standards. No major congestion expected.', NULL, 'ok', 'AUTOMATED SIMULATION: Grid capacity verified. Local transformer can handle the projected electrical load of the new development.', NULL, NULL, '2026-02-25 11:53:45', '2026-02-25 19:53:45');

-- --------------------------------------------------------

--
-- Table structure for table `inspections`
--

CREATE TABLE `inspections` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `inspector_id` int(11) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','violation') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspections`
--

INSERT INTO `inspections` (`id`, `application_id`, `scheduled_at`, `inspector_id`, `status`, `notes`, `created_at`) VALUES
(59, 37, '2026-02-26 11:19:00', 1, 'completed', '\nChecklist Completed: Compliant with all requirements.\nChecklist Completed: Compliant with all requirements.\nChecklist Completed: Compliant with all requirements.', '2026-02-26 03:19:18'),
(60, 35, '2026-02-26 11:26:00', 1, 'completed', '\nChecklist Completed: Compliant with all requirements.', '2026-02-26 03:26:33');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `message_type` enum('notification','message','system') DEFAULT 'message',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `application_id`, `sender_id`, `receiver_id`, `subject`, `message`, `is_read`, `message_type`, `created_at`) VALUES
(72, 37, 1, 15, 'CONGRATULATIONS: Approved Locational Clearance / Permit #DP-2026-4364', 'Dear Applicant,\n\nWe are pleased to inform you that your application for \'Proposed 2-Storey Residence\' has been officially APPROVED.\n\nYour Locational Clearance / Permit is now attached to this record. You may download and print the official document directly from the \'Documents\' section of your applicant portal.\n\nPermit Details:\n- Permit No: DP-2026-4364\n- Location: Barangay South Triangle\n\nOffice Remarks:\n\"123\"\n\nThank you for your cooperation.\n\nQuezon City Urban Planning Department', 0, '', '2026-02-26 02:58:03'),
(74, 37, 1, 15, 'CONGRATULATIONS: Approved Locational Clearance / Permit #DP-2026-4364', 'Dear Applicant,\n\nWe are pleased to inform you that your application for \'Proposed 2-Storey Residence\' has been officially APPROVED.\n\nYour Locational Clearance / Permit is now attached to this record. You may download and print the official document directly from the \'Documents\' section of your applicant portal.\n\nPermit Details:\n- Permit No: DP-2026-4364\n- Location: Barangay South Triangle\n\nOffice Remarks:\n\"124\"\n\nThank you for your cooperation.\n\nQuezon City Urban Planning Department', 0, '', '2026-02-26 03:04:55'),
(76, 37, 1, 15, 'CONGRATULATIONS: Approved Locational Clearance / Permit #DP-2026-4364', 'Dear Applicant,\n\nWe are pleased to inform you that your application for \'Proposed 2-Storey Residence\' has been officially APPROVED.\n\nYour Locational Clearance / Permit is now attached to this record. You may download and print the official document directly from the \'Documents\' section of your applicant portal.\n\nPermit Details:\n- Permit No: DP-2026-4364\n- Location: Barangay South Triangle\n\nOffice Remarks:\n\"125\"\n\nThank you for your cooperation.\n\nQuezon City Urban Planning Department', 0, '', '2026-02-26 03:09:30'),
(78, 37, 1, 15, 'CONGRATULATIONS: Approved Locational Clearance / Permit #DP-2026-4364', 'Dear Applicant,\n\nWe are pleased to inform you that your application for \'Proposed 2-Storey Residence\' has been officially APPROVED.\n\nYour Locational Clearance / Permit is now attached to this record. You may download and print the official document directly from the \'Documents\' section of your applicant portal.\n\nPermit Details:\n- Permit No: DP-2026-4364\n- Location: Barangay South Triangle\n\nOffice Remarks:\n\"126\"\n\nThank you for your cooperation.\n\nQuezon City Urban Planning Department', 0, '', '2026-02-26 03:15:24'),
(80, 37, 1, 15, 'CONGRATULATIONS: Approved Locational Clearance / Permit #DP-2026-4364', 'Dear Applicant,\n\nWe are pleased to inform you that your application for \'Proposed 2-Storey Residence\' has been officially APPROVED.\n\nYour Locational Clearance / Permit is now attached to this record. You may download and print the official document directly from the \'Documents\' section of your applicant portal.\n\nPermit Details:\n- Permit No: DP-2026-4364\n- Location: Barangay South Triangle\n\nOffice Remarks:\n\"127\"\n\nThank you for your cooperation.\n\nQuezon City Urban Planning Department', 0, '', '2026-02-26 03:19:18'),
(81, 37, 1, 15, 'OFFICIAL NOTICE: Inspection Schedule for App #37', 'Dear Applicant,\n\nThis is an official notification from the Building Official\'s Office. An onsite inspection for your application (#37) has been scheduled on February 26, 2026, 11:19 AM.\n\nRemarks: No specific instructions provided.\n\nPlease ensure that the project site is accessible and a representative is present during the visit. Thank you.', 0, 'message', '2026-02-26 03:19:40'),
(82, 35, 1, 15, 'CONGRATULATIONS: Approved Locational Clearance / Permit #DP-2026-2180', 'Dear Applicant,\n\nWe are pleased to inform you that your application for \'Small Retail Convenience Store\' has been officially APPROVED.\n\nYour Locational Clearance / Permit is now attached to this record. You may download and print the official document directly from the \'Documents\' section of your applicant portal.\n\nPermit Details:\n- Permit No: DP-2026-2180\n- Location: Barangay Bagong Pag-asa\n\nOffice Remarks:\n\"15685\"\n\nThank you for your cooperation.\n\nQuezon City Urban Planning Department', 0, '', '2026-02-26 03:26:33'),
(83, 35, 1, 15, 'OFFICIAL NOTICE: Inspection Schedule for App #35', 'Dear Applicant,\n\nThis is an official notification from the Building Official\'s Office. An onsite inspection for your application (#35) has been scheduled on February 26, 2026, 11:26 AM.\n\nRemarks: No specific instructions provided.\n\nPlease ensure that the project site is accessible and a representative is present during the visit. Thank you.', 0, 'message', '2026-02-26 03:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

CREATE TABLE `parcels` (
  `id` int(11) NOT NULL,
  `parcel_id` varchar(100) NOT NULL,
  `lot_number` varchar(50) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `area_sqm` decimal(12,2) DEFAULT NULL,
  `zoning_classification_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `geom_json` longtext DEFAULT NULL,
  `is_master_data` tinyint(1) DEFAULT 0,
  `zoning_name` varchar(100) DEFAULT NULL,
  `boundary_coordinates` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`id`, `parcel_id`, `lot_number`, `barangay`, `owner_name`, `area_sqm`, `zoning_classification_id`, `latitude`, `longitude`, `geom_json`, `is_master_data`, `zoning_name`, `boundary_coordinates`, `created_at`, `updated_at`) VALUES
(3, 'QC-MASTER-NORTH', 'MASTER-N', 'Novaliches', NULL, NULL, 1, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.03,14.70],[121.06,14.70],[121.06,14.75],[121.03,14.75],[121.03,14.70]]]},\"properties\":{\"zone_code\":\"R1\"}}', 0, 'Low Density Residential (R-1)', NULL, '2026-02-20 12:09:58', '2026-02-20 12:09:58'),
(4, 'QC-MASTER-CENTRAL', 'MASTER-C', 'Diliman', NULL, NULL, 4, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.02,14.63],[121.05,14.63],[121.05,14.68],[121.02,14.68],[121.02,14.63]]]},\"properties\":{\"zone_code\":\"C2\"}}', 0, 'Major Commercial (C-2)', NULL, '2026-02-20 12:09:58', '2026-02-20 12:09:58'),
(5, 'QC-MASTER-INST', 'MASTER-I', 'Central', NULL, NULL, 9, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.045,14.645],[121.055,14.645],[121.055,14.655],[121.045,14.655],[121.045,14.645]]]},\"properties\":{\"zone_code\":\"INST\"}}', 0, 'Institutional Zone', NULL, '2026-02-20 12:09:58', '2026-02-20 12:09:58'),
(6, 'QC-ZONE-R1', 'R1-01', 'Novaliches', NULL, NULL, 1, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.03,14.70],[121.06,14.70],[121.06,14.75],[121.03,14.75],[121.03,14.70]]]},\"properties\":{\"zone_code\":\"R1\"}}', 0, 'Low Density Residential (R-1)', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(7, 'QC-ZONE-R2', 'R2-01', 'Fairview', NULL, NULL, 2, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.05,14.68],[121.08,14.68],[121.08,14.72],[121.05,14.72],[121.05,14.68]]]},\"properties\":{\"zone_code\":\"R2\"}}', 0, 'Medium Density Residential (R-2)', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(8, 'QC-ZONE-R3', 'R3-01', 'Batasan Hills', NULL, NULL, 6, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.09,14.67],[121.11,14.67],[121.11,14.70],[121.09,14.70],[121.09,14.67]]]},\"properties\":{\"zone_code\":\"R-3\"}}', 0, 'High-Density Residential', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(9, 'QC-ZONE-C1', 'C1-01', 'Project 4', NULL, NULL, 3, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.06,14.62],[121.08,14.62],[121.08,14.65],[121.06,14.65],[121.06,14.62]]]},\"properties\":{\"zone_code\":\"C1\"}}', 0, 'Neighborhood Commercial (C-1)', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(10, 'QC-ZONE-C2', 'C2-01', 'Diliman', NULL, NULL, 4, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.02,14.63],[121.05,14.63],[121.05,14.68],[121.02,14.68],[121.02,14.63]]]},\"properties\":{\"zone_code\":\"C2\"}}', 0, 'Major Commercial (C-2)', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(11, 'QC-ZONE-C3', 'C3-01', 'Cubao', NULL, NULL, 7, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.04,14.61],[121.06,14.61],[121.06,14.63],[121.04,14.63],[121.04,14.61]]]},\"properties\":{\"zone_code\":\"C-3\"}}', 0, 'Metropolitan Commercial', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(12, 'QC-ZONE-I1', 'I1-01', 'Balintawak', NULL, NULL, 5, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.00,14.65],[121.02,14.65],[121.02,14.67],[121.00,14.67],[121.00,14.65]]]},\"properties\":{\"zone_code\":\"I1\"}}', 0, 'Light Industrial (I-1)', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(13, 'QC-ZONE-I2', 'I2-01', 'Talipapa', NULL, NULL, 8, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.01,14.68],[121.03,14.68],[121.03,14.70],[121.01,14.70],[121.01,14.68]]]},\"properties\":{\"zone_code\":\"I-2\"}}', 0, 'Medium Industrial', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(14, 'QC-ZONE-INST', 'INST-01', 'Central', NULL, NULL, 9, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.045,14.645],[121.055,14.645],[121.055,14.655],[121.045,14.655],[121.045,14.645]]]},\"properties\":{\"zone_code\":\"INST\"}}', 0, 'Institutional Zone', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(15, 'QC-ZONE-PRK', 'PRK-01', 'Vasra', NULL, NULL, 10, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.04,14.65],[121.05,14.65],[121.05,14.66],[121.04,14.66],[121.04,14.65]]]},\"properties\":{\"zone_code\":\"PRK\"}}', 0, 'Parks and Recreation', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05'),
(16, 'QC-ZONE-SCZ', 'SCZ-01', 'Payatas', NULL, NULL, 11, NULL, NULL, '{\"type\":\"Feature\",\"geometry\":{\"type\":\"Polygon\",\"coordinates\":[[[121.10,14.71],[121.13,14.71],[121.13,14.74],[121.10,14.74],[121.10,14.71]]]},\"properties\":{\"zone_code\":\"S-CZ\"}}', 0, 'Special Control Zone', NULL, '2026-02-20 12:14:05', '2026-02-20 12:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `permitted_uses`
--

CREATE TABLE `permitted_uses` (
  `id` int(11) NOT NULL,
  `zone_code` varchar(10) DEFAULT NULL,
  `project_type` varchar(100) DEFAULT NULL,
  `is_allowed` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permitted_uses`
--

INSERT INTO `permitted_uses` (`id`, `zone_code`, `project_type`, `is_allowed`) VALUES
(6, 'R1', 'Single-family dwelling', 1),
(7, 'R1', 'Duplex', 1),
(8, 'C1', 'Sari-sari Store', 1),
(9, 'C2', 'Malls', 1),
(12, 'R2', 'Multi-family dwellings', 1),
(13, 'R2', 'Residential condominiums', 1),
(14, 'R-3', 'High-rise residential buildings', 1),
(16, 'C1', 'Bakeries', 1),
(17, 'C1', 'Barber shops', 1),
(19, 'C2', 'Commercial', 1),
(20, 'C2', 'BPO Offices', 1),
(21, 'C2', 'Hotels', 1),
(22, 'C-3', 'Regional shopping centers', 1),
(23, 'C-3', 'Metropolitan Commercial', 1),
(24, 'I1', 'Light Industrial', 1),
(25, 'I1', 'Warehouses', 1),
(26, 'I-2', 'Medium Industrial', 1),
(27, 'I-2', 'Factories', 1),
(28, 'INST', 'Schools', 1),
(29, 'INST', 'Hospitals', 1),
(30, 'INST', 'Government Offices', 1),
(31, 'PRK', 'Public parks', 1),
(32, 'PRK', 'Playgrounds', 1),
(33, 'S-CZ', 'Low-impact structures', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `parameters` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `report_type`, `report_name`, `generated_by`, `file_path`, `parameters`, `created_at`) VALUES
(1, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-26 19:11:21'),
(2, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-25\",\"date_to\":\"2025-12-27\",\"year\":\"2025\"}', '2025-12-26 19:16:56'),
(3, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-29\",\"year\":\"2025\"}', '2025-12-29 15:31:21'),
(4, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 15:50:18'),
(5, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-29\",\"year\":\"2025\"}', '2025-12-29 15:50:39'),
(6, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-29\",\"year\":\"2025\"}', '2025-12-29 15:51:46'),
(7, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-26\",\"date_to\":\"2025-12-29\",\"year\":\"2025\"}', '2025-12-29 15:52:00'),
(8, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-29\",\"year\":\"2025\"}', '2025-12-29 15:52:22'),
(9, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 15:57:11'),
(10, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 16:02:50'),
(11, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:03:07'),
(12, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:03:26'),
(13, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:03:39'),
(14, 'monthly_analytics', 'Monthly Analytics Report - 2025', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:03:51'),
(15, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 16:04:11'),
(16, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 16:06:44'),
(17, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 16:11:28'),
(18, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 16:11:35'),
(19, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 16:11:39'),
(20, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 16:14:47'),
(21, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:14:54'),
(22, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:16:41'),
(23, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"\",\"date_to\":\"\",\"year\":\"2025\"}', '2025-12-29 16:17:39'),
(24, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:18:19'),
(25, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:18:46'),
(26, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:18:54'),
(27, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:19:03'),
(28, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:19:14'),
(29, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:22:40'),
(30, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:22:55'),
(31, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:23:09'),
(32, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:23:14'),
(33, 'permits_issued', 'Permits Issued Report', 1, NULL, '[]', '2025-12-29 16:23:20'),
(34, 'permits_issued', 'Permits Issued Report', 1, NULL, '[]', '2025-12-29 16:23:38'),
(35, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:25:15'),
(36, 'permits_issued', 'Permits Issued Report', 1, NULL, '[]', '2025-12-29 16:28:53'),
(37, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:29:03'),
(38, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:29:06'),
(39, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:29:17'),
(40, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:29:34'),
(41, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:29:46'),
(42, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:31:11'),
(43, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:31:29'),
(44, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:32:38'),
(45, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:32:46'),
(46, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:32:50'),
(47, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:33:18'),
(48, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:33:26'),
(49, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:33:48'),
(50, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:36:34'),
(51, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:36:46'),
(52, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:36:52'),
(53, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:36:54'),
(54, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:36:57'),
(55, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:38:01'),
(56, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:41:11'),
(57, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:42:03'),
(58, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:42:13'),
(59, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:44:34'),
(60, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:44:45'),
(61, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:44:48'),
(62, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:45:36'),
(63, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:45:41'),
(64, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:46:54'),
(65, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:47:10'),
(66, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:47:13'),
(67, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:47:20'),
(68, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:47:23'),
(69, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"date_from\":\"2025-12-27\",\"date_to\":\"2025-12-30\",\"year\":\"2025\"}', '2025-12-29 16:47:27'),
(70, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:48:47'),
(71, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:50:08'),
(72, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:50:11'),
(73, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:51:58'),
(74, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:52:07'),
(75, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:53:43'),
(76, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:53:48'),
(77, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:55:50'),
(78, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:58:14'),
(79, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:58:21'),
(80, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:58:24'),
(81, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 16:59:13'),
(82, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 17:01:32'),
(83, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 17:01:48'),
(84, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 17:03:19'),
(85, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 17:04:08'),
(86, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-29 17:06:53'),
(87, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:02:53'),
(88, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:03:11'),
(89, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:03:21'),
(90, 'monthly_analytics', 'Monthly Analytics Report - 2025', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:04:33'),
(91, 'zoning_compliance', 'Zoning Compliance Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:05:50'),
(92, 'applications_summary', 'Applications Summary Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:05:58'),
(93, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:07:10'),
(94, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:08:10'),
(95, 'zoning_compliance', 'Zoning Compliance List', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:08:16'),
(96, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:14:57'),
(97, 'zoning_compliance', 'Zoning Compliance List', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:16:42'),
(98, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:16:57'),
(99, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:17:00'),
(100, 'zoning_compliance', 'Zoning Compliance List', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:17:02'),
(101, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:20:19'),
(102, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:22:47'),
(103, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:23:38'),
(104, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:24:18'),
(105, 'user_growth', 'User Growth Report (2025)', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:25:15'),
(106, 'monthly_analytics', 'Monthly Analytics (2025)', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:25:21'),
(107, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:25:43'),
(108, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:25:55'),
(109, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:26:07'),
(110, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:26:10'),
(111, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:26:15'),
(112, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:26:27'),
(113, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:26:31'),
(114, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:30:36'),
(115, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:30:53'),
(116, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:30:55'),
(117, 'user_growth', 'User Growth Report (2025)', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:31:26'),
(118, 'monthly_analytics', 'Monthly Analytics (2025)', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:31:36'),
(119, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:32:58'),
(120, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:34:01'),
(121, 'zoning_compliance', 'Zoning Compliance List', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:34:07'),
(122, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":\"2025\"}', '2025-12-30 18:34:18'),
(123, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":2025}', '2025-12-30 18:39:50'),
(124, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":2024}', '2025-12-30 18:39:50'),
(125, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2025}', '2025-12-30 18:40:08'),
(126, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2024}', '2025-12-30 18:40:08'),
(127, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2026}', '2026-01-01 18:19:39'),
(128, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2025}', '2026-01-01 18:19:40'),
(129, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":2026}', '2026-01-01 18:19:45'),
(130, 'permits_issued', 'Permits Issued Report', 1, NULL, '{\"year\":2025}', '2026-01-01 18:19:45'),
(131, 'zoning_compliance', 'Zoning Compliance List', 1, NULL, '{\"year\":2026}', '2026-01-01 18:19:50'),
(132, 'zoning_compliance', 'Zoning Compliance List', 1, NULL, '{\"year\":2025}', '2026-01-01 18:19:50'),
(133, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":2026}', '2026-01-01 18:19:58'),
(134, 'audit_summary', 'System Audit Summary', 1, NULL, '{\"year\":2025}', '2026-01-01 18:19:58'),
(135, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2026}', '2026-02-20 21:33:05'),
(136, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2025}', '2026-02-20 21:33:05'),
(137, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2026}', '2026-02-23 05:25:14'),
(138, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2025}', '2026-02-23 05:25:14'),
(139, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2026}', '2026-02-25 05:28:39'),
(140, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2025}', '2026-02-25 05:28:39'),
(141, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2026}', '2026-02-25 05:30:49'),
(142, 'applications_summary', 'Applications Summary', 1, NULL, '{\"year\":2025}', '2026-02-25 05:30:49'),
(143, 'applications_summary', 'Applications Summary (2026)', 1, NULL, '{\"year\":2026}', '2026-02-25 17:50:14'),
(144, 'audit_summary', 'System Audit Summary (Latest 100)', 1, NULL, '{\"year\":2026}', '2026-02-25 17:53:43'),
(145, 'audit_summary', 'System Audit Summary (Latest 100)', 1, NULL, '{\"year\":2025}', '2026-02-25 17:53:43'),
(146, 'user_growth', 'User Growth Report (2026)', 1, NULL, '{\"year\":2026}', '2026-02-25 17:54:02'),
(147, 'user_growth', 'User Growth Report (2025)', 1, NULL, '{\"year\":2025}', '2026-02-25 17:54:02'),
(148, 'monthly_analytics', 'Monthly Analytics (2026)', 1, NULL, '{\"year\":2026}', '2026-02-25 17:54:10'),
(149, 'zoning_compliance', 'Zoning Compliance Report (2026)', 1, NULL, '{\"year\":2026}', '2026-02-25 18:02:00'),
(150, 'inspector_performance', 'Inspector Workload Summary (2026)', 1, NULL, '{\"year\":2026}', '2026-02-25 18:03:40');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `permission` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role`, `permission`) VALUES
(4, 'admin', 'generate_reports'),
(1, 'admin', 'manage_users'),
(3, 'admin', 'manage_zoning'),
(2, 'admin', 'view_all_applications'),
(5, 'admin', 'view_audit_logs'),
(13, 'applicant', 'submit_application'),
(15, 'applicant', 'upload_documents'),
(14, 'applicant', 'view_own_applications'),
(12, 'assessor', 'update_parcel_info'),
(11, 'assessor', 'view_applications'),
(9, 'building_official', 'review_applications'),
(10, 'building_official', 'update_application_status'),
(7, 'zoning_officer', 'check_zoning_compliance'),
(6, 'zoning_officer', 'review_applications'),
(8, 'zoning_officer', 'update_application_status');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `is_active`) VALUES
(1, 'system_announcement', 'We are currently performing system updates. You may encounter issues with registration or submissions; please save your drafts and try again later.', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin','zoning_officer','building_official','assessor','applicant','inspector') NOT NULL DEFAULT 'applicant',
  `id_front_path` varchar(255) DEFAULT NULL,
  `id_back_path` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `verification_token` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `rejection_reason` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_activity` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `id_front_path`, `id_back_path`, `phone`, `birth_date`, `street`, `barangay`, `city`, `verification_token`, `is_verified`, `rejection_reason`, `is_active`, `created_at`, `updated_at`, `last_activity`, `otp_code`, `otp_expiry`) VALUES
(1, 'admin', 'admin@lgu.gov.ph', '$2y$10$glP2wdrg2PXTtNH0Nd6tX.m9ondcehIPhsJk70mfoZRiCy1icD36W', 'System', 'Administrator', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 1, '2025-12-21 19:07:31', '2026-02-26 03:26:52', '2026-02-26 11:26:52', NULL, NULL),
(15, 'aelousssn', 'yumiedalagan01@gmail.com', '$2y$10$FV8qOqX2TsYa1/lF5E6NBeDdZMbt9WplyWxo21lcTWJsxAr8MVsuG', 'Aelous', 'Nexus', 'applicant', 'uploads/ids/aelousssn_FRONT_1770468733.jpg', 'uploads/ids/aelousssn_BACK_1770468733.jpg', '9207249702', '2003-11-07', '6835 Sto Nino St.', '177', 'Caloocan City', NULL, 1, NULL, 1, '2026-02-07 12:52:13', '2026-02-26 03:10:24', '2026-02-26 11:10:24', NULL, NULL),
(17, 'inspector', 'inspector@lgu.gov.ph', '$2y$10$y6S0D3FpaYF/bvns.DtE5uyG/qcEvUOLUiZzxKg5kvL4Mt3vwS8r2', 'Inspector', 'Juan', 'inspector', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 1, '2026-02-22 20:26:07', '2026-02-25 18:05:21', '2026-02-26 02:05:21', NULL, NULL),
(19, 'your.fallensky', 'unknownfire01@gmail.com', '$2y$10$ewgGzoHy46/XxQxzXunDO.qq02I0wZgUYst52/8Jokanb70FGKDf6', 'Skyler', 'Rush', 'applicant', 'uploads/ids/your.fallensky_FRONT_1771992940.jpg', 'uploads/ids/your.fallensky_BACK_1771992940.jpg', '9207249702', '2003-11-07', '6835 Sto Nino St.', '177', 'Caloocan City', NULL, 1, NULL, 1, '2026-02-25 04:15:40', '2026-02-25 04:24:28', '2026-02-25 12:24:28', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `violations`
--

CREATE TABLE `violations` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  `violation_type` varchar(100) DEFAULT NULL,
  `severity` enum('low','medium','high') DEFAULT 'low',
  `notes` text DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zoning_classifications`
--

CREATE TABLE `zoning_classifications` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `allowed_uses` text DEFAULT NULL,
  `restrictions` text DEFAULT NULL,
  `max_height` decimal(10,2) DEFAULT NULL,
  `max_density` decimal(10,2) DEFAULT NULL,
  `max_far` decimal(5,2) DEFAULT NULL,
  `setback_front` decimal(10,2) DEFAULT NULL,
  `setback_rear` decimal(10,2) DEFAULT NULL,
  `setback_side` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zoning_classifications`
--

INSERT INTO `zoning_classifications` (`id`, `code`, `name`, `description`, `allowed_uses`, `restrictions`, `max_height`, `max_density`, `max_far`, `setback_front`, `setback_rear`, `setback_side`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'R1', 'Low Density Residential (R-1)', 'Mainly for single-family, single-detached dwellings (Section 13)', 'Single-family dwellings, Duplex, Community facilities, Home laundries', NULL, 10.00, NULL, 0.60, 5.00, 3.00, 2.00, 1, '2025-12-21 19:07:31', '2026-02-20 01:55:12'),
(2, 'R2', 'Medium Density Residential (R-2)', 'Multi-family dwellings, residential condominiums (Section 14)', 'Apartments, Boarding houses, Townhouses, Clinics', NULL, 15.00, NULL, 1.00, 4.00, 3.00, 2.00, 1, '2025-12-21 19:07:31', '2026-02-20 01:55:12'),
(3, 'C1', 'Neighborhood Commercial (C-1)', 'Small-scale commercial for neighborhood needs (Section 16)', 'Sari-sari stores, Bakeries, Barber shops, Neighborhood clinics', NULL, 20.00, NULL, 2.00, 3.00, 3.00, 2.00, 1, '2025-12-21 19:07:31', '2026-02-20 01:55:12'),
(4, 'C2', 'Major Commercial (C-2)', 'Medium to high-intensity commercial developments (Section 17)', 'Malls, BPO Offices, Hotels, Banks, Major hospitals', NULL, 30.00, NULL, 3.00, 5.00, 5.00, 3.00, 1, '2025-12-21 19:07:31', '2026-02-20 01:55:12'),
(5, 'I1', 'Light Industrial (I-1)', 'Non-pollutive/non-hazardous manufacturing (Section 20)', 'Warehouses, Food processing, Garment factories', NULL, 25.00, NULL, 1.50, 10.00, 10.00, 5.00, 1, '2025-12-21 19:07:31', '2026-02-20 01:55:12'),
(6, 'R-3', 'High-Density Residential', 'High-rise residential buildings', 'Condominiums, high-rise apartments', NULL, 60.00, NULL, NULL, 5.00, 3.00, 3.00, 1, '2026-02-20 01:52:41', '2026-02-20 01:52:41'),
(7, 'C-3', 'Metropolitan Commercial', 'Heavy commercial developments', 'Regional shopping centers, skyscrapers, transport terminals', NULL, 100.00, NULL, NULL, 5.00, 5.00, 4.00, 1, '2026-02-20 01:52:41', '2026-02-20 01:52:41'),
(8, 'I-2', 'Medium Industrial', 'Medium-scale manufacturing', 'Factories, large assembly plants, food processing', NULL, 25.00, NULL, NULL, 10.00, 10.00, 10.00, 1, '2026-02-20 01:52:41', '2026-02-20 01:52:41'),
(9, 'INST', 'Institutional Zone', 'Community and government facilities', 'Schools, Hospitals, Government Offices, Churches', NULL, 20.00, NULL, NULL, 5.00, 4.00, 4.00, 1, '2026-02-20 01:52:41', '2026-02-20 01:52:41'),
(10, 'PRK', 'Parks and Recreation', 'Open spaces and leisure areas', 'Public parks, playgrounds, botanical gardens', NULL, 5.00, NULL, NULL, 5.00, 5.00, 5.00, 1, '2026-02-20 01:52:41', '2026-02-20 01:52:41'),
(11, 'S-CZ', 'Special Control Zone', 'Heritage or environmental protection areas', 'Regulated low-impact structures', NULL, 10.00, NULL, NULL, 6.00, 4.00, 4.00, 1, '2026-02-20 01:52:41', '2026-02-20 01:52:41');

-- --------------------------------------------------------

--
-- Table structure for table `zoning_compliance_checks`
--

CREATE TABLE `zoning_compliance_checks` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `parcel_id` varchar(50) DEFAULT NULL,
  `zoning_type` varchar(100) DEFAULT NULL,
  `compliance_status` enum('compliant','non_compliant') DEFAULT NULL,
  `technical_analysis` text DEFAULT NULL,
  `checked_by` int(11) DEFAULT NULL,
  `checked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zoning_compliance_checks`
--

INSERT INTO `zoning_compliance_checks` (`id`, `application_id`, `parcel_id`, `zoning_type`, `compliance_status`, `technical_analysis`, `checked_by`, `checked_at`) VALUES
(56, 35, '10', 'Major Commercial (C-2)', 'compliant', 'AUTOMATED WARNING: Project type \'Commercial\' is NOT listed as a permitted use in Major Commercial (C-2). Spatial verification performed on coordinates [14.653300, 121.033300]. The project site is verified to be within the Major Commercial (C-2) zone. Matched cadastral record Lot C2-01, Block undefined. Automated spatial check indicates the location is consistent with LGU land use mapping.', 1, '2026-02-25 19:55:38'),
(59, 37, '10', 'Major Commercial (C-2)', 'compliant', 'Coordinates: [14.639400, 121.034700]\r\nZoning Zone: Major Commercial (C-2)\r\nLand Record: Lot 5, Block 10\r\nStatus Check: Consistent with LGU Land Use Mapping.', 1, '2026-02-25 22:52:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `assigned_officer_id` (`assigned_officer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_applicant_id` (`applicant_id`),
  ADD KEY `idx_application_number` (`application_number`);

--
-- Indexes for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_application_id` (`application_id`);

--
-- Indexes for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_application_id` (`application_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `gis_layers`
--
ALTER TABLE `gis_layers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `impact_assessments`
--
ALTER TABLE `impact_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessed_by` (`assessed_by`),
  ADD KEY `idx_app` (`application_id`);

--
-- Indexes for table `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inspector_id` (`inspector_id`),
  ADD KEY `idx_app_inspection` (`application_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_receiver_id` (`receiver_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_application_id` (`application_id`);

--
-- Indexes for table `parcels`
--
ALTER TABLE `parcels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parcel_id` (`parcel_id`),
  ADD KEY `zoning_classification_id` (`zoning_classification_id`),
  ADD KEY `idx_parcel_id` (`parcel_id`),
  ADD KEY `idx_lot_number` (`lot_number`),
  ADD KEY `idx_barangay` (`barangay`);

--
-- Indexes for table `permitted_uses`
--
ALTER TABLE `permitted_uses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_zone_project` (`zone_code`,`project_type`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role`,`permission`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `violations`
--
ALTER TABLE `violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inspection_id` (`inspection_id`),
  ADD KEY `idx_app_violation` (`application_id`);

--
-- Indexes for table `zoning_classifications`
--
ALTER TABLE `zoning_classifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `zoning_compliance_checks`
--
ALTER TABLE `zoning_compliance_checks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_id_2` (`application_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `zoning_classification_id` (`zoning_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `application_documents`
--
ALTER TABLE `application_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `application_status_history`
--
ALTER TABLE `application_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=411;

--
-- AUTO_INCREMENT for table `gis_layers`
--
ALTER TABLE `gis_layers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `impact_assessments`
--
ALTER TABLE `impact_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inspections`
--
ALTER TABLE `inspections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `parcels`
--
ALTER TABLE `parcels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `permitted_uses`
--
ALTER TABLE `permitted_uses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zoning_classifications`
--
ALTER TABLE `zoning_classifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `zoning_compliance_checks`
--
ALTER TABLE `zoning_compliance_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`assigned_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD CONSTRAINT `application_documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `application_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD CONSTRAINT `application_status_history_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `application_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `impact_assessments`
--
ALTER TABLE `impact_assessments`
  ADD CONSTRAINT `impact_assessments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `impact_assessments_ibfk_2` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inspections`
--
ALTER TABLE `inspections`
  ADD CONSTRAINT `inspections_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspections_ibfk_2` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parcels`
--
ALTER TABLE `parcels`
  ADD CONSTRAINT `parcels_ibfk_1` FOREIGN KEY (`zoning_classification_id`) REFERENCES `zoning_classifications` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `permitted_uses`
--
ALTER TABLE `permitted_uses`
  ADD CONSTRAINT `permitted_uses_ibfk_1` FOREIGN KEY (`zone_code`) REFERENCES `zoning_classifications` (`code`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `violations`
--
ALTER TABLE `violations`
  ADD CONSTRAINT `violations_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `violations_ibfk_2` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
