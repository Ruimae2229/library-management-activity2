-- XAMPP-Lite
-- version 8.5.5
-- https://xampplite.sf.net/
--
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 06:42 AM
-- Server version: 11.4.10-MariaDB-log
-- PHP Version: 8.5.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(150) NOT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `available` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `isbn`, `category`, `quantity`, `available`, `created_at`) VALUES
(2, 'maemae', 'homer', NULL, 'horror', 13, 13, '2026-11-08 07:27:12'),
(3, 'lale', 'ruimae', '12333', 'fairytale', 15, 15, '2026-11-08 07:27:58'),
(4, 'LOUISE', 'queensley', '22222', 'horror', 10, 9, '2026-11-08 07:46:37');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_records`
--

CREATE TABLE `borrow_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned') DEFAULT 'borrowed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrow_records`
--

INSERT INTO `borrow_records` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `created_at`) VALUES
(1, 3, 3, '2026-11-08', '2026-11-15', '2026-11-08', 'returned', '2026-11-08 11:55:17'),
(2, 3, 4, '2026-11-08', '2026-11-15', '2026-11-08', 'returned', '2026-11-08 12:01:21'),
(3, 5, 3, '2026-11-12', '2026-11-19', NULL, 'borrowed', '2026-11-11 23:38:12'),
(4, 5, 4, '2026-11-12', '2026-11-19', NULL, 'borrowed', '2026-11-12 01:29:51'),
(5, 1, 2, '2026-11-12', '2026-11-19', '2026-11-12', 'returned', '2026-11-12 02:10:11'),
(6, 1, 2, '2026-11-12', '2026-11-19', '2026-11-12', 'returned', '2026-11-12 02:15:32'),
(7, 1, 2, '2026-11-12', '2026-11-19', '2026-11-12', 'returned', '2026-11-12 02:21:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `role` enum('admin','member') DEFAULT 'member',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `status`, `role`, `created_at`) VALUES
(1, 'rui mae baltazar', 'ruimaebaltazar@gmail.com', '$2y$12$ivB0BkA09SS.NlatEIGUv.GobI9DNPle6UI.93rhUO4XveL29OB3u', 'active', 'admin', '2026-11-08 06:17:41'),
(3, 'rui mae baltazar', 'lala@gmail.com', '$2y$12$JoqpHfnWEeBZvjJ5uUSZRuJcgzLLFw4zyCOk8eiaSu3NuQzXwtcFa', 'disabled', 'member', '2026-11-08 07:23:48'),
(4, 'princess', 'princess@gmail.com', '$2y$12$HxqRUIVDiUIz2U40K3.Bh.ZS3NIK7mWs0k8/KGsXzoRb3zeO/3y0.', 'active', 'member', '2026-11-08 07:49:46'),
(5, 'rui mae baltazar', 'mae@gmail.com', '$2y$12$EC8XDBUqs.K/OSKKjXPH1ed6Hy51WLHrK1MEqVss8mVE8PAtZFTjG', 'active', 'member', '2026-11-11 23:37:45'),
(6, 'stacey farrah baltazar', 'stacey@gmail.com', '$2y$12$XfoVLBR6HNMG.t8m46qH/eTyu5Leg5cjpZegv4fDlf3vc2RydXO..', 'active', 'member', '2026-11-12 04:28:54'),
(7, 'ian', 'ian@gmail.com', '$2y$12$INpWARfS953A7F8imD4nYuudVgz/WY3EOsTHIK6wjxoFwfKZnNbDK', 'active', 'member', '2026-11-12 04:35:40'),
(8, 'a', 'a@gmail.com', '$2y$12$X5hCN/0lZslv73ZTjQURj.96adH2kRivombRySS/vqgB4nqQI.ZqO', 'disabled', 'member', '2026-11-12 04:50:59'),
(9, 'ggg', 'ruimaebaltazgar@gmail.com', '$2y$12$UCz880LStYZS/Cr4diaZSuBHo2vHim124I6JxRsrDuLDPvHuvLiV6', 'disabled', 'member', '2026-11-12 05:27:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `borrow_records`
--
ALTER TABLE `borrow_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `borrow_records`
--
ALTER TABLE `borrow_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_records`
--
ALTER TABLE `borrow_records`
  ADD CONSTRAINT `borrow_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `borrow_records_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
