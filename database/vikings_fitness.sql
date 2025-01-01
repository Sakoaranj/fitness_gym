-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 01, 2025 at 05:06 PM
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
-- Database: `vikings_fitness`
--

-- --------------------------------------------------------

--
-- Table structure for table `class_bookings`
--

CREATE TABLE `class_bookings` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('booked','cancelled','completed') DEFAULT 'booked',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_schedules`
--

CREATE TABLE `class_schedules` (
  `id` int(11) NOT NULL,
  `workout_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `max_participants` int(11) NOT NULL DEFAULT 10,
  `status` enum('scheduled','cancelled','completed') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_schedules`
--

INSERT INTO `class_schedules` (`id`, `workout_id`, `schedule_date`, `start_time`, `end_time`, `instructor`, `max_participants`, `status`, `created_at`) VALUES
(1, 13, '2025-01-02', '08:00:00', '11:30:00', 'jhon smith', 10, 'scheduled', '2024-12-31 07:57:59'),
(2, 13, '2025-01-02', '08:00:00', '11:30:00', 'jhon smith', 10, 'cancelled', '2024-12-31 07:59:39');

-- --------------------------------------------------------

--
-- Table structure for table `member_preferences`
--

CREATE TABLE `member_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `workout_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `type` enum('workout','schedule') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_preferences`
--

INSERT INTO `member_preferences` (`id`, `user_id`, `workout_id`, `schedule_id`, `type`, `created_at`) VALUES
(14, 10, 2, NULL, 'workout', '2025-01-01 10:50:54'),
(15, 10, 8, NULL, 'workout', '2025-01-01 10:51:04'),
(16, 10, NULL, 1, 'schedule', '2025-01-01 10:51:16');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `recipient_id`, `subject`, `message`, `created_at`) VALUES
(47, 1, 10, 'update', 'yh', '2025-01-01 14:00:44'),
(48, 1, 11, 'update', 'yh', '2025-01-01 14:00:44'),
(49, 1, 13, 'update', 'yh', '2025-01-01 14:00:44'),
(50, 1, 10, 'update', 'yh', '2025-01-01 14:00:47'),
(51, 1, 11, 'update', 'yh', '2025-01-01 14:00:47'),
(52, 1, 13, 'update', 'yh', '2025-01-01 14:00:47'),
(53, 1, 10, 'update', 'yh', '2025-01-01 14:00:50'),
(54, 1, 11, 'update', 'yh', '2025-01-01 14:00:50'),
(55, 1, 13, 'update', 'yh', '2025-01-01 14:00:50'),
(56, 1, 10, 'update', 'yh', '2025-01-01 14:00:53'),
(57, 1, 11, 'update', 'yh', '2025-01-01 14:00:53'),
(58, 1, 13, 'update', 'yh', '2025-01-01 14:00:53'),
(59, 1, 10, 'update', 'yh', '2025-01-01 14:00:56'),
(60, 1, 11, 'update', 'yh', '2025-01-01 14:00:56'),
(61, 1, 13, 'update', 'yh', '2025-01-01 14:00:56'),
(62, 1, 10, 'update', 'yh', '2025-01-01 14:00:58'),
(63, 1, 11, 'update', 'yh', '2025-01-01 14:00:58'),
(64, 1, 13, 'update', 'yh', '2025-01-01 14:00:58'),
(65, 1, 10, 'update', 'yh', '2025-01-01 14:01:00'),
(66, 1, 11, 'update', 'yh', '2025-01-01 14:01:00'),
(67, 1, 13, 'update', 'yh', '2025-01-01 14:01:00'),
(68, 1, 10, 'update', 'yh', '2025-01-01 14:01:02'),
(69, 1, 11, 'update', 'yh', '2025-01-01 14:01:02'),
(70, 1, 13, 'update', 'yh', '2025-01-01 14:01:02'),
(71, 1, 10, 'update', 'yh', '2025-01-01 14:01:05'),
(72, 1, 11, 'update', 'yh', '2025-01-01 14:01:05'),
(73, 1, 13, 'update', 'yh', '2025-01-01 14:01:05'),
(74, 1, 10, 'update', 'yh', '2025-01-01 14:01:08'),
(75, 1, 11, 'update', 'yh', '2025-01-01 14:01:08'),
(76, 1, 13, 'update', 'yh', '2025-01-01 14:01:08'),
(77, 1, 10, 'update', 'yh', '2025-01-01 14:01:11'),
(78, 1, 11, 'update', 'yh', '2025-01-01 14:01:11'),
(79, 1, 13, 'update', 'yh', '2025-01-01 14:01:11'),
(80, 1, 10, 'update', 'yh', '2025-01-01 14:01:14'),
(81, 1, 11, 'update', 'yh', '2025-01-01 14:01:14'),
(82, 1, 13, 'update', 'yh', '2025-01-01 14:01:14'),
(83, 1, 10, 'update', 'yh', '2025-01-01 14:01:17'),
(84, 1, 11, 'update', 'yh', '2025-01-01 14:01:17'),
(85, 1, 13, 'update', 'yh', '2025-01-01 14:01:17'),
(86, 1, 10, 'update', 'yh', '2025-01-01 14:01:19'),
(87, 1, 11, 'update', 'yh', '2025-01-01 14:01:19'),
(88, 1, 13, 'update', 'yh', '2025-01-01 14:01:19'),
(89, 1, 10, 'update', 'yh', '2025-01-01 14:01:21'),
(90, 1, 11, 'update', 'yh', '2025-01-01 14:01:21'),
(91, 1, 13, 'update', 'yh', '2025-01-01 14:01:21'),
(92, 1, 10, 'update', 'yh', '2025-01-01 14:01:23'),
(93, 1, 11, 'update', 'yh', '2025-01-01 14:01:23'),
(94, 1, 13, 'update', 'yh', '2025-01-01 14:01:23'),
(95, 1, 10, 'update', 'yh', '2025-01-01 14:01:25'),
(96, 1, 11, 'update', 'yh', '2025-01-01 14:01:25'),
(97, 1, 13, 'update', 'yh', '2025-01-01 14:01:25'),
(98, 1, 10, 'update', 'yh', '2025-01-01 14:01:27'),
(99, 1, 11, 'update', 'yh', '2025-01-01 14:01:27'),
(100, 1, 13, 'update', 'yh', '2025-01-01 14:01:27'),
(101, 1, 10, 'update', 'yh', '2025-01-01 14:01:29'),
(102, 1, 11, 'update', 'yh', '2025-01-01 14:01:29'),
(103, 1, 13, 'update', 'yh', '2025-01-01 14:01:29'),
(104, 1, 10, 'update', 'yh', '2025-01-01 14:01:31'),
(105, 1, 11, 'update', 'yh', '2025-01-01 14:01:31'),
(106, 1, 13, 'update', 'yh', '2025-01-01 14:01:31'),
(107, 1, 10, 'update', 'yh', '2025-01-01 14:01:34'),
(108, 1, 11, 'update', 'yh', '2025-01-01 14:01:34'),
(109, 1, 13, 'update', 'yh', '2025-01-01 14:01:34'),
(110, 1, 10, 'update', 'yh', '2025-01-01 14:01:36'),
(111, 1, 11, 'update', 'yh', '2025-01-01 14:01:36'),
(112, 1, 13, 'update', 'yh', '2025-01-01 14:01:36'),
(113, 1, 10, 'update', 'yh', '2025-01-01 14:01:38'),
(114, 1, 11, 'update', 'yh', '2025-01-01 14:01:38'),
(115, 1, 13, 'update', 'yh', '2025-01-01 14:01:38'),
(116, 1, 10, 'update', 'yh', '2025-01-01 14:01:40'),
(117, 1, 11, 'update', 'yh', '2025-01-01 14:01:40'),
(118, 1, 13, 'update', 'yh', '2025-01-01 14:01:40'),
(119, 1, 10, 'update', 'yh', '2025-01-01 14:01:42'),
(120, 1, 11, 'update', 'yh', '2025-01-01 14:01:42'),
(121, 1, 13, 'update', 'yh', '2025-01-01 14:01:42'),
(122, 1, 10, 'update', 'yh', '2025-01-01 14:01:44'),
(123, 1, 11, 'update', 'yh', '2025-01-01 14:01:44'),
(124, 1, 13, 'update', 'yh', '2025-01-01 14:01:44'),
(125, 1, 13, 'g', 'rg', '2025-01-01 14:02:03'),
(126, 1, 13, 'g', 'rg', '2025-01-01 14:02:05'),
(127, 1, 13, 'g', 'rg', '2025-01-01 14:02:07'),
(128, 1, 13, 'g', 'rg', '2025-01-01 14:02:09');

-- --------------------------------------------------------

--
-- Table structure for table `message_recipients`
--

CREATE TABLE `message_recipients` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `read_status` enum('unread','read') DEFAULT 'unread',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('read','unread') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','gcash') NOT NULL DEFAULT 'cash',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_screenshot` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `amount`, `payment_method`, `payment_reference`, `payment_screenshot`, `payment_date`, `status`, `created_at`) VALUES
(20, 10, 7000.00, 'gcash', '917272726', 'payment_1735725465_677511996b55c.png', '2025-01-01 09:57:45', 'verified', '2025-01-01 09:57:45'),
(21, 11, 700.00, 'gcash', '1626366363', 'payment_1735729103_67751fcf8b8bd.jpg', '2025-01-01 10:58:23', 'rejected', '2025-01-01 10:58:23'),
(22, 13, 7000.00, 'gcash', '123456', 'payment_1735738958_6775464e6559e.jpg', '2025-01-01 13:42:38', 'verified', '2025-01-01 13:42:38');

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `payment_method`, `account_name`, `account_number`, `qr_code_path`, `is_active`, `created_at`, `updated_at`, `status`) VALUES
(1, 'gcash', 'Jandel Villa', '09367506824', NULL, 1, '2024-12-30 13:55:09', '2025-01-01 14:34:25', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in months',
  `price` decimal(10,2) NOT NULL,
  `features` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `name`, `description`, `duration`, `price`, `features`, `created_at`, `status`) VALUES
(3, 'Elite Plan', 'Ultimate fitness experience', 6, 7000.00, 'All Premium Plan features\nUnlimited guest passes\nPriority class booking\nMonthly body composition analysis\nCustom meal plans', '2024-12-30 14:16:49', 'active'),
(12, 'Basic Monthly', 'wala lng', 1, 700.00, 'batakk', '2025-01-01 10:30:38', 'active'),
(13, 'Premium Monthly', 'batak', 1, 1200.00, 'veteran equipment access', '2025-01-01 14:54:24', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `workout_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 20,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `workout_id`, `trainer_id`, `schedule_date`, `start_time`, `end_time`, `capacity`, `created_at`) VALUES
(1, 8, 1, '2024-12-01', '08:00:00', '00:00:00', 5, '2024-12-30 15:18:02'),
(4, 13, 1, '2024-12-30', '08:30:00', '11:30:00', 3, '2024-12-31 07:29:49');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_bookings`
--

CREATE TABLE `schedule_bookings` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('booked','cancelled') DEFAULT 'booked',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_id` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `status` enum('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan`, `amount`, `start_date`, `end_date`, `created_at`, `payment_id`, `plan_id`, `status`) VALUES
(20, 10, NULL, 0.00, '2025-01-01', '2025-07-01', '2025-01-01 09:57:45', 20, 3, 'active'),
(21, 11, NULL, 0.00, '0000-00-00', '0000-00-00', '2025-01-01 10:58:23', 21, 12, 'cancelled'),
(22, 13, NULL, 0.00, '2025-01-01', '2025-07-01', '2025-01-01 13:42:38', 22, 3, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`id`, `name`, `email`, `phone`, `specialization`, `bio`, `status`, `created_at`) VALUES
(1, 'John Smith', 'john.smith@vikingsfitness.com', '09123456789', 'Strength Training, HIIT', 'Certified personal trainer with 5 years of experience', 'active', '2024-12-30 15:16:23'),
(2, 'Sarah Johnson', 'sarah.johnson@vikingsfitness.com', '09234567890', 'Yoga, Flexibility', 'Yoga instructor and wellness coach', 'active', '2024-12-30 15:16:23'),
(3, 'Mike Wilson', 'mike.wilson@vikingsfitness.com', '09345678901', 'Cardio, Boxing', 'Former professional athlete and fitness enthusiast', 'active', '2024-12-30 15:16:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@vikingsfitness.com', 'System Administrator', 'admin', '2024-12-30 13:32:36'),
(10, 'testmember', '$2y$10$qMw0GS6QcU0bgbWyB4W5yu5BGIWViUEgeTkwCF.4ztvCwvamIlR2W', 'test@example.com', 'Test Member', 'member', '2025-01-01 09:55:36'),
(11, 'testmember2', '$2y$10$OtG67.T0pLXOnffEyVmYPONRbG7BIlEHOswLiTXNNFL9fBfpxd9qu', 'test2@example.com', 'Test Member2', 'member', '2025-01-01 10:56:33'),
(13, 'jess', '$2y$10$fJI7gCQVRnadV0UsHrUulOllwt0gWasWTpiLcXy16zj3XZmXxMfn6', 'villajessa3@gmail.com', 'Jessa ', 'member', '2025-01-01 13:41:03'),
(14, 'testmember3', '$2y$10$FN6Gz6bKGCM0T8hvT7jNDODtNdEPcyFcKhWdZ0TlzkajFEdVUZ1BC', 'test3@example.com', 'Test Member3', 'member', '2025-01-01 14:37:08'),
(15, 'testmember4', '$2y$10$F2Fscz.xU2dOMBzcoX2GoOux1pZslcnBlRyuaOQhgP2LBZ02x.DrW', 'test4@example.com', 'test member4', 'member', '2025-01-01 15:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `workouts`
--

CREATE TABLE `workouts` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workouts`
--

INSERT INTO `workouts` (`id`, `category_id`, `name`, `description`, `difficulty_level`, `duration`, `created_at`, `instructions`) VALUES
(2, 7, 'Morning Cardio', 'Start your day with an energizing cardio workout', 'beginner', 30, '2024-12-30 14:10:02', NULL),
(4, 1, 'Full Body Strength', 'Complete body workout targeting all major muscle groups', 'intermediate', 45, '2024-12-30 14:10:14', NULL),
(8, 8, 'Yoga Flow', 'Dynamic yoga sequences for flexibility', 'beginner', 45, '2024-12-30 14:10:14', NULL),
(10, 4, 'HIIT Express', 'Quick but intense workout for busy schedules', 'intermediate', 25, '2024-12-30 14:10:14', NULL),
(13, 5, 'Core Crusher', 'Intensive core workout for strong abs', 'intermediate', 30, '2024-12-30 14:10:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `workout_categories`
--

CREATE TABLE `workout_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_categories`
--

INSERT INTO `workout_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Strength Training', 'Build muscle and increase strength through resistance training', '2024-12-30 13:46:06'),
(4, 'HIIT', 'High-Intensity Interval Training for maximum calorie burn', '2024-12-30 13:46:06'),
(5, 'Core', 'Focus on strengthening core muscles and improving stability', '2024-12-30 13:46:06'),
(7, 'Cardio', 'Improve cardiovascular health and endurance', '2024-12-30 13:55:09'),
(8, 'Flexibility', 'Enhance flexibility and mobility through stretching exercises', '2024-12-30 13:55:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class_bookings`
--
ALTER TABLE `class_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workout_id` (`workout_id`);

--
-- Indexes for table `member_preferences`
--
ALTER TABLE `member_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_workout_id` (`workout_id`),
  ADD KEY `idx_schedule_id` (`schedule_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_status` (`status`),
  ADD KEY `idx_notifications_type` (`type`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workout_id` (`workout_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `schedule_bookings`
--
ALTER TABLE `schedule_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `workout_categories`
--
ALTER TABLE `workout_categories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `class_bookings`
--
ALTER TABLE `class_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_schedules`
--
ALTER TABLE `class_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `member_preferences`
--
ALTER TABLE `member_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `message_recipients`
--
ALTER TABLE `message_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedule_bookings`
--
ALTER TABLE `schedule_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `workouts`
--
ALTER TABLE `workouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `workout_categories`
--
ALTER TABLE `workout_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `class_bookings`
--
ALTER TABLE `class_bookings`
  ADD CONSTRAINT `class_bookings_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`),
  ADD CONSTRAINT `class_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD CONSTRAINT `class_schedules_ibfk_1` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`);

--
-- Constraints for table `member_preferences`
--
ALTER TABLE `member_preferences`
  ADD CONSTRAINT `fk_member_preferences_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_member_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_member_preferences_workout` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `message_recipients`
--
ALTER TABLE `message_recipients`
  ADD CONSTRAINT `message_recipients_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`),
  ADD CONSTRAINT `message_recipients_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_bookings`
--
ALTER TABLE `schedule_bookings`
  ADD CONSTRAINT `schedule_bookings_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  ADD CONSTRAINT `subscriptions_ibfk_3` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`);

--
-- Constraints for table `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `workouts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `workout_categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
