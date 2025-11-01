<?php
session_start();
include '../config.php';

// Check login
$usermail = $_SESSION['usermail'] ?? '';
if(empty($usermail)){
    header("location: ../login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    $avatar = $_FILES['avatar'];
    
    // Check file errors
    if($avatar['error'] !== UPLOAD_ERR_OK) {
        header("location: profile.php?error=" . urlencode("Lỗi upload file!"));
        exit();
    }
    
    // Check file size (max 2MB)
    if($avatar['size'] > 2 * 1024 * 1024) {
        header("location: profile.php?error=" . urlencode("File quá lớn! Tối đa 2MB"));
        exit();
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $avatar['tmp_name']);
    finfo_close($finfo);
    
    if(!in_array($mime_type, $allowed_types)) {
        header("location: profile.php?error=" . urlencode("Chỉ chấp nhận file JPG, PNG, GIF!"));
        exit();
    }
    
    // Generate unique filename
    $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
    $filename = uniqid('avatar_') . '.' . $ext;
    $upload_path = __DIR__ . '/uploads/avatars/' . $filename;
    
    // Create directory if not exists
    if(!is_dir(__DIR__ . '/uploads/avatars/')) {
        mkdir(__DIR__ . '/uploads/avatars/', 0755, true);
    }
    
    // Move uploaded file
    if(move_uploaded_file($avatar['tmp_name'], $upload_path)) {
        // Get old avatar
        $old_avatar_sql = "SELECT avatar FROM signup WHERE Email='$usermail'";
        $old_result = mysqli_query($conn, $old_avatar_sql);
        $old_data = mysqli_fetch_array($old_result);
        
        // Delete old avatar if exists and not default
        if(!empty($old_data['avatar']) && $old_data['avatar'] != 'default-avatar.png') {
            $old_file = __DIR__ . '/uploads/avatars/' . $old_data['avatar'];
            if(file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        // Update database
        $update_sql = "UPDATE signup SET avatar='$filename' WHERE Email='$usermail'";
        if(mysqli_query($conn, $update_sql)) {
            header("location: profile.php?success=" . urlencode("Cập nhật ảnh đại diện thành công!"));
        } else {
            header("location: profile.php?error=" . urlencode("Lỗi cập nhật database!"));
        }
    } else {
        header("location: profile.php?error=" . urlencode("Lỗi di chuyển file!"));
    }
} else {
    header("location: profile.php");
}
?>




