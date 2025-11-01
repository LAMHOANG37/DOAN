<?php
/**
 * API: Lấy danh sách phòng theo loại phòng
 * GET params: roomType
 * Returns: JSON array of available rooms
 */

include 'config.php';

header('Content-Type: application/json');

// Get parameters
$roomType = $_GET['roomType'] ?? '';

// Validation
if (empty($roomType)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin: roomType',
        'rooms' => []
    ]);
    exit;
}

// Lấy tất cả phòng của loại phòng này
// Debug: Log roomType để kiểm tra
error_log("get_available_rooms.php - roomType received: " . $roomType);

$roomTypeEscaped = mysqli_real_escape_string($conn, $roomType);
$sql = "SELECT id, room_number, room_type, status FROM rooms WHERE room_type = '$roomTypeEscaped' AND status = 'available' ORDER BY room_number ASC";
$result = mysqli_query($conn, $sql);

// Debug: Log SQL query
error_log("get_available_rooms.php - SQL: " . $sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn database: ' . mysqli_error($conn),
        'rooms' => [],
        'debug' => ['sql' => $sql]
    ]);
    exit;
}

// Format response
$rooms = [];
$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = [
        'id' => $row['id'],
        'room_number' => $row['room_number']
    ];
    $count++;
}

// Nếu không có phòng, thử query không có điều kiện status để debug
if ($count == 0) {
    $sql2 = "SELECT id, room_number, room_type, status FROM rooms WHERE room_type = '$roomTypeEscaped' ORDER BY room_number ASC";
    $result2 = mysqli_query($conn, $sql2);
    $allRooms = [];
    while ($row2 = mysqli_fetch_assoc($result2)) {
        $allRooms[] = $row2;
    }
    
    // Thử query tất cả phòng để xem có dữ liệu không
    $sql3 = "SELECT id, room_number, room_type, status FROM rooms ORDER BY room_type, room_number ASC LIMIT 20";
    $result3 = mysqli_query($conn, $sql3);
    $allRoomsInDB = [];
    while ($row3 = mysqli_fetch_assoc($result3)) {
        $allRoomsInDB[] = $row3;
    }
    
    echo json_encode([
        'success' => false,
        'message' => "Không tìm thấy phòng trống (status='available') cho loại phòng '$roomType'. Tổng số phòng tìm thấy: " . count($allRooms),
        'rooms' => [],
        'debug' => [
            'roomType' => $roomType,
            'roomTypeEscaped' => $roomTypeEscaped,
            'allRooms' => $allRooms,
            'allRoomsInDB' => $allRoomsInDB,
            'sql' => $sql,
            'sql2' => $sql2
        ]
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'OK',
    'rooms' => $rooms
]);
?>


