-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 07:22 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `admdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `ambulances`
--

CREATE TABLE `ambulances` (
  `id` int(11) NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` enum('AVAILABLE','DISPATCHED','MAINTENANCE') DEFAULT 'AVAILABLE',
  `location` varchar(100) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ambulances`
--

INSERT INTO `ambulances` (`id`, `vehicle_number`, `type`, `status`, `location`, `last_updated`) VALUES
(4, 'AMB-003', 'SUBARU', 'AVAILABLE', 'NAIROBI', '2025-03-01 10:53:09'),
(5, 'AMB-001', 'PATIENT_TRANSPORT', 'AVAILABLE', 'Parking', '2025-04-02 07:38:34'),
(8, 'AMB-002', 'PATIENT TRANSPORT ', 'MAINTENANCE', 'Pangani', '2025-05-07 13:26:23');

-- --------------------------------------------------------

--
-- Table structure for table `ambulance_assignments`
--

CREATE TABLE `ambulance_assignments` (
  `id` int(11) NOT NULL,
  `ambulance_id` int(11) NOT NULL,
  `paramedic_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ambulance_assignments`
--

INSERT INTO `ambulance_assignments` (`id`, `ambulance_id`, `paramedic_id`, `driver_id`, `active`, `created_at`) VALUES
(11, 4, 9, 19, 1, '2025-05-06 02:20:07'),
(14, 5, 8, 12, 1, '2025-05-06 02:33:42');

-- --------------------------------------------------------

--
-- Table structure for table `ambulance_inventory`
--

CREATE TABLE `ambulance_inventory` (
  `id` int(11) NOT NULL,
  `ambulance_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('AVAILABLE','LOW','OUT_OF_STOCK') DEFAULT 'AVAILABLE',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ambulance_inventory`
--

INSERT INTO `ambulance_inventory` (`id`, `ambulance_id`, `item_name`, `quantity`, `status`, `last_updated`) VALUES
(1, 5, 'First Aid Kit', 3, 'AVAILABLE', '2025-05-06 09:37:18'),
(2, 5, 'Bandage', 2, 'AVAILABLE', '2025-05-07 13:24:33'),
(3, 4, 'Bandage', 1, 'LOW', '2025-05-24 07:03:15'),
(4, 5, 'Extinguisher', 1, 'LOW', '2025-05-06 01:57:28'),
(5, 8, 'First Aid Kit', 1, 'LOW', '2025-05-07 13:27:53'),
(6, 5, 'First Aid Kit', 1, 'LOW', '2025-05-07 13:28:24'),
(7, 5, 'First Aid Kit', 2, 'AVAILABLE', '2025-05-07 13:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_logs`
--

CREATE TABLE `assignment_logs` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dispatches`
--

CREATE TABLE `dispatches` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) DEFAULT NULL,
  `ambulance_id` int(11) DEFAULT NULL,
  `paramedic_id` int(11) DEFAULT NULL,
  `dispatch_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledgment_time` timestamp NULL DEFAULT NULL,
  `completion_time` timestamp NULL DEFAULT NULL,
  `status` enum('PENDING','ACKNOWLEDGED','COMPLETED','CANCELLED') DEFAULT 'PENDING',
  `driver_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dispatches`
--

INSERT INTO `dispatches` (`id`, `incident_id`, `ambulance_id`, `paramedic_id`, `dispatch_time`, `acknowledgment_time`, `completion_time`, `status`, `driver_id`) VALUES
(2, 1, 4, NULL, '2025-03-01 12:04:26', NULL, NULL, 'PENDING', NULL),
(3, 4, 5, NULL, '2025-04-03 10:45:32', NULL, NULL, 'PENDING', NULL),
(4, 6, 4, NULL, '2025-04-03 12:14:28', NULL, NULL, 'PENDING', NULL),
(5, 21, 5, 9, '2025-05-06 06:04:05', NULL, NULL, 'COMPLETED', 19),
(6, 22, 5, 9, '2025-05-06 09:26:31', NULL, NULL, 'COMPLETED', 19),
(7, 24, 3, 9, '2025-05-07 13:04:46', NULL, NULL, 'COMPLETED', 19),
(8, 26, 4, 9, '2025-05-08 13:20:20', NULL, NULL, 'COMPLETED', 19),
(9, 27, 4, 9, '2025-05-13 05:32:38', NULL, NULL, 'COMPLETED', 19),
(10, 28, 4, 9, '2025-05-24 07:00:38', NULL, NULL, 'COMPLETED', 19);

-- --------------------------------------------------------

--
-- Table structure for table `driver_details`
--

CREATE TABLE `driver_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `license_type` varchar(50) NOT NULL,
  `status` enum('ACTIVE','INACTIVE','ON_LEAVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `history` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `narrative` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `severity` enum('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
  `type` varchar(100) NOT NULL,
  `status` enum('UNVERIFIED','ASSIGNED','CLOSED') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `dispatcher_id` int(11) DEFAULT NULL,
  `reporter_name` varchar(100) DEFAULT NULL,
  `emergency_type` varchar(50) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `ambulance_id` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `assigned_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `narrative`, `location`, `severity`, `type`, `status`, `created_at`, `dispatcher_id`, `reporter_name`, `emergency_type`, `contact_phone`, `ambulance_id`, `submitted_at`, `verified_at`, `assigned_at`, `closed_at`, `assigned_by`) VALUES
(21, 'Test', 'Kiamaiko, Kwa chief', 'CRITICAL', '', 'CLOSED', '2025-05-06 05:44:55', NULL, 'Vera', 'Traffic accident', '0766554433', 5, '2025-05-06 08:44:55', NULL, '2025-05-06 09:04:05', '2025-05-06 10:11:10', 'santana'),
(22, 'the man cant', 'Pangani, HQ', 'CRITICAL', '', 'CLOSED', '2025-05-06 09:22:56', NULL, 'Grace', 'Other', '0766554433', 5, '2025-05-06 12:22:56', '2025-05-06 12:26:31', '2025-05-06 12:26:31', '2025-05-06 12:38:26', 'santana'),
(23, 'head on collision', 'Kiamaiko, Kwa nyama', 'MEDIUM', '', 'UNVERIFIED', '2025-05-06 14:26:50', NULL, 'Rashford', 'Traffic accident', '0766554433', NULL, '2025-05-06 17:26:50', NULL, NULL, NULL, NULL),
(26, 'Breathing difficulty ', 'Huruma, Stage', 'CRITICAL', '', 'CLOSED', '2025-05-08 13:17:03', NULL, 'Mr Odera', 'Traffic accident', '0788889065', 4, '2025-05-08 16:17:03', '2025-05-08 16:20:20', '2025-05-08 16:20:20', '2025-05-08 16:22:44', 'santana'),
(27, 'Someone has been knocked by a car', 'Kiamaiko, Stage', 'CRITICAL', '', 'CLOSED', '2025-05-13 05:29:53', NULL, 'Sylvia W', 'Traffic accident', '0788889065', 4, '2025-05-13 08:29:53', '2025-05-13 08:32:38', '2025-05-13 08:32:38', '2025-05-13 08:37:20', 'santana'),
(28, 'Someone has been knocked by a car', 'Mlango Kubwa, Stage', 'CRITICAL', '', 'CLOSED', '2025-05-24 06:56:12', NULL, 'Lavenda', 'I dont know', '0788889065', 4, '2025-05-24 09:56:12', '2025-05-24 10:00:38', '2025-05-24 10:00:38', '2025-05-24 10:04:00', 'santana');

-- --------------------------------------------------------

--
-- Table structure for table `incident_logs`
--

CREATE TABLE `incident_logs` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_logs`
--

INSERT INTO `incident_logs` (`id`, `incident_id`, `user_id`, `status`, `notes`, `location`, `created_at`) VALUES
(2, 1, 1, 'EN_ROUTE', 'Incident updated by administrator', NULL, '2025-03-01 12:06:15'),
(3, 3, 0, 'NEW', 'Emergency reported by public user. Contact: 0712345670 (Name: Nehema)', '-1.2783,36.9187 - Mihango', '2025-04-02 09:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `paramedic_details`
--

CREATE TABLE `paramedic_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `certification` varchar(100) NOT NULL,
  `status` enum('ACTIVE','INACTIVE','ON_LEAVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paramedic_details`
--

INSERT INTO `paramedic_details` (`id`, `user_id`, `license_number`, `certification`, `status`, `created_at`) VALUES
(2, 8, 'AGN2323', 'AGN2323', 'ACTIVE', '2025-03-01 11:47:14'),
(3, 9, 'IRN2324', 'IRN2324', 'ACTIVE', '2025-04-02 07:41:52');

-- --------------------------------------------------------

--
-- Table structure for table `patient_outcomes`
--

CREATE TABLE `patient_outcomes` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `outcome` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_outcomes`
--

INSERT INTO `patient_outcomes` (`id`, `incident_id`, `patient_name`, `outcome`, `created_at`) VALUES
(1, 21, 'Bebeto', 'sadly the patient died', '2025-05-06 07:11:10'),
(2, 22, 'Dan', 'sadly the patient died', '2025-05-06 09:38:26'),
(4, 26, 'Caren', 'I was able to provide first aid', '2025-05-08 13:22:44'),
(5, 27, 'Mwangi', 'I was able to provide first aid', '2025-05-13 05:37:20'),
(6, 28, 'Mwangi', 'I was able to provide first aid', '2025-05-24 07:04:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN','DISPATCHER','PARAMEDIC','DRIVER') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `email`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'ADMIN', '2025-02-26 18:38:05', NULL),
(8, 'Agnes', '42f749ade7f9e195bf475f37a44cafcb', 'PARAMEDIC', '2025-03-01 11:47:14', 'agnes@gmail.com'),
(9, 'Irine', '42f749ade7f9e195bf475f37a44cafcb', 'PARAMEDIC', '2025-04-02 07:41:52', 'irineaddy@gmail.com'),
(11, 'santana', '42f749ade7f9e195bf475f37a44cafcb', 'DISPATCHER', '2025-04-02 10:15:58', 'santana.diana@gmail.com'),
(12, 'john.mwangi', '42f749ade7f9e195bf475f37a44cafcb', 'DRIVER', '2025-05-04 15:13:15', 'john.mwangi@gmail.com'),
(19, 'ezra', '42f749ade7f9e195bf475f37a44cafcb', 'DRIVER', '2025-05-06 02:00:19', 'ezra.d@gmail.com'),
(20, 'glen', '0192023a7bbd73250516f069df18b500', 'ADMIN', '2025-05-06 03:35:23', 'glen.osa@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ambulances`
--
ALTER TABLE `ambulances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_number` (`vehicle_number`);

--
-- Indexes for table `ambulance_assignments`
--
ALTER TABLE `ambulance_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `active` (`active`),
  ADD KEY `ambulance_assignments_ibfk_1` (`ambulance_id`),
  ADD KEY `ambulance_assignments_ibfk_2` (`paramedic_id`),
  ADD KEY `ambulance_assignments_ibfk_3` (`driver_id`);

--
-- Indexes for table `ambulance_inventory`
--
ALTER TABLE `ambulance_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ambulance_id` (`ambulance_id`);

--
-- Indexes for table `assignment_logs`
--
ALTER TABLE `assignment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `dispatches`
--
ALTER TABLE `dispatches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `ambulance_id` (`ambulance_id`),
  ADD KEY `paramedic_id` (`paramedic_id`),
  ADD KEY `fk_driver_id` (`driver_id`);

--
-- Indexes for table `driver_details`
--
ALTER TABLE `driver_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dispatcher_id` (`dispatcher_id`);

--
-- Indexes for table `incident_logs`
--
ALTER TABLE `incident_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `paramedic_details`
--
ALTER TABLE `paramedic_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `patient_outcomes`
--
ALTER TABLE `patient_outcomes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ambulances`
--
ALTER TABLE `ambulances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ambulance_assignments`
--
ALTER TABLE `ambulance_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `ambulance_inventory`
--
ALTER TABLE `ambulance_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `assignment_logs`
--
ALTER TABLE `assignment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dispatches`
--
ALTER TABLE `dispatches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `driver_details`
--
ALTER TABLE `driver_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `incident_logs`
--
ALTER TABLE `incident_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `paramedic_details`
--
ALTER TABLE `paramedic_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient_outcomes`
--
ALTER TABLE `patient_outcomes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ambulance_assignments`
--
ALTER TABLE `ambulance_assignments`
  ADD CONSTRAINT `ambulance_assignments_ibfk_1` FOREIGN KEY (`ambulance_id`) REFERENCES `ambulances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ambulance_assignments_ibfk_2` FOREIGN KEY (`paramedic_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ambulance_assignments_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ambulance_inventory`
--
ALTER TABLE `ambulance_inventory`
  ADD CONSTRAINT `ambulance_inventory_ibfk_1` FOREIGN KEY (`ambulance_id`) REFERENCES `ambulances` (`id`);

--
-- Constraints for table `assignment_logs`
--
ALTER TABLE `assignment_logs`
  ADD CONSTRAINT `assignment_logs_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dispatches`
--
ALTER TABLE `dispatches`
  ADD CONSTRAINT `fk_driver_id` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `driver_details`
--
ALTER TABLE `driver_details`
  ADD CONSTRAINT `driver_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`dispatcher_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `paramedic_details`
--
ALTER TABLE `paramedic_details`
  ADD CONSTRAINT `paramedic_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_outcomes`
--
ALTER TABLE `patient_outcomes`
  ADD CONSTRAINT `patient_outcomes_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
