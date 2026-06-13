-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 09, 2026 at 02:34 PM
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
-- Database: `carplus`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `location`) VALUES
(2001, 'Desa Rejang', 'Desa Rejang, Setapak, Kuala Lumpur'),
(2002, 'Petaling Jaya', 'Petaling Jaya, Selangor'),
(2003, 'Kempas', 'Kempas, Johor Bahru, Johor');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `car_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `car_type` varchar(50) DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `availability` tinyint(1) DEFAULT 1,
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`car_id`, `name`, `car_type`, `price_per_day`, `description`, `image_url`, `availability`, `branch_id`) VALUES
(1001, 'Porsche 911', 'coupe', 600.00, 'Anyone who dreams of a Porsche usually has an image in their mind. The 911 has been the epitome of an exciting, powerful sports car with day-to-day usability for 60 years. Take a seat behind the wheel of the new 911 and become part of a unique community.', 'https://static0.carbuzzimages.com/wordpress/wp-content/uploads/2024/08/2025-porsche-911-turbo-50-4.jpg', 1, 2001),
(1002, 'Mercedes AMG GT', 'coupe', 1000.00, 'luxury car for rent', 'https://cdn.motor1.com/images/mgl/jl9Gmo/s1/mercedes-amg-gt-coupe-2023.jpg', 1, 2001),
(1003, 'LaFerrari', 'supercar', 3500.00, 'The LaFerrari (project name F150)[4] is a limited production mid-engine, mild hybrid sports car built by Italian automotive manufacturer Ferrari.[5] Its name means \"The Ferrari\" in Italian, as it is intended to be the definitive Ferrari.', 'https://cdn.wallpapersafari.com/84/77/t2YXSak.webp', 0, 2003),
(1004, 'Volkswagen Polo', 'hatchback', 200.00, 'The Volkswagen Polo is a supermini car produced by the German car manufacturer Volkswagen since 1975. It is sold in Europe and other markets worldwide in hatchback, saloon, and estate variants throughout its production run. As of 2018, six separate generations of the Polo had been produced, usually identified by a \"Series\" or \"Mark\" number', 'https://dhzc82x38ceu.cloudfront.net/Prod/Stock_Item__c/a1pTv00000Ft3PeIAJ/Image/IMG-20250312-WA1702.jpg', 1, 2003),
(1005, 'The Dodge SRT Tomahawk ', 'Concept Car', 90000.00, 'It features a 6.98-liter V-10 engine combined with a pneumatic powertrain, delivering between 1,007 hp (S version) and over 2,500 hp (X version', 'https://upload.wikimedia.org/wikipedia/en/3/38/SRT_Tomahawk_S.jpeg', 0, 2002),
(1006, 'Evolution 9', 'sedan', 600.00, 'JDM for rent', 'https://petrolpositive.at/wp-content/uploads/2022/08/JDM-Mitsubishi-EVO-9-GSR-Electric-Blue_3.jpg', 1, 2002);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `rental_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `rental_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `pickup_type` enum('pickup','delivery') DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `total_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `dropoff_date` date DEFAULT NULL,
  `dropoff_time` time DEFAULT NULL,
  `rental_days` int(11) DEFAULT NULL,
  `dropoff_branch_id` int(11) DEFAULT NULL,
  `rental_method` varchar(20) DEFAULT 'pickup',
  `delivery_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`rental_id`, `user_id`, `car_id`, `branch_id`, `start_date`, `end_date`, `pickup_type`, `status`, `total_price`, `created_at`, `pickup_date`, `pickup_time`, `dropoff_date`, `dropoff_time`, `rental_days`, `dropoff_branch_id`, `rental_method`, `delivery_address`) VALUES
(23456, 6, 1003, 2001, '2026-05-22', '2026-05-24', 'pickup', 'completed', 10000.00, '2026-05-22 11:21:01', NULL, NULL, NULL, NULL, NULL, NULL, 'pickup', NULL),
(23457, 10, 1002, 2001, NULL, NULL, NULL, '', 1210.00, '2026-05-26 07:40:44', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23458, 10, 1002, 2001, NULL, NULL, NULL, 'cancelled', 1210.00, '2026-05-26 07:40:52', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23459, 10, 1003, 2003, NULL, NULL, NULL, '', 4235.00, '2026-05-26 07:47:06', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23460, 10, 1003, 2003, NULL, NULL, NULL, '', 4235.00, '2026-05-26 07:47:52', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23461, 10, 1003, 2003, NULL, NULL, NULL, '', 4235.00, '2026-05-26 07:48:24', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23462, 10, 1003, 2003, NULL, NULL, NULL, '', 4235.00, '2026-05-26 07:53:16', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23463, 10, 1005, 2002, NULL, NULL, NULL, 'completed', 326700.00, '2026-05-26 08:14:11', '2026-05-26', '10:00:00', '2026-05-29', '00:00:00', 3, NULL, 'pickup', NULL),
(23464, 10, 1003, 2003, NULL, NULL, NULL, 'completed', 4235.00, '2026-05-27 01:05:24', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23465, 12346, 1001, 2001, NULL, NULL, NULL, 'completed', 726.00, '2026-06-06 13:19:19', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23466, 12346, 1004, 2003, NULL, NULL, NULL, 'completed', 242.00, '2026-06-08 01:03:23', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23467, 12346, 1003, 2003, NULL, NULL, NULL, 'completed', 4235.00, '2026-06-08 01:19:27', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', NULL),
(23468, 10, 1001, 2001, NULL, NULL, NULL, 'completed', 726.00, '2026-06-08 01:59:59', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', ''),
(23469, 10, 1001, 2001, NULL, NULL, NULL, 'completed', 726.00, '2026-06-08 02:20:40', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', ''),
(23470, 10, 1002, 2001, NULL, NULL, NULL, 'completed', 1210.00, '2026-06-08 02:54:13', '0000-00-00', '00:00:00', '0000-00-00', '00:00:00', 1, NULL, 'pickup', ''),
(23471, 10, 1001, 2001, NULL, NULL, NULL, 'completed', 726.00, '2026-06-08 03:32:19', '2026-06-09', '23:11:00', '2026-06-10', '23:11:00', 1, NULL, 'pickup', ''),
(23472, 12346, 1001, 2001, NULL, NULL, NULL, 'completed', 1452.00, '2026-06-08 17:33:12', '2026-06-10', '13:33:00', '2026-06-12', '15:32:00', 2, NULL, 'delivery', 'No 37, Jalan Nusaputra 1/2D, Bandar Nusa'),
(23473, 12347, 1003, 2003, NULL, NULL, NULL, 'completed', 4235.00, '2026-06-09 00:16:58', '2026-06-16', '08:16:00', '2026-06-17', '08:16:00', 1, NULL, 'pickup', ''),
(23474, 12347, 1002, 2001, NULL, NULL, NULL, 'cancelled', 3630.00, '2026-06-09 00:53:03', '2026-06-23', '08:52:00', '2026-06-26', '08:52:00', 3, NULL, 'pickup', ''),
(23475, 12347, 1002, 2001, NULL, NULL, NULL, 'cancelled', 2420.00, '2026-06-09 01:08:20', '2026-06-09', '09:08:00', '2026-06-11', '09:08:00', 2, NULL, 'pickup', ''),
(23476, 12345, 1002, 2001, NULL, NULL, NULL, 'cancelled', 2420.00, '2026-06-09 03:50:41', '2026-06-09', '11:50:00', '2026-06-11', '11:50:00', 2, NULL, 'pickup', '');

-- --------------------------------------------------------

--
-- Table structure for table `rental_status_history`
--

CREATE TABLE `rental_status_history` (
  `history_id` int(11) NOT NULL,
  `rental_id` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `position` varchar(100) DEFAULT 'Staff',
  `hire_date` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `user_id`, `branch_id`, `position`, `hire_date`, `created_at`) VALUES
(3002, 12345, 2001, 'Staff', '2026-06-09', '2026-06-09 09:15:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin','staff') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT 'default-avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `role`, `created_at`, `profile_image`) VALUES
(1, 'Umar Jaaztar', 'alex@example.com', '$2y$10$cRS8pFdR/URaaw5NzyaMV.k6scK7B1q9E569Yj.GC8idKORfOe.Bm', NULL, 'customer', '2026-05-05 13:45:42', 'default-avatar.png'),
(2, 'aimanhamizan', 'hello@gmail.com', '$2y$10$xhtulvK40Y9QSe9pPyn.w.9XT.b.Eq.6XeuH7NRZm42nn580bj.sK', '1172231681', 'customer', '2026-05-05 14:01:14', 'default-avatar.png'),
(3, 'adam', 'adammmm@gmail.com', '$2y$10$KYwNlWtQnaPhUmg7RbzRz.zdE/d8u4jBa6jydG2L4fwl9qc21xnyq', '01362672', 'customer', '2026-05-05 14:14:32', 'default-avatar.png'),
(4, 'icad', 'aleicad@example.com', '$2y$10$3xQBXxdNuP4sFpX8fQngSepKSxm5M9dyfPSriKfSWBc0CrL2iIIAG', '1234567', 'customer', '2026-05-07 02:24:03', 'default-avatar.png'),
(5, 'Jaaztar', 'jaaztar@gmail.com', '$2y$10$GZF.rtbqxoy9GWMjKegfU.Xd8D07uUfDxoQluVXVVK6xSEDRVPoim', '123456789', 'customer', '2026-05-07 02:33:32', 'default-avatar.png'),
(6, 'Aiman Hamizan', 'aimanhamizan047@gmail.com', '$2y$10$gSVRWbQbIs7xX0AwzigJcOfmFAd49k8OdPlGMqEg/2Ml/hdQeM5Ba', '0115678987', 'customer', '2026-05-10 10:55:51', '1778726415_Screenshot 2026-01-19 123946.png'),
(7, 'Evelyn', 'evelyn@example.com', '$2y$10$IiY87G4k7ujSXxUF4u/RTeNvBYSmqyHIrRpnEMY8IFlnlLC6TZd/S', '123456789', 'customer', '2026-05-11 04:22:25', 'default-avatar.png'),
(8, 'MIDR', 'icad@example.com', '$2y$10$c81omwj6mIyNwLBk4987q.OLIpVsqwAl26LZQCPId/qMWJG36v8cK', '0196133140', 'customer', '2026-05-12 09:31:39', '1778582582_Screenshot 2026-01-07 120438.png'),
(9, 'Fahim ', 'fahim@gmail.com', '$2y$10$l0PHIqs41xTF.YBzrj7Teea8luVevBWZSI4ZnHlH6iBHECPrFzpB6', '0182343122', 'customer', '2026-05-12 13:01:07', '1778591597_Screenshot 2026-03-03 083245.png'),
(10, 'Fariesh ', 'farieshbull@gmail.com', '$2y$10$Mnb8.EkAwHvlgX5obkT/VeH3TFbYgZXeccgMKg2jNbDfK1iKtT6BC', '018976542', 'customer', '2026-05-13 00:12:55', '1778631902_Screenshot 2026-01-07 120438.png'),
(12, 'Umar Jaaztar', 'umar@example.com', '$2y$10$sk7EDo9DqszQJX6FeifgIu0yRZKqCHcwWkGqhsKffkxmxBOtA6CCe', '0196133140', 'customer', '2026-05-13 01:03:29', 'default-avatar.png'),
(13, 'ali', 'ali@gmail.com', '$2y$10$VPsKVekTAK7kIBP6FWAEJevIw4iCBA5rL4rt1Bxrc9LQ29DAAcOZS', '0196133140', 'customer', '2026-05-13 01:05:57', 'default-avatar.png'),
(14, 'low min jing 123', 'lowminjing@gmail.com', '$2y$10$k/rYMu.8fenxdBVu0U8HuerDMGPlcbP4A2Ry/H9LvEQb2H.QM/r0S', '011826386', 'customer', '2026-05-14 04:05:58', '1778732310_Screenshot 2026-01-26 122124.png'),
(12345, 'Fahim', 'fahim@carplus.com', '$2y$10$kybqJxNS9niTMoPgWSWiiOzVbnbj4PCxbgPR9bebEzq2kaVzKm4O.', '0112345', 'staff', '2026-05-22 10:37:01', '1780979383_The Libertines, Babyshambles, Pete Doherty, Peter Doherty, Carl Barat, Up the Bracket.jfif'),
(12346, 'testinghello', 'hellotesting@gmail.com', '$2y$10$TAMASihnSQwN.7T6335CEOQUha8qWqdZ/wvONqmlehGGjFsxAZjKy', '+60196656697', 'customer', '2026-06-06 11:40:22', '1780985429_The Libertines, Babyshambles, Pete Doherty, Peter Doherty, Carl Barat, Up the Bracket.jfif'),
(12347, 'Adam Shafiy', 'uj@gmail.com', '$2y$10$gJJd/5/aiLs2iaShLx7YcO.O.gn9HJlxkKaTxef0dW003q86bXg5S', '01234567', 'customer', '2026-06-09 00:15:28', 'default-avatar.png'),
(12348, 'aliabu', 'aliabu@carplus.com', '$2y$10$e9xuybvdY5pT0/E6AGinmOaYEHmRZsLCx5uQxnJk39jGL1XDdVUvO', '0189992999', 'admin', '2026-06-09 04:47:07', 'default-avatar.png'),
(12350, 'adam shafiy', 'adamshafiy3450@gmail.com', '$2y$10$UBVPFdWVo93RlGiJUc6BMekJd.JswtXWFisfl25CK5yE/8GAlJSV.', '01139111952', 'customer', '2026-06-09 10:09:14', 'default-avatar.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`car_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `rental_id` (`rental_id`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `rental_status_history`
--
ALTER TABLE `rental_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `rental_id` (`rental_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `fk_staff_user` (`user_id`),
  ADD KEY `fk_staff_branch` (`branch_id`);

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
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2004;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `car_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1007;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23477;

--
-- AUTO_INCREMENT for table `rental_status_history`
--
ALTER TABLE `rental_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3003;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12352;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`);

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`),
  ADD CONSTRAINT `rentals_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`);

--
-- Constraints for table `rental_status_history`
--
ALTER TABLE `rental_status_history`
  ADD CONSTRAINT `rental_status_history_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
