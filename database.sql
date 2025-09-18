-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 06:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bachelor_room_expense_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `added_by`, `added_at`) VALUES
(1, 1, 2, 2, '2025-09-17 16:25:50'),
(2, 2, 2, 2, '2025-09-17 16:29:56'),
(3, 3, 2, 2, '2025-09-17 17:12:30'),
(4, 4, 2, 2, '2025-09-17 17:14:09'),
(5, 5, 2, 2, '2025-09-17 17:14:19'),
(6, 6, 2, 2, '2025-09-17 17:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `group_messages`
--

CREATE TABLE `group_messages` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_invitations`
--

CREATE TABLE `member_invitations` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `invited_by` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `invitation_code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_recipients`
--

CREATE TABLE `message_recipients` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_paid` tinyint(1) DEFAULT 0,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_verification`
--

CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `otp` varchar(6) NOT NULL,
  `purpose` enum('registration','password_reset') NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_verification`
--

INSERT INTO `otp_verification` (`id`, `user_id`, `phone`, `email`, `otp`, `purpose`, `is_verified`, `created_at`, `expires_at`) VALUES
(1, NULL, '7377659925', NULL, '418340', 'registration', 1, '2025-09-17 13:38:46', '2025-09-17 13:53:46'),
(2, NULL, '7377659925', NULL, '429850', 'registration', 1, '2025-09-17 14:05:45', '2025-09-17 14:20:45'),
(3, NULL, '7377659925', '262706', '2025-0', 'password_reset', 0, '2025-09-17 14:45:13', '2025-09-17 15:00:13'),
(4, 2, '6370599093', '', '433073', '', 0, '2025-09-17 14:50:00', '2025-09-17 15:05:00'),
(5, 2, '6370599093', '', '785832', '', 0, '2025-09-17 15:00:09', '2025-09-17 15:15:09'),
(6, 2, '6370599093', '', '562038', '', 0, '2025-09-17 15:00:21', '2025-09-17 15:15:21'),
(7, 2, '6370599093', '', '429390', '', 0, '2025-09-17 15:00:51', '2025-09-17 15:15:51'),
(8, NULL, '6370599093', NULL, '985519', 'registration', 0, '2025-09-17 15:01:46', '2025-09-17 15:09:15'),
(9, 2, '7377659925', '', '182495', '', 0, '2025-09-17 15:02:29', '2025-09-17 15:17:29'),
(10, NULL, '6370599093', NULL, '267511', 'registration', 0, '2025-09-17 15:09:15', '2025-09-17 15:24:15');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `latitude` varchar(20) DEFAULT NULL,
  `longitude` varchar(20) DEFAULT NULL,
  `rental_date` date DEFAULT NULL,
  `monthly_rent` decimal(10,2) DEFAULT NULL,
  `advance_payment` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `description`, `address`, `latitude`, `longitude`, `rental_date`, `monthly_rent`, `advance_payment`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'My Room 1', '', 'null', '100', '2009', '2025-09-17', 1000.00, 1000.00, 2, '2025-09-17 16:25:50', '2025-09-17 16:25:50'),
(2, 'My Room 2', '', 'null', '100', '2009', '2025-09-17', 3000.00, 3000.00, 2, '2025-09-17 17:17:42', '2025-09-17 17:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `room_groups`
--

CREATE TABLE `room_groups` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_groups`
--

INSERT INTO `room_groups` (`id`, `room_id`, `name`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Common Expenses', 'Shared expenses like rent, electricity, water, etc.', 2, '2025-09-17 16:25:50', '2025-09-17 16:25:50'),
(2, 1, 'dfg', 'sdfg', 2, '2025-09-17 16:29:56', '2025-09-17 16:29:56'),
(3, 1, 'dfg', 'dfg', 2, '2025-09-17 17:12:30', '2025-09-17 17:12:30'),
(4, 1, 'fgdhf', 'dfgh', 2, '2025-09-17 17:14:09', '2025-09-17 17:14:09'),
(5, 1, 'rtyurtyu', 'fgjh', 2, '2025-09-17 17:14:19', '2025-09-17 17:14:19'),
(6, 2, 'Common Expenses', 'Shared expenses like rent, electricity, water, etc.', 2, '2025-09-17 17:17:42', '2025-09-17 17:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `room_members`
--

CREATE TABLE `room_members` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_co_admin` tinyint(1) DEFAULT 0,
  `is_read_only` tinyint(1) DEFAULT 0,
  `invitation_status` enum('pending','accepted','rejected') DEFAULT 'accepted',
  `joined_at` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_members`
--

INSERT INTO `room_members` (`id`, `room_id`, `user_id`, `is_admin`, `is_co_admin`, `is_read_only`, `invitation_status`, `joined_at`, `last_activity`) VALUES
(1, 1, 2, 1, 0, 0, 'accepted', '2025-09-17 16:25:50', '2025-09-17 16:25:50'),
(2, 1, 4, 0, 0, 0, 'pending', NULL, NULL),
(3, 2, 2, 1, 0, 0, 'accepted', '2025-09-17 17:17:42', '2025-09-17 17:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default_avatar.png',
  `room_number` varchar(20) DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `kyc_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `id_proof` varchar(255) DEFAULT NULL,
  `address_proof` varchar(255) DEFAULT NULL,
  `selfie_verification` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `phone`, `email`, `password`, `profile_image`, `room_number`, `joined_date`, `occupation`, `emergency_contact`, `kyc_status`, `id_proof`, `address_proof`, `selfie_verification`, `created_at`, `updated_at`) VALUES
(2, 'MUNMUN MAHARANA', '7377659925', '', '$2y$10$Nn6Yu4PhaGDvRm/fMeb2wOliY7rH8d2oDFdGCZO4J8QIOlETaWkN6', '2_1758121375.jpg', '', NULL, '', '6370599093', 'verified', '2_id_proof_1758118357.jpg', '2_address_proof_1758118364.jpg', '2_selfie_1758119250.png', '2025-09-17 14:06:07', '2025-09-17 15:08:57'),
(3, 'Amit Kumar', '9876543210', 'amit@example.com', 'pass123', 'amit.jpg', '101', '2025-01-10', 'Engineer', '9123456780', 'verified', 'amit_id.jpg', 'amit_addr.jpg', 'amit_selfie.jpg', '2025-09-17 16:28:39', '2025-09-17 16:28:39'),
(4, 'Rohit Sharma', '9876543211', 'rohit@example.com', '$2y$10$Nn6Yu4PhaGDvRm/fMeb2wOliY7rH8d2oDFdGCZO4J8QIOlETaWkN6', 'rohit.jpg', '102', '2025-01-12', 'Doctor', '9123456781', 'pending', 'rohit_id.jpg', 'rohit_addr.jpg', 'rohit_selfie.jpg', '2025-09-17 16:28:39', '2025-09-17 16:32:44'),
(5, 'Suman Das', '9876543212', 'suman@example.com', 'pass123', 'suman.jpg', '103', '2025-01-14', 'Student', '9123456782', 'rejected', 'suman_id.jpg', 'suman_addr.jpg', 'suman_selfie.jpg', '2025-09-17 16:28:39', '2025-09-17 16:28:39'),
(6, 'Priya Singh', '9876543213', 'priya@example.com', 'pass123', 'priya.jpg', '104', '2025-01-15', 'Teacher', '9123456783', 'verified', 'priya_id.jpg', 'priya_addr.jpg', 'priya_selfie.jpg', '2025-09-17 16:28:39', '2025-09-17 16:28:39'),
(7, 'Arjun Mehta', '9876543214', 'arjun@example.com', 'pass123', 'arjun.jpg', '105', '2025-01-16', 'Designer', '9123456784', 'pending', 'arjun_id.jpg', 'arjun_addr.jpg', 'arjun_selfie.jpg', '2025-09-17 16:28:39', '2025-09-17 16:28:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_id` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `group_messages`
--
ALTER TABLE `group_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `member_invitations`
--
ALTER TABLE `member_invitations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `invited_by` (`invited_by`);

--
-- Indexes for table `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_id` (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone` (`phone`),
  ADD KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `room_groups`
--
ALTER TABLE `room_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `room_members`
--
ALTER TABLE `room_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_id` (`room_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `group_messages`
--
ALTER TABLE `group_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_invitations`
--
ALTER TABLE `member_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_recipients`
--
ALTER TABLE `message_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `room_groups`
--
ALTER TABLE `room_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `room_members`
--
ALTER TABLE `room_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `room_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_messages`
--
ALTER TABLE `group_messages`
  ADD CONSTRAINT `group_messages_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `room_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_invitations`
--
ALTER TABLE `member_invitations`
  ADD CONSTRAINT `member_invitations_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `member_invitations_ibfk_2` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD CONSTRAINT `message_recipients_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `group_messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otp_verification`
--rror  
ALTER TABLE `otp_verification`
  ADD CONSTRAINT `otp_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `password_reset_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_groups`
--
ALTER TABLE `room_groups`
  ADD CONSTRAINT `room_groups_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_groups_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_members`
--
ALTER TABLE `room_members`
  ADD CONSTRAINT `room_members_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
