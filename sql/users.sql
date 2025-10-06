-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 29, 2025 at 03:42 PM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','buyer') NOT NULL,
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `remember_selector` varchar(40) DEFAULT NULL,
  `remember_validator_hash` varchar(128) DEFAULT NULL,
  `remember_expiry` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password`, `role`, `is_suspended`, `otp_code`, `otp_expires_at`, `is_verified`, `gender`, `date_of_birth`, `phone`, `remember_selector`, `remember_validator_hash`, `remember_expiry`) VALUES
(1, 'admin', 'admin', 'admin', 'vonrevenmewe@gmail.com', '$2y$10$972J0mW3gQvrN/E7ap24R.I88xELUzJIssqvl63L5nTeK2PVpK7na', 'admin', 0, NULL, NULL, 0, NULL, NULL, NULL, 'c64d83ddc53b91fa7c', '$2y$10$Ly9y7vzkQ33NCUUlcn3Y8eL8cXZEYvUUxFjzuGbF4d7yCSrqR3zD2', 2025),
(2, 'cashier', 'cashier', 'cashier', '', '$2y$10$rNy7s8s90y4IlyRZJWIs6OBiI3gT/q9mDlbgc7wXSAch01lWaiDqa', 'admin', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'manager', 'manager', 'manager', '', '$2y$10$rNy7s8s90y4IlyRZJWIs6OBiI3gT/q9mDlbgc7wXSAch01lWaiDqa', 'admin', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'niga', 'black', 'black', '', '/q9mDlbgc7wXSAch01lWaiDqa$2y$10$rNy7s8s90y4IlyRZJWIs6OBiI3gT', '', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'pol', 'walker', 'polpol', '', '$2y$10$LoQ6MckTUB32FUmhmwmJ8uZrbKi7Ckc00GIucAn/bgRQcouR4v30K', '', 0, NULL, NULL, 1, 'male', '2004-10-02', NULL, NULL, NULL, NULL),
(22, 'bonbon', 'reven', 'vonreven15', '', '$2y$10$JgHwlni4NMXnIxGlPXvnOuRPpiyJzy11yeedncBt3cRHZ.GNT.0g6', '', 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'jessie', 'j', 'jesi', '', '$2y$10$yWCJLA7tPGl2Cr0RfhHfOe.eMWoc4z5IDiwTis0h23TTt7eUZOYZq', '', 0, NULL, NULL, 1, 'female', '2004-10-22', '09926462192', NULL, NULL, NULL),
(24, 'von', 'reven', 'vonvon', '', '$2y$10$beJHiuau6wfHJI9GFdsT.O/UbWL/amR9AsVTyCz6CtjZH9WoHD5Ny', '', 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'borje', 'jerbsz', 'jerbs', '', '$2y$10$mKxiiWdvefg65U5GJANQFOrXRDO8L.FQarHE8tdsUYQWEPL0Qns/6', '', 0, NULL, NULL, 1, 'other', NULL, NULL, NULL, NULL, NULL),
(27, 'charlie', 'floyd', 'charliekirk', '', '$2y$10$aoix2bgNEFAd28INPN7PuuHWWNxEMBKlpuORU/i3KZIRDGPV9zab6', '', 0, NULL, NULL, 1, 'other', '2025-09-17', NULL, NULL, NULL, 2025),
(28, 'Seth', 'Tumacay', 'sethadmin', '', '$2y$10$CF4xU7ODouwoe5eXYtArWur0n0/Cl94T8seiqtJZLVvzlkKoqJiCy', '', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 2025),
(33, 'VON REVEN', 'GALOS', 'qvrgalos01470', 'qvrgalos01@tip.edu.ph', '$2y$10$4aZ0V1FR3IV6.Rt2hbeCbOcWLaV/49YGxpkrv8CJWVF8RXbVH5ZyG', '', 0, NULL, NULL, 1, 'male', NULL, '09060212372', NULL, NULL, NULL),
(34, 'JESSIERHY MAE', 'CANO', 'qjmbcano622', 'qjmbcano@tip.edu.ph', '$2y$10$F.GxlWAd9w8LAEfLoDJx/.BJKuz/I1CTIyVd5N.7z4P3gnGFnbFSm', '', 0, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 'User', 'User', 'vongalos637360', 'vongalos637@gmail.com', '$2y$10$f9eV3PoMwNulwPsSsCF9XO8JAghbLSZvvGeoPI1y3BwxegOR.FT9O', '', 0, NULL, NULL, 1, 'male', '2003-11-19', '09060212372', NULL, NULL, NULL),
(36, 'Jessierhy Mae', 'Cano', 'jssrhy', 'jessierhycano@gmail.com', '$2y$10$C3Y94ERv/jXO0e/zReosFO7iTGMgUOR3uf7a5kvhZqoxq5A/bUFvW', '', 0, NULL, NULL, 1, 'female', '2004-10-22', '09926462192', 'e3fb30fb18c00618938dae176835a4cf', 'c5b2f59ecdd7f32f271847726e21c2b964b80118f5cb77a24d08ea347cb892d9', 2147483647),
(37, 'Jes', 'Cano', 'jesijesi', 'jessymharie07@gmail.com', '$2y$10$gmazyQXiofwMHjMbw07v3.G4mpCXHlN3qwsNQuLhyr.Zpq9cauXr.', '', 0, NULL, NULL, 1, 'female', '2025-09-10', '09926462192', NULL, NULL, 2147483647),
(38, 'Luis', 'Espino', 'itsmelowest129', 'itsmelowest@gmail.com', '$2y$10$YDLEkRb7EA07jAJMNjw1sOYxqSFeZJeR59X.5AWaq8gfiYdt/WYh.', '', 0, NULL, NULL, 1, NULL, NULL, NULL, '8352871bd447faacd75382b921945f51', '04cdca2592dc20bf2c7d1198cd017b0883b49f2e6eb9cdee2bdee0611f236611', 2147483647);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
