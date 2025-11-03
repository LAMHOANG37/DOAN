<?php
/**
 * Xử lý logic booking phòng từ người dùng
 */

include '../config.php';
include '../admin/room_manager.php';
session_start();

// Chỉ xử lý khi có POST request từ form booking
if (!isset($_POST['guestdetailsubmit'])) {
    header("Location: ../index.php");
    exit;
}

// Check if user is logged in before processing
$usermail = $_SESSION['usermail'] ?? '';
$isLoggedIn = !empty($usermail);

if(!$isLoggedIn) {
    $_SESSION['error_message'] = 'Yêu cầu đăng nhập. Vui lòng đăng nhập để đặt phòng.';
    header("Location: ../login.php");
    exit;
}

// Lấy user info
$user_id = null;
if($isLoggedIn) {
    $user_sql = "SELECT UserID FROM signup WHERE Email = '$usermail'";
    $user_result = mysqli_query($conn, $user_sql);
    if($user_result && mysqli_num_rows($user_result) > 0) {
        $user_data = mysqli_fetch_array($user_result);
        $user_id = $user_data['UserID'] ?? null;
    }
}

// Lấy dữ liệu từ form
$Name = mysqli_real_escape_string($conn, trim($_POST['Name']));
$Email = mysqli_real_escape_string($conn, trim($_POST['Email']));
$Country = mysqli_real_escape_string($conn, trim($_POST['Country']));
$Phone = mysqli_real_escape_string($conn, trim($_POST['Phone']));
$RoomType = mysqli_real_escape_string($conn, $_POST['RoomType']);
$Service = mysqli_real_escape_string($conn, $_POST['Service']);
$cin = $_POST['cin'];
$cout = $_POST['cout'];

// Validation
if($Name == "" || $Email == "" || $Country == "" || $Phone == ""){
    $_SESSION['error_message'] = 'Thiếu thông tin cá nhân. Vui lòng điền đầy đủ thông tin khách hàng.';
    header("Location: ../index.php");
    exit;
}
elseif($RoomType == "" || $Service == ""){
    $_SESSION['error_message'] = 'Thiếu thông tin đặt phòng. Vui lòng chọn đầy đủ thông tin phòng.';
    header("Location: ../index.php");
    exit;
}
elseif($cin == "" || $cout == ""){
    $_SESSION['error_message'] = 'Thiếu ngày nhận/trả phòng. Vui lòng chọn ngày nhận và trả phòng.';
    header("Location: ../index.php");
    exit;
}
elseif(strtotime($cin) < strtotime(date('Y-m-d'))){
    $_SESSION['error_message'] = 'Ngày nhận phòng không hợp lệ. Ngày nhận phòng phải từ hôm nay trở đi.';
    header("Location: ../index.php");
    exit;
}
elseif(strtotime($cout) <= strtotime($cin)){
    $_SESSION['error_message'] = 'Ngày trả phòng không hợp lệ. Ngày trả phòng phải sau ngày nhận phòng.';
    header("Location: ../index.php");
    exit;
}

// Lấy số phòng đã chọn từ form (chỉ chọn 1 phòng)
$selectedRoomNumber = isset($_POST['RoomNumber']) ? trim($_POST['RoomNumber']) : '';

// Debug: Log dữ liệu nhận được
if (empty($selectedRoomNumber) || $selectedRoomNumber == '' || $selectedRoomNumber == '0') {
    $_SESSION['error_message'] = 'Chưa chọn phòng. Vui lòng chọn phòng để đặt.';
    header("Location: ../index.php");
    exit;
}

$roomNumber = intval($selectedRoomNumber);
$numRooms = 1; // Luôn là 1 vì chỉ chọn 1 phòng

// Lấy thông tin phòng từ database
$roomInfoSql = "SELECT id, room_type, status FROM rooms WHERE room_number = $roomNumber AND room_type = '$RoomType'";
$roomInfoResult = mysqli_query($conn, $roomInfoSql);

if (!$roomInfoResult || mysqli_num_rows($roomInfoResult) == 0) {
    $_SESSION['error_message'] = 'Phòng không tồn tại. Phòng đã chọn không tồn tại hoặc không thuộc loại phòng này.';
    header("Location: ../index.php");
    exit;
}

$roomInfo = mysqli_fetch_assoc($roomInfoResult);
$roomId = $roomInfo['id'];

// Kiểm tra phòng có đang được đặt trong khoảng thời gian này không
$checkSql = "SELECT COUNT(*) as count FROM room_assignments 
             WHERE room_id = $roomId 
             AND (
                 (check_in <= '$cin' AND check_out > '$cin') OR
                 (check_in < '$cout' AND check_out >= '$cout') OR
                 (check_in >= '$cin' AND check_out <= '$cout')
             )";
$checkResult = mysqli_query($conn, $checkSql);
$checkRow = mysqli_fetch_assoc($checkResult);

if ($checkRow['count'] > 0) {
    $_SESSION['error_message'] = 'Phòng không khả dụng. Phòng đã được đặt trong khoảng thời gian này. Vui lòng chọn phòng khác.';
    header("Location: ../index.php");
    exit;
}

// Phòng hợp lệ, tạo booking
$roomData = [[
    'id' => $roomId,
    'room_number' => $roomNumber
]];

// Tạo booking
$sta = "Confirm";
$user_id_str = $user_id ? "'$user_id'" : "NULL";
// Lưu Bed = NULL vì đã bỏ, Meal = Service
// Tính số ngày trước
$nodays = (strtotime($cout) - strtotime($cin)) / (60 * 60 * 24);
$nodays = intval($nodays);

// Escape các giá trị
$cin_escaped = mysqli_real_escape_string($conn, $cin);
$cout_escaped = mysqli_real_escape_string($conn, $cout);

// Kiểm tra nếu Bed column không cho phép NULL, dùng empty string thay thế
$sql = "INSERT INTO roombook(Name,Email,user_id,Country,Phone,RoomType,Bed,NoofRoom,Meal,cin,cout,stat,nodays) VALUES ('$Name','$Email',$user_id_str,'$Country','$Phone','$RoomType','','$numRooms','$Service','$cin_escaped','$cout_escaped','$sta',$nodays)";
$result = mysqli_query($conn, $sql);

if (!$result) {
    $error_detail = mysqli_error($conn);
    // Log lỗi chi tiết để debug
    error_log("Booking Error: " . $error_detail);
    error_log("SQL Query: " . $sql);
    $_SESSION['error_message'] = 'Có lỗi xảy ra. Không thể tạo booking. Chi tiết: ' . htmlspecialchars($error_detail) . '. Vui lòng thử lại hoặc liên hệ hỗ trợ.';
    header("Location: ../index.php");
    exit;
}

// Lấy ID booking vừa tạo
$booking_id = mysqli_insert_id($conn);

// Gán các phòng đã chọn cho booking
$assignSuccess = assignRoomsToBooking($conn, $booking_id, $roomData, $cin, $cout);

// Kiểm tra lỗi SQL nếu có
if (!$assignSuccess) {
    // Nếu gán phòng thất bại, xóa booking và thông báo
    $deleteSql = "DELETE FROM roombook WHERE id = $booking_id";
    mysqli_query($conn, $deleteSql);
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi gán phòng. Vui lòng thử lại.';
    header("Location: ../index.php");
    exit;
}

// Lấy số phòng đã gán để hiển thị
$roomNumbers = getBookingRoomNumbers($conn, $booking_id);

// Tính toán giá (VND)
// ⚠️ GIÁ TEST (Tiền Trăm) - Phù hợp cho test MoMo Sandbox
$type_of_room = 0;
if($RoomType=="Phòng Cao Cấp") {
    $type_of_room = 500000; // 500k VND (test) - Production: 3,000,000
}
else if($RoomType=="Phòng Sang Trọng") {
    $type_of_room = 300000; // 300k VND (test) - Production: 2,000,000
}
else if($RoomType=="Nhà Khách") {
    $type_of_room = 200000; // 200k VND (test) - Production: 1,500,000
}
else if($RoomType=="Phòng Đơn") {
    $type_of_room = 100000; // 100k VND (test) - Production: 1,000,000
}

// Tính giá dịch vụ (bỏ Bed)
$type_of_service = 0;
if($Service=="Chỉ phòng") {
    $type_of_service = 0;
}
else if($Service=="Bữa sáng") {
    $type_of_service = $type_of_room * 0.1; // 10% giá phòng
}
else if($Service=="Nửa suất") {
    $type_of_service = $type_of_room * 0.2; // 20% giá phòng
}
else if($Service=="Toàn bộ") {
    $type_of_service = $type_of_room * 0.3; // 30% giá phòng
}

// Lấy số ngày
$get_days = "SELECT nodays FROM roombook WHERE id = '$booking_id'";
$days_result = mysqli_query($conn, $get_days);
$days_row = mysqli_fetch_array($days_result);
$noofday = $days_row['nodays'];

$ttot = $type_of_room * $noofday * $numRooms;
$servicetot = $type_of_service * $noofday;
$fintot = $ttot + $servicetot;

// Tạo payment
// Lưu ý: Bed column là NOT NULL trong database, nên dùng empty string thay vì NULL
$bed_value = ''; // Empty string vì đã bỏ chọn loại giường
$cin_payment = mysqli_real_escape_string($conn, $cin);
$cout_payment = mysqli_real_escape_string($conn, $cout);

$psql = "INSERT INTO payment(id,Name,Email,RoomType,Bed,NoofRoom,cin,cout,noofdays,roomtotal,bedtotal,meal,mealtotal,finaltotal) VALUES ('$booking_id', '$Name', '$Email', '$RoomType', '$bed_value', '$numRooms', '$cin_payment', '$cout_payment', '$noofday', '$ttot', 0, '$Service', '$servicetot', '$fintot')";
$paymentResult = mysqli_query($conn, $psql);

// Kiểm tra lỗi khi tạo payment
if (!$paymentResult) {
    $error_detail = mysqli_error($conn);
    // Log lỗi chi tiết để debug
    error_log("Payment Error: " . $error_detail);
    error_log("Payment SQL Query: " . $psql);
    error_log("Booking ID: " . $booking_id);
    
    // Nếu tạo payment thất bại, xóa booking và assignments, sau đó thông báo lỗi
    releaseRoomsFromBooking($conn, $booking_id);
    $deleteSql = "DELETE FROM roombook WHERE id = $booking_id";
    mysqli_query($conn, $deleteSql);
    
    // Hiển thị lỗi chi tiết
    $error_msg = 'Có lỗi xảy ra khi tạo thông tin thanh toán.';
    if (!empty($error_detail)) {
        $error_msg .= ' Chi tiết: ' . htmlspecialchars($error_detail);
    } else {
        $error_msg .= ' Không thể lưu thông tin thanh toán vào database.';
    }
    $error_msg .= ' Vui lòng thử lại hoặc liên hệ hỗ trợ.';
    
    $_SESSION['error_message'] = $error_msg;
    header("Location: ../index.php");
    exit;
}

// Chuyển đến trang thanh toán (đảm bảo không có output trước header)
if (ob_get_level()) {
    ob_end_clean();
}
header("Location: ../payment/index.php?id=$booking_id");
exit;
