<?php
    session_start();
    session_unset();  // Xóa tất cả session variables
    session_destroy();  // Hủy session
    
    // Xóa session cookie trên browser
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Admin logout → Quay về trang chính (không phải admin)
    header("Location: ../index.php");
    exit();
?>