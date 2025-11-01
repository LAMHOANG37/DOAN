<?php
/**
 * Script để tạo dữ liệu phòng nếu chưa có
 * Chạy một lần để seed dữ liệu phòng vào database
 */

include 'config.php';

echo "<h2>Đang kiểm tra và tạo dữ liệu phòng...</h2>";

// Kiểm tra xem bảng rooms có tồn tại không
$checkTable = "SHOW TABLES LIKE 'rooms'";
$tableExists = mysqli_query($conn, $checkTable);

if (mysqli_num_rows($tableExists) == 0) {
    echo "<p style='color: red;'>Bảng 'rooms' chưa tồn tại. Đang tạo bảng...</p>";
    
    // Tạo bảng rooms
    $createTable = "CREATE TABLE IF NOT EXISTS `rooms` (
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
    
    if (mysqli_query($conn, $createTable)) {
        echo "<p style='color: green;'>✓ Đã tạo bảng 'rooms' thành công!</p>";
    } else {
        echo "<p style='color: red;'>✗ Lỗi khi tạo bảng: " . mysqli_error($conn) . "</p>";
        exit;
    }
} else {
    echo "<p style='color: green;'>✓ Bảng 'rooms' đã tồn tại.</p>";
}

// Kiểm tra xem đã có dữ liệu phòng chưa
$checkData = "SELECT COUNT(*) as count FROM rooms";
$result = mysqli_query($conn, $checkData);
$row = mysqli_fetch_assoc($result);
$existingCount = $row['count'];

if ($existingCount > 0) {
    echo "<p style='color: orange;'>⚠ Đã có $existingCount phòng trong database.</p>";
    echo "<p>Bạn có muốn xóa dữ liệu cũ và tạo lại không?</p>";
    echo "<p><a href='?recreate=1' style='color: red;'>Xóa và tạo lại</a> | <a href='?skip=1' style='color: green;'>Bỏ qua</a></p>";
    
    if (isset($_GET['recreate'])) {
        mysqli_query($conn, "DELETE FROM rooms");
        echo "<p style='color: orange;'>Đã xóa dữ liệu cũ.</p>";
    } else if (isset($_GET['skip'])) {
        echo "<p style='color: green;'>Đã bỏ qua. Giữ nguyên dữ liệu hiện có.</p>";
        exit;
    }
    
    if (!isset($_GET['recreate']) && !isset($_GET['skip'])) {
        exit;
    }
}

// Tạo dữ liệu phòng
echo "<p>Đang tạo dữ liệu phòng...</p>";

$rooms = [
    // Phòng Cao Cấp: 101-105
    ['room_number' => 101, 'room_type' => 'Phòng Cao Cấp', 'status' => 'available'],
    ['room_number' => 102, 'room_type' => 'Phòng Cao Cấp', 'status' => 'available'],
    ['room_number' => 103, 'room_type' => 'Phòng Cao Cấp', 'status' => 'available'],
    ['room_number' => 104, 'room_type' => 'Phòng Cao Cấp', 'status' => 'available'],
    ['room_number' => 105, 'room_type' => 'Phòng Cao Cấp', 'status' => 'available'],
    
    // Phòng Sang Trọng: 201-205
    ['room_number' => 201, 'room_type' => 'Phòng Sang Trọng', 'status' => 'available'],
    ['room_number' => 202, 'room_type' => 'Phòng Sang Trọng', 'status' => 'available'],
    ['room_number' => 203, 'room_type' => 'Phòng Sang Trọng', 'status' => 'available'],
    ['room_number' => 204, 'room_type' => 'Phòng Sang Trọng', 'status' => 'available'],
    ['room_number' => 205, 'room_type' => 'Phòng Sang Trọng', 'status' => 'available'],
    
    // Nhà Khách: 301-305
    ['room_number' => 301, 'room_type' => 'Nhà Khách', 'status' => 'available'],
    ['room_number' => 302, 'room_type' => 'Nhà Khách', 'status' => 'available'],
    ['room_number' => 303, 'room_type' => 'Nhà Khách', 'status' => 'available'],
    ['room_number' => 304, 'room_type' => 'Nhà Khách', 'status' => 'available'],
    ['room_number' => 305, 'room_type' => 'Nhà Khách', 'status' => 'available'],
    
    // Phòng Đơn: 401-405
    ['room_number' => 401, 'room_type' => 'Phòng Đơn', 'status' => 'available'],
    ['room_number' => 402, 'room_type' => 'Phòng Đơn', 'status' => 'available'],
    ['room_number' => 403, 'room_type' => 'Phòng Đơn', 'status' => 'available'],
    ['room_number' => 404, 'room_type' => 'Phòng Đơn', 'status' => 'available'],
    ['room_number' => 405, 'room_type' => 'Phòng Đơn', 'status' => 'available'],
];

$successCount = 0;
$errorCount = 0;

foreach ($rooms as $room) {
    $roomNumber = intval($room['room_number']);
    $roomType = mysqli_real_escape_string($conn, $room['room_type']);
    $status = mysqli_real_escape_string($conn, $room['status']);
    
    $sql = "INSERT INTO rooms (room_number, room_type, status) 
            VALUES ($roomNumber, '$roomType', '$status')
            ON DUPLICATE KEY UPDATE room_type = '$roomType', status = '$status'";
    
    if (mysqli_query($conn, $sql)) {
        $successCount++;
        echo "<p style='color: green;'>✓ Phòng {$roomNumber} ({$roomType})</p>";
    } else {
        $errorCount++;
        echo "<p style='color: red;'>✗ Lỗi phòng {$roomNumber}: " . mysqli_error($conn) . "</p>";
    }
}

echo "<hr>";
echo "<h3 style='color: green;'>Hoàn tất!</h3>";
echo "<p>✓ Đã tạo thành công: $successCount phòng</p>";
if ($errorCount > 0) {
    echo "<p style='color: red;'>✗ Lỗi: $errorCount phòng</p>";
}

// Kiểm tra lại
$finalCheck = "SELECT room_type, COUNT(*) as count FROM rooms GROUP BY room_type";
$result = mysqli_query($conn, $finalCheck);

echo "<hr><h3>Dữ liệu hiện tại trong database:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Loại Phòng</th><th>Số Lượng</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>{$row['room_type']}</td><td>{$row['count']}</td></tr>";
}
echo "</table>";

echo "<hr><p><a href='index.php'>← Quay lại trang chủ</a></p>";
?>

