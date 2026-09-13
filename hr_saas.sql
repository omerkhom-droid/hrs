-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 13, 2026 at 01:08 PM
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
-- Database: `hr_saas`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_adjustments`
--

CREATE TABLE `attendance_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_record_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `original_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`original_values`)),
  `requested_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`requested_values`)),
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_breaks`
--

CREATE TABLE `attendance_breaks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_record_id` bigint(20) UNSIGNED NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `duration_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `source` enum('web','mobile','manual','device','api') NOT NULL DEFAULT 'manual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_policies`
--

CREATE TABLE `attendance_policies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Riyadh',
  `late_grace_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 10,
  `early_leave_grace_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 5,
  `early_check_in_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 120,
  `late_check_out_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 240,
  `overtime_after_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `rounding_rule` enum('none','nearest_5','nearest_10','nearest_15') NOT NULL DEFAULT 'none',
  `allow_web` tinyint(1) NOT NULL DEFAULT 1,
  `allow_mobile` tinyint(1) NOT NULL DEFAULT 1,
  `require_geofence` tinyint(1) NOT NULL DEFAULT 0,
  `allow_outside_geofence` tinyint(1) NOT NULL DEFAULT 0,
  `require_photo` tinyint(1) NOT NULL DEFAULT 0,
  `auto_check_out` tinyint(1) NOT NULL DEFAULT 0,
  `auto_check_out_after_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `approval_mode` enum('manual','auto_clean') NOT NULL DEFAULT 'auto_clean',
  `max_location_accuracy` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `weekend_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`weekend_days`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_policies`
--

INSERT INTO `attendance_policies` (`id`, `uuid`, `tenant_id`, `code`, `name`, `timezone`, `late_grace_minutes`, `early_leave_grace_minutes`, `early_check_in_minutes`, `late_check_out_minutes`, `overtime_after_minutes`, `rounding_rule`, `allow_web`, `allow_mobile`, `require_geofence`, `allow_outside_geofence`, `require_photo`, `auto_check_out`, `auto_check_out_after_minutes`, `approval_mode`, `max_location_accuracy`, `weekend_days`, `is_default`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '89279cbf-216d-4053-b6dc-ebac52fdfb7b', 3, 'DEFAULT', 'سياسة الدوام الأساسية', 'Asia/Riyadh', 10, 5, 120, 240, 0, 'nearest_5', 1, 1, 1, 0, 1, 1, 60, 'auto_clean', 100, '[5]', 1, 1, NULL, '2026-08-17 10:15:52', '2026-09-12 10:25:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `work_shift_id` bigint(20) UNSIGNED DEFAULT NULL,
  `work_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Riyadh',
  `scheduled_check_in_at` datetime DEFAULT NULL,
  `scheduled_check_out_at` datetime DEFAULT NULL,
  `check_in_at` datetime DEFAULT NULL,
  `check_out_at` datetime DEFAULT NULL,
  `check_in_source` enum('web','mobile','manual','device','api') DEFAULT NULL,
  `check_out_source` enum('web','mobile','manual','device','api','system') DEFAULT NULL,
  `check_in_latitude` decimal(10,7) DEFAULT NULL,
  `check_in_longitude` decimal(10,7) DEFAULT NULL,
  `check_in_distance` int(10) UNSIGNED DEFAULT NULL,
  `check_out_latitude` decimal(10,7) DEFAULT NULL,
  `check_out_longitude` decimal(10,7) DEFAULT NULL,
  `check_out_distance` int(10) UNSIGNED DEFAULT NULL,
  `check_in_ip` varchar(45) DEFAULT NULL,
  `check_out_ip` varchar(45) DEFAULT NULL,
  `check_in_device` varchar(255) DEFAULT NULL,
  `check_out_device` varchar(255) DEFAULT NULL,
  `check_in_photo_path` varchar(1000) DEFAULT NULL,
  `check_out_photo_path` varchar(1000) DEFAULT NULL,
  `status` enum('present','late','absent','on_leave','holiday','remote','incomplete') NOT NULL DEFAULT 'incomplete',
  `work_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `break_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `late_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `early_leave_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `overtime_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `approved_overtime_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `uuid`, `tenant_id`, `employee_id`, `work_shift_id`, `work_location_id`, `attendance_date`, `timezone`, `scheduled_check_in_at`, `scheduled_check_out_at`, `check_in_at`, `check_out_at`, `check_in_source`, `check_out_source`, `check_in_latitude`, `check_in_longitude`, `check_in_distance`, `check_out_latitude`, `check_out_longitude`, `check_out_distance`, `check_in_ip`, `check_out_ip`, `check_in_device`, `check_out_device`, `check_in_photo_path`, `check_out_photo_path`, `status`, `work_minutes`, `break_minutes`, `late_minutes`, `early_leave_minutes`, `overtime_minutes`, `approved_overtime_minutes`, `approval_status`, `approved_at`, `approved_by`, `created_by`, `notes`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(17, '734a1d74-3b5d-40eb-861e-1f7089b47edb', 3, 1, 1, 1, '2026-09-12', 'Asia/Riyadh', '2026-09-12 05:00:00', '2026-09-12 13:00:00', '2026-09-12 13:05:04', '2026-09-12 13:26:18', 'mobile', 'mobile', 21.9172973, 39.3110437, 79, 21.9174456, 39.3108677, 55, '192.168.11.211', '192.168.11.211', 'TP1A.220624.014', 'TP1A.220624.014', NULL, 'tenants/3/employees/5b2e95f6-4cd8-4ab9-8718-4e81364c6bae/attendance/2026-09-12/check-out-0a8f9aee-cf9e-45d7-ae98-cf0aa46555ae.jpg', 'late', 0, 21, 475, 0, 26, 0, 'pending', NULL, NULL, 6, NULL, '{\"check_in_accuracy\":\"2.0759999752044678\",\"mobile_device_id\":1,\"check_in_channel\":\"mobile\",\"check_out_accuracy\":\"1.2640000581741333\",\"check_out_channel\":\"mobile\",\"check_out_capture_method\":\"camera\",\"check_out_captured_at\":\"2026-09-12T13:26:16.997713Z\",\"check_out_camera_facing\":\"user\",\"approval\":{\"mode\":\"auto_clean\",\"source\":\"pending_review\",\"evaluated_at\":\"2026-09-12T13:26:19+00:00\",\"review_reasons\":[\"exception_status\",\"late\",\"overtime_pending_approval\",\"missing_photo\"]}}', '2026-09-12 10:05:04', '2026-09-12 10:26:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `country_code` varchar(2) NOT NULL DEFAULT 'SA',
  `city` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Riyadh',
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `tenant_id`, `code`, `name`, `name_en`, `email`, `phone`, `country_code`, `city`, `address`, `timezone`, `is_main`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, '10001040105', 'جدة', 'jeddah', 'omer9066359591@gmail.com', '050943752', 'SA', 'جدة', 'طريق عسفان', 'Asia/Riyadh', 1, 1, NULL, '2026-08-10 12:11:54', '2026-08-10 12:11:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `tenant_id`, `branch_id`, `parent_id`, `code`, `name`, `name_en`, `description`, `sort_order`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, NULL, NULL, 'HRS', 'الموارد البشرية', 'HR', 'ادارة الموارد البشرية', 1, 1, NULL, '2026-08-11 06:41:29', '2026-08-11 06:43:43', NULL),
(2, 3, NULL, 1, 'HR-HEAD', 'ادارة الموارد البشرية', 'HR-header', 'ادارة الموارد البشرية', 2, 1, NULL, '2026-08-11 06:43:29', '2026-08-11 06:43:29', NULL),
(3, 3, NULL, 1, 'HR-SALARY', 'منظمو الرواتب', 'HR-SALARY', 'HR-SALARY', 3, 1, NULL, '2026-08-11 06:45:16', '2026-08-11 06:45:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_number` varchar(50) NOT NULL,
  `attendance_code` varchar(50) DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `job_title_id` bigint(20) UNSIGNED DEFAULT NULL,
  `work_location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `grandfather_name` varchar(255) DEFAULT NULL,
  `family_name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `identity_type` enum('national_id','iqama','passport','gcc','other') DEFAULT NULL,
  `identity_number` varchar(100) DEFAULT NULL,
  `identity_expiry_date` date DEFAULT NULL,
  `nationality_code` varchar(2) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `personal_email` varchar(255) DEFAULT NULL,
  `work_email` varchar(255) DEFAULT NULL,
  `personal_phone` varchar(50) DEFAULT NULL,
  `work_phone` varchar(50) DEFAULT NULL,
  `country_code` varchar(2) NOT NULL DEFAULT 'SA',
  `city` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_relation` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','temporary','intern','consultant') NOT NULL DEFAULT 'full_time',
  `employment_status` enum('draft','probation','active','on_leave','suspended','terminated') NOT NULL DEFAULT 'draft',
  `hire_date` date DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `termination_reason` text DEFAULT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `uuid`, `tenant_id`, `user_id`, `employee_number`, `attendance_code`, `branch_id`, `department_id`, `job_title_id`, `work_location_id`, `manager_id`, `first_name`, `father_name`, `grandfather_name`, `family_name`, `name_en`, `identity_type`, `identity_number`, `identity_expiry_date`, `nationality_code`, `gender`, `birth_date`, `marital_status`, `personal_email`, `work_email`, `personal_phone`, `work_phone`, `country_code`, `city`, `address`, `emergency_contact_name`, `emergency_contact_relation`, `emergency_contact_phone`, `employment_type`, `employment_status`, `hire_date`, `probation_end_date`, `confirmation_date`, `termination_date`, `termination_reason`, `timezone`, `photo_path`, `notes`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '5b2e95f6-4cd8-4ab9-8718-4e81364c6bae', 3, 6, '02144', '110011', 1, 1, 1, 1, NULL, 'عمر', 'خالد', 'عمر', 'عبدالفضيل', 'Omer Khalid Omer', 'national_id', '312456645645', '2026-08-28', 'SD', 'male', '2026-08-05', 'married', 'omer-khalid@r-yoom.com', 'omer-khalid@r-yoom.com', '0509423752', '0509423752', 'SA', 'جدة', 'طريق عسفان', 'omer khalid', 'bro', '050943752', 'full_time', 'active', '2026-08-11', '2026-09-30', '2026-11-11', '2026-09-24', 'ر ؤئبؤيسب  بلالر', NULL, 'tenants/3/employees/5b2e95f6-4cd8-4ab9-8718-4e81364c6bae/gIXLFvpi2ZBAFqaKQpLldS5eQCjeIvVj1HctqSAD.jpg', 'يظهر فقط المستخدمون غير المرتبطين بموظف آخر.', NULL, '2026-08-11 09:46:40', '2026-09-12 09:28:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_bank_accounts`
--

CREATE TABLE `employee_bank_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `bank_code` varchar(50) DEFAULT NULL,
  `branch_code` varchar(50) DEFAULT NULL,
  `account_holder_name` varchar(255) NOT NULL,
  `account_number` text DEFAULT NULL,
  `iban` text DEFAULT NULL,
  `iban_hash` char(64) DEFAULT NULL,
  `iban_last4` varchar(4) DEFAULT NULL,
  `swift_code` varchar(30) DEFAULT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `payment_method` enum('bank_transfer','cash','cheque','wallet') NOT NULL DEFAULT 'bank_transfer',
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_bank_accounts`
--

INSERT INTO `employee_bank_accounts` (`id`, `uuid`, `tenant_id`, `employee_id`, `bank_name`, `bank_code`, `branch_code`, `account_holder_name`, `account_number`, `iban`, `iban_hash`, `iban_last4`, `swift_code`, `currency_code`, `payment_method`, `is_primary`, `is_active`, `is_verified`, `verified_at`, `verified_by`, `created_by`, `updated_by`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'cb71d063-5d87-47c3-b663-3218d7058ae0', 3, 1, 'اهلي', '001', NULL, 'عمر خالد', 'eyJpdiI6Ik1pcTZkd1MrUDZpVTVvY2xLOUM1aGc9PSIsInZhbHVlIjoiclB5elJLNHZISjJlNk1QV2ZINjFNUT09IiwibWFjIjoiMGFlZDRiYjE4MDdkOWM0MTQ4MzQ2NDhjZmU2OWRhNDVlZWZhMjJkMGE0ZmM4NDZmNWRlZmIwNTEwMDE3ZTBjOSIsInRhZyI6IiJ9', 'eyJpdiI6IllrOGkyemp3QXhJUVRVQjY1MUtHeGc9PSIsInZhbHVlIjoiU2FMT1lOZVp0SURNd0lEZDBKK2Q2dXoyVjQ2UEdFWHIxK3hUY0ZTK1QxUT0iLCJtYWMiOiJjZDcyOTQ3NTQ4ZWVmNmU2ZTQzNWQxMTNjYTA1OTAxZmJkMDJjMzkzOGFlYWFhNTY2MzM1NmZhODQ2MzU4NjY2IiwidGFnIjoiIn0=', 'd04d0f087d83c6289425bb0df44c7dd1f15ad3bb05a10a0c10b59adaf98df831', '7519', 'NCBKSAJE', 'SAR', 'bank_transfer', 1, 1, 1, '2026-08-30 11:57:34', 5, 5, 5, NULL, '2026-08-27 09:21:42', '2026-08-30 11:57:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_contracts`
--

CREATE TABLE `employee_contracts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `renewed_from_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contract_number` varchar(50) NOT NULL,
  `contract_type` enum('indefinite','fixed_term','temporary','seasonal','part_time','training') NOT NULL DEFAULT 'fixed_term',
  `status` enum('draft','active','suspended','expired','terminated','cancelled') NOT NULL DEFAULT 'draft',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_allowances` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `pay_frequency` enum('monthly','daily','hourly') NOT NULL DEFAULT 'monthly',
  `working_hours_per_day` decimal(4,2) NOT NULL DEFAULT 8.00,
  `working_days_per_week` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `annual_leave_days` smallint(5) UNSIGNED NOT NULL DEFAULT 21,
  `notice_period_days` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `renewal_notice_days` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `signed_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `activated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `termination_date` date DEFAULT NULL,
  `termination_reason` text DEFAULT NULL,
  `terminated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `terms` longtext DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_contracts`
--

INSERT INTO `employee_contracts` (`id`, `uuid`, `tenant_id`, `employee_id`, `renewed_from_id`, `contract_number`, `contract_type`, `status`, `start_date`, `end_date`, `probation_end_date`, `basic_salary`, `housing_allowance`, `transport_allowance`, `other_allowances`, `currency_code`, `pay_frequency`, `working_hours_per_day`, `working_days_per_week`, `annual_leave_days`, `notice_period_days`, `auto_renew`, `renewal_notice_days`, `signed_at`, `activated_at`, `activated_by`, `termination_date`, `termination_reason`, `terminated_by`, `terms`, `notes`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'eeb2b3cf-6949-4bb7-8d39-688d9a6e92c8', 3, 1, NULL, 'CON-2026-000001', 'fixed_term', 'terminated', '2026-08-17', '2027-08-17', '2026-11-17', 2000.00, 200.00, 200.00, 150.00, 'SAR', 'monthly', 8.00, 6, 21, 30, 0, 30, '2026-08-17 12:01:00', '2026-08-17 09:03:17', 5, '2026-09-09', 'sdfdfdg fgdfd', 5, NULL, NULL, NULL, '2026-08-17 09:02:26', '2026-09-09 10:46:47', NULL),
(2, '5b16cb95-ca55-42db-ab89-ce986433b291', 3, 1, NULL, 'CON-2026-000002', 'fixed_term', 'active', '2026-09-01', '2027-09-01', '2026-09-30', 3000.00, 0.00, 0.00, 0.00, 'SAR', 'monthly', 8.00, 5, 21, 30, 1, 30, '2026-09-09 13:47:00', '2026-09-09 10:48:16', 5, NULL, NULL, NULL, 'fs fgd dfg', 'fdggdfgd', NULL, '2026-09-09 10:47:53', '2026-09-09 10:48:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` enum('identity','passport','residency','contract','qualification','certificate','medical','insurance','bank','license','other') NOT NULL DEFAULT 'other',
  `document_number` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `issuing_authority` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'local',
  `file_path` varchar(1000) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `file_extension` varchar(20) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_documents`
--

INSERT INTO `employee_documents` (`id`, `uuid`, `tenant_id`, `employee_id`, `document_type`, `document_number`, `title`, `issuing_authority`, `issue_date`, `expiry_date`, `disk`, `file_path`, `original_name`, `mime_type`, `file_extension`, `file_size`, `is_verified`, `verified_at`, `verified_by`, `uploaded_by`, `notes`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '87ffb576-f868-482e-88be-6163c4dc8612', 3, 1, 'identity', '0111', '0111', 'اجوازت', '2026-08-18', '2026-08-28', 'local', 'tenants/3/employees/5b2e95f6-4cd8-4ab9-8718-4e81364c6bae/documents/deac83e0-2234-46f9-bbd6-2dba1b30d911.jpeg', 'WhatsApp Image 2026-07-29 at 12.32.12 PM.jpeg', 'image/jpeg', 'jpeg', 127679, 1, '2026-08-17 09:37:35', 5, 5, 'بسيبي', NULL, '2026-08-17 09:37:00', '2026-08-17 09:37:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_loans`
--

CREATE TABLE `employee_loans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `loan_type` varchar(50) NOT NULL DEFAULT 'salary_advance',
  `requested_amount` decimal(15,2) NOT NULL,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `installments_count` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `installment_amount` decimal(15,2) DEFAULT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `first_installment_date` date DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected','active','completed','cancelled') NOT NULL DEFAULT 'draft',
  `reason` text NOT NULL,
  `employee_notes` text DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cancelled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_loans`
--

INSERT INTO `employee_loans` (`id`, `uuid`, `tenant_id`, `employee_id`, `request_number`, `loan_type`, `requested_amount`, `approved_amount`, `installments_count`, `installment_amount`, `paid_amount`, `remaining_amount`, `first_installment_date`, `status`, `reason`, `employee_notes`, `approval_notes`, `rejection_reason`, `cancellation_reason`, `submitted_at`, `approved_at`, `rejected_at`, `cancelled_at`, `completed_at`, `approved_by`, `rejected_by`, `cancelled_by`, `created_by`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'fa803764-78ae-497e-981e-8fe19b4542be', 3, 1, 'LN-2026-EFQPNDEZ', 'salary_advance', 100.00, 100.00, 1, 100.00, 100.00, 0.00, '2026-09-30', 'completed', 'ythb  gfdf', 'dgfdg gffgh', 'yhb gfggf', NULL, NULL, '2026-09-09 10:30:02', '2026-09-09 10:30:41', NULL, NULL, '2026-09-09 10:51:20', 5, NULL, NULL, 6, NULL, '2026-09-09 10:29:50', '2026-09-09 10:51:20', NULL),
(2, '2bb66457-1624-4ec0-89d0-a0ea87ab931a', 3, 1, 'LN-2026-WPWSJTTD', 'personal_loan', 1200.00, 1200.00, 3, 400.00, 0.00, 0.00, '2026-10-01', 'cancelled', 'يسؤ بلليب', 'يلبيل', 'يبلي', NULL, 'gvgg', '2026-09-09 11:54:34', '2026-09-09 11:54:51', NULL, '2026-09-12 08:23:05', NULL, 5, NULL, 6, 6, NULL, '2026-09-09 11:54:31', '2026-09-12 08:23:05', NULL),
(3, 'a7ab1e19-0844-407e-9d92-7ee0bbdfea7a', 3, 1, 'LN-2026-AWGHT0JG', 'salary_advance', 500.00, 500.00, 1, 500.00, 0.00, 0.00, '2026-09-30', 'cancelled', 'test', 'tested', 'تم وتصرف غدا', NULL, 'oh b', '2026-09-12 08:24:45', '2026-09-12 08:25:16', NULL, '2026-09-12 08:26:11', NULL, 5, NULL, 6, 6, NULL, '2026-09-12 08:24:27', '2026-09-12 08:26:11', NULL),
(4, '8d300ceb-419d-4e7c-bded-c7a19000b7cb', 3, 1, 'LN-2026-XDWZBI3F', 'emergency_loan', 800.00, 800.00, 1, 800.00, 0.00, 0.00, '2026-09-30', 'cancelled', 'vhk bjk', 'bbbn', 'ا', NULL, 'ألغيت من إدارة السلف', '2026-09-12 08:26:36', '2026-09-12 08:26:51', NULL, '2026-09-12 11:01:20', NULL, 5, NULL, 5, 6, NULL, '2026-09-12 08:26:35', '2026-09-12 11:01:21', NULL),
(5, '53e0536b-dab9-48ee-82bc-b8f88a770cf0', 3, 1, 'LN-2026-XSHWDCBG', 'salary_advance', 100.00, 100.00, 1, 100.00, 0.00, 100.00, '2026-09-29', 'approved', 'sas s', 'dsdssd', NULL, NULL, NULL, '2026-09-12 11:02:30', '2026-09-12 11:02:50', NULL, NULL, NULL, 5, NULL, NULL, 5, NULL, '2026-09-12 11:02:07', '2026-09-12 11:02:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_loan_installments`
--

CREATE TABLE `employee_loan_installments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_loan_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `installment_number` smallint(5) UNSIGNED NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','partially_paid','deducted','paid','postponed','cancelled') NOT NULL DEFAULT 'pending',
  `payroll_run_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payroll_run_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `deducted_at` date DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_loan_installments`
--

INSERT INTO `employee_loan_installments` (`id`, `uuid`, `tenant_id`, `employee_loan_id`, `employee_id`, `installment_number`, `due_date`, `amount`, `paid_amount`, `remaining_amount`, `status`, `payroll_run_id`, `payroll_run_item_id`, `deducted_at`, `paid_at`, `notes`, `metadata`, `created_at`, `updated_at`) VALUES
(1, '3569a764-c884-4ce2-9471-02e91b4a3a50', 3, 1, 1, 1, '2026-09-30', 100.00, 100.00, 0.00, 'deducted', 5, 10, '2026-09-09', '2026-09-09 10:51:20', NULL, NULL, '2026-09-09 10:30:41', '2026-09-09 10:51:20'),
(2, '28f404b4-ed02-43a9-a899-790c3efddcb5', 3, 2, 1, 1, '2026-10-01', 400.00, 0.00, 400.00, 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 11:54:51', '2026-09-12 08:23:05'),
(3, '0897d07f-a43f-4178-aac5-47dd70c26ed6', 3, 2, 1, 2, '2026-11-01', 400.00, 0.00, 400.00, 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 11:54:51', '2026-09-12 08:23:05'),
(4, '390fc464-24a3-489e-91ca-6bc9e79bed37', 3, 2, 1, 3, '2026-12-01', 400.00, 0.00, 400.00, 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-09 11:54:51', '2026-09-12 08:23:05'),
(5, '321ecdce-5b98-418f-a9c9-b9e4b016c6a2', 3, 3, 1, 1, '2026-09-30', 500.00, 0.00, 500.00, 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-12 08:25:17', '2026-09-12 08:26:11'),
(6, '1c8deaa4-7ec4-4280-bfeb-bb4c67bb3016', 3, 4, 1, 1, '2026-09-30', 800.00, 0.00, 800.00, 'cancelled', 7, 13, NULL, NULL, NULL, NULL, '2026-09-12 08:26:51', '2026-09-12 11:01:19'),
(7, '0f2b8c5d-c8cf-487c-8fd6-0ee584ff6a5d', 3, 5, 1, 1, '2026-09-29', 100.00, 0.00, 100.00, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-12 11:02:50', '2026-09-12 11:02:50');

-- --------------------------------------------------------

--
-- Table structure for table `employee_mobile_devices`
--

CREATE TABLE `employee_mobile_devices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `device_uuid` varchar(191) NOT NULL,
  `platform` varchar(30) NOT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `device_model` varchar(255) DEFAULT NULL,
  `os_version` varchar(100) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `push_token` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_mobile_devices`
--

INSERT INTO `employee_mobile_devices` (`id`, `tenant_id`, `user_id`, `employee_id`, `device_uuid`, `platform`, `device_name`, `device_model`, `os_version`, `app_version`, `push_token`, `is_active`, `last_seen_at`, `last_ip`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, 6, 1, 'TP1A.220624.014', 'android', 'ITEL itel A665L', 'itel A665L', '13', '1.0.0', NULL, 1, '2026-09-13 06:26:52', '192.168.11.211', '2026-09-05 11:46:39', '2026-09-13 06:26:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_salary_components`
--

CREATE TABLE `employee_salary_components` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `salary_structure_id` bigint(20) UNSIGNED NOT NULL,
  `salary_component_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `percentage` decimal(9,4) DEFAULT NULL,
  `rate` decimal(18,4) DEFAULT NULL,
  `quantity` decimal(12,4) DEFAULT NULL,
  `formula` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_salary_components`
--

INSERT INTO `employee_salary_components` (`id`, `tenant_id`, `salary_structure_id`, `salary_component_id`, `amount`, `percentage`, `rate`, `quantity`, `formula`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 3, 2, 3, 3000.0000, NULL, NULL, NULL, NULL, 1, NULL, '2026-09-09 11:50:14', '2026-09-09 11:50:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_salary_structures`
--

CREATE TABLE `employee_salary_structures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'SAR',
  `basic_salary` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_earnings` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_deductions` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `net_salary` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `status` enum('draft','active','expired','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_salary_structures`
--

INSERT INTO `employee_salary_structures` (`id`, `uuid`, `tenant_id`, `employee_id`, `version`, `effective_from`, `effective_to`, `currency_code`, `basic_salary`, `total_earnings`, `total_deductions`, `net_salary`, `status`, `notes`, `approved_by`, `approved_at`, `created_by`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'a38e777b-74f0-4433-8a49-06937ccab730', 3, 1, 1, '2026-09-01', '2027-09-01', 'SAR', 3000.0000, 3000.0000, 0.0000, 3000.0000, 'active', 'سييب يسب', 5, '2026-09-09 11:50:22', 5, '{\"created_source\":\"web\",\"created_at\":\"2026-09-09T14:50:13+00:00\",\"calculation\":{\"calculated_at\":\"2026-09-09T14:50:22+00:00\",\"components\":{\"BASIC\":3000},\"basic_salary\":3000,\"total_earnings\":3000,\"total_deductions\":0,\"net_salary\":3000},\"approval\":{\"approved_by\":5,\"approved_at\":\"2026-09-09T14:50:22+00:00\"}}', '2026-09-09 11:50:13', '2026-09-09 11:50:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_shift_assignments`
--

CREATE TABLE `employee_shift_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `work_shift_id` bigint(20) UNSIGNED NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_shift_assignments`
--

INSERT INTO `employee_shift_assignments` (`id`, `tenant_id`, `employee_id`, `work_shift_id`, `effective_from`, `effective_to`, `is_primary`, `notes`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, 1, 1, '2026-08-17', '2026-08-17', 1, NULL, 5, '2026-08-17 10:20:00', '2026-08-17 11:23:21', '2026-08-17 11:23:21'),
(2, 3, 1, 1, '2026-08-17', '2026-11-19', 1, 'تجربة', 5, '2026-08-17 11:23:21', '2026-08-17 11:25:39', '2026-08-17 11:25:39'),
(3, 3, 1, 1, '2026-08-17', '2026-08-18', 1, NULL, 5, '2026-08-17 11:25:39', '2026-08-19 07:49:27', NULL),
(4, 3, 1, 1, '2026-08-19', NULL, 1, NULL, 5, '2026-08-19 07:49:27', '2026-08-19 07:49:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `features`
--

CREATE TABLE `features` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'boolean',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `features`
--

INSERT INTO `features` (`id`, `code`, `name`, `module`, `type`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'hr.employees', 'إدارة الموظفين', 'hr', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(2, 'hr.contracts', 'عقود الموظفين', 'hr', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(3, 'hr.documents', 'وثائق الموظفين', 'hr', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(4, 'organization.multi_company', 'تعدد الشركات', 'organization', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(5, 'organization.branches', 'إدارة الفروع', 'organization', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(6, 'organization.structure', 'الأقسام والمسميات الوظيفية', 'organization', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(7, 'attendance.basic', 'الحضور والانصراف', 'attendance', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(8, 'attendance.shifts', 'إدارة الورديات', 'attendance', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(9, 'attendance.overtime', 'العمل الإضافي', 'attendance', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(10, 'leave.management', 'الإجازات والأرصدة', 'leave', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(11, 'payroll.management', 'الرواتب ومسير الرواتب', 'payroll', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(12, 'recruitment.management', 'التوظيف والمرشحين', 'recruitment', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(13, 'performance.management', 'تقييم الأداء', 'performance', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(14, 'training.management', 'التدريب والتطوير', 'training', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(15, 'self_service.portal', 'الخدمة الذاتية للموظف', 'self_service', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(16, 'workflow.approvals', 'مسارات الموافقات', 'workflow', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(17, 'reports.advanced', 'التقارير المتقدمة', 'reports', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(18, 'integration.api', 'API', 'integration', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(19, 'integration.webhooks', 'Webhooks', 'integration', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58'),
(20, 'audit.advanced', 'سجل التدقيق المتقدم', 'audit', 'boolean', NULL, 1, '2026-08-06 09:18:58', '2026-08-06 09:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'public',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `exclude_from_leave_days` tinyint(1) NOT NULL DEFAULT 1,
  `affects_attendance` tinyint(1) NOT NULL DEFAULT 1,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `uuid`, `tenant_id`, `branch_id`, `code`, `name`, `name_en`, `type`, `start_date`, `end_date`, `is_paid`, `exclude_from_leave_days`, `affects_attendance`, `is_recurring`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '4365e000-03ab-4239-aeba-db6db29c264e', 3, NULL, 'SU124', 'النقدية', 'Master', 'national', '2026-08-24', '2026-08-24', 1, 1, 1, 1, 1, NULL, '2026-08-24 07:39:56', '2026-08-24 07:40:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_titles`
--

CREATE TABLE `job_titles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_titles`
--

INSERT INTO `job_titles` (`id`, `tenant_id`, `department_id`, `code`, `name`, `name_en`, `description`, `sort_order`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, 1, 'MNR', 'مدير', 'manager', 'مدير', 1, 1, NULL, '2026-08-11 07:24:40', '2026-08-11 07:24:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `opening_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `accrued_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `carried_forward` decimal(8,2) NOT NULL DEFAULT 0.00,
  `adjustment_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `used_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `pending_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `available_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `calculated_at` date DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_balances`
--

INSERT INTO `leave_balances` (`id`, `tenant_id`, `employee_id`, `leave_type_id`, `year`, `opening_balance`, `accrued_balance`, `carried_forward`, `adjustment_balance`, `used_balance`, `pending_balance`, `available_balance`, `calculated_at`, `metadata`, `created_at`, `updated_at`) VALUES
(5, 3, 1, 1, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '2026-09-12', NULL, '2026-09-12 09:17:16', '2026-09-12 09:17:16'),
(6, 3, 1, 3, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '2026-09-12', NULL, '2026-09-12 09:17:16', '2026-09-12 09:17:16');

-- --------------------------------------------------------

--
-- Table structure for table `leave_balance_transactions`
--

CREATE TABLE `leave_balance_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `leave_balance_id` bigint(20) UNSIGNED NOT NULL,
  `leave_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('opening','accrual','carry_forward','usage','reversal','adjustment','expiry') NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `balance_after` decimal(8,2) NOT NULL,
  `effective_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `replacement_employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `start_session` enum('full_day','first_half','second_half') NOT NULL DEFAULT 'full_day',
  `end_session` enum('full_day','first_half','second_half') NOT NULL DEFAULT 'full_day',
  `requested_amount` decimal(8,2) NOT NULL,
  `approved_amount` decimal(8,2) DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `reason` text NOT NULL,
  `handover_notes` text DEFAULT NULL,
  `contact_during_leave` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(1000) DEFAULT NULL,
  `decision_notes` text DEFAULT NULL,
  `current_approval_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `required_approval_levels` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `requested_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cancelled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `uuid`, `tenant_id`, `employee_id`, `leave_type_id`, `replacement_employee_id`, `start_date`, `end_date`, `return_date`, `start_session`, `end_session`, `requested_amount`, `approved_amount`, `status`, `reason`, `handover_notes`, `contact_during_leave`, `attachment_path`, `decision_notes`, `current_approval_level`, `required_approval_levels`, `requested_at`, `approved_at`, `rejected_at`, `cancelled_at`, `requested_by`, `approved_by`, `cancelled_by`, `created_by`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(9, '08450045-e5ba-43d8-8e86-6c65c7e4bd96', 3, 1, 2, NULL, '2026-09-12', '2026-09-15', '2026-09-16', 'full_day', 'full_day', 3.00, 0.00, 'pending', 'ghbx', 'bxndnhnnsn jjwn', 'nxnzn', 'tenants/3/employees/1/leave-requests/ayUoWuAQs4EguCn0cWf7Nx75wRh4WwGRNEKRLY8t.jpg', NULL, 1, 1, '2026-09-12 09:18:31', NULL, NULL, NULL, 6, NULL, NULL, 6, '{\"history\":[{\"action\":\"submitted\",\"user_id\":6,\"user_name\":\"\\u0639\\u0645\\u0631 \\u062e\\u0627\\u0644\\u062f \\u0639\\u0645\\u0631\",\"notes\":null,\"recorded_at\":\"2026-09-12T12:18:31+00:00\"}]}', '2026-09-12 09:18:30', '2026-09-12 09:18:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leave_request_days`
--

CREATE TABLE `leave_request_days` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `leave_request_id` bigint(20) UNSIGNED NOT NULL,
  `leave_date` date NOT NULL,
  `amount` decimal(5,2) NOT NULL DEFAULT 1.00,
  `is_working_day` tinyint(1) NOT NULL DEFAULT 1,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_request_days`
--

INSERT INTO `leave_request_days` (`id`, `tenant_id`, `leave_request_id`, `leave_date`, `amount`, `is_working_day`, `is_paid`, `metadata`, `created_at`, `updated_at`) VALUES
(141, 3, 9, '2026-09-12', 0.00, 0, 0, NULL, '2026-09-12 09:18:30', '2026-09-12 09:18:30'),
(142, 3, 9, '2026-09-13', 1.00, 1, 1, NULL, '2026-09-12 09:18:30', '2026-09-12 09:18:30'),
(143, 3, 9, '2026-09-14', 1.00, 1, 1, NULL, '2026-09-12 09:18:30', '2026-09-12 09:18:30'),
(144, 3, 9, '2026-09-15', 1.00, 1, 1, NULL, '2026-09-12 09:18:30', '2026-09-12 09:18:30');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `unit` enum('day','hour') NOT NULL DEFAULT 'day',
  `payment_type` enum('paid','unpaid','partially_paid') NOT NULL DEFAULT 'paid',
  `paid_percentage` tinyint(3) UNSIGNED NOT NULL DEFAULT 100,
  `default_entitlement` decimal(8,2) NOT NULL DEFAULT 0.00,
  `accrual_method` enum('none','annual','monthly') NOT NULL DEFAULT 'annual',
  `requires_balance` tinyint(1) NOT NULL DEFAULT 1,
  `allow_negative_balance` tinyint(1) NOT NULL DEFAULT 0,
  `allow_during_probation` tinyint(1) NOT NULL DEFAULT 0,
  `allow_half_day` tinyint(1) NOT NULL DEFAULT 1,
  `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `minimum_notice_days` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `maximum_consecutive_days` smallint(5) UNSIGNED DEFAULT NULL,
  `allow_carry_forward` tinyint(1) NOT NULL DEFAULT 0,
  `maximum_carry_forward` decimal(8,2) NOT NULL DEFAULT 0.00,
  `gender` enum('all','male','female') NOT NULL DEFAULT 'all',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `uuid`, `tenant_id`, `code`, `name`, `name_en`, `unit`, `payment_type`, `paid_percentage`, `default_entitlement`, `accrual_method`, `requires_balance`, `allow_negative_balance`, `allow_during_probation`, `allow_half_day`, `requires_attachment`, `minimum_notice_days`, `maximum_consecutive_days`, `allow_carry_forward`, `maximum_carry_forward`, `gender`, `sort_order`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '7f306d2c-0e9b-4c48-b542-a14fd1c47e4e', 3, 'ANNUAL', 'الإجازة السنوية', 'Annual Leave', 'day', 'paid', 100, 21.00, 'monthly', 1, 0, 0, 1, 0, 3, NULL, 1, 5.00, 'all', 10, 1, '{\"source\":\"system_default\",\"editable\":true}', '2026-08-20 10:47:30', '2026-08-20 10:47:30', NULL),
(2, '89c64446-43c5-4ab2-85a3-4666b96589c3', 3, 'SICK', 'الإجازة المرضية', 'Sick Leave', 'day', 'paid', 100, 0.00, 'none', 0, 0, 1, 1, 1, 0, NULL, 0, 0.00, 'all', 20, 1, '{\"source\":\"system_default\",\"editable\":true,\"requires_medical_review\":true}', '2026-08-20 10:47:31', '2026-08-20 10:47:31', NULL),
(3, '1ffade82-377d-49ec-b164-98994474dd8b', 3, 'EMERGENCY', 'الإجازة الاضطرارية', 'Emergency Leave', 'day', 'paid', 100, 5.00, 'annual', 1, 0, 1, 1, 0, 0, 3, 0, 0.00, 'all', 30, 1, '{\"source\":\"system_default\",\"editable\":true}', '2026-08-20 10:47:31', '2026-08-20 10:47:31', NULL),
(4, '0f9d7553-482b-481d-aa33-051a92e8fbb5', 3, 'UNPAID', 'إجازة بدون راتب', 'Unpaid Leave', 'day', 'unpaid', 0, 0.00, 'none', 0, 0, 0, 1, 0, 7, NULL, 0, 0.00, 'all', 40, 1, '{\"source\":\"system_default\",\"editable\":true,\"affects_payroll\":true}', '2026-08-20 10:47:31', '2026-08-20 10:47:31', NULL),
(5, '2e5b3916-931c-42d3-afc1-53ff1049fc8e', 3, 'OTHER', 'إجازة أخرى', 'Other Leave', 'day', 'unpaid', 0, 0.00, 'none', 0, 0, 1, 1, 0, 0, NULL, 0, 0.00, 'all', 100, 1, '{\"source\":\"system_default\",\"editable\":true}', '2026-08-20 10:47:31', '2026-08-20 10:47:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_08_06_101642_create_system_core_tables', 1),
(2, '2026_08_06_110515_create_saas_core_tables', 2),
(3, '2026_08_08_134929_create_permission_tables', 3),
(4, '2026_08_10_143806_create_organization_structure_tables', 4),
(5, '2026_08_11_120000_create_employees_table', 5),
(6, '2026_08_17_120000_create_employee_contracts_table', 6),
(7, '2026_08_17_130000_create_employee_documents_table', 7),
(8, '2026_08_17_140000_create_attendance_management_tables', 8),
(9, '2026_08_17_160000_add_auto_check_out_settings_to_attendance', 9),
(10, '2026_08_18_120000_add_approval_settings_to_attendance_policies', 10),
(11, '2026_08_18_150000_create_overtime_requests_table', 11),
(12, '2026_08_20_120000_create_leave_management_tables', 12),
(13, '2026_08_24_101928_create_holidays_table', 13),
(14, '2026_08_24_151847_create_payroll_management_tables', 14),
(15, '2026_08_26_141146_create_payroll_banking_tables', 15),
(16, '2026_08_26_144449_create_payroll_payment_batches_tables', 16),
(17, '2026_08_30_151305_create_personal_access_tokens_table', 17),
(19, '2026_09_05_144111_create_employee_mobile_devices_table', 18),
(22, '2026_09_09_101247_create_employee_loans_table', 19),
(23, '2026_09_09_101250_create_employee_loan_installments_table', 19);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `tenant_id`) VALUES
(1, 'App\\Models\\User', 3, 1),
(2, 'App\\Models\\User', 4, 1),
(3, 'App\\Models\\User', 4, 1),
(14, 'App\\Models\\User', 5, 3),
(19, 'App\\Models\\User', 6, 3);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('2f34494d-219a-42bd-b31e-d5f5c9970d17', 'App\\Notifications\\EmployeeLoanStatusNotification', 'App\\Models\\User', 6, '{\"notification_type\":\"employee_loan\",\"event\":\"cancelled\",\"title_ar\":\"\\u062a\\u0645 \\u0625\\u0644\\u063a\\u0627\\u0621 \\u0637\\u0644\\u0628 \\u0627\\u0644\\u0633\\u0644\\u0641\\u0629\",\"title_en\":\"Loan Request Cancelled\",\"message_ar\":\"\\u062a\\u0645 \\u0625\\u0644\\u063a\\u0627\\u0621 \\u0637\\u0644\\u0628 \\u0627\\u0644\\u0633\\u0644\\u0641\\u0629 \\u0631\\u0642\\u0645 LN-2026-XDWZBI3F.\",\"message_en\":\"Loan request LN-2026-XDWZBI3F has been cancelled.\",\"entity_type\":\"employee_loan\",\"entity_uuid\":\"8d300ceb-419d-4e7c-bded-c7a19000b7cb\",\"request_number\":\"LN-2026-XDWZBI3F\",\"status\":\"cancelled\",\"created_at\":\"2026-09-12T14:01:38+00:00\"}', '2026-09-12 11:41:20', '2026-09-12 11:01:38', '2026-09-12 11:41:20'),
('4f8bc283-1d1b-4cac-95bb-fd3e91ed07d0', 'App\\Notifications\\EmployeeLoanStatusNotification', 'App\\Models\\User', 6, '{\"notification_type\":\"employee_loan\",\"event\":\"approved\",\"title_ar\":\"\\u062a\\u0645 \\u0627\\u0639\\u062a\\u0645\\u0627\\u062f \\u0637\\u0644\\u0628 \\u0627\\u0644\\u0633\\u0644\\u0641\\u0629\",\"title_en\":\"Loan Request Approved\",\"message_ar\":\"\\u062a\\u0645 \\u0627\\u0639\\u062a\\u0645\\u0627\\u062f \\u0637\\u0644\\u0628 \\u0627\\u0644\\u0633\\u0644\\u0641\\u0629 \\u0631\\u0642\\u0645 LN-2026-XSHWDCBG \\u0628\\u0645\\u0628\\u0644\\u063a 100.00 SAR.\",\"message_en\":\"Loan request LN-2026-XSHWDCBG has been approved for 100.00 SAR.\",\"entity_type\":\"employee_loan\",\"entity_uuid\":\"53e0536b-dab9-48ee-82bc-b8f88a770cf0\",\"request_number\":\"LN-2026-XSHWDCBG\",\"status\":\"approved\",\"created_at\":\"2026-09-12T14:02:50+00:00\"}', '2026-09-12 11:41:45', '2026-09-12 11:02:50', '2026-09-12 11:41:45'),
('9ebcae2b-5148-44aa-b136-dfccfb84ef69', 'App\\Notifications\\EmployeeLoanStatusNotification', 'App\\Models\\User', 6, '{\"notification_type\":\"employee_loan\",\"event\":\"submitted\",\"title_ar\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 \\u0637\\u0644\\u0628 \\u0627\\u0644\\u0633\\u0644\\u0641\\u0629\",\"title_en\":\"Loan Request Submitted\",\"message_ar\":\"\\u062a\\u0645 \\u0625\\u0631\\u0633\\u0627\\u0644 \\u0637\\u0644\\u0628 \\u0627\\u0644\\u0633\\u0644\\u0641\\u0629 \\u0631\\u0642\\u0645 LN-2026-XSHWDCBG \\u0644\\u0644\\u0627\\u0639\\u062a\\u0645\\u0627\\u062f.\",\"message_en\":\"Loan request LN-2026-XSHWDCBG has been submitted for approval.\",\"entity_type\":\"employee_loan\",\"entity_uuid\":\"53e0536b-dab9-48ee-82bc-b8f88a770cf0\",\"request_number\":\"LN-2026-XSHWDCBG\",\"status\":\"submitted\",\"created_at\":\"2026-09-12T14:02:30+00:00\"}', '2026-09-12 11:41:26', '2026-09-12 11:02:30', '2026-09-12 11:41:26');

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `overtime_date` date NOT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Riyadh',
  `planned_start_at` datetime NOT NULL,
  `planned_end_at` datetime NOT NULL,
  `requested_minutes` smallint(5) UNSIGNED NOT NULL,
  `actual_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `approved_minutes` smallint(5) UNSIGNED DEFAULT NULL,
  `type` enum('regular_day','rest_day','holiday','emergency') NOT NULL DEFAULT 'regular_day',
  `status` enum('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `reason` text NOT NULL,
  `decision_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_adjustments`
--

CREATE TABLE `payroll_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_period_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salary_component_id` bigint(20) UNSIGNED DEFAULT NULL,
  `applied_payroll_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `adjustment_number` varchar(50) NOT NULL,
  `type` enum('earning','deduction') NOT NULL,
  `amount` decimal(18,4) NOT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'SAR',
  `effective_date` date NOT NULL,
  `status` enum('draft','pending','approved','applied','rejected','cancelled') NOT NULL DEFAULT 'draft',
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_payment_batches`
--

CREATE TABLE `payroll_payment_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_run_id` bigint(20) UNSIGNED NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `payment_reference` varchar(150) NOT NULL,
  `name` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `file_format` enum('bank_csv','csv','txt','sif') NOT NULL DEFAULT 'bank_csv',
  `status` enum('draft','validated','generated','submitted','processing','completed','partially_completed','failed','cancelled') NOT NULL DEFAULT 'draft',
  `employees_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ready_employees_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `exception_employees_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `successful_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `failed_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `source_bank_name` varchar(255) DEFAULT NULL,
  `source_bank_code` varchar(50) DEFAULT NULL,
  `source_account_number` text DEFAULT NULL,
  `source_account_last_four` varchar(4) DEFAULT NULL,
  `source_iban` text DEFAULT NULL,
  `source_iban_last_four` varchar(4) DEFAULT NULL,
  `source_swift_code` varchar(20) DEFAULT NULL,
  `file_disk` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_mime_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `file_checksum` varchar(128) DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `validated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `generated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `submitted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_payment_batches`
--

INSERT INTO `payroll_payment_batches` (`id`, `uuid`, `tenant_id`, `payroll_run_id`, `batch_number`, `payment_reference`, `name`, `payment_date`, `currency_code`, `file_format`, `status`, `employees_count`, `ready_employees_count`, `exception_employees_count`, `total_amount`, `successful_amount`, `failed_amount`, `source_bank_name`, `source_bank_code`, `source_account_number`, `source_account_last_four`, `source_iban`, `source_iban_last_four`, `source_swift_code`, `file_disk`, `file_path`, `file_name`, `file_mime_type`, `file_size`, `file_checksum`, `validated_at`, `validated_by`, `generated_at`, `generated_by`, `submitted_at`, `submitted_by`, `completed_at`, `completed_by`, `cancelled_at`, `cancelled_by`, `cancellation_reason`, `notes`, `created_by`, `updated_by`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, '0cd116c5-fd3c-4b59-97d1-860337095138', 3, 7, 'PB-2026-000001', 'PAY-2026-000001', 'دفعة تحويل رواتب - رواتب شهر سبتمبر 2026', '2026-09-12', 'SAR', 'bank_csv', 'generated', 1, 1, 0, 2200.00, 0.00, 0.00, 'رؤية يوم', '12121', 'eyJpdiI6IlFZRERhYTlkVEwvZlE0eHZhb3BIR1E9PSIsInZhbHVlIjoiYkw1d01EbnpYbDdBWittVmVBZTlQMCsrSDczT0NxTFMzSkpvcVFMdWREND0iLCJtYWMiOiIwOTIzNzI4OTg0MWU5ZjhmZWY0ZGQ5OGVkNzVlODgyN2Q0ZTFiNGU3YzkwODc4Zjk5Nzk2MzMzOTJjMDQ4NDlkIiwidGFnIjoiIn0=', '4641', 'eyJpdiI6Ikl2bGl5Y0M4OTJBTit0OHpsamFWTlE9PSIsInZhbHVlIjoiVmFSUVlZUEtGR3c3STlRemFta25YTitpdmZkT29PQ2JLM0FyNUlBR3dZQT0iLCJtYWMiOiIyMGMyYTRmNDA4NjlmYmViM2Q5YzQwMjg4MmJlNjdiZGQ5YmE4YTllYmVhYzk0NDhhN2Y5NDAzZGJkOGY0NDBmIiwidGFnIjoiIn0=', '7533', NULL, 'local', 'tenants/3/payroll/payment-batches/0cd116c5-fd3c-4b59-97d1-860337095138/PB-2026-000001.csv', 'PB-2026-000001.csv', 'text/csv; charset=UTF-8', 339, '0ed95cd637c1e5fb170ed05459fc455746d619f317e7fdce748beff6b7966462', '2026-09-12 08:29:38', 5, '2026-09-12 08:30:35', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 5, '{\"source\":\"payroll_run\",\"payroll_run_status\":\"approved\",\"created_manually\":true,\"validation\":{\"evaluated_at\":\"2026-09-12T11:29:38+00:00\",\"evaluated_by\":5,\"ready_items\":1,\"exception_items\":0},\"generated_file\":{\"format\":\"generic_bank_csv\",\"generated_at\":\"2026-09-12T11:30:35+00:00\",\"generated_by\":5,\"items_count\":1}}', '2026-09-12 08:29:24', '2026-09-12 08:30:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_payment_batch_items`
--

CREATE TABLE `payroll_payment_batch_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_payment_batch_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_run_item_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `employee_bank_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_number` varchar(100) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `payment_method` enum('bank_transfer','cash','cheque','wallet') NOT NULL DEFAULT 'bank_transfer',
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_code` varchar(50) DEFAULT NULL,
  `bank_branch_code` varchar(50) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `account_number` text DEFAULT NULL,
  `account_number_last_four` varchar(4) DEFAULT NULL,
  `iban` text DEFAULT NULL,
  `iban_hash` varchar(64) DEFAULT NULL,
  `iban_last_four` varchar(4) DEFAULT NULL,
  `swift_code` varchar(20) DEFAULT NULL,
  `status` enum('pending','ready','exception','submitted','paid','failed','excluded','cancelled') NOT NULL DEFAULT 'pending',
  `exception_code` varchar(100) DEFAULT NULL,
  `exception_message` text DEFAULT NULL,
  `bank_transaction_reference` varchar(150) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_payment_batch_items`
--

INSERT INTO `payroll_payment_batch_items` (`id`, `uuid`, `tenant_id`, `payroll_payment_batch_id`, `payroll_run_item_id`, `employee_id`, `employee_bank_account_id`, `employee_number`, `employee_name`, `amount`, `currency_code`, `payment_method`, `bank_name`, `bank_code`, `bank_branch_code`, `account_holder_name`, `account_number`, `account_number_last_four`, `iban`, `iban_hash`, `iban_last_four`, `swift_code`, `status`, `exception_code`, `exception_message`, `bank_transaction_reference`, `submitted_at`, `paid_at`, `failed_at`, `notes`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'd6f01d7a-7213-4311-8c64-cf2e56c260c2', 3, 2, 13, 1, 1, '02144', 'عمر خالد عمر عبدالفضيل', 2200.00, 'SAR', 'bank_transfer', 'اهلي', '001', NULL, 'عمر خالد', 'eyJpdiI6IkxCcmJNMG5GbTIyVVVlVjY4VlN2dWc9PSIsInZhbHVlIjoiSTNRL0xVSmdidklweVQvbnRMY0lmUT09IiwibWFjIjoiZTU4YThjNjMzYmFlZTk1YjUzZTJlOWY4ZTZjYmU5NmQxYTQ0MDliMmI3OTY3YmYyMzM1OTgxZGY1ZGUyNjkwZSIsInRhZyI6IiJ9', '7519', 'eyJpdiI6ImpXZUNlT0Y3ck9idWdUMlB5YUFqTmc9PSIsInZhbHVlIjoieC9qdEhqWUpUV1BXOEdtcGE2aXJSLytSa1oxNGtiNzdsTi9kRFlTNkkyQT0iLCJtYWMiOiI5YzU2NTg3YzllYTFlN2FmOTlkZWRiYTUzNGJiZWU4MGYyZjhkN2M3ZGEzMzc3MzRiMmMzMDU5NDU2YTgxOTY5IiwidGFnIjoiIn0=', 'd04d0f087d83c6289425bb0df44c7dd1f15ad3bb05a10a0c10b59adaf98df831', '7519', 'NCBKSAJE', 'ready', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"bank_account_verified\":true,\"bank_account_primary\":true,\"salary_snapshot\":{\"basic_salary\":\"3000.00\",\"total_earnings\":\"3000.00\",\"total_deductions\":\"800.00\",\"net_salary\":\"2200.00\"},\"last_revalidated_at\":\"2026-09-12T11:29:38+00:00\"}', '2026-09-12 08:29:24', '2026-09-12 08:29:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `month` tinyint(3) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('draft','open','processing','review','approved','paid','closed','cancelled') NOT NULL DEFAULT 'draft',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_periods`
--

INSERT INTO `payroll_periods` (`id`, `uuid`, `tenant_id`, `code`, `name`, `year`, `month`, `start_date`, `end_date`, `payment_date`, `status`, `is_locked`, `locked_at`, `locked_by`, `created_by`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, '44ea0d8e-4579-48dd-9760-422d77d7249a', 3, 'PAY-2026-09', 'رواتب شهر سبتمبر 2026', 2026, 9, '2026-09-01', '2026-09-30', '2026-09-30', 'paid', 0, NULL, NULL, 5, '{\"created_source\":\"web\",\"created_at\":\"2026-09-09T13:49:39+00:00\",\"opening\":{\"opened_by\":5,\"opened_at\":\"2026-09-09T13:49:44+00:00\"},\"unlock_history\":[{\"reason\":\"\\u0633\\u0624\\u0628\\u064a\\u0631 \\u0628\\u064a\\u0633\\u064a\\u0628\",\"unlocked_by\":5,\"unlocked_at\":\"2026-09-09T14:52:51+00:00\"}]}', '2026-09-09 10:49:39', '2026-09-09 11:52:51', NULL),
(5, '79cd341c-296b-4c9e-b765-345828001993', 3, 'PAY-2026-10', 'رواتب شهر سبتمبر 2026', 2026, 10, '2026-10-01', '2026-10-31', '2026-10-31', 'approved', 1, '2026-09-12 08:28:24', 5, 5, '{\"created_source\":\"web\",\"created_at\":\"2026-09-09T14:53:15+00:00\",\"opening\":{\"opened_by\":5,\"opened_at\":\"2026-09-09T14:53:23+00:00\"}}', '2026-09-09 11:53:15', '2026-09-12 08:28:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_runs`
--

CREATE TABLE `payroll_runs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_period_id` bigint(20) UNSIGNED NOT NULL,
  `run_number` varchar(50) NOT NULL,
  `type` enum('regular','off_cycle','final_settlement') NOT NULL DEFAULT 'regular',
  `status` enum('draft','calculating','calculated','review','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
  `currency_code` varchar(3) NOT NULL DEFAULT 'SAR',
  `employee_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_basic_salary` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_earnings` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_deductions` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_net_salary` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `calculated_at` timestamp NULL DEFAULT NULL,
  `calculated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `paid_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_runs`
--

INSERT INTO `payroll_runs` (`id`, `uuid`, `tenant_id`, `payroll_period_id`, `run_number`, `type`, `status`, `currency_code`, `employee_count`, `total_basic_salary`, `total_earnings`, `total_deductions`, `total_net_salary`, `calculated_at`, `calculated_by`, `approved_at`, `approved_by`, `paid_at`, `paid_by`, `created_by`, `notes`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(5, '6b220394-410e-4850-93ab-27458483e637', 3, 4, 'PAY-2026-09-R001', 'regular', 'paid', 'SAR', 1, 4000.0000, 4000.0000, 100.0000, 3900.0000, '2026-09-09 10:50:13', 5, '2026-09-09 10:51:08', 5, '2026-09-09 10:51:20', 5, 5, 'hfg dgfg', '{\"selection_mode\":\"all_eligible\",\"selected_employee_ids\":null,\"created_from\":\"web\",\"created_at\":\"2026-09-09T13:50:09+00:00\",\"calculation\":{\"calculated_at\":\"2026-09-09T13:50:13+00:00\",\"calculated_by\":5,\"employee_count\":1,\"calculated_items\":1,\"exception_items\":0},\"review\":{\"submitted_at\":\"2026-09-09T13:51:03+00:00\",\"submitted_by\":5}}', '2026-09-09 10:50:09', '2026-09-09 10:51:20', NULL),
(6, '75d80894-5003-4f8c-a521-9f148848ba88', 3, 5, 'PAY-2026-10-R001', 'off_cycle', 'cancelled', 'SAR', 1, 3000.0000, 3000.0000, 400.0000, 2600.0000, '2026-09-09 11:56:09', 5, '2026-09-09 11:56:20', 5, NULL, NULL, 5, 'بلبيلا', '{\"selection_mode\":\"selected_employees\",\"selected_employee_ids\":[1],\"created_from\":\"web\",\"created_at\":\"2026-09-09T14:55:54+00:00\",\"calculation\":{\"calculated_at\":\"2026-09-09T14:56:09+00:00\",\"calculated_by\":5,\"employee_count\":1,\"calculated_items\":1,\"exception_items\":0},\"review\":{\"submitted_at\":\"2026-09-09T14:56:15+00:00\",\"submitted_by\":5},\"cancellation\":{\"cancelled_at\":\"2026-09-09T14:58:20+00:00\",\"cancelled_by\":5,\"reason\":\"\\u0628\\u0633\\u0633\\u0633\\u0633\\u0633\"}}', '2026-09-09 11:55:54', '2026-09-09 11:58:20', NULL),
(7, '9a4b1b17-e1f4-4873-9d8b-4d75198fe279', 3, 5, 'PAY-2026-10-R002', 'regular', 'approved', 'SAR', 1, 3000.0000, 3000.0000, 800.0000, 2200.0000, '2026-09-12 08:28:08', 5, '2026-09-12 08:28:24', 5, NULL, NULL, 5, 'لالل', '{\"selection_mode\":\"selected_employees\",\"selected_employee_ids\":[1],\"created_from\":\"web\",\"created_at\":\"2026-09-12T11:28:00+00:00\",\"calculation\":{\"calculated_at\":\"2026-09-12T11:28:08+00:00\",\"calculated_by\":5,\"employee_count\":1,\"calculated_items\":1,\"exception_items\":0},\"review\":{\"submitted_at\":\"2026-09-12T11:28:16+00:00\",\"submitted_by\":5}}', '2026-09-12 08:28:00', '2026-09-12 08:28:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_run_items`
--

CREATE TABLE `payroll_run_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_run_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `salary_structure_id` bigint(20) UNSIGNED DEFAULT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'SAR',
  `basic_salary` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `gross_salary` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_earnings` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `total_deductions` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `net_salary` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `scheduled_work_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `actual_work_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `absent_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `paid_leave_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `unpaid_leave_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `overtime_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('pending','calculated','exception','approved','paid') NOT NULL DEFAULT 'pending',
  `calculation_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculation_snapshot`)),
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_run_items`
--

INSERT INTO `payroll_run_items` (`id`, `uuid`, `tenant_id`, `payroll_run_id`, `employee_id`, `salary_structure_id`, `currency_code`, `basic_salary`, `gross_salary`, `total_earnings`, `total_deductions`, `net_salary`, `scheduled_work_days`, `actual_work_days`, `absent_days`, `paid_leave_days`, `unpaid_leave_days`, `overtime_minutes`, `status`, `calculation_snapshot`, `errors`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(10, 'a0325393-fe97-4914-98d5-b2e600b074b9', 3, 5, 1, NULL, 'SAR', 4000.0000, 4000.0000, 4000.0000, 100.0000, 3900.0000, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'paid', '{\"employee\":{\"id\":1,\"employee_number\":\"02144\",\"name\":\"\\u0639\\u0645\\u0631 \\u062e\\u0627\\u0644\\u062f \\u0639\\u0645\\u0631 \\u0639\\u0628\\u062f\\u0627\\u0644\\u0641\\u0636\\u064a\\u0644\"},\"salary_structure\":{\"id\":1,\"uuid\":\"87ae52be-49a5-4c2d-aeb1-e1140c0cbfba\",\"version\":1,\"effective_from\":\"2026-08-25\",\"effective_to\":\"2027-08-25\",\"currency_code\":\"SAR\",\"basic_salary\":4000},\"period\":{\"id\":4,\"code\":\"PAY-2026-09\",\"start_date\":\"2026-09-01\",\"end_date\":\"2026-09-30\",\"payment_date\":\"2026-09-30\"},\"work_metrics\":{\"scheduled_work_days\":0,\"actual_work_days\":0,\"absent_days\":0,\"paid_leave_days\":0,\"unpaid_leave_days\":0,\"overtime_minutes\":0,\"proration_ratio\":1},\"totals\":{\"basic_salary\":4000,\"gross_salary\":4000,\"total_earnings\":4000,\"total_deductions\":100,\"net_salary\":3900},\"warnings\":[]}', NULL, '{\"warnings\":[],\"calculated_at\":\"2026-09-09T13:50:13+00:00\"}', '2026-09-09 10:50:13', '2026-09-09 10:51:19', NULL),
(12, '0ed7399e-0487-435b-a87c-b64731939d74', 3, 6, 1, 2, 'SAR', 3000.0000, 3000.0000, 3000.0000, 400.0000, 2600.0000, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'approved', '{\"employee\":{\"id\":1,\"employee_number\":\"02144\",\"name\":\"\\u0639\\u0645\\u0631 \\u062e\\u0627\\u0644\\u062f \\u0639\\u0645\\u0631 \\u0639\\u0628\\u062f\\u0627\\u0644\\u0641\\u0636\\u064a\\u0644\"},\"salary_structure\":{\"id\":2,\"uuid\":\"a38e777b-74f0-4433-8a49-06937ccab730\",\"version\":1,\"effective_from\":\"2026-09-01\",\"effective_to\":\"2027-09-01\",\"currency_code\":\"SAR\",\"basic_salary\":3000},\"period\":{\"id\":5,\"code\":\"PAY-2026-10\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-31\",\"payment_date\":\"2026-10-31\"},\"work_metrics\":{\"scheduled_work_days\":0,\"actual_work_days\":0,\"absent_days\":0,\"paid_leave_days\":0,\"unpaid_leave_days\":0,\"overtime_minutes\":0,\"proration_ratio\":1},\"totals\":{\"basic_salary\":3000,\"gross_salary\":3000,\"total_earnings\":3000,\"total_deductions\":400,\"net_salary\":2600},\"warnings\":[]}', NULL, '{\"warnings\":[],\"calculated_at\":\"2026-09-09T14:56:09+00:00\"}', '2026-09-09 11:56:09', '2026-09-09 11:56:20', NULL),
(13, 'b13ea349-d498-40b8-afe6-8b8f53e13118', 3, 7, 1, 2, 'SAR', 3000.0000, 3000.0000, 3000.0000, 800.0000, 2200.0000, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'approved', '{\"employee\":{\"id\":1,\"employee_number\":\"02144\",\"name\":\"\\u0639\\u0645\\u0631 \\u062e\\u0627\\u0644\\u062f \\u0639\\u0645\\u0631 \\u0639\\u0628\\u062f\\u0627\\u0644\\u0641\\u0636\\u064a\\u0644\"},\"salary_structure\":{\"id\":2,\"uuid\":\"a38e777b-74f0-4433-8a49-06937ccab730\",\"version\":1,\"effective_from\":\"2026-09-01\",\"effective_to\":\"2027-09-01\",\"currency_code\":\"SAR\",\"basic_salary\":3000},\"period\":{\"id\":5,\"code\":\"PAY-2026-10\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-31\",\"payment_date\":\"2026-10-31\"},\"work_metrics\":{\"scheduled_work_days\":0,\"actual_work_days\":0,\"absent_days\":0,\"paid_leave_days\":0,\"unpaid_leave_days\":0,\"overtime_minutes\":0,\"proration_ratio\":1},\"totals\":{\"basic_salary\":3000,\"gross_salary\":3000,\"total_earnings\":3000,\"total_deductions\":800,\"net_salary\":2200},\"warnings\":[]}', NULL, '{\"warnings\":[],\"calculated_at\":\"2026-09-12T11:28:07+00:00\"}', '2026-09-12 08:28:07', '2026-09-12 08:28:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_run_item_components`
--

CREATE TABLE `payroll_run_item_components` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_item_id` bigint(20) UNSIGNED NOT NULL,
  `salary_component_id` bigint(20) UNSIGNED DEFAULT NULL,
  `component_code` varchar(50) NOT NULL,
  `component_name` varchar(255) NOT NULL,
  `type` enum('earning','deduction') NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `source` enum('salary_structure','attendance','overtime','leave','manual','system') NOT NULL DEFAULT 'salary_structure',
  `quantity` decimal(12,4) DEFAULT NULL,
  `rate` decimal(18,4) DEFAULT NULL,
  `percentage` decimal(9,4) DEFAULT NULL,
  `amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `is_subject_to_insurance` tinyint(1) NOT NULL DEFAULT 0,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_run_item_components`
--

INSERT INTO `payroll_run_item_components` (`id`, `tenant_id`, `payroll_item_id`, `salary_component_id`, `component_code`, `component_name`, `type`, `category`, `source`, `quantity`, `rate`, `percentage`, `amount`, `is_taxable`, `is_subject_to_insurance`, `reference_type`, `reference_id`, `metadata`, `created_at`, `updated_at`) VALUES
(18, 3, 10, NULL, 'BASIC', 'الراتب الأساسي', 'earning', 'basic_salary', 'system', 0.0000, 0.0000, 0.0000, 4000.0000, 0, 1, 'App\\Models\\EmployeeSalaryStructure', 1, '{\"affects_net_salary\":true,\"is_proratable\":true,\"proration_ratio\":1,\"original_amount\":4000,\"generated_by_system\":true}', '2026-09-09 10:50:13', '2026-09-09 10:50:13'),
(19, 3, 10, NULL, 'LOAN_INSTALLMENT', 'قسط سلفة LN-2026-EFQPNDEZ - القسط 1', 'deduction', 'loan', 'system', 1.0000, 100.0000, 0.0000, 100.0000, 0, 0, 'App\\Models\\EmployeeLoanInstallment', 1, '{\"affects_net_salary\":true,\"loan_id\":1,\"loan_uuid\":\"fa803764-78ae-497e-981e-8fe19b4542be\",\"request_number\":\"LN-2026-EFQPNDEZ\",\"installment_number\":1,\"due_date\":\"2026-09-30\",\"generated_by_system\":true}', '2026-09-09 10:50:13', '2026-09-09 10:50:13'),
(22, 3, 12, 3, 'BASIC', 'الراتب الاساسي', 'earning', 'basic_salary', 'salary_structure', 0.0000, 0.0000, 0.0000, 3000.0000, 0, 0, 'App\\Models\\EmployeeSalaryStructure', 2, '{\"salary_structure_component_id\":3,\"salary_structure_version\":1,\"calculation_method\":\"fixed\",\"affects_net_salary\":true,\"is_proratable\":true,\"proration_ratio\":1,\"formula\":null}', '2026-09-09 11:56:09', '2026-09-09 11:56:09'),
(23, 3, 12, NULL, 'LOAN_INSTALLMENT', 'قسط سلفة LN-2026-WPWSJTTD - القسط 1', 'deduction', 'loan', 'system', 1.0000, 400.0000, 0.0000, 400.0000, 0, 0, 'App\\Models\\EmployeeLoanInstallment', 2, '{\"affects_net_salary\":true,\"loan_id\":2,\"loan_uuid\":\"2bb66457-1624-4ec0-89d0-a0ea87ab931a\",\"request_number\":\"LN-2026-WPWSJTTD\",\"installment_number\":1,\"due_date\":\"2026-10-01\",\"generated_by_system\":true}', '2026-09-09 11:56:09', '2026-09-09 11:56:09'),
(24, 3, 13, 3, 'BASIC', 'الراتب الاساسي', 'earning', 'basic_salary', 'salary_structure', 0.0000, 0.0000, 0.0000, 3000.0000, 0, 0, 'App\\Models\\EmployeeSalaryStructure', 2, '{\"salary_structure_component_id\":3,\"salary_structure_version\":1,\"calculation_method\":\"fixed\",\"affects_net_salary\":true,\"is_proratable\":true,\"proration_ratio\":1,\"formula\":null}', '2026-09-12 08:28:07', '2026-09-12 08:28:07'),
(25, 3, 13, NULL, 'LOAN_INSTALLMENT', 'قسط سلفة LN-2026-XDWZBI3F - القسط 1', 'deduction', 'loan', 'system', 1.0000, 800.0000, 0.0000, 800.0000, 0, 0, 'App\\Models\\EmployeeLoanInstallment', 6, '{\"affects_net_salary\":true,\"loan_id\":4,\"loan_uuid\":\"8d300ceb-419d-4e7c-bded-c7a19000b7cb\",\"request_number\":\"LN-2026-XDWZBI3F\",\"installment_number\":1,\"due_date\":\"2026-09-30\",\"generated_by_system\":true}', '2026-09-12 08:28:07', '2026-09-12 08:28:07');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `establishment_name` varchar(255) DEFAULT NULL,
  `establishment_number` varchar(100) DEFAULT NULL,
  `unified_number` varchar(100) DEFAULT NULL,
  `commercial_registration_number` varchar(100) DEFAULT NULL,
  `wps_employer_id` varchar(100) DEFAULT NULL,
  `payroll_bank_name` varchar(255) DEFAULT NULL,
  `payroll_bank_code` varchar(50) DEFAULT NULL,
  `payroll_account_holder_name` varchar(255) DEFAULT NULL,
  `payroll_account_number` text DEFAULT NULL,
  `payroll_iban` text DEFAULT NULL,
  `payroll_iban_last4` varchar(4) DEFAULT NULL,
  `swift_code` varchar(30) DEFAULT NULL,
  `wps_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `default_file_format` varchar(50) NOT NULL DEFAULT 'bank_csv',
  `salary_payment_day` tinyint(3) UNSIGNED DEFAULT NULL,
  `payment_reference_prefix` varchar(50) DEFAULT NULL,
  `require_verified_bank_account` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`id`, `tenant_id`, `establishment_name`, `establishment_number`, `unified_number`, `commercial_registration_number`, `wps_employer_id`, `payroll_bank_name`, `payroll_bank_code`, `payroll_account_holder_name`, `payroll_account_number`, `payroll_iban`, `payroll_iban_last4`, `swift_code`, `wps_enabled`, `default_file_format`, `salary_payment_day`, `payment_reference_prefix`, `require_verified_bank_account`, `created_by`, `updated_by`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 3, 'رؤية يوم', '001', '12121200', '123211321321', '001', 'رؤية يوم', '12121', 'رؤية يوم', 'eyJpdiI6Ikh1Zm5BUW1nRWpJaDY4VXpPR3NQeHc9PSIsInZhbHVlIjoiem5HQmFUVnM4K3hvTjlUaEgraDhjTVBXWnZHSGxPUDk1Q0l4Nmh5NzZxTT0iLCJtYWMiOiIwMTYxOTViY2Q3MzNmMjNiZjVhMzU5ZDViODY5Yjc3YTM5ZTk3MGVkNzE0NTRkZmMxNzJlMjg3YjQ5ZTA1OTRkIiwidGFnIjoiIn0=', 'eyJpdiI6InlnQm5jNFlVUGhCQmtaMkJJdFRudGc9PSIsInZhbHVlIjoiT2JjTk5GTkFBK05US1FvSC9Sem9MMFBtWmVnbXAvbTBPeHlYcjJUN2Z3OD0iLCJtYWMiOiJkZWYzOTMxZGJlYmFiNGU4MTI4YzBhNDk4ZTBhMjg3NGNkYzY1NjVmNGM2NTg0OThjMTZiYTU4YWQ1OTU0ODE3IiwidGFnIjoiIn0=', '7533', NULL, 1, 'csv', 27, 'PAY', 1, 5, 5, NULL, '2026-08-26 11:39:01', '2026-08-27 09:22:22');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view', 'web', '2026-08-08 11:12:00', '2026-08-08 11:12:00'),
(2, 'users.view', 'web', '2026-08-08 11:12:00', '2026-08-08 11:12:00'),
(3, 'users.create', 'web', '2026-08-08 11:12:00', '2026-08-08 11:12:00'),
(4, 'users.update', 'web', '2026-08-08 11:12:00', '2026-08-08 11:12:00'),
(5, 'users.deactivate', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(6, 'roles.view', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(7, 'roles.manage', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(8, 'organization.view', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(9, 'organization.manage', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(10, 'employees.view', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(11, 'employees.create', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(12, 'employees.update', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(13, 'employees.archive', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(14, 'employees.import', 'web', '2026-08-08 11:12:01', '2026-08-08 11:12:01'),
(15, 'employees.export', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(16, 'contracts.view', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(17, 'contracts.create', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(18, 'contracts.update', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(19, 'contracts.end', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(20, 'documents.view', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(21, 'documents.manage', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(22, 'attendance.view', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(23, 'attendance.manage', 'web', '2026-08-08 11:12:02', '2026-08-08 11:12:02'),
(24, 'attendance.approve', 'web', '2026-08-08 11:12:03', '2026-08-08 11:12:03'),
(25, 'leave.view', 'web', '2026-08-08 11:12:03', '2026-08-08 11:12:03'),
(26, 'leave.manage', 'web', '2026-08-08 11:12:03', '2026-08-08 11:12:03'),
(27, 'leave.approve', 'web', '2026-08-08 11:12:03', '2026-08-08 11:12:03'),
(28, 'payroll.view', 'web', '2026-08-08 11:12:03', '2026-08-08 11:12:03'),
(29, 'payroll.manage', 'web', '2026-08-08 11:12:03', '2026-08-08 11:12:03'),
(30, 'payroll.process', 'web', '2026-08-08 11:12:03', '2026-08-08 11:12:03'),
(31, 'payroll.approve', 'web', '2026-08-08 11:12:04', '2026-08-08 11:12:04'),
(32, 'recruitment.view', 'web', '2026-08-08 11:12:04', '2026-08-08 11:12:04'),
(33, 'recruitment.manage', 'web', '2026-08-08 11:12:04', '2026-08-08 11:12:04'),
(34, 'performance.view', 'web', '2026-08-08 11:12:04', '2026-08-08 11:12:04'),
(35, 'performance.manage', 'web', '2026-08-08 11:12:04', '2026-08-08 11:12:04'),
(36, 'training.view', 'web', '2026-08-08 11:12:05', '2026-08-08 11:12:05'),
(37, 'training.manage', 'web', '2026-08-08 11:12:05', '2026-08-08 11:12:05'),
(38, 'reports.view', 'web', '2026-08-08 11:12:05', '2026-08-08 11:12:05'),
(39, 'reports.export', 'web', '2026-08-08 11:12:05', '2026-08-08 11:12:05'),
(40, 'audit.view', 'web', '2026-08-08 11:12:05', '2026-08-08 11:12:05'),
(41, 'settings.view', 'web', '2026-08-08 11:12:05', '2026-08-08 11:12:05'),
(42, 'settings.update', 'web', '2026-08-08 11:12:05', '2026-08-08 11:12:05'),
(43, 'self_service.profile', 'web', '2026-08-08 11:12:06', '2026-08-08 11:12:06'),
(44, 'self_service.leave', 'web', '2026-08-08 11:12:06', '2026-08-08 11:12:06'),
(45, 'self_service.attendance', 'web', '2026-08-08 11:12:06', '2026-08-08 11:12:06'),
(46, 'branches.view', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(47, 'branches.create', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(48, 'branches.update', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(49, 'branches.delete', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(50, 'departments.view', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(51, 'departments.create', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(52, 'departments.update', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(53, 'departments.delete', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(54, 'job_titles.view', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(55, 'job_titles.create', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(56, 'job_titles.update', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(57, 'job_titles.delete', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(58, 'work_locations.view', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(59, 'work_locations.create', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(60, 'work_locations.update', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(61, 'work_locations.delete', 'web', '2026-08-10 11:54:00', '2026-08-10 11:54:00'),
(62, 'self_service.payslips', 'web', '2026-08-26 07:51:19', '2026-08-26 07:51:19'),
(63, 'loans.view', 'web', '2026-09-09 07:39:25', '2026-09-09 07:39:25'),
(64, 'loans.manage', 'web', '2026-09-09 07:39:25', '2026-09-09 07:39:25'),
(65, 'loans.approve', 'web', '2026-09-09 07:39:25', '2026-09-09 07:39:25'),
(66, 'self_service.loans', 'web', '2026-09-09 07:39:25', '2026-09-09 07:39:25');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(52, 'App\\Models\\User', 6, 'mobile-8f52cf238fda246cd34ec82eb7154def', 'b6f4ffc10148e17f7a96ffd5ecefc1eeac09da6506c432c375593af04a0298bc', '[\"mobile\",\"self-service\"]', '2026-09-13 06:29:03', '2026-12-12 06:26:52', '2026-09-13 06:26:52', '2026-09-13 06:29:03');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `monthly_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `yearly_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `trial_days` int(10) UNSIGNED NOT NULL DEFAULT 15,
  `max_users` int(10) UNSIGNED DEFAULT NULL,
  `max_employees` int(10) UNSIGNED DEFAULT NULL,
  `max_branches` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `code`, `name`, `description`, `monthly_price`, `yearly_price`, `currency_code`, `trial_days`, `max_users`, `max_employees`, `max_branches`, `is_active`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'STARTS', 'البداية1', 'خطة البداية للشركات الناشئة', 100.00, 1000.00, 'SAR', 15, 5, 100, 5, 1, 1, '2026-08-06 08:46:16', '2026-08-06 09:44:23', NULL),
(2, 'SU124', 'الشاملة', 'سبي', 150.00, 1500.00, 'SAR', 15, NULL, NULL, 10, 1, 0, '2026-08-06 09:57:54', '2026-08-06 09:57:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `plan_features`
--

CREATE TABLE `plan_features` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `feature_id` bigint(20) UNSIGNED NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plan_features`
--

INSERT INTO `plan_features` (`id`, `plan_id`, `feature_id`, `value`, `created_at`, `updated_at`) VALUES
(1, 1, 8, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(2, 1, 7, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(3, 1, 9, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(4, 1, 20, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(5, 1, 1, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(6, 1, 2, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(7, 1, 3, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(8, 1, 18, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(9, 1, 19, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(10, 1, 10, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(11, 1, 5, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(12, 1, 6, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(13, 1, 4, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(14, 1, 11, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(15, 1, 13, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(16, 1, 12, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(17, 1, 17, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(19, 1, 14, '1', '2026-08-06 09:40:31', '2026-08-06 09:42:03'),
(21, 1, 15, '1', '2026-08-06 09:42:03', '2026-08-06 09:42:03'),
(22, 1, 16, '1', '2026-08-06 09:42:03', '2026-08-06 09:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `tenant_id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'tenant_owner', 'web', '2026-08-08 11:12:06', '2026-08-08 11:12:06'),
(2, 1, 'hr_manager', 'web', '2026-08-08 11:12:06', '2026-08-08 11:12:06'),
(3, 1, 'hr_officer', 'web', '2026-08-08 11:12:06', '2026-08-08 11:12:06'),
(4, 1, 'payroll_manager', 'web', '2026-08-08 11:12:06', '2026-08-08 11:12:06'),
(5, 1, 'manager', 'web', '2026-08-08 11:12:07', '2026-08-08 11:12:07'),
(6, 1, 'employee', 'web', '2026-08-08 11:12:07', '2026-08-08 11:12:07'),
(7, 2, 'tenant_owner', 'web', '2026-08-08 11:12:07', '2026-08-08 11:12:07'),
(8, 2, 'hr_manager', 'web', '2026-08-08 11:12:07', '2026-08-08 11:12:07'),
(9, 2, 'hr_officer', 'web', '2026-08-08 11:12:07', '2026-08-08 11:12:07'),
(10, 2, 'payroll_manager', 'web', '2026-08-08 11:12:08', '2026-08-08 11:12:08'),
(11, 2, 'manager', 'web', '2026-08-08 11:12:08', '2026-08-08 11:12:08'),
(12, 2, 'employee', 'web', '2026-08-08 11:12:08', '2026-08-08 11:12:08'),
(14, 3, 'tenant_owner', 'web', '2026-08-09 11:34:22', '2026-08-09 11:34:22'),
(15, 3, 'hr_manager', 'web', '2026-08-09 11:34:23', '2026-08-09 11:34:23'),
(16, 3, 'hr_officer', 'web', '2026-08-09 11:34:23', '2026-08-09 11:34:23'),
(17, 3, 'payroll_manager', 'web', '2026-08-09 11:34:23', '2026-08-09 11:34:23'),
(18, 3, 'manager', 'web', '2026-08-09 11:34:23', '2026-08-09 11:34:23'),
(19, 3, 'employee', 'web', '2026-08-09 11:34:23', '2026-08-09 11:34:23');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(2, 1),
(2, 2),
(2, 7),
(2, 8),
(2, 14),
(2, 15),
(3, 1),
(3, 7),
(3, 14),
(4, 1),
(4, 7),
(4, 14),
(5, 1),
(5, 7),
(5, 14),
(6, 1),
(6, 4),
(6, 7),
(6, 14),
(7, 1),
(7, 4),
(7, 7),
(7, 14),
(8, 1),
(8, 2),
(8, 3),
(8, 7),
(8, 8),
(8, 9),
(8, 14),
(8, 15),
(8, 16),
(9, 1),
(9, 2),
(9, 7),
(9, 8),
(9, 14),
(9, 15),
(10, 1),
(10, 2),
(10, 3),
(10, 4),
(10, 7),
(10, 8),
(10, 9),
(10, 10),
(10, 14),
(10, 15),
(10, 16),
(10, 17),
(11, 1),
(11, 2),
(11, 3),
(11, 7),
(11, 8),
(11, 9),
(11, 14),
(11, 15),
(11, 16),
(12, 1),
(12, 2),
(12, 3),
(12, 7),
(12, 8),
(12, 9),
(12, 14),
(12, 15),
(12, 16),
(13, 1),
(13, 2),
(13, 7),
(13, 8),
(13, 14),
(13, 15),
(14, 1),
(14, 2),
(14, 7),
(14, 8),
(14, 14),
(14, 15),
(15, 1),
(15, 2),
(15, 7),
(15, 8),
(15, 14),
(15, 15),
(16, 1),
(16, 2),
(16, 3),
(16, 4),
(16, 7),
(16, 8),
(16, 9),
(16, 10),
(16, 14),
(16, 15),
(16, 16),
(16, 17),
(17, 1),
(17, 2),
(17, 3),
(17, 4),
(17, 7),
(17, 8),
(17, 9),
(17, 14),
(17, 15),
(17, 16),
(18, 1),
(18, 2),
(18, 3),
(18, 4),
(18, 7),
(18, 8),
(18, 9),
(18, 14),
(18, 15),
(18, 16),
(19, 1),
(19, 2),
(19, 4),
(19, 7),
(19, 8),
(19, 14),
(19, 15),
(20, 1),
(20, 2),
(20, 3),
(20, 7),
(20, 8),
(20, 9),
(20, 14),
(20, 15),
(20, 16),
(21, 1),
(21, 2),
(21, 3),
(21, 7),
(21, 8),
(21, 9),
(21, 14),
(21, 15),
(21, 16),
(22, 1),
(22, 2),
(22, 3),
(22, 5),
(22, 7),
(22, 8),
(22, 9),
(22, 11),
(22, 14),
(22, 15),
(22, 16),
(22, 18),
(23, 1),
(23, 2),
(23, 3),
(23, 7),
(23, 8),
(23, 9),
(23, 14),
(23, 15),
(23, 16),
(24, 1),
(24, 2),
(24, 5),
(24, 7),
(24, 8),
(24, 11),
(24, 14),
(24, 15),
(24, 18),
(25, 1),
(25, 2),
(25, 3),
(25, 5),
(25, 7),
(25, 8),
(25, 9),
(25, 11),
(25, 14),
(25, 15),
(25, 16),
(25, 18),
(26, 1),
(26, 2),
(26, 3),
(26, 7),
(26, 8),
(26, 9),
(26, 14),
(26, 15),
(26, 16),
(27, 1),
(27, 2),
(27, 5),
(27, 7),
(27, 8),
(27, 11),
(27, 14),
(27, 15),
(27, 18),
(28, 1),
(28, 4),
(28, 7),
(28, 10),
(28, 14),
(28, 17),
(29, 1),
(29, 4),
(29, 7),
(29, 10),
(29, 14),
(29, 17),
(30, 1),
(30, 4),
(30, 7),
(30, 10),
(30, 14),
(30, 17),
(31, 1),
(31, 4),
(31, 7),
(31, 10),
(31, 14),
(31, 17),
(32, 1),
(32, 2),
(32, 3),
(32, 7),
(32, 8),
(32, 9),
(32, 14),
(32, 15),
(32, 16),
(33, 1),
(33, 2),
(33, 3),
(33, 7),
(33, 8),
(33, 9),
(33, 14),
(33, 15),
(33, 16),
(34, 1),
(34, 2),
(34, 5),
(34, 7),
(34, 8),
(34, 11),
(34, 14),
(34, 15),
(34, 18),
(35, 1),
(35, 2),
(35, 5),
(35, 7),
(35, 8),
(35, 11),
(35, 14),
(35, 15),
(35, 18),
(36, 1),
(36, 2),
(36, 3),
(36, 7),
(36, 8),
(36, 9),
(36, 14),
(36, 15),
(36, 16),
(37, 1),
(37, 2),
(37, 7),
(37, 8),
(37, 14),
(37, 15),
(38, 1),
(38, 2),
(38, 3),
(38, 4),
(38, 7),
(38, 8),
(38, 9),
(38, 10),
(38, 14),
(38, 15),
(38, 16),
(38, 17),
(39, 1),
(39, 2),
(39, 4),
(39, 7),
(39, 8),
(39, 10),
(39, 14),
(39, 15),
(39, 17),
(40, 1),
(40, 2),
(40, 7),
(40, 8),
(40, 14),
(40, 15),
(41, 1),
(41, 7),
(41, 14),
(42, 1),
(42, 7),
(42, 14),
(43, 1),
(43, 5),
(43, 6),
(43, 7),
(43, 11),
(43, 12),
(43, 14),
(43, 18),
(43, 19),
(44, 1),
(44, 5),
(44, 6),
(44, 7),
(44, 11),
(44, 12),
(44, 14),
(44, 18),
(44, 19),
(45, 1),
(45, 5),
(45, 6),
(45, 7),
(45, 11),
(45, 12),
(45, 14),
(45, 18),
(45, 19),
(46, 1),
(46, 7),
(46, 14),
(47, 1),
(47, 7),
(47, 14),
(48, 1),
(48, 7),
(48, 14),
(49, 1),
(49, 7),
(49, 14),
(50, 1),
(50, 7),
(50, 14),
(51, 1),
(51, 7),
(51, 14),
(52, 1),
(52, 7),
(52, 14),
(53, 1),
(53, 7),
(53, 14),
(54, 1),
(54, 7),
(54, 14),
(55, 1),
(55, 7),
(55, 14),
(56, 1),
(56, 7),
(56, 14),
(57, 1),
(57, 7),
(57, 14),
(58, 1),
(58, 7),
(58, 14),
(59, 1),
(59, 7),
(59, 14),
(60, 1),
(60, 7),
(60, 14),
(61, 1),
(61, 7),
(61, 14),
(62, 14),
(62, 15),
(62, 16),
(62, 17),
(62, 18),
(62, 19),
(63, 1),
(63, 7),
(63, 14),
(64, 1),
(64, 7),
(64, 14),
(65, 1),
(65, 7),
(65, 14),
(66, 1),
(66, 6),
(66, 7),
(66, 12),
(66, 14),
(66, 19);

-- --------------------------------------------------------

--
-- Table structure for table `salary_components`
--

CREATE TABLE `salary_components` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `type` enum('earning','deduction') NOT NULL,
  `category` enum('basic_salary','allowance','bonus','commission','overtime','reimbursement','tax','insurance','loan','absence','penalty','other') NOT NULL DEFAULT 'other',
  `calculation_method` enum('fixed','percentage','formula','quantity_rate') NOT NULL DEFAULT 'fixed',
  `percentage_base_component_id` bigint(20) UNSIGNED DEFAULT NULL,
  `default_amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `default_percentage` decimal(9,4) DEFAULT NULL,
  `default_rate` decimal(18,4) DEFAULT NULL,
  `formula` text DEFAULT NULL,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `is_subject_to_insurance` tinyint(1) NOT NULL DEFAULT 0,
  `is_included_in_overtime_base` tinyint(1) NOT NULL DEFAULT 0,
  `is_proratable` tinyint(1) NOT NULL DEFAULT 1,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 1,
  `requires_input` tinyint(1) NOT NULL DEFAULT 0,
  `affects_net_salary` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `salary_components`
--

INSERT INTO `salary_components` (`id`, `uuid`, `tenant_id`, `code`, `name`, `name_en`, `type`, `category`, `calculation_method`, `percentage_base_component_id`, `default_amount`, `default_percentage`, `default_rate`, `formula`, `is_taxable`, `is_subject_to_insurance`, `is_included_in_overtime_base`, `is_proratable`, `is_recurring`, `requires_input`, `affects_net_salary`, `is_system`, `is_active`, `sort_order`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, '91190cde-f8cb-49e9-8094-4ca240020407', 3, 'BASIC', 'الراتب الاساسي', 'basic', 'earning', 'basic_salary', 'fixed', NULL, 3000.0000, NULL, NULL, NULL, 0, 0, 0, 1, 1, 0, 1, 0, 1, 0, '{\"audit\":{\"last_action\":\"created\",\"last_actor_id\":5,\"last_action_at\":\"2026-09-09T14:49:25+00:00\",\"created_by\":5,\"created_at\":\"2026-09-09T14:49:25+00:00\"}}', '2026-09-09 11:49:25', '2026-09-09 11:49:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('w4Noh0lkzvFhDQi0gJaBpqHSr7HyC77DEMzp0msc', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVVhWdVFDcVNvaUNNSlU4ZndwU2tnMDJHeGZYaDVsMTg0cmE4T21lNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcHAvbG9naW4iO3M6NToicm91dGUiO3M6OToiYXBwLmxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789287921),
('zGOhKjGxeapvrbLzlWESYiIzy4Qq78S7qYz8HP3V', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiWFN5eXY2Q2lTcUVDRkNsVHRvaDRBRTBGQjZZYTcyeFJJUmNuWVNCRyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjUwOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBwL29yZ2FuaXphdGlvbi9kZXBhcnRtZW50cyI7czo1OiJyb3V0ZSI7czozNDoiYXBwLm9yZ2FuaXphdGlvbi5kZXBhcnRtZW50cy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjU7fQ==', 1789224059);

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'trial',
  `billing_cycle` varchar(30) NOT NULL DEFAULT 'monthly',
  `price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `plan_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`plan_snapshot`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `uuid`, `tenant_id`, `plan_id`, `status`, `billing_cycle`, `price`, `currency_code`, `starts_at`, `trial_ends_at`, `ends_at`, `auto_renew`, `cancelled_at`, `cancellation_reason`, `plan_snapshot`, `metadata`, `created_at`, `updated_at`) VALUES
(6, '71e39a7a-7be6-4b67-9543-a7b24dcce63d', 1, 2, 'expired', 'yearly', 1500.00, 'SAR', '2026-08-08 11:53:32', NULL, '2026-08-08 08:53:32', 1, NULL, NULL, NULL, '{\"events\":[{\"type\":\"suspended\",\"at\":\"2026-08-08T11:40:48+00:00\",\"actor_id\":1,\"data\":[]},{\"type\":\"resumed\",\"at\":\"2026-08-08T11:51:39+00:00\",\"actor_id\":1,\"data\":[]},{\"type\":\"plan_changed\",\"at\":\"2026-08-08T11:53:32+00:00\",\"actor_id\":1,\"data\":{\"new_plan_id\":1}}]}', '2026-08-08 08:07:56', '2026-08-08 08:53:32'),
(7, 'ffaf83a5-376b-4110-8761-b9da53221c30', 1, 1, 'active', 'monthly', 100.00, 'SAR', '2026-08-08 08:53:32', NULL, '2026-09-08 08:53:32', 1, NULL, NULL, '{\"id\":1,\"code\":\"STARTS\",\"name\":\"\\u0627\\u0644\\u0628\\u062f\\u0627\\u064a\\u06291\",\"monthly_price\":\"100.00\",\"yearly_price\":\"1000.00\",\"currency_code\":\"SAR\",\"trial_days\":15,\"limits\":{\"users\":5,\"employees\":100,\"branches\":5},\"features\":[{\"code\":\"attendance.shifts\",\"value\":\"1\"},{\"code\":\"attendance.basic\",\"value\":\"1\"},{\"code\":\"attendance.overtime\",\"value\":\"1\"},{\"code\":\"audit.advanced\",\"value\":\"1\"},{\"code\":\"hr.employees\",\"value\":\"1\"},{\"code\":\"hr.contracts\",\"value\":\"1\"},{\"code\":\"hr.documents\",\"value\":\"1\"},{\"code\":\"integration.api\",\"value\":\"1\"},{\"code\":\"integration.webhooks\",\"value\":\"1\"},{\"code\":\"leave.management\",\"value\":\"1\"},{\"code\":\"organization.branches\",\"value\":\"1\"},{\"code\":\"organization.structure\",\"value\":\"1\"},{\"code\":\"organization.multi_company\",\"value\":\"1\"},{\"code\":\"payroll.management\",\"value\":\"1\"},{\"code\":\"performance.management\",\"value\":\"1\"},{\"code\":\"recruitment.management\",\"value\":\"1\"},{\"code\":\"reports.advanced\",\"value\":\"1\"},{\"code\":\"training.management\",\"value\":\"1\"},{\"code\":\"self_service.portal\",\"value\":\"1\"},{\"code\":\"workflow.approvals\",\"value\":\"1\"}]}', '{\"source\":\"plan_change\",\"created_by\":1,\"events\":[],\"previous_subscription_id\":6}', '2026-08-08 08:53:33', '2026-08-08 08:53:33'),
(8, 'bc8b97c6-cb46-40c5-80d9-30fcd24cb8c4', 3, 2, 'expired', 'yearly', 0.00, 'SAR', '2026-08-09 14:44:32', '2026-08-23 18:00:00', '2026-08-09 11:44:32', 1, NULL, NULL, '{\"id\":2,\"code\":\"SU124\",\"name\":\"\\u0627\\u0644\\u0634\\u0627\\u0645\\u0644\\u0629\",\"monthly_price\":\"150.00\",\"yearly_price\":\"1500.00\",\"currency_code\":\"SAR\",\"trial_days\":15,\"limits\":{\"users\":null,\"employees\":null,\"branches\":10},\"features\":[]}', '{\"source\":\"system_admin\",\"created_by\":1,\"events\":[{\"type\":\"trial_converted\",\"at\":\"2026-08-09T14:44:32+00:00\",\"actor_id\":1,\"data\":[]}]}', '2026-08-09 11:34:24', '2026-08-09 11:44:32'),
(9, '1d34ca46-0333-4d77-a69c-75812b8c7465', 3, 2, 'active', 'yearly', 1500.00, 'SAR', '2026-08-09 11:44:32', NULL, '2027-08-09 11:44:32', 1, NULL, NULL, '{\"id\":2,\"code\":\"SU124\",\"name\":\"\\u0627\\u0644\\u0634\\u0627\\u0645\\u0644\\u0629\",\"monthly_price\":\"150.00\",\"yearly_price\":\"1500.00\",\"currency_code\":\"SAR\",\"trial_days\":15,\"limits\":{\"users\":null,\"employees\":null,\"branches\":10},\"features\":[]}', '{\"source\":\"trial_conversion\",\"created_by\":1,\"events\":[],\"previous_subscription_id\":8}', '2026-08-09 11:44:32', '2026-08-09 11:44:32');

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'SA',
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Riyadh',
  `locale` varchar(10) NOT NULL DEFAULT 'ar',
  `currency_code` char(3) NOT NULL DEFAULT 'SAR',
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `uuid`, `code`, `name`, `slug`, `contact_name`, `email`, `phone`, `country_code`, `timezone`, `locale`, `currency_code`, `status`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'fd665980-16bc-42a1-bb71-6b042489288b', '1000104', 'شركة المشوار لتاجير السيارات1', '1000104', 'عمر خالد عمر', 'omer9066359591@gmail.com', '050943752', 'SA', 'Asia/Riyadh', 'ar', 'SAR', 'active', NULL, '2026-08-06 08:34:17', '2026-08-09 11:32:22', '2026-08-09 11:32:22'),
(2, '6f5e13ba-5fb4-4ec5-87e5-6f47cf9e04d9', '500', 'عمر خالد عمر', '500', 'عمر خالد عمر', 'omer9066359591@gmail.com', '050943752', 'SA', 'Asia/Riyadh', 'ar', 'SAR', 'active', NULL, '2026-08-06 09:57:06', '2026-08-09 11:32:29', '2026-08-09 11:32:29'),
(3, '3b09be59-677e-4f53-9913-e7b6fcc75889', 'SU124', 'شركة النعيم للمقاولات المحدودة', 'su124', 'عمر خالد عمر', 'omer@gmail.com', '050943752', 'SA', 'Asia/Riyadh', 'ar', 'SAR', 'active', NULL, '2026-08-09 11:34:20', '2026-08-09 11:34:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_system_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `locale` varchar(10) NOT NULL DEFAULT 'ar',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `tenant_id`, `name`, `email`, `email_verified_at`, `password`, `is_system_admin`, `is_active`, `locale`, `last_login_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'omer khalid', 'admin@admin.com', NULL, '$2y$12$M5kNdmhxWT4aNVUFFEhq6.TyYExygkbwIEFWBWFexvwfQMrGg9Pq.', 1, 1, 'ar', '2026-09-07 10:52:46', NULL, '2026-08-06 07:42:11', '2026-09-07 10:52:46', NULL),
(3, 1, 'omer', 'omer@omer.omer', NULL, '$2y$12$M5kNdmhxWT4aNVUFFEhq6.TyYExygkbwIEFWBWFexvwfQMrGg9Pq.', 0, 1, 'ar', '2026-09-07 10:50:16', NULL, '2026-08-08 07:55:10', '2026-09-07 10:50:16', NULL),
(4, 1, 'images', 'omer9066359591@yahoo.com', NULL, '$2y$12$dfS1EJMcZB1OeyVjhSOkZeGI6P6z39hvpcA/s748jHqkdvbdugA3y', 0, 1, 'ar', '2026-09-07 10:48:13', NULL, '2026-08-08 11:51:40', '2026-09-07 10:48:13', NULL),
(5, 3, 'مدير النظام', 'omer@gmail.com', NULL, '$2y$12$M5kNdmhxWT4aNVUFFEhq6.TyYExygkbwIEFWBWFexvwfQMrGg9Pq.', 0, 1, 'ar', '2026-09-12 08:18:38', 'ctoRWX2YL6jUPQSNWJDrj65sLZKualsqhoLvusOk7qub0gPrFemIaef9A5kz', '2026-08-09 11:34:21', '2026-09-12 08:18:38', NULL),
(6, 3, 'عمر خالد عمر', 'omer-khalid@r-yoom.com', NULL, '$2y$12$NSpCnqdUgAHM20/x8l8pb.Qc0bzKksOg6a4Bv1k1gqBCjFO30gwZa', 0, 1, 'ar', '2026-09-13 06:26:52', NULL, '2026-08-09 11:41:38', '2026-09-13 06:26:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `work_locations`
--

CREATE TABLE `work_locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `type` enum('office','site','warehouse','remote','other') NOT NULL DEFAULT 'office',
  `country_code` varchar(2) NOT NULL DEFAULT 'SA',
  `city` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `attendance_radius` int(10) UNSIGNED NOT NULL DEFAULT 100,
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Riyadh',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_locations`
--

INSERT INTO `work_locations` (`id`, `tenant_id`, `branch_id`, `code`, `name`, `name_en`, `type`, `country_code`, `city`, `address`, `latitude`, `longitude`, `attendance_radius`, `timezone`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, NULL, '1000202', 'مكتب عسفان', 'Asfan Office', 'office', 'SA', ',', 'طريق عسفان\r\n,', 21.9178733, 39.3105956, 300, 'Asia/Riyadh', 1, NULL, '2026-08-11 08:09:24', '2026-09-08 07:55:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `work_shifts`
--

CREATE TABLE `work_shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_policy_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `shift_type` enum('regular','flexible','night') NOT NULL DEFAULT 'regular',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `crosses_midnight` tinyint(1) NOT NULL DEFAULT 0,
  `break_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `working_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 480,
  `work_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`work_days`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_shifts`
--

INSERT INTO `work_shifts` (`id`, `uuid`, `tenant_id`, `attendance_policy_id`, `code`, `name`, `name_en`, `shift_type`, `start_time`, `end_time`, `crosses_midnight`, `break_minutes`, `working_minutes`, `work_days`, `is_default`, `is_active`, `metadata`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '4a3b71e3-05ca-4bb8-ac8a-f2450b484849', 3, 1, 'BASIC', 'الوردية الاساسية', 'Basic', 'regular', '08:00:00', '16:00:00', 0, 60, 420, '[0,1,2,3,4,6]', 1, 1, NULL, '2026-08-17 10:17:32', '2026-08-18 10:47:33', NULL),
(2, 'bd99df1b-2d20-4c26-b6c1-b889dcde4719', 3, 1, 'SU124', 'شركة المشوار لتاجير السيارات', 'Maintenance', 'regular', '19:42:00', '16:43:00', 1, 60, 1201, '[0,1,2,3,4]', 0, 1, NULL, '2026-09-07 10:42:55', '2026-09-07 11:21:19', '2026-09-07 11:21:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_adjustments`
--
ALTER TABLE `attendance_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_adjustments_uuid_unique` (`uuid`),
  ADD KEY `attendance_adjustments_attendance_record_id_foreign` (`attendance_record_id`),
  ADD KEY `attendance_adjustments_requested_by_foreign` (`requested_by`),
  ADD KEY `attendance_adjustments_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `attendance_adjustments_tenant_id_status_created_at_index` (`tenant_id`,`status`,`created_at`);

--
-- Indexes for table `attendance_breaks`
--
ALTER TABLE `attendance_breaks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_breaks_attendance_record_id_foreign` (`attendance_record_id`),
  ADD KEY `attendance_breaks_tenant_id_attendance_record_id_index` (`tenant_id`,`attendance_record_id`);

--
-- Indexes for table `attendance_policies`
--
ALTER TABLE `attendance_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_policies_tenant_id_code_unique` (`tenant_id`,`code`),
  ADD UNIQUE KEY `attendance_policies_uuid_unique` (`uuid`),
  ADD KEY `attendance_policies_tenant_id_is_default_is_active_index` (`tenant_id`,`is_default`,`is_active`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_employee_date_unique` (`tenant_id`,`employee_id`,`attendance_date`),
  ADD UNIQUE KEY `attendance_records_uuid_unique` (`uuid`),
  ADD KEY `attendance_records_employee_id_foreign` (`employee_id`),
  ADD KEY `attendance_records_work_shift_id_foreign` (`work_shift_id`),
  ADD KEY `attendance_records_work_location_id_foreign` (`work_location_id`),
  ADD KEY `attendance_records_approved_by_foreign` (`approved_by`),
  ADD KEY `attendance_records_created_by_foreign` (`created_by`),
  ADD KEY `attendance_records_tenant_id_attendance_date_status_index` (`tenant_id`,`attendance_date`,`status`),
  ADD KEY `attendance_approval_date_index` (`tenant_id`,`approval_status`,`attendance_date`),
  ADD KEY `attendance_auto_check_out_index` (`check_out_at`,`deleted_at`,`scheduled_check_out_at`,`tenant_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branches_tenant_id_code_unique` (`tenant_id`,`code`),
  ADD KEY `branches_tenant_id_is_active_index` (`tenant_id`,`is_active`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_tenant_id_code_unique` (`tenant_id`,`code`),
  ADD KEY `departments_branch_id_foreign` (`branch_id`),
  ADD KEY `departments_parent_id_foreign` (`parent_id`),
  ADD KEY `departments_tenant_id_branch_id_parent_id_index` (`tenant_id`,`branch_id`,`parent_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_tenant_id_employee_number_unique` (`tenant_id`,`employee_number`),
  ADD UNIQUE KEY `employees_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `employees_tenant_id_attendance_code_unique` (`tenant_id`,`attendance_code`),
  ADD UNIQUE KEY `employees_tenant_id_identity_number_unique` (`tenant_id`,`identity_number`),
  ADD UNIQUE KEY `employees_tenant_id_work_email_unique` (`tenant_id`,`work_email`),
  ADD UNIQUE KEY `employees_tenant_id_user_id_unique` (`tenant_id`,`user_id`),
  ADD KEY `employees_user_id_foreign` (`user_id`),
  ADD KEY `employees_branch_id_foreign` (`branch_id`),
  ADD KEY `employees_department_id_foreign` (`department_id`),
  ADD KEY `employees_job_title_id_foreign` (`job_title_id`),
  ADD KEY `employees_work_location_id_foreign` (`work_location_id`),
  ADD KEY `employees_manager_id_foreign` (`manager_id`),
  ADD KEY `employees_tenant_id_employment_status_index` (`tenant_id`,`employment_status`),
  ADD KEY `employees_tenant_id_branch_id_department_id_index` (`tenant_id`,`branch_id`,`department_id`),
  ADD KEY `employees_tenant_id_job_title_id_index` (`tenant_id`,`job_title_id`),
  ADD KEY `employees_tenant_id_manager_id_index` (`tenant_id`,`manager_id`),
  ADD KEY `employees_tenant_id_hire_date_termination_date_index` (`tenant_id`,`hire_date`,`termination_date`);

--
-- Indexes for table `employee_bank_accounts`
--
ALTER TABLE `employee_bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_bank_accounts_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `employee_bank_iban_unique` (`tenant_id`,`employee_id`,`iban_hash`),
  ADD KEY `employee_bank_accounts_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_bank_accounts_verified_by_foreign` (`verified_by`),
  ADD KEY `employee_bank_accounts_created_by_foreign` (`created_by`),
  ADD KEY `employee_bank_accounts_updated_by_foreign` (`updated_by`),
  ADD KEY `employee_bank_accounts_tenant_id_employee_id_is_active_index` (`tenant_id`,`employee_id`,`is_active`),
  ADD KEY `employee_bank_accounts_tenant_id_employee_id_is_primary_index` (`tenant_id`,`employee_id`,`is_primary`),
  ADD KEY `employee_bank_accounts_tenant_id_is_verified_index` (`tenant_id`,`is_verified`);

--
-- Indexes for table `employee_contracts`
--
ALTER TABLE `employee_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_contracts_tenant_id_contract_number_unique` (`tenant_id`,`contract_number`),
  ADD UNIQUE KEY `employee_contracts_uuid_unique` (`uuid`),
  ADD KEY `employee_contracts_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_contracts_renewed_from_id_foreign` (`renewed_from_id`),
  ADD KEY `employee_contracts_activated_by_foreign` (`activated_by`),
  ADD KEY `employee_contracts_terminated_by_foreign` (`terminated_by`),
  ADD KEY `employee_contracts_tenant_id_employee_id_status_index` (`tenant_id`,`employee_id`,`status`),
  ADD KEY `employee_contracts_tenant_id_status_end_date_index` (`tenant_id`,`status`,`end_date`),
  ADD KEY `employee_contracts_tenant_id_start_date_end_date_index` (`tenant_id`,`start_date`,`end_date`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_documents_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `employee_documents_tenant_id_document_number_unique` (`tenant_id`,`document_number`),
  ADD KEY `employee_documents_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_documents_verified_by_foreign` (`verified_by`),
  ADD KEY `employee_documents_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `employee_documents_tenant_id_employee_id_document_type_index` (`tenant_id`,`employee_id`,`document_type`),
  ADD KEY `employee_documents_tenant_id_expiry_date_is_verified_index` (`tenant_id`,`expiry_date`,`is_verified`);

--
-- Indexes for table `employee_loans`
--
ALTER TABLE `employee_loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_loans_request_unique` (`tenant_id`,`request_number`),
  ADD UNIQUE KEY `employee_loans_uuid_unique` (`uuid`),
  ADD KEY `employee_loans_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_loans_approved_by_foreign` (`approved_by`),
  ADD KEY `employee_loans_rejected_by_foreign` (`rejected_by`),
  ADD KEY `employee_loans_cancelled_by_foreign` (`cancelled_by`),
  ADD KEY `employee_loans_created_by_foreign` (`created_by`),
  ADD KEY `employee_loans_employee_status_index` (`tenant_id`,`employee_id`,`status`),
  ADD KEY `employee_loans_status_date_index` (`tenant_id`,`status`,`first_installment_date`);

--
-- Indexes for table `employee_loan_installments`
--
ALTER TABLE `employee_loan_installments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_loan_installment_unique` (`employee_loan_id`,`installment_number`),
  ADD UNIQUE KEY `employee_loan_installments_uuid_unique` (`uuid`),
  ADD KEY `employee_loan_installments_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_loan_installments_payroll_run_item_id_foreign` (`payroll_run_item_id`),
  ADD KEY `loan_installments_due_index` (`tenant_id`,`employee_id`,`status`,`due_date`),
  ADD KEY `loan_installments_payroll_index` (`payroll_run_id`,`payroll_run_item_id`);

--
-- Indexes for table `employee_mobile_devices`
--
ALTER TABLE `employee_mobile_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_devices_unique` (`tenant_id`,`user_id`,`device_uuid`),
  ADD KEY `employee_mobile_devices_user_id_foreign` (`user_id`),
  ADD KEY `employee_mobile_devices_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_devices_active_index` (`tenant_id`,`employee_id`,`is_active`);

--
-- Indexes for table `employee_salary_components`
--
ALTER TABLE `employee_salary_components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_salary_component_unique` (`salary_structure_id`,`salary_component_id`),
  ADD KEY `employee_salary_components_salary_component_id_foreign` (`salary_component_id`),
  ADD KEY `employee_salary_component_lookup` (`tenant_id`,`salary_component_id`);

--
-- Indexes for table `employee_salary_structures`
--
ALTER TABLE `employee_salary_structures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `salary_structures_version_unique` (`tenant_id`,`employee_id`,`version`),
  ADD UNIQUE KEY `employee_salary_structures_uuid_unique` (`uuid`),
  ADD KEY `employee_salary_structures_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_salary_structures_approved_by_foreign` (`approved_by`),
  ADD KEY `employee_salary_structures_created_by_foreign` (`created_by`),
  ADD KEY `salary_structures_employee_index` (`tenant_id`,`employee_id`,`status`),
  ADD KEY `salary_structures_effective_index` (`effective_from`,`effective_to`);

--
-- Indexes for table `employee_shift_assignments`
--
ALTER TABLE `employee_shift_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_shift_assignments_employee_id_foreign` (`employee_id`),
  ADD KEY `employee_shift_assignments_work_shift_id_foreign` (`work_shift_id`),
  ADD KEY `employee_shift_assignments_created_by_foreign` (`created_by`),
  ADD KEY `employee_shift_assignment_period_index` (`tenant_id`,`employee_id`,`effective_from`,`effective_to`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `features`
--
ALTER TABLE `features`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `features_code_unique` (`code`),
  ADD KEY `features_module_index` (`module`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `holidays_tenant_id_code_unique` (`tenant_id`,`code`),
  ADD UNIQUE KEY `holidays_uuid_unique` (`uuid`),
  ADD KEY `holidays_branch_id_foreign` (`branch_id`),
  ADD KEY `holidays_tenant_dates_index` (`tenant_id`,`start_date`,`end_date`,`is_active`),
  ADD KEY `holidays_branch_index` (`tenant_id`,`branch_id`,`is_active`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_titles`
--
ALTER TABLE `job_titles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_titles_tenant_id_code_unique` (`tenant_id`,`code`),
  ADD KEY `job_titles_department_id_foreign` (`department_id`),
  ADD KEY `job_titles_tenant_id_department_id_is_active_index` (`tenant_id`,`department_id`,`is_active`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_balances_employee_type_year_unique` (`tenant_id`,`employee_id`,`leave_type_id`,`year`),
  ADD KEY `leave_balances_employee_id_foreign` (`employee_id`),
  ADD KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_balances_tenant_year_idx` (`tenant_id`,`year`);

--
-- Indexes for table `leave_balance_transactions`
--
ALTER TABLE `leave_balance_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_balance_transactions_uuid_unique` (`uuid`),
  ADD KEY `leave_balance_transactions_leave_balance_id_foreign` (`leave_balance_id`),
  ADD KEY `leave_balance_transactions_leave_request_id_foreign` (`leave_request_id`),
  ADD KEY `leave_balance_transactions_created_by_foreign` (`created_by`),
  ADD KEY `leave_balance_transactions_date_idx` (`tenant_id`,`leave_balance_id`,`effective_date`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_requests_uuid_unique` (`uuid`),
  ADD KEY `leave_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_requests_replacement_employee_id_foreign` (`replacement_employee_id`),
  ADD KEY `leave_requests_requested_by_foreign` (`requested_by`),
  ADD KEY `leave_requests_approved_by_foreign` (`approved_by`),
  ADD KEY `leave_requests_cancelled_by_foreign` (`cancelled_by`),
  ADD KEY `leave_requests_created_by_foreign` (`created_by`),
  ADD KEY `leave_requests_tenant_status_dates_idx` (`tenant_id`,`status`,`start_date`,`end_date`),
  ADD KEY `leave_requests_employee_date_idx` (`tenant_id`,`employee_id`,`start_date`);

--
-- Indexes for table `leave_request_days`
--
ALTER TABLE `leave_request_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_request_days_request_date_unique` (`leave_request_id`,`leave_date`),
  ADD KEY `leave_request_days_tenant_date_idx` (`tenant_id`,`leave_date`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_types_tenant_id_code_unique` (`tenant_id`,`code`),
  ADD UNIQUE KEY `leave_types_uuid_unique` (`uuid`),
  ADD KEY `leave_types_tenant_active_sort_idx` (`tenant_id`,`is_active`,`sort_order`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`tenant_id`,`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  ADD KEY `model_has_permissions_permission_id_foreign` (`permission_id`),
  ADD KEY `model_has_permissions_team_foreign_key_index` (`tenant_id`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`tenant_id`,`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  ADD KEY `model_has_roles_role_id_foreign` (`role_id`),
  ADD KEY `model_has_roles_team_foreign_key_index` (`tenant_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `overtime_requests_uuid_unique` (`uuid`),
  ADD KEY `overtime_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `overtime_requests_attendance_record_id_foreign` (`attendance_record_id`),
  ADD KEY `overtime_requests_requested_by_foreign` (`requested_by`),
  ADD KEY `overtime_requests_approved_by_foreign` (`approved_by`),
  ADD KEY `overtime_requests_created_by_foreign` (`created_by`),
  ADD KEY `overtime_tenant_date_status_index` (`tenant_id`,`overtime_date`,`status`),
  ADD KEY `overtime_employee_date_index` (`tenant_id`,`employee_id`,`overtime_date`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payroll_adjustments`
--
ALTER TABLE `payroll_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_adjustment_number_unique` (`tenant_id`,`adjustment_number`),
  ADD UNIQUE KEY `payroll_adjustments_uuid_unique` (`uuid`),
  ADD KEY `payroll_adjustments_employee_id_foreign` (`employee_id`),
  ADD KEY `payroll_adjustments_salary_component_id_foreign` (`salary_component_id`),
  ADD KEY `payroll_adjustments_applied_payroll_item_id_foreign` (`applied_payroll_item_id`),
  ADD KEY `payroll_adjustments_approved_by_foreign` (`approved_by`),
  ADD KEY `payroll_adjustments_created_by_foreign` (`created_by`),
  ADD KEY `payroll_adjustment_employee_index` (`tenant_id`,`employee_id`,`status`),
  ADD KEY `payroll_adjustment_period_index` (`payroll_period_id`,`status`);

--
-- Indexes for table `payroll_payment_batches`
--
ALTER TABLE `payroll_payment_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_payment_batches_number_unique` (`tenant_id`,`batch_number`),
  ADD UNIQUE KEY `payroll_payment_batches_reference_unique` (`tenant_id`,`payment_reference`),
  ADD UNIQUE KEY `payroll_payment_batches_uuid_unique` (`uuid`),
  ADD KEY `payroll_payment_batches_payroll_run_id_foreign` (`payroll_run_id`),
  ADD KEY `payroll_payment_batches_validated_by_foreign` (`validated_by`),
  ADD KEY `payroll_payment_batches_generated_by_foreign` (`generated_by`),
  ADD KEY `payroll_payment_batches_submitted_by_foreign` (`submitted_by`),
  ADD KEY `payroll_payment_batches_completed_by_foreign` (`completed_by`),
  ADD KEY `payroll_payment_batches_cancelled_by_foreign` (`cancelled_by`),
  ADD KEY `payroll_payment_batches_created_by_foreign` (`created_by`),
  ADD KEY `payroll_payment_batches_updated_by_foreign` (`updated_by`),
  ADD KEY `payroll_payment_batches_run_status_index` (`tenant_id`,`payroll_run_id`,`status`),
  ADD KEY `payroll_payment_batches_date_index` (`tenant_id`,`payment_date`);

--
-- Indexes for table `payroll_payment_batch_items`
--
ALTER TABLE `payroll_payment_batch_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_payment_batch_run_item_unique` (`payroll_payment_batch_id`,`payroll_run_item_id`),
  ADD UNIQUE KEY `payroll_payment_batch_items_uuid_unique` (`uuid`),
  ADD KEY `payroll_payment_batch_items_payroll_run_item_id_foreign` (`payroll_run_item_id`),
  ADD KEY `payroll_payment_batch_items_employee_id_foreign` (`employee_id`),
  ADD KEY `payroll_payment_batch_items_employee_bank_account_id_foreign` (`employee_bank_account_id`),
  ADD KEY `payroll_payment_batch_employee_status_index` (`tenant_id`,`employee_id`,`status`),
  ADD KEY `payroll_payment_batch_item_status_index` (`tenant_id`,`payroll_payment_batch_id`,`status`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_period_tenant_code_unique` (`tenant_id`,`code`),
  ADD UNIQUE KEY `payroll_period_tenant_month_unique` (`tenant_id`,`year`,`month`),
  ADD UNIQUE KEY `payroll_periods_uuid_unique` (`uuid`),
  ADD KEY `payroll_periods_locked_by_foreign` (`locked_by`),
  ADD KEY `payroll_periods_created_by_foreign` (`created_by`),
  ADD KEY `payroll_period_status_index` (`tenant_id`,`status`);

--
-- Indexes for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_run_number_unique` (`tenant_id`,`run_number`),
  ADD UNIQUE KEY `payroll_runs_uuid_unique` (`uuid`),
  ADD KEY `payroll_runs_payroll_period_id_foreign` (`payroll_period_id`),
  ADD KEY `payroll_runs_calculated_by_foreign` (`calculated_by`),
  ADD KEY `payroll_runs_approved_by_foreign` (`approved_by`),
  ADD KEY `payroll_runs_paid_by_foreign` (`paid_by`),
  ADD KEY `payroll_runs_created_by_foreign` (`created_by`),
  ADD KEY `payroll_run_period_status_index` (`tenant_id`,`payroll_period_id`,`status`);

--
-- Indexes for table `payroll_run_items`
--
ALTER TABLE `payroll_run_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_run_employee_unique` (`payroll_run_id`,`employee_id`),
  ADD UNIQUE KEY `payroll_run_items_uuid_unique` (`uuid`),
  ADD KEY `payroll_run_items_employee_id_foreign` (`employee_id`),
  ADD KEY `payroll_run_items_salary_structure_id_foreign` (`salary_structure_id`),
  ADD KEY `payroll_item_employee_status_index` (`tenant_id`,`employee_id`,`status`);

--
-- Indexes for table `payroll_run_item_components`
--
ALTER TABLE `payroll_run_item_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_run_item_components_salary_component_id_foreign` (`salary_component_id`),
  ADD KEY `payroll_run_item_components_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `payroll_item_component_type_index` (`payroll_item_id`,`type`),
  ADD KEY `payroll_component_lookup_index` (`tenant_id`,`salary_component_id`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_settings_tenant_id_unique` (`tenant_id`),
  ADD KEY `payroll_settings_created_by_foreign` (`created_by`),
  ADD KEY `payroll_settings_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plans_code_unique` (`code`),
  ADD KEY `plans_is_active_index` (`is_active`);

--
-- Indexes for table `plan_features`
--
ALTER TABLE `plan_features`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_features_plan_id_feature_id_unique` (`plan_id`,`feature_id`),
  ADD KEY `plan_features_feature_id_foreign` (`feature_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_tenant_id_name_guard_name_unique` (`tenant_id`,`name`,`guard_name`),
  ADD KEY `roles_team_foreign_key_index` (`tenant_id`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `salary_components`
--
ALTER TABLE `salary_components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `salary_components_tenant_code_unique` (`tenant_id`,`code`),
  ADD UNIQUE KEY `salary_components_uuid_unique` (`uuid`),
  ADD KEY `salary_components_percentage_base_component_id_foreign` (`percentage_base_component_id`),
  ADD KEY `salary_components_lookup_index` (`tenant_id`,`type`,`is_active`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscriptions_uuid_unique` (`uuid`),
  ADD KEY `subscriptions_plan_id_foreign` (`plan_id`),
  ADD KEY `subscriptions_tenant_id_status_index` (`tenant_id`,`status`),
  ADD KEY `subscriptions_starts_at_ends_at_index` (`starts_at`,`ends_at`),
  ADD KEY `subscriptions_status_index` (`status`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenants_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `tenants_code_unique` (`code`),
  ADD UNIQUE KEY `tenants_slug_unique` (`slug`),
  ADD KEY `tenants_status_index` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_is_system_admin_index` (`is_system_admin`),
  ADD KEY `users_is_active_index` (`is_active`),
  ADD KEY `users_tenant_id_is_active_index` (`tenant_id`,`is_active`);

--
-- Indexes for table `work_locations`
--
ALTER TABLE `work_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `work_locations_tenant_id_code_unique` (`tenant_id`,`code`),
  ADD KEY `work_locations_branch_id_foreign` (`branch_id`),
  ADD KEY `work_locations_tenant_id_branch_id_is_active_index` (`tenant_id`,`branch_id`,`is_active`);

--
-- Indexes for table `work_shifts`
--
ALTER TABLE `work_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `work_shifts_tenant_id_code_unique` (`tenant_id`,`code`),
  ADD UNIQUE KEY `work_shifts_uuid_unique` (`uuid`),
  ADD KEY `work_shifts_attendance_policy_id_foreign` (`attendance_policy_id`),
  ADD KEY `work_shifts_tenant_id_attendance_policy_id_is_active_index` (`tenant_id`,`attendance_policy_id`,`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_adjustments`
--
ALTER TABLE `attendance_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_breaks`
--
ALTER TABLE `attendance_breaks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_policies`
--
ALTER TABLE `attendance_policies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_bank_accounts`
--
ALTER TABLE `employee_bank_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_contracts`
--
ALTER TABLE `employee_contracts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_loans`
--
ALTER TABLE `employee_loans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employee_loan_installments`
--
ALTER TABLE `employee_loan_installments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `employee_mobile_devices`
--
ALTER TABLE `employee_mobile_devices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_salary_components`
--
ALTER TABLE `employee_salary_components`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee_salary_structures`
--
ALTER TABLE `employee_salary_structures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_shift_assignments`
--
ALTER TABLE `employee_shift_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `features`
--
ALTER TABLE `features`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_titles`
--
ALTER TABLE `job_titles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `leave_balance_transactions`
--
ALTER TABLE `leave_balance_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `leave_request_days`
--
ALTER TABLE `leave_request_days`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_adjustments`
--
ALTER TABLE `payroll_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payroll_payment_batches`
--
ALTER TABLE `payroll_payment_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_payment_batch_items`
--
ALTER TABLE `payroll_payment_batch_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payroll_run_items`
--
ALTER TABLE `payroll_run_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payroll_run_item_components`
--
ALTER TABLE `payroll_run_item_components`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `plan_features`
--
ALTER TABLE `plan_features`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `salary_components`
--
ALTER TABLE `salary_components`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `work_locations`
--
ALTER TABLE `work_locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_shifts`
--
ALTER TABLE `work_shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_adjustments`
--
ALTER TABLE `attendance_adjustments`
  ADD CONSTRAINT `attendance_adjustments_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `attendance_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_adjustments_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_adjustments_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_adjustments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_breaks`
--
ALTER TABLE `attendance_breaks`
  ADD CONSTRAINT `attendance_breaks_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `attendance_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_breaks_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_policies`
--
ALTER TABLE `attendance_policies`
  ADD CONSTRAINT `attendance_policies_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_records_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_work_location_id_foreign` FOREIGN KEY (`work_location_id`) REFERENCES `work_locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_records_work_shift_id_foreign` FOREIGN KEY (`work_shift_id`) REFERENCES `work_shifts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `branches_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `departments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `departments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_job_title_id_foreign` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_work_location_id_foreign` FOREIGN KEY (`work_location_id`) REFERENCES `work_locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_bank_accounts`
--
ALTER TABLE `employee_bank_accounts`
  ADD CONSTRAINT `employee_bank_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_bank_accounts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_bank_accounts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_bank_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_bank_accounts_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_contracts`
--
ALTER TABLE `employee_contracts`
  ADD CONSTRAINT `employee_contracts_activated_by_foreign` FOREIGN KEY (`activated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_contracts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_contracts_renewed_from_id_foreign` FOREIGN KEY (`renewed_from_id`) REFERENCES `employee_contracts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_contracts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_contracts_terminated_by_foreign` FOREIGN KEY (`terminated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_documents_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_loans`
--
ALTER TABLE `employee_loans`
  ADD CONSTRAINT `employee_loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_loans_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_loans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_loans_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `employee_loans_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_loans_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_loan_installments`
--
ALTER TABLE `employee_loan_installments`
  ADD CONSTRAINT `employee_loan_installments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `employee_loan_installments_employee_loan_id_foreign` FOREIGN KEY (`employee_loan_id`) REFERENCES `employee_loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_loan_installments_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_loan_installments_payroll_run_item_id_foreign` FOREIGN KEY (`payroll_run_item_id`) REFERENCES `payroll_run_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_loan_installments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_mobile_devices`
--
ALTER TABLE `employee_mobile_devices`
  ADD CONSTRAINT `employee_mobile_devices_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_mobile_devices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_mobile_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_salary_components`
--
ALTER TABLE `employee_salary_components`
  ADD CONSTRAINT `employee_salary_components_salary_component_id_foreign` FOREIGN KEY (`salary_component_id`) REFERENCES `salary_components` (`id`),
  ADD CONSTRAINT `employee_salary_components_salary_structure_id_foreign` FOREIGN KEY (`salary_structure_id`) REFERENCES `employee_salary_structures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_salary_components_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_salary_structures`
--
ALTER TABLE `employee_salary_structures`
  ADD CONSTRAINT `employee_salary_structures_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_salary_structures_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_salary_structures_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `employee_salary_structures_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_shift_assignments`
--
ALTER TABLE `employee_shift_assignments`
  ADD CONSTRAINT `employee_shift_assignments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_shift_assignments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_shift_assignments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_shift_assignments_work_shift_id_foreign` FOREIGN KEY (`work_shift_id`) REFERENCES `work_shifts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `holidays_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `holidays_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_titles`
--
ALTER TABLE `job_titles`
  ADD CONSTRAINT `job_titles_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `job_titles_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balances_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balance_transactions`
--
ALTER TABLE `leave_balance_transactions`
  ADD CONSTRAINT `leave_balance_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_balance_transactions_leave_balance_id_foreign` FOREIGN KEY (`leave_balance_id`) REFERENCES `leave_balances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balance_transactions_leave_request_id_foreign` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_balance_transactions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  ADD CONSTRAINT `leave_requests_replacement_employee_id_foreign` FOREIGN KEY (`replacement_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_request_days`
--
ALTER TABLE `leave_request_days`
  ADD CONSTRAINT `leave_request_days_leave_request_id_foreign` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_request_days_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD CONSTRAINT `leave_types_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `overtime_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `overtime_requests_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `attendance_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `overtime_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `overtime_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `overtime_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `overtime_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_adjustments`
--
ALTER TABLE `payroll_adjustments`
  ADD CONSTRAINT `payroll_adjustments_applied_payroll_item_id_foreign` FOREIGN KEY (`applied_payroll_item_id`) REFERENCES `payroll_run_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_adjustments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_adjustments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `payroll_adjustments_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_adjustments_salary_component_id_foreign` FOREIGN KEY (`salary_component_id`) REFERENCES `salary_components` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_adjustments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_payment_batches`
--
ALTER TABLE `payroll_payment_batches`
  ADD CONSTRAINT `payroll_payment_batches_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_payment_batches_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_payment_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_payment_batches_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_payment_batches_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`),
  ADD CONSTRAINT `payroll_payment_batches_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_payment_batches_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_payment_batches_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_payment_batches_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll_payment_batch_items`
--
ALTER TABLE `payroll_payment_batch_items`
  ADD CONSTRAINT `payroll_payment_batch_items_employee_bank_account_id_foreign` FOREIGN KEY (`employee_bank_account_id`) REFERENCES `employee_bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_payment_batch_items_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `payroll_payment_batch_items_payroll_payment_batch_id_foreign` FOREIGN KEY (`payroll_payment_batch_id`) REFERENCES `payroll_payment_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_payment_batch_items_payroll_run_item_id_foreign` FOREIGN KEY (`payroll_run_item_id`) REFERENCES `payroll_run_items` (`id`),
  ADD CONSTRAINT `payroll_payment_batch_items_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD CONSTRAINT `payroll_periods_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_periods_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_periods_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  ADD CONSTRAINT `payroll_runs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_runs_calculated_by_foreign` FOREIGN KEY (`calculated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_runs_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_runs_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`),
  ADD CONSTRAINT `payroll_runs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_run_items`
--
ALTER TABLE `payroll_run_items`
  ADD CONSTRAINT `payroll_run_items_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `payroll_run_items_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_run_items_salary_structure_id_foreign` FOREIGN KEY (`salary_structure_id`) REFERENCES `employee_salary_structures` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_run_items_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_run_item_components`
--
ALTER TABLE `payroll_run_item_components`
  ADD CONSTRAINT `payroll_run_item_components_payroll_item_id_foreign` FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_run_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_run_item_components_salary_component_id_foreign` FOREIGN KEY (`salary_component_id`) REFERENCES `salary_components` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_run_item_components_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD CONSTRAINT `payroll_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_settings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `plan_features`
--
ALTER TABLE `plan_features`
  ADD CONSTRAINT `plan_features_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_features_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salary_components`
--
ALTER TABLE `salary_components`
  ADD CONSTRAINT `salary_components_percentage_base_component_id_foreign` FOREIGN KEY (`percentage_base_component_id`) REFERENCES `salary_components` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `salary_components_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
  ADD CONSTRAINT `subscriptions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);

--
-- Constraints for table `work_locations`
--
ALTER TABLE `work_locations`
  ADD CONSTRAINT `work_locations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_locations_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_shifts`
--
ALTER TABLE `work_shifts`
  ADD CONSTRAINT `work_shifts_attendance_policy_id_foreign` FOREIGN KEY (`attendance_policy_id`) REFERENCES `attendance_policies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_shifts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
