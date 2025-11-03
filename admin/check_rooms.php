<?php
/**
 * Script để kiểm tra dữ liệu phòng trong database
 */

include '../config.php';

echo "<h2>Kiểm tra dữ liệu phòng trong database</h2>";

// Kiểm tra xem bảng rooms có tồn tại không
$checkTable = "SHOW TABLES LIKE 'rooms'";
$tableExists = mysqli_query($conn, $checkTable);

if (mysqli_num_rows($tableExists) == 0) {
    echo "<p style='color: red;'>❌ Bảng 'rooms' chưa tồn tại!</p>";
    echo "<p><a href='admin/setup_rooms_data.php'>Chạy script tạo dữ liệu</a></p>";
    exit;
}

echo "<p style='color: green;'>✓ Bảng 'rooms' đã tồn tại.</p>";

// Lấy tất cả dữ liệu phòng
$sql = "SELECT id, room_number, room_type, status FROM rooms ORDER BY room_type, room_number ASC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "<p style='color: red;'>❌ Lỗi: " . mysqli_error($conn) . "</p>";
    exit;
}

$rooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = $row;
}

echo "<h3>Tổng số phòng: " . count($rooms) . "</h3>";

// Nhóm theo loại phòng
$groupedRooms = [];
foreach ($rooms as $room) {
    $roomType = $room['room_type'];
    if (!isset($groupedRooms[$roomType])) {
        $groupedRooms[$roomType] = [];
    }
    $groupedRooms[$roomType][] = $room;
}

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Loại Phòng</th><th>Số Lượng</th><th>Danh Sách Phòng</th><th>Trạng Thái</th></tr>";

foreach ($groupedRooms as $roomType => $roomList) {
    $roomNumbers = array_map(function($r) { return $r['room_number']; }, $roomList);
    $statusCounts = array_count_values(array_map(function($r) { return $r['status']; }, $roomList));
    
    echo "<tr>";
    echo "<td><strong>{$roomType}</strong></td>";
    echo "<td>" . count($roomList) . "</td>";
    echo "<td>" . implode(", ", $roomNumbers) . "</td>";
    echo "<td>";
    foreach ($statusCounts as $status => $count) {
        echo "$status: $count<br>";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Kiểm tra xem có đúng format không
echo "<hr><h3>Kiểm tra format:</h3>";
$expectedRooms = [
    'Phòng Cao Cấp' => [101, 102, 103, 104, 105],
    'Phòng Sang Trọng' => [201, 202, 203, 204, 205],
    'Nhà Khách' => [301, 302, 303, 304, 305],
    'Phòng Đơn' => [401, 402, 403, 404, 405]
];

foreach ($expectedRooms as $roomType => $expectedNumbers) {
    $found = isset($groupedRooms[$roomType]) ? $groupedRooms[$roomType] : [];
    $foundNumbers = array_map(function($r) { return $r['room_number']; }, $found);
    
    echo "<p><strong>{$roomType}:</strong> ";
    if (empty($found)) {
        echo "<span style='color: red;'>❌ Không có dữ liệu</span>";
    } else {
        $missing = array_diff($expectedNumbers, $foundNumbers);
        $extra = array_diff($foundNumbers, $expectedNumbers);
        
        if (empty($missing) && empty($extra)) {
            echo "<span style='color: green;'>✓ Đúng ({count($found)} phòng)</span>";
        } else {
            if (!empty($missing)) {
                echo "<span style='color: orange;'>⚠ Thiếu: " . implode(", ", $missing) . "</span>";
            }
            if (!empty($extra)) {
                echo "<span style='color: red;'>⚠ Dư/Sai: " . implode(", ", $extra) . "</span>";
            }
        }
    }
    echo "</p>";
}

echo "<hr>";
echo "<p><a href='admin/setup_rooms_data.php?recreate=1' style='color: red; font-weight: bold;'>🔧 Tạo lại dữ liệu phòng</a></p>";
echo "<p><a href='index.php'>← Quay lại trang chủ</a></p>";
?>

