-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2025 at 04:25 AM
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
-- Database: `moelci-ii`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `breaks_schedule`
--

CREATE TABLE `breaks_schedule` (
  `id` int(11) NOT NULL,
  `break_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `breaks_schedule`
--

INSERT INTO `breaks_schedule` (`id`, `break_name`, `start_time`, `end_time`, `is_active`, `created_at`) VALUES
(7, 'Lunch Break', '12:00:00', '13:00:00', 1, '2025-12-21 13:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `operating_hours`
--

CREATE TABLE `operating_hours` (
  `id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `is_closed` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operating_hours`
--

INSERT INTO `operating_hours` (`id`, `day_of_week`, `open_time`, `close_time`, `is_closed`, `is_active`) VALUES
(1, 'Monday', '08:00:00', '17:00:00', 0, 1),
(2, 'Tuesday', '08:00:00', '17:00:00', 0, 1),
(3, 'Wednesday', '08:00:00', '17:00:00', 0, 1),
(4, 'Thursday', '08:00:00', '17:00:00', 0, 1),
(5, 'Friday', '08:00:00', '17:00:00', 0, 1),
(6, 'Saturday', '00:00:00', '12:00:00', 1, 1),
(7, 'Sunday', NULL, NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `queue_tickets`
--

CREATE TABLE `queue_tickets` (
  `ticket_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `service_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `status` enum('waiting','in_progress','completed','cancelled','skipped') DEFAULT 'waiting',
  `priority` enum('low','normal','high') DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `called_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `customer_type` enum('regular','pwd') NOT NULL DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_ticket_history`
--

CREATE TABLE `queue_ticket_history` (
  `history_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `service_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `status` enum('waiting','in_progress','completed','cancelled','skipped') NOT NULL,
  `priority` enum('low','normal','high') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `called_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue_ticket_history`
--

INSERT INTO `queue_ticket_history` (`history_id`, `ticket_id`, `ticket_number`, `service_id`, `customer_id`, `customer_name`, `status`, `priority`, `created_at`, `called_at`, `completed_at`, `assigned_user_id`, `archived_at`, `archived_by`) VALUES
(1, 60, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 03:48:24', '2025-12-22 03:49:12', '2025-12-22 03:49:13', 5, '2025-12-22 03:49:34', 1),
(2, 61, 'R0002', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 03:48:32', '2025-12-22 03:49:13', '2025-12-22 03:49:14', 5, '2025-12-22 03:49:34', 1),
(3, 62, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-22 03:48:38', '2025-12-22 03:49:10', '2025-12-22 03:49:12', 5, '2025-12-22 03:49:34', 1),
(4, 63, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-22 04:03:56', '2025-12-22 04:04:12', '2025-12-22 04:04:13', 5, '2025-12-22 04:04:25', 1),
(5, 64, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 04:04:01', '2025-12-22 04:04:13', '2025-12-22 04:04:14', 5, '2025-12-22 04:04:25', 1),
(7, 65, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-22 04:07:10', '2025-12-22 04:07:18', '2025-12-22 04:07:18', 5, '2025-12-22 04:07:24', 1),
(8, 66, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 04:08:11', '2025-12-22 04:08:18', '2025-12-22 04:08:19', 5, '2025-12-22 04:08:27', 1),
(9, 67, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-22 04:11:36', '2025-12-22 04:12:07', '2025-12-22 04:12:08', 5, '2025-12-22 04:13:08', 1),
(10, 68, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 04:11:41', '2025-12-22 04:12:10', '2025-12-22 04:12:11', 5, '2025-12-22 04:13:08', 1),
(11, 69, 'R0002', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 04:11:46', '2025-12-22 04:12:11', '2025-12-22 04:12:12', 5, '2025-12-22 04:13:08', 1),
(12, 70, 'R0003', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 04:11:50', '2025-12-22 04:12:12', '2025-12-22 04:12:13', 5, '2025-12-22 04:13:08', 1),
(13, 71, 'R0004', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 04:11:56', '2025-12-22 04:12:13', '2025-12-22 04:12:14', 5, '2025-12-22 04:13:08', 1),
(14, 72, 'P0002', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-22 04:12:00', '2025-12-22 04:12:08', '2025-12-22 04:12:10', 5, '2025-12-22 04:13:08', 1),
(16, 73, 'R0001', 1, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-22 04:17:03', NULL, NULL, NULL, '2025-12-22 04:17:51', 1),
(17, 74, 'R0001', 1, 3, 'Customer Customer', 'cancelled', 'normal', '2025-12-22 04:18:19', '2025-12-22 04:18:27', '2025-12-22 04:18:28', 5, '2025-12-22 04:18:37', 1),
(18, 75, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 04:32:35', '2025-12-22 04:35:14', '2025-12-22 04:35:15', 5, '2025-12-22 05:26:19', 1),
(19, 76, 'R0002', 3, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 05:25:38', '2025-12-22 05:25:52', '2025-12-22 05:25:54', 11, '2025-12-22 05:26:19', 1),
(20, 77, 'P0001', 3, 3, 'Customer Customer', 'completed', 'high', '2025-12-22 05:25:43', '2025-12-22 05:25:50', '2025-12-22 05:25:52', 11, '2025-12-22 05:26:19', 1),
(21, 78, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-22 07:45:33', '2025-12-22 09:00:47', '2025-12-22 09:00:56', 5, '2025-12-22 09:01:08', 1),
(22, 79, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-22 07:45:58', '2025-12-22 07:46:43', '2025-12-22 09:00:47', 5, '2025-12-22 09:01:08', 1),
(23, 80, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 04:44:46', '2025-12-23 04:45:03', '2025-12-23 05:04:01', 8, '2025-12-23 07:14:52', 1),
(24, 81, 'R0002', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 04:45:37', '2025-12-23 05:04:01', '2025-12-23 05:26:49', 8, '2025-12-23 07:14:52', 1),
(25, 82, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 05:54:58', '2025-12-23 05:56:35', '2025-12-23 05:57:03', 5, '2025-12-23 07:14:52', 1),
(26, 83, 'P0002', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 05:55:06', '2025-12-23 05:56:39', '2025-12-23 05:57:10', 8, '2025-12-23 07:14:52', 1),
(27, 84, 'P0003', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 05:55:12', '2025-12-23 05:57:03', '2025-12-23 05:57:04', 5, '2025-12-23 07:14:52', 1),
(28, 85, 'R0003', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 05:55:20', '2025-12-23 05:57:04', '2025-12-23 05:57:06', 5, '2025-12-23 07:14:52', 1),
(29, 86, 'R0004', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 05:55:25', '2025-12-23 05:57:06', '2025-12-23 05:57:06', 5, '2025-12-23 07:14:52', 1),
(30, 87, 'R0005', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 05:55:31', '2025-12-23 05:57:06', '2025-12-23 05:57:08', 5, '2025-12-23 07:14:52', 1),
(31, 88, 'R0006', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 06:00:26', '2025-12-23 06:00:49', '2025-12-23 06:01:33', 5, '2025-12-23 07:14:52', 1),
(32, 89, 'R0007', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 06:00:32', '2025-12-23 06:01:33', '2025-12-23 06:01:45', 5, '2025-12-23 07:14:52', 1),
(33, 90, 'P0004', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 06:01:12', '2025-12-23 06:01:45', '2025-12-23 06:01:46', 5, '2025-12-23 07:14:52', 1),
(34, 91, 'P0005', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 06:22:38', '2025-12-23 06:23:55', '2025-12-23 06:24:03', 8, '2025-12-23 07:14:52', 1),
(35, 92, 'R0008', 3, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-23 06:22:45', NULL, NULL, NULL, '2025-12-23 07:14:52', 1),
(36, 93, 'R0009', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 06:22:53', '2025-12-23 06:23:59', '2025-12-23 06:24:01', 5, '2025-12-23 07:14:52', 1),
(37, 94, 'R0010', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:11:37', '2025-12-23 07:11:52', '2025-12-23 07:11:54', 5, '2025-12-23 07:14:52', 1),
(38, 95, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:15:44', '2025-12-23 07:15:55', '2025-12-23 07:15:56', 5, '2025-12-23 08:56:17', 1),
(39, 96, 'R0002', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:23:43', '2025-12-23 07:30:45', '2025-12-23 07:31:08', 5, '2025-12-23 08:56:17', 1),
(40, 97, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 07:23:55', '2025-12-23 07:31:03', '2025-12-23 07:31:05', 8, '2025-12-23 08:56:17', 1),
(41, 98, 'P0002', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 07:31:21', '2025-12-23 07:32:47', '2025-12-23 07:32:58', 5, '2025-12-23 08:56:17', 1),
(42, 99, 'R0003', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:31:26', '2025-12-23 07:32:40', '2025-12-23 07:32:47', 5, '2025-12-23 08:56:17', 1),
(43, 100, 'R0004', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:33:10', '2025-12-23 07:33:40', '2025-12-23 07:33:49', 5, '2025-12-23 08:56:17', 1),
(44, 101, 'P0003', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 07:33:15', '2025-12-23 07:33:44', '2025-12-23 07:33:47', 8, '2025-12-23 08:56:17', 1),
(45, 102, 'P0004', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 07:38:38', '2025-12-23 07:39:18', '2025-12-23 07:39:19', 8, '2025-12-23 08:56:17', 1),
(46, 103, 'R0005', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:38:44', '2025-12-23 07:39:19', '2025-12-23 07:39:28', 8, '2025-12-23 08:56:17', 1),
(47, 104, 'R0006', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:39:48', '2025-12-23 07:40:15', '2025-12-23 07:40:19', 5, '2025-12-23 08:56:17', 1),
(48, 105, 'R0007', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:39:53', '2025-12-23 07:40:19', '2025-12-23 07:40:33', 5, '2025-12-23 08:56:17', 1),
(49, 106, 'R0008', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 07:46:45', '2025-12-23 07:47:16', '2025-12-23 07:47:17', 5, '2025-12-23 08:56:17', 1),
(50, 107, 'P0005', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 07:46:51', '2025-12-23 07:47:12', '2025-12-23 07:47:16', 5, '2025-12-23 08:56:17', 1),
(51, 108, 'R0009', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 08:16:01', '2025-12-23 08:16:50', '2025-12-23 08:16:52', 5, '2025-12-23 08:56:17', 1),
(52, 109, 'R0010', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 08:17:04', '2025-12-23 08:18:33', '2025-12-23 08:18:51', 5, '2025-12-23 08:56:17', 1),
(53, 110, 'R0011', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 08:17:08', '2025-12-23 08:18:51', '2025-12-23 08:18:53', 5, '2025-12-23 08:56:17', 1),
(54, 111, 'P0006', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 08:17:13', '2025-12-23 08:18:53', '2025-12-23 08:19:05', 5, '2025-12-23 08:56:17', 1),
(55, 112, 'P0007', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 08:17:18', '2025-12-23 08:19:03', '2025-12-23 08:19:04', 8, '2025-12-23 08:56:17', 1),
(56, 113, 'R0012', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 08:38:18', '2025-12-23 08:40:48', '2025-12-23 08:41:07', 5, '2025-12-23 08:56:17', 1),
(57, 114, 'P0008', 1, 3, 'Customer Customer', 'waiting', 'high', '2025-12-23 08:38:23', NULL, NULL, NULL, '2025-12-23 08:56:17', 1),
(58, 115, 'P0009', 1, 3, 'Customer Customer', 'waiting', 'high', '2025-12-23 08:47:16', NULL, NULL, NULL, '2025-12-23 08:56:17', 1),
(59, 116, 'P0010', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 08:55:05', '2025-12-23 08:56:03', '2025-12-23 08:56:04', 8, '2025-12-23 08:56:17', 1),
(60, 117, 'P0011', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 08:55:12', '2025-12-23 08:56:04', '2025-12-23 08:56:05', 8, '2025-12-23 08:56:17', 1),
(69, 118, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 09:12:38', '2025-12-23 09:14:04', '2025-12-23 09:43:22', 5, '2025-12-23 10:08:21', 1),
(70, 119, 'R0002', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 09:12:45', '2025-12-23 09:43:22', '2025-12-23 09:43:49', 5, '2025-12-23 10:08:21', 1),
(71, 120, 'R0003', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 09:12:52', '2025-12-23 09:43:49', '2025-12-23 09:43:50', 5, '2025-12-23 10:08:21', 1),
(72, 121, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 09:12:59', '2025-12-23 09:44:14', '2025-12-23 09:44:37', 8, '2025-12-23 10:08:21', 1),
(73, 122, 'P0002', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 09:13:05', '2025-12-23 09:44:37', '2025-12-23 09:44:41', 8, '2025-12-23 10:08:21', 1),
(74, 123, 'P0003', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 09:44:53', '2025-12-23 09:45:49', '2025-12-23 09:46:50', 8, '2025-12-23 10:08:21', 1),
(75, 124, 'P0004', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 09:44:58', '2025-12-23 09:46:50', '2025-12-23 09:46:52', 8, '2025-12-23 10:08:21', 1),
(76, 125, 'R0004', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 09:45:03', '2025-12-23 09:45:55', '2025-12-23 09:46:31', 5, '2025-12-23 10:08:21', 1),
(77, 126, 'R0005', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 09:45:08', '2025-12-23 09:46:31', '2025-12-23 09:47:09', 5, '2025-12-23 10:08:21', 1),
(78, 127, 'R0006', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 09:45:14', '2025-12-23 09:46:52', '2025-12-23 09:46:58', 8, '2025-12-23 10:08:21', 1),
(79, 128, 'R0007', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 10:00:57', '2025-12-23 10:04:01', '2025-12-23 10:04:04', 5, '2025-12-23 10:08:21', 1),
(80, 129, 'R0008', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 10:01:04', '2025-12-23 10:04:04', '2025-12-23 10:07:02', 5, '2025-12-23 10:08:21', 1),
(81, 130, 'R0009', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-23 10:01:11', '2025-12-23 10:06:34', '2025-12-23 10:06:38', 8, '2025-12-23 10:08:21', 1),
(82, 131, 'P0005', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 10:01:16', '2025-12-23 10:05:06', '2025-12-23 10:05:11', 8, '2025-12-23 10:08:21', 1),
(83, 132, 'P0006', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-23 10:01:22', '2025-12-23 10:05:11', '2025-12-23 10:06:34', 8, '2025-12-23 10:08:21', 1),
(84, 133, 'R0010', 3, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-23 10:01:28', NULL, NULL, NULL, '2025-12-23 10:08:21', 1),
(85, 134, 'R0011', 3, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-23 10:01:33', NULL, NULL, NULL, '2025-12-23 10:08:21', 1),
(86, 135, 'P0007', 3, 3, 'Customer Customer', 'waiting', 'high', '2025-12-23 10:01:39', NULL, NULL, NULL, '2025-12-23 10:08:21', 1),
(87, 136, 'R0001', 1, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-24 02:05:03', NULL, NULL, NULL, '2025-12-26 03:21:58', 1),
(88, 137, 'R0002', 1, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-24 02:24:02', NULL, NULL, NULL, '2025-12-26 03:21:58', 1),
(89, 138, 'P0001', 1, 3, 'Customer Customer', 'waiting', 'high', '2025-12-24 02:35:06', NULL, NULL, NULL, '2025-12-26 03:21:58', 1),
(90, 139, 'R0001', 1, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-26 03:27:28', NULL, NULL, NULL, '2025-12-26 03:27:58', 1),
(91, 140, 'R0002', 1, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-26 03:27:38', NULL, NULL, NULL, '2025-12-26 03:27:58', 1),
(93, 141, 'R0001', 1, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-26 03:28:10', NULL, NULL, NULL, '2025-12-26 03:30:58', 1),
(94, 142, 'P0001', 3, 3, 'Customer Customer', 'completed', 'high', '2025-12-26 04:29:59', '2025-12-26 04:32:10', '2025-12-26 04:33:52', 11, '2025-12-26 04:36:58', 1),
(95, 143, 'P0002', 3, 3, 'Customer Customer', 'completed', 'high', '2025-12-26 04:30:04', '2025-12-26 04:33:52', '2025-12-26 04:33:53', 11, '2025-12-26 04:36:58', 1),
(96, 144, 'R0001', 3, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 04:30:10', '2025-12-26 04:32:40', '2025-12-26 04:33:56', 10, '2025-12-26 04:36:58', 1),
(97, 145, 'R0002', 3, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 04:30:22', '2025-12-26 04:33:53', '2025-12-26 04:34:03', 11, '2025-12-26 04:36:58', 1),
(98, 146, 'R0003', 3, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 04:30:27', '2025-12-26 04:33:56', '2025-12-26 04:33:57', 10, '2025-12-26 04:36:58', 1),
(99, 147, 'R0004', 3, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 04:30:45', '2025-12-26 04:33:57', '2025-12-26 04:33:58', 10, '2025-12-26 04:36:58', 1),
(100, 148, 'R0001', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:03:41', '2025-12-26 10:05:49', '2025-12-26 10:07:20', 5, '2025-12-27 03:22:25', 1),
(101, 149, 'R0002', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:03:51', '2025-12-26 10:06:29', '2025-12-26 10:07:49', 8, '2025-12-27 03:22:25', 1),
(102, 150, 'P0001', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-26 10:03:59', '2025-12-26 10:04:52', '2025-12-26 10:06:29', 8, '2025-12-27 03:22:25', 1),
(103, 151, 'R0003', 3, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:08:55', '2025-12-26 10:10:36', '2025-12-26 10:11:05', 10, '2025-12-27 03:22:25', 1),
(104, 152, 'R0004', 3, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:09:05', '2025-12-26 10:11:05', '2025-12-26 10:11:56', 10, '2025-12-27 03:22:25', 1),
(105, 153, 'R0005', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:09:15', '2025-12-26 10:10:18', '2025-12-26 10:10:55', 5, '2025-12-27 03:22:25', 1),
(106, 154, 'R0006', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:09:25', '2025-12-26 10:10:55', '2025-12-26 10:11:34', 5, '2025-12-27 03:22:25', 1),
(107, 155, 'R0007', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:16:10', '2025-12-26 10:28:59', '2025-12-26 10:30:47', 5, '2025-12-27 03:22:25', 1),
(108, 156, 'R0008', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:21:03', '2025-12-26 10:30:47', '2025-12-26 10:33:09', 5, '2025-12-27 03:22:25', 1),
(109, 157, 'R0009', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:21:12', '2025-12-26 10:33:09', '2025-12-26 10:33:45', 5, '2025-12-27 03:22:25', 1),
(110, 158, 'R0010', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:23:47', '2025-12-26 10:33:25', '2025-12-26 10:34:02', 8, '2025-12-27 03:22:25', 1),
(111, 159, 'R0011', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:24:31', '2025-12-26 10:33:45', '2025-12-26 10:34:28', 5, '2025-12-27 03:22:25', 1),
(112, 160, 'R0012', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 10:25:06', '2025-12-26 10:34:02', '2025-12-26 10:34:38', 8, '2025-12-27 03:22:25', 1),
(113, 161, 'P0002', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-26 10:26:23', '2025-12-26 10:31:03', '2025-12-26 10:33:25', 8, '2025-12-27 03:22:25', 1),
(114, 162, 'P0003', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-26 10:26:30', '2025-12-26 10:29:43', '2025-12-26 10:31:03', 8, '2025-12-27 03:22:25', 1),
(115, 163, 'R0013', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:27:35', '2025-12-26 13:28:55', '2025-12-26 13:43:22', 5, '2025-12-27 03:22:25', 1),
(116, 164, 'P0004', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-26 13:27:42', '2025-12-26 13:31:09', '2025-12-26 13:43:32', 8, '2025-12-27 03:22:25', 1),
(117, 165, 'R0014', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:27:52', '2025-12-26 13:43:38', '2025-12-26 13:44:56', 5, '2025-12-27 03:22:25', 1),
(118, 166, 'R0015', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:27:58', '2025-12-26 13:43:48', '2025-12-26 13:44:58', 8, '2025-12-27 03:22:25', 1),
(119, 167, 'R0016', 1, 3, 'Customer Customer', 'cancelled', 'normal', '2025-12-26 13:28:03', '2025-12-26 13:45:09', '2025-12-26 13:46:40', 5, '2025-12-27 03:22:25', 1),
(120, 168, 'R0017', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:28:08', '2025-12-26 13:45:20', '2025-12-26 13:46:04', 5, '2025-12-27 03:22:25', 1),
(121, 169, 'R0018', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:28:14', '2025-12-26 13:45:26', '2025-12-26 13:45:43', 5, '2025-12-27 03:22:25', 1),
(122, 170, 'R0019', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:47:43', '2025-12-26 13:50:24', '2025-12-26 13:50:44', 5, '2025-12-27 03:22:25', 1),
(123, 171, 'R0020', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:47:51', '2025-12-26 13:50:50', '2025-12-26 14:06:08', 5, '2025-12-27 03:22:25', 1),
(124, 172, 'P0005', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-26 13:47:58', '2025-12-26 13:50:36', '2025-12-26 13:50:46', 8, '2025-12-27 03:22:25', 1),
(125, 173, 'P0006', 1, 3, 'Customer Customer', 'completed', 'high', '2025-12-26 13:48:03', '2025-12-26 13:52:29', '2025-12-26 13:52:47', 8, '2025-12-27 03:22:25', 1),
(126, 174, 'R0021', 3, 3, 'Customer Customer', 'waiting', 'normal', '2025-12-26 13:48:12', NULL, NULL, NULL, '2025-12-27 03:22:25', 1),
(127, 175, 'R0022', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:48:16', '2025-12-26 13:52:36', '2025-12-26 13:52:43', 5, '2025-12-27 03:22:25', 1),
(128, 176, 'R0023', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:48:22', '2025-12-26 14:06:15', '2025-12-26 14:06:17', 5, '2025-12-27 03:22:25', 1),
(129, 177, 'R0024', 1, 3, 'Customer Customer', 'completed', 'normal', '2025-12-26 13:48:27', '2025-12-26 14:06:18', '2025-12-26 14:06:19', 5, '2025-12-27 03:22:25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(20) NOT NULL,
  `role_description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `role_description`) VALUES
(1, 'Admin', 'System administrator with full access'),
(2, 'Teller', 'Service counter staff who handle queue tickets'),
(3, 'Customer', 'Registered customers who can join queues');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `service_description` text DEFAULT NULL,
  `estimated_duration` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `service_description`, `estimated_duration`, `is_active`) VALUES
(1, 'Payment', 'To Pay', 30, 1),
(3, 'Notice of Billing', 'Notice Bill', 45, 1);

-- --------------------------------------------------------

--
-- Table structure for table `teller_services`
--

CREATE TABLE `teller_services` (
  `teller_service_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teller_services`
--

INSERT INTO `teller_services` (`teller_service_id`, `user_id`, `service_id`, `is_active`, `created_at`) VALUES
(11, 2, 1, 1, '2025-09-28 13:24:40'),
(13, 2, 3, 1, '2025-09-28 13:24:40'),
(26, 5, 1, 1, '2025-10-02 15:43:27'),
(29, 10, 3, 1, '2025-10-02 15:44:07'),
(43, 8, 1, 1, '2025-12-21 12:34:06'),
(45, 11, 3, 1, '2025-12-21 12:34:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `role_id`, `first_name`, `last_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin', '$2y$10$nGPpkbCkcF0I7ccgMXZ6geTi.4Dmv5maq3oq5d0W6RB0Up.53YK9K', 'admin@example.com', 1, 'Admin', 'Admin', 1, '2025-09-28 13:26:44', '2025-09-28 13:26:44'),
(2, 'Teller', '$2y$10$WmhVZRkfzrp4Tf7ziyjnbebgNPeuYatFvTINyJmKNHUTmSWuzAI0K', 'teller@example.com', 2, 'Teller', 'Teller', 0, '2025-09-28 13:26:44', '2025-10-02 15:37:47'),
(3, 'Customer', '$2y$10$yI5uX1jD6aGJpxHPjN29M.6ffHSJzOzywWKYboAkXG9FjYahhCYxe', 'customer@example.com', 3, 'Customer', 'Customer', 1, '2025-09-28 13:27:26', '2025-09-28 13:27:52'),
(5, 'Teller0', '$2y$10$4X/P70sX4ZMEZlxWjPb2ceiMAttrOR9wMyIsjEhNYNPQawE6R/Vma', 'teller@example.com', 2, 'Teller', '1', 1, '2025-10-02 15:37:24', '2025-10-02 15:37:24'),
(8, 'Teller1', '$2y$10$wJNm3D/vjIKXJW8ls2KLOeshCj1HU0qbIWdEK2pn9kiEV5S9o.u2q', 'teller@example.com', 2, 'Teller', '2', 1, '2025-10-02 15:40:57', '2025-10-02 15:40:57'),
(10, 'Teller2', '$2y$10$KTSxCcsxZSoWOIxHKErQDuLYyprdG2n1rIS2zq7mcpSgd5.aXOT8y', 'teller@example.com', 2, 'Teller', '3', 1, '2025-10-02 15:41:48', '2025-10-02 15:42:24'),
(11, 'Teller3', '$2y$10$qZZ7rcPem4CV2xVrhQp7s.QR5IBpe7.hSnUsiVbAS12j9ZE1XCOOq', 'teller@example.com', 2, 'Teller', '4', 1, '2025-10-02 15:43:00', '2025-10-02 15:43:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `breaks_schedule`
--
ALTER TABLE `breaks_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `operating_hours`
--
ALTER TABLE `operating_hours`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `queue_tickets`
--
ALTER TABLE `queue_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `assigned_teller_id` (`assigned_user_id`);

--
-- Indexes for table `queue_ticket_history`
--
ALTER TABLE `queue_ticket_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `fk_history_service` (`service_id`),
  ADD KEY `fk_history_customer` (`customer_id`),
  ADD KEY `fk_history_archived_by` (`archived_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `teller_services`
--
ALTER TABLE `teller_services`
  ADD PRIMARY KEY (`teller_service_id`),
  ADD UNIQUE KEY `unique_teller_service` (`user_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `breaks_schedule`
--
ALTER TABLE `breaks_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `operating_hours`
--
ALTER TABLE `operating_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `queue_tickets`
--
ALTER TABLE `queue_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT for table `queue_ticket_history`
--
ALTER TABLE `queue_ticket_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teller_services`
--
ALTER TABLE `teller_services`
  MODIFY `teller_service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `queue_tickets`
--
ALTER TABLE `queue_tickets`
  ADD CONSTRAINT `queue_tickets_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`),
  ADD CONSTRAINT `queue_tickets_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `queue_tickets_ibfk_3` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `queue_ticket_history`
--
ALTER TABLE `queue_ticket_history`
  ADD CONSTRAINT `fk_history_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_history_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_history_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

--
-- Constraints for table `teller_services`
--
ALTER TABLE `teller_services`
  ADD CONSTRAINT `teller_services_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teller_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `auto_archive_and_clear_queue` ON SCHEDULE EVERY 3 MINUTE STARTS '2025-12-27 11:19:25' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    INSERT INTO queue_ticket_history (
        ticket_id,
        ticket_number,
        service_id,
        customer_id,
        customer_name,
        status,
        priority,
        created_at,
        called_at,
        completed_at,
        assigned_user_id,
        archived_by
    )
    SELECT
        ticket_id,
        ticket_number,
        service_id,
        customer_id,
        customer_name,
        status,
        priority,
        created_at,
        called_at,
        completed_at,
        assigned_user_id,
        1
    FROM queue_tickets;

    DELETE FROM queue_tickets;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
