-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 25, 2025 at 02:40 PM
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
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Pending','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `shipping_address_id` int(11) DEFAULT NULL,
  `voucher_code` varchar(255) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `status`, `total_price`, `payment_method`, `shipping_address_id`, `voucher_code`, `discount_amount`, `created_at`, `updated_at`) VALUES
(70, 33, 'Pending', 2052.88, 'bank_transfer', 13, NULL, 0.00, '2025-09-23 17:36:15', '2025-09-25 05:48:42'),
(71, 33, 'Pending', 2052.88, 'card', 13, NULL, 0.00, '2025-09-23 17:38:11', '2025-09-25 05:48:50'),
(72, 33, 'Pending', 2052.88, 'cod', 13, NULL, 0.00, '2025-09-23 17:39:09', '2025-09-25 05:49:02'),
(73, 33, 'Pending', 2052.88, 'bank_transfer', 13, NULL, 0.00, '2025-09-23 17:48:33', '2025-09-25 05:49:05'),
(74, 33, 'Pending', 2052.88, 'bank_transfer', 13, NULL, 0.00, '2025-09-23 18:08:57', '2025-09-25 05:49:22'),
(75, 33, 'Pending', 2052.88, 'gcash', 13, NULL, 0.00, '2025-09-23 18:09:56', '2025-09-25 05:49:29'),
(76, 33, 'Pending', 2052.88, 'cod', 13, NULL, 0.00, '2025-09-23 18:10:56', '2025-09-23 18:10:56'),
(77, 33, 'Pending', 10228.88, 'bank_transfer', 13, NULL, 0.00, '2025-09-24 16:04:20', '2025-09-25 05:49:49'),
(78, 33, 'Pending', 10228.88, 'bank_transfer', 13, NULL, 0.00, '2025-09-24 16:04:27', '2025-09-25 05:49:53'),
(79, 33, 'Pending', 10228.88, 'cod', 13, NULL, 0.00, '2025-09-24 16:04:31', '2025-09-24 16:04:31'),
(80, 33, 'Pending', 22538.80, 'cod', 13, NULL, 0.00, '2025-09-25 05:10:52', '2025-09-25 05:10:52'),
(81, 33, 'Pending', 8660.88, 'gcash', 13, NULL, 0.00, '2025-09-25 05:15:22', '2025-09-25 05:44:53'),
(82, 33, 'Pending', 8660.88, 'card', 13, NULL, 0.00, '2025-09-25 05:15:29', '2025-09-25 05:50:07'),
(85, 33, 'Pending', 6084.88, 'gcash', 13, NULL, 0.00, '2025-09-25 05:51:26', '2025-09-25 05:56:19'),
(86, 33, 'Pending', 2724.88, 'card', 13, NULL, 0.00, '2025-09-25 06:01:04', '2025-09-25 06:01:43'),
(87, 33, 'Pending', 1268.88, 'cod', 13, NULL, 0.00, '2025-09-25 06:03:14', '2025-09-25 06:03:14'),
(88, 33, 'Pending', 1940.88, 'gcash', 13, NULL, 0.00, '2025-09-25 06:03:31', '2025-09-25 06:04:06'),
(89, 33, '', 2388.88, 'gcash', 13, NULL, 0.00, '2025-09-25 06:22:25', '2025-09-25 06:22:25'),
(90, 33, 'Pending', 3059.76, 'gcash', 13, NULL, 0.00, '2025-09-25 06:23:55', '2025-09-25 06:24:16'),
(91, 33, 'Pending', 820.88, 'cod', 13, NULL, 0.00, '2025-09-25 06:44:15', '2025-09-25 06:44:15'),
(92, 33, 'Pending', 1940.88, 'cod', 13, NULL, 0.00, '2025-09-25 07:58:25', '2025-09-25 07:58:25'),
(93, 33, 'Pending', 11011.76, 'cod', 13, NULL, 0.00, '2025-09-25 08:14:22', '2025-09-25 08:14:22'),
(94, 33, 'Pending', 1268.88, 'cod', 13, NULL, 0.00, '2025-09-25 10:17:19', '2025-09-25 10:17:19'),
(95, 33, 'Pending', 10228.88, 'cod', 13, NULL, 0.00, '2025-09-25 13:19:54', '2025-09-25 13:19:54'),
(96, 33, 'Pending', 1056.19, 'gcash', 13, 'FINALSOFF', 89.90, '2025-09-25 14:32:05', '2025-09-25 14:33:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `shipping_address_id` (`shipping_address_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shipping_address_id`) REFERENCES `user_addresses` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
