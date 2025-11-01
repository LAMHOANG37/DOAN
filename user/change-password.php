<?php
session_start();
include '../config.php';

// Check login
$usermail = $_SESSION['usermail'] ?? '';
if(empty($usermail)){
    header("location: ../login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = mysqli_real_escape_string($conn, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    // Check if new passwords match
    if($new_password !== $confirm_password) {
        header("location: profile.php?error=" . urlencode("Mật khẩu xác nhận không khớp!"));
        exit();
    }
    
    // Check current password
    $check_sql = "SELECT Password FROM signup WHERE Email='$usermail' AND Password='$current_password'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if(mysqli_num_rows($check_result) == 0) {
        header("location: profile.php?error=" . urlencode("Mật khẩu hiện tại không đúng!"));
        exit();
    }
    
    // Update password
    $update_sql = "UPDATE signup SET Password='$new_password' WHERE Email='$usermail'";
    
    if(mysqli_query($conn, $update_sql)) {
        header("location: profile.php?success=" . urlencode("Đổi mật khẩu thành công!"));
    } else {
        header("location: profile.php?error=" . urlencode("Có lỗi xảy ra. Vui lòng thử lại!"));
    }
} else {
    header("location: profile.php");
}
?>




