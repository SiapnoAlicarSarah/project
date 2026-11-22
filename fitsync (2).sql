-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2026 at 03:51 PM
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
-- Database: `fitsync`
--

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `emergency_contact` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`client_id`, `user_id`, `contact_number`, `address`, `birthdate`, `emergency_contact`) VALUES
(1, 1, '', '', '0000-00-00', ''),
(2, 2, '0955-820-7825', 'Sitio Sabangan', '2005-09-04', 'mama ko');

-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

CREATE TABLE `membership` (
  `membership_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
  `cancelled_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership`
--

INSERT INTO `membership` (`membership_id`, `client_id`, `plan_id`, `start_date`, `end_date`, `status`, `cancelled_by`) VALUES
(2, 2, 1, '2026-11-22', '2026-11-28', 'active', NULL),
(3, 2, 2, '2026-11-29', '2026-12-05', 'cancelled', 2);

-- --------------------------------------------------------

--
-- Table structure for table `membership_plan`
--

CREATE TABLE `membership_plan` (
  `plan_id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_price` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_plan`
--

INSERT INTO `membership_plan` (`plan_id`, `plan_name`, `plan_price`, `duration_days`, `description`, `is_active`, `image`) VALUES
(1, 'Strength and Resistance Training', 1000.00, 7, 'Focuses on lifting heavy loads for low repetitions (typically 3–5 reps) to maximize raw strength, often centering around the big compound lifts: squat, bench press, and deadlift', 1, 'weightTrain.png'),
(2, 'Cardiovascular and Conditioning Training', 1000.00, 7, 'Involves low-to-moderate intensity aerobic work (running, cycling, rowing) sustained for longer durations to build heart health and endurance.', 1, 'cardioTrain.png'),
(3, 'Functional, Mobility and Core Training', 1500.00, 7, 'Involves low-to-moderate intensity aerobic work (running, cycling, rowing) sustained for longer durations to build heart health and endurance.', 1, 'coreTrain.png'),
(4, 'Circuit Group Training Program', 500.00, 7, 'Moves quickly from one exercise station to the next with minimal rest, combining strength and aerobic conditioning.', 1, 'circuitTrain.png');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `membership_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `role` enum('client','staff','owner') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notification_id`, `user_id`, `membership_id`, `message`, `is_read`, `created_at`, `role`) VALUES
(10, 2, 3, 'Your Cardiovascular and Conditioning Training membership was cancelled by staff.', 0, '2026-11-22 22:20:53', 'client');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `membership_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','gcash','bank_transfer','') NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `received_by` int(11) DEFAULT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `membership_id`, `client_id`, `amount`, `payment_method`, `payment_date`, `payment_status`, `received_by`, `reference_no`, `created_at`) VALUES
(1, 2, 2, 1000.00, 'gcash', '2026-11-22 21:33:28', 'paid', 1, '111-222-333', '2026-11-22 21:33:28'),
(2, 3, 2, 1000.00, 'cash', '2026-11-22 21:40:01', 'refunded', 1, 'FS-20261122-98CCD6', '2026-11-22 21:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `user_id`, `position`, `hire_date`, `status`) VALUES
(1, 1, 'Owner', '2026-11-22', 'approved'),
(2, 3, 'Staff', '2026-11-22', 'approved'),
(3, 5, 'Staff', '2026-11-22', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','staff','owner','') NOT NULL DEFAULT 'client',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'OWNER', 'owner@gmail.com', '$2y$10$x986Eey3jqtRX0KWQ57ToOQEcDx0vDhTzU56Wr6DCVMzlMGn45LmC', 'owner', '2026-11-22 20:58:26'),
(2, 'client', 'client@gmail.com', '$2y$10$FMfrqB5PosmDmo4wsGYKw.Y0kmY33jrgsihdmXv90TFuwOuZlJ9QW', 'client', '2026-11-22 21:03:36'),
(3, 'staff', 'staff@gmail.com', '$2y$10$fQpgb30cJZh5Asv/vnVgK.2mNOwFMsNYV07MHoKSXE0x9KvJjhMSi', 'staff', '2026-11-22 22:11:45'),
(5, 'staff2', 'staff2@gmail.com', '$2y$10$3B8xf04FgZC4eydgVKgrpuxp7JvdHaHv8KeAiS85Co58RPv6pRjri', 'staff', '2026-11-22 22:13:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `membership`
--
ALTER TABLE `membership`
  ADD PRIMARY KEY (`membership_id`);

--
-- Indexes for table `membership_plan`
--
ALTER TABLE `membership_plan`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notification_membership` (`membership_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_notification_user` (`user_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `membership_id` (`membership_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `membership`
--
ALTER TABLE `membership`
  MODIFY `membership_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `membership_plan`
--
ALTER TABLE `membership_plan`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
