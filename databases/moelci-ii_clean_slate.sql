-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 04:40 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `operating_hours`
--

CREATE TABLE `operating_hours` (
  `id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `is_closed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operating_hours`
--

INSERT INTO `operating_hours` (`id`, `day_of_week`, `open_time`, `close_time`, `is_closed`) VALUES
(1, 'Monday', '08:00:00', '17:00:00', 0),
(2, 'Tuesday', '08:00:00', '17:00:00', 1),
(3, 'Wednesday', '08:00:00', '17:00:00', 0),
(4, 'Thursday', '08:00:00', '17:00:00', 1),
(5, 'Friday', '08:00:00', '17:00:00', 0),
(6, 'Saturday', '00:00:00', '12:00:00', 1),
(7, 'Sunday', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `queue_reset_log`
--

CREATE TABLE `queue_reset_log` (
  `id` int(11) NOT NULL,
  `reset_date` date NOT NULL,
  `reset_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(3, 'Customer'),
(2, 'Teller');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `is_active`) VALUES
(1, 'Payment', 1),
(3, 'Notice of Billing', 1);

-- --------------------------------------------------------

--
-- Table structure for table `teller_availability`
--

CREATE TABLE `teller_availability` (
  `user_id` int(11) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teller_availability`
--

INSERT INTO `teller_availability` (`user_id`, `is_available`, `last_seen`) VALUES
(5, 1, NULL),
(8, 1, NULL),
(10, 1, NULL),
(11, 1, NULL);

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
(53, 8, 1, 1, '2026-10-07 14:00:30'),
(54, 10, 3, 1, '2026-10-07 14:00:55'),
(55, 11, 3, 1, '2026-10-07 14:01:44'),
(56, 5, 1, 1, '2026-10-19 13:08:31');

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
(1, 'Admin', '$2y$10$nGPpkbCkcF0I7ccgMXZ6geTi.4Dmv5maq3oq5d0W6RB0Up.53YK9K', 'moelci-ii-tangubcity@moelci.com', 1, 'Moelci - II', 'Tangub City', 1, '2025-09-28 13:26:44', '2026-08-15 10:43:36'),
(2, 'Teller', '$2y$10$WmhVZRkfzrp4Tf7ziyjnbebgNPeuYatFvTINyJmKNHUTmSWuzAI0K', 'teller@example.com', 2, 'Teller', 'Teller', 0, '2025-09-28 13:26:44', '2025-10-02 15:37:47'),
(3, 'Customer', '$2y$10$yI5uX1jD6aGJpxHPjN29M.6ffHSJzOzywWKYboAkXG9FjYahhCYxe', 'queuingcustomer@moelci.com', 3, 'Queuing', 'Customer', 1, '2025-09-28 13:27:26', '2026-08-15 10:43:07'),
(5, 'Teller_1', '$2y$10$4X/P70sX4ZMEZlxWjPb2ceiMAttrOR9wMyIsjEhNYNPQawE6R/Vma', 'teller_1@moelci.com', 2, 'Teller', '1', 1, '2025-10-02 15:37:24', '2026-10-07 14:00:14'),
(8, 'Teller_2', '$2y$10$wJNm3D/vjIKXJW8ls2KLOeshCj1HU0qbIWdEK2pn9kiEV5S9o.u2q', 'teller_2@moelci.com', 2, 'Teller', '2', 1, '2025-10-02 15:40:57', '2026-10-07 14:00:30'),
(10, 'Teller_3', '$2y$10$KTSxCcsxZSoWOIxHKErQDuLYyprdG2n1rIS2zq7mcpSgd5.aXOT8y', 'teller_3@moelci.com', 2, 'Teller', '3', 1, '2025-10-02 15:41:48', '2026-10-07 14:00:55'),
(11, 'Teller_4', '$2y$10$qZZ7rcPem4CV2xVrhQp7s.QR5IBpe7.hSnUsiVbAS12j9ZE1XCOOq', 'teller_4@moelci.com', 2, 'Teller', '4', 1, '2025-10-02 15:43:00', '2026-10-07 14:01:44');

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
-- Indexes for table `queue_reset_log`
--
ALTER TABLE `queue_reset_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reset_date` (`reset_date`);

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
-- Indexes for table `teller_availability`
--
ALTER TABLE `teller_availability`
  ADD PRIMARY KEY (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `operating_hours`
--
ALTER TABLE `operating_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `queue_reset_log`
--
ALTER TABLE `queue_reset_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_tickets`
--
ALTER TABLE `queue_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_ticket_history`
--
ALTER TABLE `queue_ticket_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `teller_service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

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
-- Constraints for table `teller_availability`
--
ALTER TABLE `teller_availability`
  ADD CONSTRAINT `fk_teller_availability_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
