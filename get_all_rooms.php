<?php
/**
 * API: Lấy tất cả số phòng (không filter theo loại)
 * Returns: JSON array of all available rooms
 */

include 'config.php';

header('Content-Type: application/json');

// Lấy tất cả phòng available
$sql = "SELECT id, room_number, room_type FROM rooms WHERE status = 'available' ORDER BY room_number ASC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn database: ' . mysqli_error($conn),
        'rooms' => []
    ]);
    exit;
}

// Format response
$rooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = [
        'id' => $row['id'],
        'room_number' => $row['room_number'],
        'room_type' => $row['room_type']
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'OK',
    'rooms' => $rooms
]);
?>


