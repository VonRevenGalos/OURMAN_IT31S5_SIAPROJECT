-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 27, 2025 at 04:11 AM
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
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail1` varchar(255) DEFAULT NULL,
  `thumbnail2` varchar(255) DEFAULT NULL,
  `thumbnail3` varchar(255) DEFAULT NULL,
  `color` enum('Black','Blue','Brown','Green','Gray','Multi-Colour','Orange','Pink','Purple','Red','White','Yellow') DEFAULT 'Black',
  `height` enum('low top','mid top','high top') DEFAULT 'mid top',
  `width` enum('regular','wide','extra wide') DEFAULT 'regular',
  `brand` varchar(100) DEFAULT 'Generic',
  `collection` varchar(100) DEFAULT 'Standard',
  `date_added` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `title`, `price`, `stock`, `image`, `category`, `description`, `thumbnail1`, `thumbnail2`, `thumbnail3`, `color`, `height`, `width`, `brand`, `collection`, `date_added`) VALUES
(1, 'Aero Strides', 1999.00, 3, 'assets/img/men/sneakers/aerostride.webp', 'sneakers', 'AeroStrides offers superior comfort and support for all-day wear, perfect for casual outings.', 'assets/img/men/sneakers/aerostride.webp', 'assets/img/men/sneakers/aerostride1.webp', 'assets/img/men/sneakers/aerostride2.webp', 'Multi-Colour', 'high top', 'wide', 'XRizz', 'Air Rizz', '2025-09-18 16:11:18'),
(2, 'Momentum', 1599.00, 12, 'assets/img/men/sneakers/momentum.webp', 'sneakers', 'Momentum provide a lightweight design with excellent ventilation for ultimate comfort during your daily activities.', 'assets/img/men/sneakers/momentum.webp', 'assets/img/men/sneakers/momentum1.webp', 'assets/img/men/sneakers/momentum2.webp', 'Black', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(3, 'PowerMove Ultra', 1899.00, 7, 'assets/img/men/sneakers/powermoveultra.webp\n', 'sneakers', 'PowerMove shoes blend style and functionality for those who demand performance without sacrificing looks.', 'assets/img/men/sneakers/powermoveultra.webp\n', 'assets/img/men/sneakers/powermoveultra1.webp\n', 'assets/img/men/sneakers/powermoveultra2.webp\n', 'Brown', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(4, 'Sneak Force', 999.00, 18, 'assets/img/men/sneakers/sneakforce.webp\n', 'sneakers', 'SneakForce shoes are designed with plush cushioning and a soft upper for a luxurious feel on your feet.', 'assets/img/men/sneakers/sneakforce.webp\n', 'assets/img/men/sneakers/sneakforce1.webp\n', 'assets/img/men/sneakers/sneakforce2.webp\n', 'Brown', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(5, 'Stride Master', 2999.00, 21, 'assets/img/men/sneakers/stridemaster.webp\n', 'sneakers', 'Stride Master shoes offer a flexible design that adapts to your foot movement, making them ideal for active lifestyles.', 'assets/img/men/sneakers/stridemaster.webp\n', 'assets/img/men/sneakers/stridemaster1.webp\n', 'assets/img/men/sneakers/stridemaster2.webp\n', 'Multi-Colour', 'high top', 'wide', 'XRizz', 'Air Rizz', '2025-09-18 16:11:18'),
(6, 'Endurance Pro X', 4599.00, 6, 'assets/img/men/running/endurancepro-x.webp\n', 'running', 'Endurance provide a stylish yet comfortable option for everyday use, with a focus on breathability and durability.', 'assets/img/men/running/endurancepro-x.webp', 'assets/img/men/running/endurancepro-x1.webp', 'assets/img/men/running/endurancepro-x2.webp', 'Black', 'mid top', 'wide', 'Generic', 'Air Rizz', '2025-09-18 16:11:18'),
(7, 'RunTech', 5999.00, 10, 'assets/img/men/running/runtech.webp\n', 'running', 'Runtech running shoes are engineered for speed, providing lightweight support that helps you go the distance.', 'assets/img/men/running/runtech.webp\n', 'assets/img/men/running/runtech1.webp\n', 'assets/img/men/running/runtech2.webp\n', 'Brown', 'mid top', 'regular', 'Generic', 'Air Rizz', '2025-09-18 16:11:18'),
(8, 'Speed Flex', 1599.00, 7, 'assets/img/men/running/speedflex.webp\n', 'running', 'SpeedFlex shoes are built for runners who want a responsive feel and excellent traction on any surface.', 'assets/img/men/running/speedflex.webp\n', 'assets/img/men/running/speedflex1.webp\n', 'assets/img/men/running/speedflex2.webp\n', 'White', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(9, 'Swift Step Max', 3999.00, 17, 'assets/img/men/running/swiftstepmax.webp', 'running', 'SwiftStep offers a unique design with advanced cushioning technology, ensuring a smooth and comfortable run.', 'assets/img/men/running/swiftstepmax.webp', 'assets/img/men/running/swiftstepmax1.webp', 'assets/img/men/running/swiftstepmax2.webp', 'Green', 'mid top', 'wide', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(10, 'Velocity Runner Pro', 4899.00, 20, 'assets/img/men/running/velocityrunnerpro.webp\n', 'running', 'Velocity shoes are perfect for competitive runners seeking to improve their performance.', 'assets/img/men/running/velocityrunnerpro.webp\n', 'assets/img/men/running/velocityrunnerpro1.webp\n', 'assets/img/men/running/velocityrunnerpro2.webp\n', 'Black', 'mid top', 'wide', 'XRizz', 'Air Rizz', '2025-09-18 16:11:18'),
(11, 'PowerStride\r\n', 5299.00, 3, 'assets/img/men/athletics/powerstride.webp\n', 'athletics', 'Powerstride shoes are designed for endurance athletes, combining durability with exceptional comfort for long runs.', 'assets/img/men/athletics/powerstride.webp\n', 'assets/img/men/athletics/powerstride1.webp\n', 'assets/img/men/athletics/powerstride2.webp\n', 'Red', 'high top', 'extra wide', 'XRizz', 'Air Rizz', '2025-09-18 16:11:18'),
(12, 'Trackzone Ultra', 6999.00, 10, 'assets/img/men/athletics/trackzoneultra.webp\n', 'athletics', 'TrackZone shoes provide a snug fit and responsive cushioning, making them perfect for sprinters and fast-paced workouts.', 'assets/img/men/athletics/trackzoneultra.webp', 'assets/img/men/athletics/trackzoneultra1.webp', 'assets/img/men/athletics/trackzoneultra2.webp', 'Red', 'high top', 'wide', 'Generic', 'Air Rizz', '2025-09-18 16:11:18'),
(13, 'Athlo Xtreme', 2599.00, 13, 'assets/img/men/athletics/athloxtreme.webp', 'athletics', 'AthloXtreme shoes are engineered for athletes seeking performance and style, offering exceptional grip and support.', 'assets/img/men/athletics/athloxtreme.webp', 'assets/img/men/athletics/athloxtreme1.webp', 'assets/img/men/athletics/athloxtreme2.webp', 'Black', 'mid top', 'wide', 'XRizz', 'Standard', '2025-09-18 16:11:18'),
(14, 'Elite Move', 7599.00, 8, 'assets/img/men/athletics/elitemove.webp\n\n', 'athletics', 'EliteMove combines advanced technology with modern design, perfect for serious athletes and fitness enthusiasts.', 'assets/img/men/athletics/elitemove.webp\n', 'assets/img/men/athletics/elitemove1.webp\n', 'assets/img/men/athletics/elitemove2.webp\n', 'Blue', 'low top', 'regular', 'Generic Rizz', 'Standard', '2025-09-18 16:11:18'),
(15, 'MotionFlex Max', 9999.00, 9, 'assets/img/men/athletics/motionflexmax.webp\n', 'athletics', 'MotionFlex Max shoes feature innovative cushioning and stability, designed to enhance your athletic performance and comfort.', 'assets/img/men/athletics/motionflexmax.webp\n', 'assets/img/men/athletics/motionflexmax1.webp\n', 'assets/img/men/athletics/motionflexmax2.webp\n', 'Black', 'high top', 'regular', 'XRizz', 'Air Rizz', '2025-09-18 16:11:18'),
(16, 'Athletica X', 2499.00, 4, 'assets/img/women/womenathletics/athleticax.webp', 'womenathletics', 'Athletica are designed for active persons offering lightweight comfort and vibrant designs.', 'assets/img/women/womenathletics/athleticax.webp', 'assets/img/women/womenathletics/athleticax1.webp', 'assets/img/women/womenathletics/athleticax2.webp', 'Pink', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(17, 'Core Motion', 1299.00, 11, 'assets/img/women/womenathletics/coremotion.webp', 'womenathletics', 'CoreMotion provide a snug fit and are perfect for athletic players.', 'assets/img/women/womenathletics/coremotion.webp', 'assets/img/women/womenathletics/coremotion1.webp', 'assets/img/women/womenathletics/coremotion2.webp', 'Green', 'mid top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(18, 'Flex Fusion', 2299.00, 9, 'assets/img/women/womenathletics/flexfusion.webp', 'womenathletics', 'FlexFusion feature rugged soles for adventurous who love outdoor activities.', 'assets/img/women/womenathletics/flexfusion.webp', 'assets/img/women/womenathletics/flexfusion1.webp', 'assets/img/women/womenathletics/flexfusion2.webp', 'White', 'mid top', 'wide', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(19, 'Maxi Step', 2699.00, 17, 'assets/img/women/womenathletics/maxistep.webp', 'womenathletics', 'Maxi step offer speed and agility on the go.', 'assets/img/women/womenathletics/maxistep.webp', 'assets/img/women/womenathletics/maxistep1.webp', 'assets/img/women/womenathletics/maxistep2.webp', 'Purple', 'low top', 'wide', 'Generic', 'Air Rizz', '2025-09-18 16:11:18'),
(20, 'Pulse Flex', 3699.00, 20, 'assets/img/women/womenathletics/pulseflex.webp', 'womenathletics', 'Maxi step offer speed and agility on the go.', 'assets/img/women/womenathletics/pulseflex.webp', 'assets/img/women/womenathletics/pulseflex1.webp', 'assets/img/women/womenathletics/pulseflex2.webp', 'Red', 'high top', 'wide', 'Generic', 'Air Rizz', '2025-09-18 16:11:18'),
(21, 'Enduro Dash', 1999.00, 17, 'assets/img/women/womenrunning/endurodash.webp', 'womenrunning', 'Enduro Dash offer unmatched speed and agility  on the go, with bold colorways.', 'assets/img/women/womenrunning/endurodash.webp', 'assets/img/women/womenrunning/endurodash1.webp', 'assets/img/women/womenrunning/endurodash2.webp', 'Brown', 'mid top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(22, 'PeakRunner', 3999.00, 30, 'assets/img/women/womenrunning/peakrunner.webp', 'womenrunning', 'Peak  offer unmatched speed and agility  on the go, with bold colorways.', 'assets/img/women/womenrunning/peakrunner.webp', 'assets/img/women/womenrunning/peakrunner1.webp', 'assets/img/women/womenrunning/peakrunner2.webp', 'Brown', 'mid top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(23, 'Run Wave', 2199.00, 30, 'assets/img/women/womenrunning/runwave.webp', 'womenrunning', 'Run Wave ensures smooth motion with a natural flow for daily runners.', 'assets/img/women/womenrunning/runwave.webp', 'assets/img/women/womenrunning/runwave1.webp', 'assets/img/women/womenrunning/runwave2.webp', 'Black', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 16:11:18'),
(24, 'Velocity Run', 1599.00, 19, 'assets/img/women/womenrunning/velocityrun.webp', 'womenrunning', 'Velocity Run offers lightweight speed with responsive cushioning for energetic runs.', 'assets/img/women/womenrunning/velocityrun.webp', 'assets/img/women/womenrunning/velocityrun1.webp', 'assets/img/women/womenrunning/velocityrun2.webp', 'Pink', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 17:58:45'),
(25, 'Viva Sprint', 1699.00, 31, 'assets/img/women/womenrunning/vivasprint.webp', 'womenrunning', 'VivaSprint provides quick responsiveness with sleek design for speed-focused runners.', 'assets/img/women/womenrunning/vivasprint.webp', 'assets/img/women/womenrunning/vivasprint1.webp', 'assets/img/women/womenrunning/vivasprint2.webp', 'Red', 'mid top', 'wide', 'Generic', 'Air Rizz', '2025-09-18 18:04:41'),
(26, 'Active Luxe', 7999.00, 20, 'assets/img/women/womensneakers/activeluxe.webp', 'womensneakers', 'Active Luxe provides sporty elegance with plush cushioning for versatile wear.', 'assets/img/women/womensneakers/activeluxe.webp', 'assets/img/women/womensneakers/activeluxe1.webp', 'assets/img/women/womensneakers/activeluxe2.webp', 'Multi-Colour', 'high top', 'extra wide', 'XRizz', 'Air Rizz', '2025-09-18 18:04:41'),
(27, 'FlexiGlide', 1599.00, 9, 'assets/img/women/womensneakers/flexiglide.webp', 'womensneakers', 'Flexi Glide offers flexible comfort with smooth transitions for casual movement.', 'assets/img/women/womensneakers/flexiglide.webp', 'assets/img/women/womensneakers/flexiglide1.webp', 'assets/img/women/womensneakers/flexiglide2.webp', 'Gray', 'mid top', 'wide', 'Generic', 'Standard', '2025-09-18 18:10:12'),
(28, 'Swift Step', 999.00, 8, 'assets/img/women/womensneakers/swiftstep.webp', 'womensneakers', 'Swift Step delivers sleek comfort with lightweight cushioning for everyday wear.', 'assets/img/women/womensneakers/swiftstep.webp', 'assets/img/women/womensneakers/swiftstep1.webp', 'assets/img/women/womensneakers/swiftstep2.webp', 'Pink', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 18:04:41'),
(29, 'Urban Flow', 1999.00, 10, 'assets/img/women/womensneakers/urbanflow.webp', 'womensneakers', 'Urban Flow blends modern street style with breathable comfort for city living.', 'assets/img/women/womensneakers/urbanflow.webp', 'assets/img/women/womensneakers/urbanflow1.webp', 'assets/img/women/womensneakers/urbanflow2.webp', 'Pink', 'mid top', 'wide', 'Generic', 'Air Rizz', '2025-09-18 18:04:41'),
(30, 'Luna Stride', 8999.00, 19, 'assets/img/women/womensneakers/lunastride.webp', 'womensneakers', 'Luna Stride combines stylish design with soft support for all-day ease.', 'assets/img/women/womensneakers/lunastride.webp', 'assets/img/women/womensneakers/lunastride1.webp', 'assets/img/women/womensneakers/lunastride2.webp', 'Purple', 'high top', 'wide', 'XRizz', 'Air Rizz', '2025-09-18 18:04:41'),
(31, 'Dash', 8999.00, 21, 'assets/img/kids/kidsathletics/dash.webp', 'kidsathletics', 'Dash offers simple, sporty comfort designed for fast-moving kids.', 'assets/img/kids/kidsathletics/dash.webp', 'assets/img/kids/kidsathletics/dash1.webp', 'assets/img/kids/kidsathletics/dash2.webp', 'Multi-Colour', 'high top', 'wide', 'XRizz', 'Air Rizz', '2025-09-18 18:04:41'),
(32, 'Peak Tots', 1599.00, 10, 'assets/img/kids/kidsathletics/peaktots.webp', 'kidsathletics', 'Peak Tots support little athletes with comfort and stability in every move.', 'assets/img/kids/kidsathletics/peaktots.webp', 'assets/img/kids/kidsathletics/peaktots1.webp', 'assets/img/kids/kidsathletics/peaktots2.webp', 'Black', 'mid top', 'wide', 'Generic', 'Standard', '2025-09-18 18:18:04'),
(33, 'PowerPaws', 2999.00, 10, 'assets/img/kids/kidsathletics/powerpaws.webp', 'kidsathletics', 'PowerPaws provide tough durability with playful energy for active kids.', 'assets/img/kids/kidsathletics/powerpaws.webp', 'assets/img/kids/kidsathletics/powerpaws1.webp', 'assets/img/kids/kidsathletics/powerpaws2.webp', 'Gray', 'mid top', 'regular', 'Generic', 'Air Rizz', '2025-09-18 18:18:04'),
(34, 'Vibe Trek', 3999.00, 10, 'assets/img/kids/kidsathletics/vibetrek.webp', 'kidsathletics', 'Vibe Trek combines adventure-ready style with cushioned comfort for daily play.', 'assets/img/kids/kidsathletics/vibetrek.webp', 'assets/img/kids/kidsathletics/vibetrek1.webp', 'assets/img/kids/kidsathletics/vibetrek2.webp', 'Black', 'mid top', 'wide', 'XRizz', 'Air Rizz', '2025-09-18 18:18:04'),
(35, 'Vibrant Velocity', 4599.00, 10, 'assets/img/kids/kidsathletics/vibrantvelocity.webp', 'kidsathletics', 'Vibrant Velocity delivers bright style with lightweight performance for growing feet.', 'assets/img/kids/kidsathletics/vibrantvelocity.webp', 'assets/img/kids/kidsathletics/vibrantvelocity2.webp', 'assets/img/kids/kidsathletics/vibrantvelocity1.webp', 'Blue', 'low top', 'extra wide', 'Generic', 'Standard', '2025-09-18 18:21:53'),
(36, 'Fast Feet', 1999.00, 10, 'assets/img/kids/kidsneakers/fastfeet.webp', 'kidsneakers', 'Fast Feet are built for kids who love to run, offering comfort and grip.', 'assets/img/kids/kidsneakers/fastfeet.webp', 'assets/img/kids/kidsneakers/fastfeet1.webp', 'assets/img/kids/kidsneakers/fastfeet2.webp', 'Black', 'low top', 'regular', 'XRizz', 'Standard', '2025-09-18 18:21:53'),
(37, 'Jump Jacks', 2999.00, 10, 'assets/img/kids/kidsneakers/jumpjacks.webp', 'kidsneakers', 'Jump Jacks add playful bounce and all-day comfort to every step.', 'assets/img/kids/kidsneakers/jumpjacks.webp', 'assets/img/kids/kidsneakers/jumpjacks1.webp', 'assets/img/kids/kidsneakers/jumpjacks2.webp', 'Blue', 'low top', 'wide', 'Generic', 'Standard', '2025-09-18 18:26:04'),
(38, 'PlayKicks', 1999.00, 10, 'assets/img/kids/kidsneakers/playkicks.webp', 'kidsneakers', 'PlayKicks deliver durability and comfort perfect for school and playtime.', 'assets/img/kids/kidsneakers/playkicks.webp', 'assets/img/kids/kidsneakers/playkicks1.webp', 'assets/img/kids/kidsneakers/playkicks2.webp', 'Pink', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 18:26:04'),
(39, 'Vivid Vibe', 999.00, 8, 'assets/img/kids/kidsneakers/vividvibe.webp', 'kidsneakers', 'Vivid Vibe adds bold color and energy with every playful step.', 'assets/img/kids/kidsneakers/vividvibe.webp', 'assets/img/kids/kidsneakers/vividvibe1.webp', 'assets/img/kids/kidsneakers/vividvibe2.webp', 'White', 'low top', 'regular', 'Generic', 'Air Rizz', '2025-09-18 18:27:24'),
(40, 'Zippy Sneaks', 999.00, 10, 'assets/img/kids/kidsneakers/zippysneaks.webp', 'kidsneakers', 'Zippy Sneaks provide speedy style with lightweight construction for active kids.', 'assets/img/kids/kidsneakers/zippysneaks.webp', 'assets/img/kids/kidsneakers/zippysneaks1.webp', 'assets/img/kids/kidsneakers/zippysneaks2.webp', 'White', 'low top', 'regular', 'Generic', 'Air Rizz', '2025-09-18 18:27:24'),
(41, 'Joy Walk', 599.00, 9, 'assets/img/kids/kidslipon/joywalks.webp', 'kidslipon', 'Joy Walk provides playful comfort with an easy slip-on design for everyday use.', 'assets/img/kids/kidslipon/joywalks.webp', 'assets/img/kids/kidslipon/joywalks1.webp', 'assets/img/kids/kidslipon/joywalks2.webp', 'Multi-Colour', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 18:27:24'),
(42, 'Play Ease', 899.00, 10, 'assets/img/kids/kidslipon/playease.webp', 'kidslipon', 'Play Ease offers hassle-free slip-on style with cushioned comfort for active kids.', 'assets/img/kids/kidslipon/playease.webp', 'assets/img/kids/kidslipon/playease1.webp', 'assets/img/kids/kidslipon/playease2.webp', 'Multi-Colour', 'low top', 'regular', 'Generic', 'Standard', '2025-09-18 18:27:24'),
(43, 'SlipSpark', 899.00, 10, 'assets/img/kids/kidslipon/slipsparks.webp', 'kidslipon', 'SlipSpark brings energetic style with quick-wear convenience for busy kids.', 'assets/img/kids/kidslipon/slipsparks.webp', 'assets/img/kids/kidslipon/slipsparks1.webp', 'assets/img/kids/kidslipon/slipsparks2.webp', 'Pink', 'mid top', 'wide', 'Generic', 'Air Rizz', '2025-09-18 18:27:24'),
(44, 'Snap Slip', 899.00, 9, 'assets/img/kids/kidslipon/snapslip.webp', 'kidslipon', 'Snap Slip makes it easy for kids to enjoy quick wear with a snug, comfy fit.', 'assets/img/kids/kidslipon/snapslip.webp', 'assets/img/kids/kidslipon/snapslip1.webp', 'assets/img/kids/kidslipon/snapslip2.webp', 'Multi-Colour', 'mid top', 'extra wide', 'Generic', 'Standard', '2025-09-18 18:32:36'),
(45, 'Zoom Tots', 1999.00, 0, 'assets/img/kids/kidslipon/zoomtots.webp', 'kidslipon', 'Zoom Tots deliver fun speed and lightweight comfort for little adventurers.', 'assets/img/kids/kidslipon/zoomtots.webp', 'assets/img/kids/kidslipon/zoomtots1.webp', 'assets/img/kids/kidslipon/zoomtots2.webp', 'Black', 'mid top', 'wide', 'XRizz', 'Air Rizz', '2025-09-18 18:32:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
