-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 27, 2025 at 08:34 AM
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
-- Table structure for table `product_rating_summary`
--

CREATE TABLE `product_rating_summary` (
  `product_id` int(11) NOT NULL,
  `total_reviews` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `rating_1_count` int(11) DEFAULT 0,
  `rating_2_count` int(11) DEFAULT 0,
  `rating_3_count` int(11) DEFAULT 0,
  `rating_4_count` int(11) DEFAULT 0,
  `rating_5_count` int(11) DEFAULT 0,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_rating_summary`
--

INSERT INTO `product_rating_summary` (`product_id`, `total_reviews`, `average_rating`, `rating_1_count`, `rating_2_count`, `rating_3_count`, `rating_4_count`, `rating_5_count`, `last_updated`) VALUES
(1, 1, 5.00, 0, 0, 0, 0, 1, '2025-09-27 08:09:10'),
(2, 1, 2.00, 0, 1, 0, 0, 0, '2025-09-27 08:33:18'),
(6, 0, 0.00, NULL, NULL, NULL, NULL, NULL, '2025-09-27 08:10:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `product_rating_summary`
--
ALTER TABLE `product_rating_summary`
  ADD PRIMARY KEY (`product_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product_rating_summary`
--
ALTER TABLE `product_rating_summary`
  ADD CONSTRAINT `product_rating_summary_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
