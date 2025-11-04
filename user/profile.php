<?php
session_start();
include '../config.php';

// Check login
$usermail = "";
$usermail = $_SESSION['usermail'];
if($usermail == true){
    // OK
}else{
    header("location: ../login.php");
    exit();
}

// Get user info
$sql = "SELECT * FROM signup WHERE Email = '$usermail'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_array($result);

if(!$user) {
    header("location: ../logout.php");
    exit();
}

// Get booking statistics
$user_id = $user['UserID'];
$total_bookings_sql = "SELECT COUNT(*) as total FROM roombook WHERE Email = '$usermail'";
$total_bookings_result = mysqli_query($conn, $total_bookings_sql);
$total_bookings = mysqli_fetch_array($total_bookings_result)['total'];

$total_spent_sql = "SELECT SUM(finaltotal) as total FROM payment WHERE Email = '$usermail'";
$total_spent_result = mysqli_query($conn, $total_spent_sql);
$total_spent = mysqli_fetch_array($total_spent_result)['total'] ?? 0;

// Get recent bookings with payment status
$recent_bookings_sql = "SELECT rb.*, 
                               pt.status as payment_status,
                               pt.transaction_id,
                               pt.gateway
                        FROM roombook rb 
                        LEFT JOIN payment_transactions pt ON rb.id = pt.booking_id 
                        WHERE rb.Email = '$usermail' 
                        ORDER BY rb.id DESC 
                        LIMIT 5";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_sql);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản Của Tôi - BlueBird Hotel</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    
    <!-- Sweet Alert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./css/profile.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <img src="../image/bluebirdlogo.png" alt="logo" height="40" class="me-2">
                <strong>BLUEBIRD HOTEL</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Trang Chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php"><i class="fas fa-user"></i> Tài Khoản</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar">
                <div class="profile-section text-center">
                    <div class="avatar-container">
                        <img src="<?php echo !empty($user['avatar']) ? './uploads/avatars/'.$user['avatar'] : '../image/Profile.png'; ?>" 
                             alt="Avatar" class="avatar" id="avatarPreview">
                        <div class="avatar-overlay">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#uploadAvatarModal">
                                <i class="fas fa-camera"></i>
                            </a>
                        </div>
                    </div>
                    <h4 class="mt-3"><?php echo htmlspecialchars($user['Username']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['Email']); ?></p>
                </div>

                <div class="menu-section">
                    <a href="#overview" class="menu-item active" onclick="showSection('overview')">
                        <i class="fas fa-th-large"></i> Tổng Quan
                    </a>
                    <a href="#edit-profile" class="menu-item" onclick="showSection('edit-profile')">
                        <i class="fas fa-user-edit"></i> Sửa Thông Tin
                    </a>
                    <a href="#my-bookings" class="menu-item" onclick="showSection('my-bookings')">
                        <i class="fas fa-bed"></i> Phòng Đã Đặt
                    </a>
                    <a href="#change-password" class="menu-item" onclick="showSection('change-password')">
                        <i class="fas fa-key"></i> Đổi Mật Khẩu
                    </a>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-md-9 content-area">
                <!-- Overview Section -->
                <div id="overview" class="content-section active">
                    <h2 class="section-title"><i class="fas fa-th-large"></i> Tổng Quan</h2>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo $total_bookings; ?></h3>
                                    <p>Tổng Booking</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo number_format($total_spent, 0, ',', '.') . 'd'; ?></h3>
                                    <p>Tổng Chi Tiêu</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></h3>
                                    <p>Thành Viên Từ</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Bookings -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Booking Gần Đây</h5>
                        </div>
                        <div class="card-body">
                            <?php if(mysqli_num_rows($recent_bookings_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Loại Phòng</th>
                                                <th>Số Phòng</th>
                                                <th>Check-In</th>
                                                <th>Check-Out</th>
                                                <th>Số Ngày</th>
                                                <th>Trạng Thái</th>
                                                <th>Thanh Toán</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $row_number = 1; // Đánh số từ 1
                                            while($booking = mysqli_fetch_array($recent_bookings_result)): 
                                                // Tính số ngày nếu không có trong DB
                                                $noofdays = $booking['nodays'] ?? 0;
                                                if($noofdays == 0 && !empty($booking['cin']) && !empty($booking['cout'])) {
                                                    $noofdays = (strtotime($booking['cout']) - strtotime($booking['cin'])) / (60 * 60 * 24);
                                                }
                                                
                                                // Xác định trạng thái thanh toán
                                                $payment_status = $booking['payment_status'] ?? 'pending';
                                                $payment_badge = 'secondary';
                                                $payment_text = 'Chưa thanh toán';
                                                
                                                if($payment_status == 'success' || $payment_status == 'completed') {
                                                    $payment_badge = 'success';
                                                    $payment_text = 'Đã thanh toán';
                                                } elseif($payment_status == 'failed') {
                                                    $payment_badge = 'danger';
                                                    $payment_text = 'Thất bại';
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $row_number++; ?></td>
                                                    <td><?php echo htmlspecialchars($booking['RoomType']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $roomNumbers = $booking['room_numbers'] ?? null;
                                                        if($roomNumbers) {
                                                            echo "<span style='color: #0d6efd; font-weight: 600;'>$roomNumbers</span>";
                                                        } else {
                                                            echo "<span style='color: #999;'>-</span>";
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($booking['cin'])); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($booking['cout'])); ?></td>
                                                    <td><?php echo $noofdays; ?> ngày</td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $booking['stat'] == 'Confirm' ? 'success' : 'warning'; ?>">
                                                            <?php echo $booking['stat']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if($payment_status == 'pending' || empty($payment_status)): ?>
                                                            <a href="../payment/index.php?id=<?php echo $booking['id']; ?>" 
                                                               class="badge bg-<?php echo $payment_badge; ?> text-decoration-none"
                                                               style="cursor: pointer; transition: all 0.3s;"
                                                               onmouseover="this.style.transform='scale(1.05)'; this.style.opacity='0.8';"
                                                               onmouseout="this.style.transform='scale(1)'; this.style.opacity='1';">
                                                                <i class="fas fa-credit-card"></i>
                                                                <?php echo $payment_text; ?> →
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-<?php echo $payment_badge; ?>">
                                                                <?php echo $payment_text; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="#my-bookings" class="btn btn-primary" onclick="showSection('my-bookings')">Xem Tất Cả <i class="fas fa-arrow-right"></i></a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Bạn chưa có booking nào</p>
                                    <a href="../index.php#secondsection" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Đặt Phòng Ngay
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Section -->
                <div id="edit-profile" class="content-section">
                    <h2 class="section-title"><i class="fas fa-user-edit"></i> Sửa Thông Tin Cá Nhân</h2>
                    <div class="card">
                        <div class="card-body">
                            <form id="editProfileForm" method="POST" action="update-profile.php">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Họ Tên</label>
                                        <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['Email']); ?>" disabled>
                                        <small class="text-muted">Email không thể thay đổi</small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Số Điện Thoại</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Địa Chỉ</label>
                                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu Thay Đổi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- My Bookings Section -->
                <div id="my-bookings" class="content-section">
                    <h2 class="section-title"><i class="fas fa-bed"></i> Phòng Đã Đặt</h2>
                    <iframe src="my-bookings.php" style="width:100%; height:800px; border:none;"></iframe>
                </div>

                <!-- Change Password Section -->
                <div id="change-password" class="content-section">
                    <h2 class="section-title"><i class="fas fa-key"></i> Đổi Mật Khẩu</h2>
                    <div class="card">
                        <div class="card-body">
                            <form id="changePasswordForm" method="POST" action="change-password.php">
                                <div class="mb-3">
                                    <label class="form-label">Mật Khẩu Hiện Tại</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mật Khẩu Mới</label>
                                    <input type="password" class="form-control" name="new_password" id="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Xác Nhận Mật Khẩu Mới</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key"></i> Đổi Mật Khẩu
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Avatar Modal -->
    <div class="modal fade" id="uploadAvatarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-camera"></i> Đổi Ảnh Đại Diện</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadAvatarForm" method="POST" action="upload-avatar.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <img id="previewImage" src="<?php echo !empty($user['avatar']) ? './uploads/avatars/'.$user['avatar'] : '../image/Profile.png'; ?>" 
                                 alt="Preview" style="width: 200px; height: 200px; object-fit: cover; border-radius: 50%; border: 3px solid #ddd;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Chọn Ảnh</label>
                            <input type="file" class="form-control" name="avatar" id="avatarInput" accept="image/*" required>
                            <small class="text-muted">Chấp nhận: JPG, PNG, GIF (Max: 2MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Section Navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked menu item
            event.target.classList.add('active');
        }

        // Image Preview
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Form Validation
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if(newPassword !== confirmPassword) {
                e.preventDefault();
                swal({
                    title: 'Lỗi!',
                    text: 'Mật khẩu xác nhận không khớp',
                    icon: 'error',
                });
                return false;
            }
        });

        // Check for success messages
        <?php if(isset($_GET['success'])): ?>
            swal({
                title: 'Thành công!',
                text: '<?php echo $_GET['success']; ?>',
                icon: 'success',
            });
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            swal({
                title: 'Lỗi!',
                text: '<?php echo $_GET['error']; ?>',
                icon: 'error',
            });
        <?php endif; ?>
    </script>
</body>
</html>

