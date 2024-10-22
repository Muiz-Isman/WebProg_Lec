-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2024 at 08:15 PM
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
-- Database: `event_registration`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `location` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `max_participants` int(11) NOT NULL,
  `status` enum('open','closed','canceled') DEFAULT 'open',
  `banner_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `name`, `date`, `time`, `location`, `description`, `max_participants`, `status`, `banner_image`, `created_at`, `updated_at`) VALUES
(2, 'blackpink', '2024-10-25', '12:00:00', 'UMN', 'konser blackpink ', 200, '', NULL, '2024-10-22 16:38:37', '2024-10-22 17:19:37'),
(4, 'ucol', '2024-02-12', '23:00:00', 'UMN', 'Gacor', 150, '', NULL, '2024-10-22 17:03:27', '2024-10-22 17:03:27'),
(5, 'budhi jawir', '2012-12-12', '12:00:00', 'ICE BSD', 'Gacor', 147, '', NULL, '2024-10-22 17:12:05', '2024-10-22 17:27:17'),
(6, '12', '2121-03-12', '11:11:00', 'jauh', 'zxc', 56, '', NULL, '2024-10-22 17:15:03', '2024-10-22 17:27:02'),
(7, 'qweqwe', '1212-12-12', '12:12:00', '12', '12', 12, '', NULL, '2024-10-22 17:30:43', '2024-10-22 17:30:43'),
(8, 'zxc', '1231-03-12', '21:12:00', '12', '12', 100, '', NULL, '2024-10-22 17:49:17', '2024-10-22 17:49:17');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('registered','canceled') DEFAULT 'registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`registration_id`, `user_id`, `event_id`, `registration_date`, `status`) VALUES
(42, 3, 8, '2024-10-22 17:50:20', 'canceled'),
(43, 3, 8, '2024-10-22 17:50:31', 'canceled'),
(44, 3, 7, '2024-10-22 17:50:38', 'canceled'),
(45, 3, 7, '2024-10-22 17:50:41', 'registered'),
(46, 3, 4, '2024-10-22 17:50:45', 'canceled'),
(47, 3, 6, '2024-10-22 17:50:48', 'canceled'),
(48, 3, 6, '2024-10-22 17:51:08', 'registered');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2024-10-22 10:21:53', '2024-10-22 10:21:53'),
(2, 'muiz', '123@asd', '$2y$10$qbL/wg7AloisuohW5LYRFe7loWNyVlMEyp5JsqrnBw85Mnqsi2QKW', 'user', '2024-10-22 13:36:49', '2024-10-22 13:36:49'),
(3, 'muizisman', 'muiz123@gmail.com', '$2y$10$1QRKbWR8FAupHW4Bgeerz.vv0Q1T0GPtfr2A6j1dGhU9A6QeFWxFO', 'user', '2024-10-22 14:22:41', '2024-10-22 18:05:30'),
(4, 'adminbaik', 'adminbaik@gmail.com', 'adminbaik123', 'admin', '2024-10-22 15:06:29', '2024-10-22 15:06:29'),
(5, 'budi', 'budi@gmail.com', '$2y$10$IIU3w4KdiSvsE28axS/M/.hvx4krk3JzV1pgri3ijvsZrFXG/bPJS', 'admin', '2024-10-22 15:17:16', '2024-10-22 15:17:16'),
(6, 'asd', 'asd@asd', '$2y$10$MehXe.0sStt8eWIDrLr1EekLqht8UaxjsQcINgUOZVbm8eVVmwMYS', 'user', '2024-10-22 15:20:50', '2024-10-22 15:20:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

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
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
