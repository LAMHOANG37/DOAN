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
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Update user info
    $sql = "UPDATE signup SET Username='$username', phone='$phone', address='$address' WHERE Email='$usermail'";
    
    if(mysqli_query($conn, $sql)) {
        header("location: profile.php?success=" . urlencode("Cập nhật thông tin thành công!"));
    } else {
        header("location: profile.php?error=" . urlencode("Có lỗi xảy ra. Vui lòng thử lại!"));
    }
} else {
    header("location: profile.php");
}
?>

