-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2022 at 11:19 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bluebirdhotel`
-- User: `bluebird_user`
-- Password:   `password`
--
DROP DATABASE IF EXISTS bluebirdhotel;
CREATE DATABASE IF NOT EXISTS bluebirdhotel;

DROP USER IF EXISTS'bluebird_user'@'%';
CREATE USER IF NOT EXISTS 'bluebird_user'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON bluebirdhotel.* TO 'bluebird_user'@'%';
USE bluebirdhotel;

-- --------------------------------------------------------

--
-- Table structure for table `emp_login`
--

CREATE TABLE `emp_login` (
  `empid` int(100) NOT NULL,
  `Emp_Email` varchar(50) NOT NULL,
  `Emp_Password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `emp_login`
--

INSERT INTO `emp_login` (`empid`, `Emp_Email`, `Emp_Password`) VALUES
(1, 'Admin@gmail.com', '1234');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
-- Lưu ý: Các trường giá tiền (roomtotal, bedtotal, mealtotal, finaltotal) 
-- được lưu dưới dạng số và nên được format khi hiển thị
--
-- Cách format trong SQL (MySQL/MariaDB):
--   SELECT CONCAT(FORMAT(roomtotal, 0, 'de_DE'), 'd') as roomtotal_formatted FROM payment;
--   Hoặc: SELECT CONCAT(REPLACE(FORMAT(roomtotal, 0), ',', '.'), 'd') as roomtotal_formatted FROM payment;
--
-- Cách format trong PHP:
--   number_format($value, 0, ',', '.') . 'd'
--   Ví dụ: 1000000.00 -> "1.000.000d"
--

CREATE TABLE `payment` (
  `id` int(30) NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `RoomType` varchar(30) NOT NULL,
  `Bed` varchar(30) NOT NULL,
  `NoofRoom` int(30) NOT NULL,
  `cin` date NOT NULL,
  `cout` date NOT NULL,
  `noofdays` int(30) NOT NULL,
  `roomtotal` double(12,2) NOT NULL COMMENT 'Tổng tiền phòng - Format SQL: CONCAT(REPLACE(FORMAT(roomtotal, 0), ",", "."), "d") -> "1.000.000d"',
  `bedtotal` double(12,2) NOT NULL COMMENT 'Tổng tiền giường - Format SQL: CONCAT(REPLACE(FORMAT(bedtotal, 0), ",", "."), "d") -> "0d"',
  `meal` varchar(30) NOT NULL,
  `mealtotal` double(12,2) NOT NULL COMMENT 'Tổng tiền bữa ăn - Format SQL: CONCAT(REPLACE(FORMAT(mealtotal, 0), ",", "."), "d") -> "0d"',
  `finaltotal` double(12,2) NOT NULL COMMENT 'Tổng thanh toán - Format SQL: CONCAT(REPLACE(FORMAT(finaltotal, 0), ",", "."), "d") -> "1.000.000d"'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `payment`
-- Lưu ý: Tất cả giá trị tiền được lưu dưới dạng số (không có đơn vị)
-- Ví dụ: 1000000.00 trong DB, khi hiển thị format thành "1.000.000d"
--
-- Format trong SQL:
--   SELECT CONCAT(REPLACE(FORMAT(roomtotal, 0), ',', '.'), 'd') FROM payment;
--   Kết quả: "1.000.000d"
--
-- Format trong PHP:
--   echo number_format($value, 0, ',', '.') . 'd';
--   Kết quả: "1.000.000d"
--
-- Công thức tính giá theo code:
-- - Phòng Cao Cấp: 3,000,000/đêm
-- - Phòng Sang Trọng: 2,000,000/đêm  
-- - Nhà Khách: 1,500,000/đêm
-- - Phòng Đơn: 1,000,000/đêm
-- - roomtotal = giá_phòng × số_đêm × số_phòng
-- - mealtotal = giá_dịch_vụ × số_đêm (Bữa sáng: 10%, Nửa suất: 20%, Toàn bộ: 30% giá phòng)
-- - bedtotal = 0 (không tính phí giường trong hệ thống mới)
-- - finaltotal = roomtotal + mealtotal + bedtotal
--

INSERT INTO `payment` (`id`, `Name`, `Email`, `RoomType`, `Bed`, `NoofRoom`, `cin`, `cout`, `noofdays`, `roomtotal`, `bedtotal`, `meal`, `mealtotal`, `finaltotal`) VALUES
-- Ví dụ 1: Phòng Đơn, 1 đêm, Chỉ phòng
-- roomtotal = 1,000,000 × 1 × 1 = 1,000,000
-- bedtotal = 0 (không tính)
-- mealtotal = 0 (Chỉ phòng)
-- finaltotal = 1,000,000 + 0 + 0 = 1,000,000
(41, 'Tushar pankhaniya', 'pankhaniyatushar9@gmail.com', 'Phòng Đơn', 'Đơn', 1, '2022-11-09', '2022-11-10', 1, 1000000.00, 0.00, 'Chỉ phòng', 0.00, 1000000.00);

-- --------------------------------------------------------

--
-- VIEW để format giá tiền theo định dạng VND (1.000.000d)
-- Sử dụng: SELECT * FROM payment_formatted;
--
CREATE OR REPLACE VIEW `payment_formatted` AS
SELECT 
    `id`,
    `Name`,
    `Email`,
    `RoomType`,
    `Bed`,
    `NoofRoom`,
    `cin`,
    `cout`,
    `noofdays`,
    `roomtotal`,
    CONCAT(REPLACE(FORMAT(`roomtotal`, 0), ',', '.'), 'd') AS `roomtotal_formatted`,
    `bedtotal`,
    CONCAT(REPLACE(FORMAT(`bedtotal`, 0), ',', '.'), 'd') AS `bedtotal_formatted`,
    `meal`,
    `mealtotal`,
    CONCAT(REPLACE(FORMAT(`mealtotal`, 0), ',', '.'), 'd') AS `mealtotal_formatted`,
    `finaltotal`,
    CONCAT(REPLACE(FORMAT(`finaltotal`, 0), ',', '.'), 'd') AS `finaltotal_formatted`
FROM `payment`;

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `id` int(30) NOT NULL,
  `type` varchar(50) NOT NULL,
  `bedding` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`id`, `type`, `bedding`) VALUES
(4, 'Phòng Cao Cấp', 'Đơn'),
(6, 'Phòng Cao Cấp', 'Ba'),
(7, 'Phòng Cao Cấp', 'Bốn'),
(8, 'Phòng Sang Trọng', 'Đơn'),
(9, 'Phòng Sang Trọng', 'Đôi'),
(10, 'Phòng Sang Trọng', 'Ba'),
(11, 'Nhà Khách', 'Đơn'),
(12, 'Nhà Khách', 'Đôi'),
(13, 'Nhà Khách', 'Ba'),
(14, 'Nhà Khách', 'Bốn'),
(16, 'Phòng Cao Cấp', 'Đôi'),
(20, 'Phòng Đơn', 'Đơn'),
(22, 'Phòng Cao Cấp', 'Đơn'),
(23, 'Phòng Sang Trọng', 'Đơn'),
(24, 'Phòng Sang Trọng', 'Ba'),
(27, 'Nhà Khách', 'Đôi'),
(30, 'Phòng Sang Trọng', 'Đơn');

-- --------------------------------------------------------

--
-- Table structure for table `roombook`
--

CREATE TABLE `roombook` (
  `id` int(10) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `Country` varchar(30) NOT NULL,
  `Phone` varchar(30) NOT NULL,
  `RoomType` varchar(30) NOT NULL,
  `Bed` varchar(30) NOT NULL,
  `Meal` varchar(30) NOT NULL,
  `NoofRoom` varchar(30) NOT NULL,
  `cin` date NOT NULL,
  `cout` date NOT NULL,
  `nodays` int(50) NOT NULL,
  `stat` varchar(30) NOT NULL,
  `room_numbers` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roombook`
--

INSERT INTO `roombook` (`id`, `Name`, `Email`, `Country`, `Phone`, `RoomType`, `Bed`, `Meal`, `NoofRoom`, `cin`, `cout`, `nodays`, `stat`) VALUES
(41, 'Tushar pankhaniya', 'pankhaniyatushar9@gmail.com', 'India', '9313346569', 'Phòng Đơn', 'Đơn', 'Chỉ phòng', '1', '2022-11-09', '2022-11-10', 1, 'Confirm');

-- --------------------------------------------------------

--
-- Table structure for table `signup`
--

CREATE TABLE `signup` (
  `UserID` int(100) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Password` varchar(50) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `signup`
--
INSERT INTO `signup` (`UserID`, `Username`, `Email`, `Password`) VALUES
(1, 'Tushar Pankhaniya', 'tusharpankhaniya2202@gmail.com', '123');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `work` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `work`) VALUES
(1, 'Tushar pankhaniya', 'Manager'),
(3, 'rohit patel', 'Cook'),
(4, 'Dipak', 'Cook'),
(5, 'tirth', 'Helper'),
(6, 'mohan', 'Helper'),
(7, 'shyam', 'cleaner'),
(8, 'rohan', 'weighter'),
(9, 'hiren', 'weighter'),
(10, 'nikunj', 'weighter'),
(11, 'rekha', 'Cook');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `chat_history`
--

CREATE TABLE `chat_history` (
  `id` int(11) NOT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `user_message` text NOT NULL,
  `bot_response` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
-- Hệ thống quản lý số phòng riêng cho từng loại phòng
-- Phòng Cao Cấp: 101-105
-- Phòng Sang Trọng: 201-205
-- Nhà Khách: 301-305
-- Phòng Đơn: 401-405
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `status` enum('available','booked','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `rooms`
--

-- Phòng Cao Cấp: 101-105
INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `status`) VALUES
(1, 101, 'Phòng Cao Cấp', 'available'),
(2, 102, 'Phòng Cao Cấp', 'available'),
(3, 103, 'Phòng Cao Cấp', 'available'),
(4, 104, 'Phòng Cao Cấp', 'available'),
(5, 105, 'Phòng Cao Cấp', 'available');

-- Phòng Sang Trọng: 201-205
INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `status`) VALUES
(6, 201, 'Phòng Sang Trọng', 'available'),
(7, 202, 'Phòng Sang Trọng', 'available'),
(8, 203, 'Phòng Sang Trọng', 'available'),
(9, 204, 'Phòng Sang Trọng', 'available'),
(10, 205, 'Phòng Sang Trọng', 'available');

-- Nhà Khách: 301-305
INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `status`) VALUES
(11, 301, 'Nhà Khách', 'available'),
(12, 302, 'Nhà Khách', 'available'),
(13, 303, 'Nhà Khách', 'available'),
(14, 304, 'Nhà Khách', 'available'),
(15, 305, 'Nhà Khách', 'available');

-- Phòng Đơn: 401-405
INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `status`) VALUES
(16, 401, 'Phòng Đơn', 'available'),
(17, 402, 'Phòng Đơn', 'available'),
(18, 403, 'Phòng Đơn', 'available'),
(19, 404, 'Phòng Đơn', 'available'),
(20, 405, 'Phòng Đơn', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `room_assignments`
-- Liên kết booking với phòng cụ thể trong khoảng thời gian
--

CREATE TABLE `room_assignments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `room_number` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `emp_login`
--
ALTER TABLE `emp_login`
  ADD PRIMARY KEY (`empid`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roombook`
--
ALTER TABLE `roombook`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `signup`
--
ALTER TABLE `signup`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `chat_history`
--
ALTER TABLE `chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `room_type` (`room_type`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `room_assignments`
--
ALTER TABLE `room_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `room_number` (`room_number`),
  ADD KEY `check_in` (`check_in`),
  ADD KEY `check_out` (`check_out`);

--
-- Indexes for table `roombook` (updated)
--
ALTER TABLE `roombook`
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `emp_login`
--
ALTER TABLE `emp_login`
  MODIFY `empid` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `roombook`
--
ALTER TABLE `roombook`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `signup`
--
ALTER TABLE `signup`
  MODIFY `UserID` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_history`
--
ALTER TABLE `chat_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `room_assignments`
--
ALTER TABLE `room_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
