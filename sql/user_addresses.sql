-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 30, 2025 at 02:56 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u585057361_shoe`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('shipping','billing') NOT NULL DEFAULT 'shipping',
  `full_name` varchar(150) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `type`, `full_name`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `phone`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 21, '', 'Von Reven Galos', '30 Clara Homes Subdivision', '', 'San Mateo', 'Rizal', '1850', 'Philippines', '09060212372', 1, '2025-09-08 10:07:33', '2025-09-08 10:07:33'),
(2, 23, '', 'Jessie', '12 Jupiter St, Liamzon Subd, Guitnang Bayan I', '', 'San Mateo', 'Rizal', '1850', 'Philippines', '09926462192', 0, '2025-09-08 17:33:18', '2025-09-08 17:33:18'),
(3, 22, '', 'Von Reven Galos', '30 Clara Homes Subdivision', '', 'Marikina', 'Rizal', '1860', 'Philippines', '09060212372', 1, '2025-09-08 18:13:15', '2025-09-08 18:13:15'),
(4, 24, '', 'Von Reven G Galos', '30 Clara Homes', '', 'San mateo', 'Rizal', '1850', 'Philippines', '09060212372', 1, '2025-09-09 09:33:25', '2025-09-09 09:33:25'),
(6, 25, '', 'jerbs', '20 Dulong Bayan 2', '', 'San Mateo', 'Rizal', '1850', 'Philippines', '09060212372', 1, '2025-09-15 14:09:28', '2025-09-15 14:09:28'),
(8, 27, '', 'Romu', 'Imperial St', '', 'QC', 'MetroManigga', '1102', 'PH', '0961', 1, '2025-09-16 08:52:29', '2025-09-16 08:52:29'),
(10, 36, 'shipping', 'Jessierhy', '12 Jupiter St, Liamzon Subd, Guitnang Bayan I', '', 'San Mateo', 'Rizal', '1850', 'Philippines', '(639) 926-462192', 0, '2025-09-18 14:59:16', '2025-09-29 14:42:14'),
(12, 37, 'shipping', 'Jessie', '12 Jupiter St, Liamzon Subd, Guitnang Bayan I', '', 'San Mateo', 'Rizal', '1850', 'Philippines', '09926462192', 0, '2025-09-20 18:21:27', '2025-09-20 18:21:27'),
(13, 33, 'shipping', 'Von Reven Galos', '30 Clara Homes Subdivision', '', 'San Mateo', 'Rizal', '1850', 'Philippines', '09060212372', 1, '2025-09-23 12:39:32', '2025-09-23 12:39:32'),
(14, 38, 'shipping', 'dwa', 'dwa', 'dwa', 'daw', 'da', '1234', 'Philippines', '09455715151', 0, '2025-09-27 14:11:14', '2025-09-27 14:11:14'),
(15, 36, 'shipping', 'jES', 'Filinvest 1', '', 'Quezon', 'City', '1292', 'Philippines', '', 1, '2025-09-29 14:41:01', '2025-09-29 14:42:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
