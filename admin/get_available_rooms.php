<?php
/**
 * API: Lấy danh sách phòng theo loại phòng
 * GET params: roomType, checkIn (optional), checkOut (optional)
 * Returns: JSON array of all rooms with availability status
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

include '../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Get parameters
$roomType = $_GET['roomType'] ?? '';
$checkIn = $_GET['checkIn'] ?? '';
$checkOut = $_GET['checkOut'] ?? '';

// Validation
if (empty($roomType)) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin: roomType',
        'rooms' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check database connection
if (!isset($conn) || !$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi kết nối database',
        'rooms' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$roomTypeEscaped = mysqli_real_escape_string($conn, $roomType);

// Lấy TẤT CẢ phòng của loại phòng này (kể cả phòng đã đặt)
$sql = "SELECT id, room_number, room_type, status FROM rooms WHERE room_type = '$roomTypeEscaped' ORDER BY room_number ASC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi truy vấn database: ' . mysqli_error($conn),
        'rooms' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Format response với thông tin phòng đã đặt
$rooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $roomId = $row['id'];
    $roomNumber = $row['room_number'];
    $isBooked = false;
    
    // Kiểm tra xem phòng có bị đặt không
    // Nếu có ngày check-in/check-out: kiểm tra xung đột trong khoảng thời gian đó
    // Nếu không có ngày: kiểm tra xem phòng có đang được đặt bất kỳ lúc nào không
    if (!empty($checkIn) && !empty($checkOut)) {
        // Kiểm tra xung đột trong khoảng thời gian cụ thể
        // Logic: Hai khoảng thời gian giao nhau nếu:
        // - Ngày check-in mới < ngày check-out cũ VÀ ngày check-out mới > ngày check-in cũ
        $checkInEscaped = mysqli_real_escape_string($conn, $checkIn);
        $checkOutEscaped = mysqli_real_escape_string($conn, $checkOut);
        
        $checkSql = "SELECT COUNT(*) as count FROM room_assignments 
                     WHERE room_id = $roomId 
                     AND check_in < '$checkOutEscaped' 
                     AND check_out > '$checkInEscaped'";
        $checkResult = mysqli_query($conn, $checkSql);
        if ($checkResult) {
            $checkRow = mysqli_fetch_assoc($checkResult);
            $isBooked = ($checkRow['count'] > 0);
        }
    } else {
        // Nếu chưa chọn ngày, kiểm tra xem phòng có đang được đặt bất kỳ lúc nào không
        // Chỉ kiểm tra các booking còn hiệu lực (check_out > ngày hiện tại)
        $currentDate = date('Y-m-d');
        $checkSql = "SELECT COUNT(*) as count FROM room_assignments 
                     WHERE room_id = $roomId 
                     AND check_out > '$currentDate'";
        $checkResult = mysqli_query($conn, $checkSql);
        if ($checkResult) {
            $checkRow = mysqli_fetch_assoc($checkResult);
            $isBooked = ($checkRow['count'] > 0);
        }
    }
    
    $rooms[] = [
        'id' => $roomId,
        'room_number' => $roomNumber,
        'status' => $row['status'],
        'is_booked' => $isBooked,
        'available' => !$isBooked
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'OK',
    'rooms' => $rooms,
    'count' => count($rooms)
], JSON_UNESCAPED_UNICODE);
?>


