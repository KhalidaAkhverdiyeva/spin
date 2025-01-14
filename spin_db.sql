-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 14, 2025 at 09:51 AM
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
-- Database: `spin_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `spin_rewards`
--

CREATE TABLE `spin_rewards` (
  `id` int(11) NOT NULL,
  `spin_reward_type` varchar(50) NOT NULL,
  `spin_reward_num` int(11) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `probability` float NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spin_rewards`
--

INSERT INTO `spin_rewards` (`id`, `spin_reward_type`, `spin_reward_num`, `icon`, `probability`, `is_active`) VALUES
(1, 'USDT', 10, '', 0.15, 1),
(2, 'COINS', 20, '', 0.2, 1),
(3, 'POINTS', 30, '', 0.2, 1),
(4, 'TICKET', 1, '', 0.2, 1),
(5, 'GIFT CARD', 5, '', 0.15, 1),
(6, 'BONUS', 50, '', 0.1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `tickets` int(11) NOT NULL DEFAULT 5,
  `coins` int(11) DEFAULT 1000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `tickets`, `coins`) VALUES
(1, 3, 110);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `spin_rewards`
--
ALTER TABLE `spin_rewards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `spin_rewards`
--
ALTER TABLE `spin_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
