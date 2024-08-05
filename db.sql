-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2024 at 10:03 PM
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
-- Database: `monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `hosts`
--

CREATE TABLE `hosts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `status` enum('Loading','Online','Down','Offline') DEFAULT 'Loading',
  `last_checked` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hosts`
--

INSERT INTO `hosts` (`id`, `name`, `ip`, `status`, `last_checked`) VALUES
(1, 'clm-aus-wk14nb', '172.20.16.81', 'Online', '2024-08-05 20:03:12'),
(2, 'clm-pun-wk14li', '10.133.175.23', 'Online', '2024-08-05 20:03:12'),
(3, 'clm-tlv-wk14m9', '10.63.34.109', 'Online', '2024-08-05 20:03:13'),
(4, 'sukumawa', '172.22.172.54', 'Online', '2024-08-05 20:03:13'),
(5, 'cfauto.bmc.com', '172.22.166.100', 'Online', '2024-08-05 20:03:12'),
(6, 'gcc.bmc.com', '172.22.165.8', 'Online', '2024-08-05 20:03:11'),
(7, 'irh.bmc.com', '172.22.174.66', 'Online', '2024-08-05 20:03:15'),
(8, 'vw-aus-cf-004', '172.20.74.168', 'Online', '2024-08-05 20:03:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hosts`
--
ALTER TABLE `hosts`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hosts`
--
ALTER TABLE `hosts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
