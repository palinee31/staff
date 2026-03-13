-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 13, 2026 at 10:25 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `staff`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`) VALUES
(1, 'ห้องฉุกเฉิน'),
(2, 'OPD'),
(3, 'IPD'),
(4, 'ห้องผ่าตัด'),
(5, 'ICU');

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `work_date` date DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `work_hours` decimal(5,2) DEFAULT NULL,
  `checked_by` int(11) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`id`, `user_id`, `department_id`, `work_date`, `time_in`, `time_out`, `work_hours`, `checked_by`, `signature`, `checked_at`, `note`) VALUES
(76, 1, 5, '2026-03-11', '2026-03-11 19:59:00', '2026-03-11 18:09:00', 22.17, 1, 'signatures/69b166ae3ef81.png', '2026-03-11 19:57:18', ''),
(77, 1, 5, '2026-03-11', '2026-03-11 19:59:00', '2026-03-12 18:09:00', 22.17, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:07:50', ''),
(78, 1, 5, '2026-03-11', '2026-03-11 19:59:00', '2026-03-12 18:09:00', 22.17, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:07:57', ''),
(79, 1, 5, '2026-03-11', '2026-03-11 22:59:00', '2026-03-12 22:38:00', 23.65, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:16:50', '12333'),
(80, 1, 3, '2026-03-11', '2026-03-11 21:54:00', '2026-03-11 23:57:00', 2.05, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:22:15', '12333'),
(81, 3, 2, '2026-03-11', '2026-03-11 20:51:00', '2026-03-11 20:51:00', 0.00, 7, 'uploads/signatures/sig_1773283876_II.png', '2026-03-12 09:54:00', ''),
(82, 1, 5, '2026-03-11', '2026-03-11 21:02:00', '2026-03-11 21:02:00', 0.00, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:17:00', ''),
(83, 1, 5, '2026-03-11', '2026-03-11 21:02:00', '2026-03-11 21:02:00', 0.00, 1, 'uploads/signatures/sign_drawn_1773390400_69b3ca40ce9bd.png', '2026-03-13 15:26:51', ''),
(84, 1, 5, '2026-03-11', '2026-03-11 21:02:00', '2026-03-11 21:02:00', 0.00, 1, 'uploads/signatures/sign_drawn_1773390468_69b3ca84b3389.png', '2026-03-13 15:27:55', ''),
(85, 1, 5, '2026-03-11', '2026-03-11 21:02:00', '2026-03-11 21:02:00', 0.00, 1, 'uploads/signatures/sign_drawn_1773390468_69b3ca84b3389.png', '2026-03-13 15:39:36', ''),
(86, 5, 1, '2026-03-12', '2026-03-12 09:10:00', '2026-03-12 09:10:00', 0.00, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:12:46', ''),
(87, 1, 3, '2026-03-12', '2026-03-12 11:41:00', '2026-03-12 11:41:00', 0.00, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:42:08', 'นอน'),
(88, 3, 5, '2026-03-12', '2026-03-12 09:33:00', '2026-03-12 09:33:00', 0.00, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:44:49', 'ด้ก้้ก้ห้'),
(89, 3, 5, '2026-03-12', '2026-03-12 09:44:00', '2026-03-12 17:46:00', 8.03, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:44:56', 'ทำใจเขาไม่รัก'),
(90, 7, 1, '2026-03-12', '2026-03-12 08:30:00', '2026-03-12 18:30:00', 10.00, 5, 'uploads/signatures/sig_1773281040_ปาลินี.png', '2026-03-12 09:53:11', ''),
(91, 1, 2, '2026-03-13', '2026-03-13 18:25:00', '2026-03-13 20:30:00', 2.08, 1, 'uploads/signatures/sign_upload_1773369214_69b3777e0ab89.png', '2026-03-13 15:26:11', ''),
(92, 1, 1, '2026-03-13', '2026-03-13 15:44:00', '2026-03-13 15:44:00', 0.00, 1, 'uploads/signatures/sign_drawn_1773391436_69b3ce4c35f59.png', '2026-03-13 15:44:44', ''),
(93, 8, 3, '2026-03-13', '2026-03-13 19:08:00', '2026-03-13 20:09:00', 1.02, NULL, NULL, NULL, 'กินข้าวช้า');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `signature_path` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `created_at`, `signature_path`, `department_id`) VALUES
(1, 'som', '12345', 'som3', '2026-03-02 07:14:57', 'sign_drawn_1773391436_69b3ce4c35f59.png', 1),
(3, 'som1', '111111', 'som1', '2026-03-10 08:43:43', NULL, 1),
(4, 'ปาลินี วันหลัง', '123456', 'ปาลินี', '2026-03-11 14:12:30', 'sig_1773238350_ปาลินี วันหลัง.png', 1),
(5, 'ปาลินี', '123456', 'ปาลินี วันหลัง', '2026-03-12 02:04:01', 'sig_1773281040_ปาลินี.png', 1),
(6, 'prd', '14785236', 'ปรินดา', '2026-03-12 02:49:55', 'sig_1773283795_prd.png', 1),
(7, 'II', '1234567890', 'ปรินดาอิอิ', '2026-03-12 02:51:16', 'sig_1773283876_II.png', 1),
(8, 'สมหมาย', '123456', 'สมหมาย', '2026-03-13 09:05:36', 'sig_1773392736_สมหมาย.png', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
