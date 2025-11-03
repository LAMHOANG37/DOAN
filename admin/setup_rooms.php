<?php
/**
 * SETUP ROOM MANAGEMENT SYSTEM
 * Tự động tạo bảng và seed data cho hệ thống quản lý phòng
 */

include '../config.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Setup Room Management</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .success { color: #27ae60; background: #d5f4e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #ebf5fb; padding: 10px; border-radius: 5px; margin: 10px 0; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🏨 Setup Room Management System</h1>";

// ==================== 1. TẠO BẢNG ROOMS ====================
$sql1 = "CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `status` enum('available','booked','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `room_type` (`room_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql1)) {
    echo "<div class='success'>✅ Đã tạo bảng <code>rooms</code></div>";
} else {
    echo "<div class='error'>❌ Lỗi tạo bảng rooms: " . mysqli_error($conn) . "</div>";
}

// ==================== 2. TẠO BẢNG ROOM_ASSIGNMENTS ====================
$sql2 = "CREATE TABLE IF NOT EXISTS `room_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `room_number` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `room_id` (`room_id`),
  KEY `room_number` (`room_number`),
  KEY `check_in` (`check_in`),
  KEY `check_out` (`check_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql2)) {
    echo "<div class='success'>✅ Đã tạo bảng <code>room_assignments</code></div>";
} else {
    echo "<div class='error'>❌ Lỗi tạo bảng room_assignments: " . mysqli_error($conn) . "</div>";
}

// ==================== 3. THÊM CỘT VÀO ROOMBOOK ====================
// Kiểm tra và thêm cột user_id
$check_user_id = "SHOW COLUMNS FROM `roombook` LIKE 'user_id'";
$result_user_id = mysqli_query($conn, $check_user_id);
if (mysqli_num_rows($result_user_id) == 0) {
    $sql3a = "ALTER TABLE `roombook` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `Email`, ADD KEY `user_id` (`user_id`)";
    if (mysqli_query($conn, $sql3a)) {
        echo "<div class='success'>✅ Đã thêm cột <code>user_id</code> vào bảng roombook</div>";
    } else {
        echo "<div class='error'>❌ Lỗi thêm cột user_id: " . mysqli_error($conn) . "</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Cột <code>user_id</code> đã tồn tại</div>";
}

// Kiểm tra và thêm cột room_numbers
$check_room_numbers = "SHOW COLUMNS FROM `roombook` LIKE 'room_numbers'";
$result_room_numbers = mysqli_query($conn, $check_room_numbers);
if (mysqli_num_rows($result_room_numbers) == 0) {
    $sql3b = "ALTER TABLE `roombook` ADD COLUMN `room_numbers` varchar(255) DEFAULT NULL AFTER `stat`";
    if (mysqli_query($conn, $sql3b)) {
        echo "<div class='success'>✅ Đã thêm cột <code>room_numbers</code> vào bảng roombook</div>";
    } else {
        echo "<div class='error'>❌ Lỗi thêm cột room_numbers: " . mysqli_error($conn) . "</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Cột <code>room_numbers</code> đã tồn tại</div>";
}

// ==================== 4. SEED DATA CHO CÁC PHÒNG ====================
$check_rooms = "SELECT COUNT(*) as count FROM `rooms`";
$result_check = mysqli_query($conn, $check_rooms);
$row_check = mysqli_fetch_assoc($result_check);
$existing_rooms = $row_check['count'];

if ($existing_rooms == 0) {
    $rooms = [
        // Phòng Cao Cấp: 101-105
        [101, 'Phòng Cao Cấp'],
        [102, 'Phòng Cao Cấp'],
        [103, 'Phòng Cao Cấp'],
        [104, 'Phòng Cao Cấp'],
        [105, 'Phòng Cao Cấp'],
        // Phòng Sang Trọng: 201-205
        [201, 'Phòng Sang Trọng'],
        [202, 'Phòng Sang Trọng'],
        [203, 'Phòng Sang Trọng'],
        [204, 'Phòng Sang Trọng'],
        [205, 'Phòng Sang Trọng'],
        // Nhà Khách: 301-305
        [301, 'Nhà Khách'],
        [302, 'Nhà Khách'],
        [303, 'Nhà Khách'],
        [304, 'Nhà Khách'],
        [305, 'Nhà Khách'],
        // Phòng Đơn: 401-405
        [401, 'Phòng Đơn'],
        [402, 'Phòng Đơn'],
        [403, 'Phòng Đơn'],
        [404, 'Phòng Đơn'],
        [405, 'Phòng Đơn'],
    ];
    
    $inserted = 0;
    foreach ($rooms as $room) {
        $room_number = $room[0];
        $room_type = $room[1];
        $sql_insert = "INSERT INTO `rooms` (`room_number`, `room_type`, `status`) VALUES ($room_number, '$room_type', 'available')";
        if (mysqli_query($conn, $sql_insert)) {
            $inserted++;
        }
    }
    
    if ($inserted == count($rooms)) {
        echo "<div class='success'>✅ Đã thêm " . $inserted . " phòng vào database</div>";
    } else {
        echo "<div class='error'>❌ Chỉ thêm được $inserted/" . count($rooms) . " phòng</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Đã có $existing_rooms phòng trong database. Bỏ qua seed data.</div>";
}

echo "<div class='success' style='margin-top: 30px; padding: 20px;'>
        <h2>✅ Setup hoàn tất!</h2>
        <p>Hệ thống quản lý phòng đã sẵn sàng sử dụng.</p>
        <p><strong>Phòng Cao Cấp:</strong> 101-105</p>
        <p><strong>Phòng Sang Trọng:</strong> 201-205</p>
        <p><strong>Nhà Khách:</strong> 301-305</p>
        <p><strong>Phòng Đơn:</strong> 401-405</p>
        <p><a href='index.php' style='display: inline-block; margin-top: 10px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>Quay về trang chủ</a></p>
    </div>";

echo "</div></body></html>";
?>


