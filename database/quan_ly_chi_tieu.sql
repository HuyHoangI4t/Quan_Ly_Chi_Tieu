-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 01:00 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quan_ly_chi_tieu`
--
CREATE DATABASE IF NOT EXISTS `quan_ly_chi_tieu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `quan_ly_chi_tieu`;

-- --------------------------------------------------------

--
-- Drop existing tables to prevent errors on re-import
--
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `goals`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`) VALUES
(1, 'testuser', 'test@example.com', '$2y$10$E.qJ4P0b1Q8cW3hI2k/fS.e.iPj5wX3zJ6A7/gH3nF4C2G5b6d7E8', 'Người dùng thử');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--
INSERT INTO `categories` (`id`, `user_id`, `name`, `type`, `is_default`) VALUES
(1, NULL, 'Thực phẩm', 'expense', 1),
(2, NULL, 'Giao thông', 'expense', 1),
(3, NULL, 'Nhà ở', 'expense', 1),
(4, NULL, 'Tiện ích', 'expense', 1),
(5, NULL, 'Giải trí', 'expense', 1),
(6, NULL, 'Sức khỏe', 'expense', 1),
(7, NULL, 'Mua sắm', 'expense', 1),
(8, NULL, 'Chi tiêu khác', 'expense', 1),
(9, NULL, 'Tiền lương', 'income', 1),
(10, NULL, 'Thưởng', 'income', 1),
(11, NULL, 'Quà tặng', 'income', 1),
(12, NULL, 'Thu nhập khác', 'income', 1);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--
INSERT INTO `transactions` (`id`, `user_id`, `category_id`, `amount`, `description`, `transaction_date`) VALUES
(1, 1, 9, 2500.00, 'Lương tháng', '2025-11-01'),
(2, 1, 3, -900.00, 'Thanh toán tiền thuê nhà', '2025-11-02'),
(3, 1, 1, -55.20, 'Mua thực phẩm hàng tuần', '2025-11-05'),
(4, 1, 2, -25.00, 'Tiền xăng xe', '2025-11-07'),
(5, 1, 5, -120.00, 'Vé xem hòa nhạc', '2025-11-10'),
(6, 1, 1, -15.75, 'Ăn trưa với đồng nghiệp', '2025-11-12'),
(7, 1, 4, -85.50, 'Hóa đơn tiền điện', '2025-11-15');

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--
CREATE TABLE `goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `target_amount` decimal(15,2) NOT NULL,
  `current_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `target_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goals`
--
INSERT INTO `goals` (`id`, `user_id`, `name`, `target_amount`, `current_amount`, `target_date`) VALUES
(1, 1, 'Mua máy tính xách tay mới', 1500.00, 350.00, '2026-06-30'),
(2, 1, 'Du lịch Nhật Bản', 4000.00, 800.00, '2026-12-31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;