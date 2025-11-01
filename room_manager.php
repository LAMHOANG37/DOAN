<?php
/**
 * ROOM MANAGEMENT SYSTEM
 * Quản lý số phòng riêng cho từng loại phòng
 * - Phòng Cao Cấp: 101-105
 * - Phòng Sang Trọng: 201-205
 * - Nhà Khách: 301-305
 * - Phòng Đơn: 401-405
 */

// ==================== HÀM KIỂM TRA PHÒNG TRỐNG ====================
/**
 * Kiểm tra phòng trống trong khoảng thời gian
 * @param mysqli $conn - Database connection
 * @param string $roomType - Loại phòng (Phòng Cao Cấp, Phòng Sang Trọng, Nhà Khách, Phòng Đơn)
 * @param date $checkIn - Ngày nhận phòng (Y-m-d)
 * @param date $checkOut - Ngày trả phòng (Y-m-d)
 * @param int $numRooms - Số phòng cần đặt
 * @return array|false - Mảng các phòng trống hoặc false nếu không đủ phòng
 */
function getAvailableRooms($conn, $roomType, $checkIn, $checkOut, $numRooms) {
    // Lấy tất cả phòng của loại này
    $sql = "SELECT id, room_number, status FROM rooms 
            WHERE room_type = '$roomType' 
            AND status IN ('available', 'booked')
            ORDER BY room_number ASC";
    
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return false;
    }
    
    $availableRooms = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $roomId = $row['id'];
        $roomNumber = $row['room_number'];
        
        // Kiểm tra xem phòng có đang được đặt trong khoảng thời gian này không
        $checkSql = "SELECT COUNT(*) as count FROM room_assignments 
                     WHERE room_id = $roomId 
                     AND (
                         (check_in <= '$checkIn' AND check_out > '$checkIn') OR
                         (check_in < '$checkOut' AND check_out >= '$checkOut') OR
                         (check_in >= '$checkIn' AND check_out <= '$checkOut')
                     )";
        
        $checkResult = mysqli_query($conn, $checkSql);
        if (!$checkResult) {
            continue;
        }
        
        $checkRow = mysqli_fetch_assoc($checkResult);
        $isBooked = $checkRow['count'] > 0;
        
        // Chỉ thêm phòng nếu trống
        if (!$isBooked || $row['status'] == 'available') {
            $availableRooms[] = [
                'id' => $roomId,
                'room_number' => $roomNumber
            ];
        }
    }
    
    // Kiểm tra xem có đủ phòng không
    if (count($availableRooms) >= $numRooms) {
        // Trả về số phòng cần thiết
        return array_slice($availableRooms, 0, $numRooms);
    }
    
    return false;
}

// ==================== HÀM GÁN PHÒNG CHO BOOKING ====================
/**
 * Gán phòng cụ thể cho booking
 * @param mysqli $conn - Database connection
 * @param int $bookingId - ID booking
 * @param array $rooms - Mảng các phòng cần gán [['id' => x, 'room_number' => y], ...]
 * @param date $checkIn - Ngày nhận phòng
 * @param date $checkOut - Ngày trả phòng
 * @return bool - true nếu thành công, false nếu thất bại
 */
function assignRoomsToBooking($conn, $bookingId, $rooms, $checkIn, $checkOut) {
    $roomNumbers = [];
    
    foreach ($rooms as $room) {
        $roomId = $room['id'];
        $roomNumber = $room['room_number'];
        $roomNumbers[] = $roomNumber;
        
        // Thêm vào room_assignments
        $sql = "INSERT INTO room_assignments (booking_id, room_id, room_number, check_in, check_out) 
                VALUES ($bookingId, $roomId, $roomNumber, '$checkIn', '$checkOut')";
        
        if (!mysqli_query($conn, $sql)) {
            // Nếu thất bại, rollback các phòng đã gán
            // Xóa các phòng đã gán trước đó
            $deleteSql = "DELETE FROM room_assignments WHERE booking_id = $bookingId";
            mysqli_query($conn, $deleteSql);
            return false;
        }
        
        // Cập nhật status của phòng thành 'booked'
        $updateSql = "UPDATE rooms SET status = 'booked' WHERE id = $roomId";
        mysqli_query($conn, $updateSql);
    }
    
    // Cập nhật room_numbers trong roombook
    $roomNumbersStr = implode(', ', $roomNumbers);
    $updateBookingSql = "UPDATE roombook SET room_numbers = '$roomNumbersStr' WHERE id = $bookingId";
    mysqli_query($conn, $updateBookingSql);
    
    return true;
}

// ==================== HÀM GIẢI PHÓNG PHÒNG ====================
/**
 * Giải phóng phòng khi booking bị hủy hoặc đã check-out
 * @param mysqli $conn - Database connection
 * @param int $bookingId - ID booking
 * @return bool - true nếu thành công
 */
function releaseRoomsFromBooking($conn, $bookingId) {
    // Lấy danh sách phòng được gán cho booking này
    $sql = "SELECT room_id FROM room_assignments WHERE booking_id = $bookingId";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        return false;
    }
    
    $roomIds = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $roomIds[] = $row['room_id'];
    }
    
    // Xóa room_assignments
    $deleteSql = "DELETE FROM room_assignments WHERE booking_id = $bookingId";
    mysqli_query($conn, $deleteSql);
    
    // Cập nhật status của phòng về 'available'
    if (!empty($roomIds)) {
        $idsStr = implode(',', $roomIds);
        $updateSql = "UPDATE rooms SET status = 'available' WHERE id IN ($idsStr)";
        mysqli_query($conn, $updateSql);
    }
    
    // Xóa room_numbers trong roombook
    $updateBookingSql = "UPDATE roombook SET room_numbers = NULL WHERE id = $bookingId";
    mysqli_query($conn, $updateBookingSql);
    
    return true;
}

// ==================== HÀM LẤY SỐ PHÒNG CỦA BOOKING ====================
/**
 * Lấy danh sách số phòng đã được gán cho booking
 * @param mysqli $conn - Database connection
 * @param int $bookingId - ID booking
 * @return string|null - Chuỗi số phòng (ví dụ: "101, 102") hoặc null
 */
function getBookingRoomNumbers($conn, $bookingId) {
    $sql = "SELECT room_numbers FROM roombook WHERE id = $bookingId";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    return $row['room_numbers'] ?? null;
}

// ==================== HÀM KIỂM TRA VÀ GÁN PHÒNG TỰ ĐỘNG ====================
/**
 * Kiểm tra và gán phòng tự động cho booking mới
 * @param mysqli $conn - Database connection
 * @param int $bookingId - ID booking
 * @param string $roomType - Loại phòng
 * @param date $checkIn - Ngày nhận phòng
 * @param date $checkOut - Ngày trả phòng
 * @param int $numRooms - Số phòng cần đặt
 * @return bool|string - true nếu thành công, chuỗi lỗi nếu thất bại
 */
function checkAndAssignRooms($conn, $bookingId, $roomType, $checkIn, $checkOut, $numRooms) {
    // Kiểm tra phòng trống
    $availableRooms = getAvailableRooms($conn, $roomType, $checkIn, $checkOut, $numRooms);
    
    if ($availableRooms === false || count($availableRooms) < $numRooms) {
        return "Không đủ phòng trống cho loại phòng này trong khoảng thời gian đã chọn. Vui lòng thử lại với ngày khác.";
    }
    
    // Gán phòng
    $success = assignRoomsToBooking($conn, $bookingId, $availableRooms, $checkIn, $checkOut);
    
    if (!$success) {
        return "Đã xảy ra lỗi khi gán phòng. Vui lòng thử lại.";
    }
    
    return true;
}

?>

