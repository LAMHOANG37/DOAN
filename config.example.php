<?php

// Copy this file to config.php and update with your database credentials

$server = "localhost";
$username = "root";
$password = "";
$database = "bluebirdhotel";

$conn = mysqli_connect($server,$username,$password,$database);

if(!$conn){
    die("<script>alert('connection Failed.')</script>");
}

// ==================== SESSION CONFIG ====================
// PHẢI SET TRƯỚC KHI session_start() được gọi!
// Session chỉ tồn tại khi browser/tab đang mở
// Khi đóng browser/tab → Tự động logout

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,  // 0 = Cookie xóa khi đóng browser
    'path' => '/',
    'domain' => '',
    'secure' => false,  // Set true nếu dùng HTTPS
    'httponly' => true,  // Bảo mật: không cho JavaScript truy cập cookie
    'samesite' => 'Lax'  // Bảo mật CSRF
]);

// Garbage collection
ini_set('session.gc_maxlifetime', 86400); // 24 giờ
?>

