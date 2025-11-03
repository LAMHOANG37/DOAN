<?php
/**
 * Room Management Helper Functions
 * Các hàm hỗ trợ quản lý phòng
 */

/**
 * Gán phòng cho booking
 */
function assignRoomsToBooking($conn, $bookingId, $roomData, $checkIn, $checkOut) {
    if (empty($roomData) || !is_array($roomData)) {
        return false;
    }
    
    // Đảm bảo bảng room_assignments tồn tại
    $checkTable = "SHOW TABLES LIKE 'room_assignments'";
    $tableExists = mysqli_query($conn, $checkTable);
    if (mysqli_num_rows($tableExists) == 0) {
        // Tạo bảng nếu chưa có
        $createTable = "CREATE TABLE IF NOT EXISTS `room_assignments` (
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
        mysqli_query($conn, $createTable);
    }
    
    $success = true;
    
    foreach ($roomData as $room) {
        $roomId = intval($room['id']);
        $roomNumber = intval($room['room_number']);
        
        // Escape values
        $checkInEscaped = mysqli_real_escape_string($conn, $checkIn);
        $checkOutEscaped = mysqli_real_escape_string($conn, $checkOut);
        
        // Insert vào room_assignments
        $sql = "INSERT INTO room_assignments (booking_id, room_id, room_number, check_in, check_out) 
                VALUES ($bookingId, $roomId, $roomNumber, '$checkInEscaped', '$checkOutEscaped')";
        
        $insertResult = mysqli_query($conn, $sql);
        if (!$insertResult) {
            // Kiểm tra lỗi cụ thể
            $error = mysqli_error($conn);
            // Nếu bảng chưa tồn tại, tạo lại
            if (strpos($error, "doesn't exist") !== false || strpos($error, "Unknown table") !== false) {
                $createTable = "CREATE TABLE IF NOT EXISTS `room_assignments` (
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
                mysqli_query($conn, $createTable);
                // Thử insert lại
                $insertResult = mysqli_query($conn, $sql);
                if (!$insertResult) {
                    $success = false;
                    break;
                }
            } else {
                $success = false;
                break;
            }
        }
        
        // Update status của phòng thành 'booked'
        $updateSql = "UPDATE rooms SET status = 'booked' WHERE id = $roomId";
        mysqli_query($conn, $updateSql);
    }
    
    // Update room_numbers trong roombook
    $roomNumbers = getBookingRoomNumbers($conn, $bookingId);
    if ($roomNumbers) {
        $roomNumbersEscaped = mysqli_real_escape_string($conn, $roomNumbers);
        $updateRoomNumbers = "UPDATE roombook SET room_numbers = '$roomNumbersEscaped' WHERE id = $bookingId";
        mysqli_query($conn, $updateRoomNumbers);
    }
    
    return $success;
}

/**
 * Lấy danh sách số phòng đã gán cho booking
 */
function getBookingRoomNumbers($conn, $bookingId) {
    $sql = "SELECT room_number FROM room_assignments WHERE booking_id = $bookingId ORDER BY room_number";
    $result = mysqli_query($conn, $sql);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return '';
    }
    
    $roomNumbers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $roomNumbers[] = $row['room_number'];
    }
    
    return implode(', ', $roomNumbers);
}

/**
 * Giải phóng phòng từ booking
 */
function releaseRoomsFromBooking($conn, $bookingId) {
    // Lấy danh sách phòng của booking
    $sql = "SELECT room_id FROM room_assignments WHERE booking_id = $bookingId";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $roomId = $row['room_id'];
            // Update status thành 'available'
            $updateSql = "UPDATE rooms SET status = 'available' WHERE id = $roomId";
            mysqli_query($conn, $updateSql);
        }
    }
    
    // Xóa assignments
    $deleteSql = "DELETE FROM room_assignments WHERE booking_id = $bookingId";
    mysqli_query($conn, $deleteSql);
    
    // Clear room_numbers trong roombook
    $updateRoomNumbers = "UPDATE roombook SET room_numbers = NULL WHERE id = $bookingId";
    mysqli_query($conn, $updateRoomNumbers);
}

/**
 * Lấy danh sách phòng có sẵn
 */
function getAvailableRooms($conn, $roomType, $checkIn, $checkOut, $limit = null) {
    $sql = "SELECT r.* FROM rooms r 
            WHERE r.room_type = '$roomType' 
            AND r.status = 'available'";
    
    // Loại trừ các phòng đã được đặt trong khoảng thời gian này
    $sql .= " AND r.id NOT IN (
                SELECT DISTINCT room_id FROM room_assignments 
                WHERE (
                    (check_in <= '$checkIn' AND check_out > '$checkIn') OR
                    (check_in < '$checkOut' AND check_out >= '$checkOut') OR
                    (check_in >= '$checkIn' AND check_out <= '$checkOut')
                )
            )";
    
    $sql .= " ORDER BY r.room_number";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        return [];
    }
    
    $rooms = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = $row;
    }
    
    return $rooms;
}



