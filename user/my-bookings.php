<?php
session_start();
include '../config.php';

// Check login
$usermail = $_SESSION['usermail'] ?? '';
if(empty($usermail)){
    die("Unauthorized");
}

// Get all bookings with payment status
$sql = "SELECT rb.*, 
               p.finaltotal,
               pt.status as payment_status,
               pt.transaction_id,
               pt.gateway,
               pt.created_at as payment_date
        FROM roombook rb 
        LEFT JOIN payment p ON rb.id = p.id 
        LEFT JOIN payment_transactions pt ON rb.id = pt.booking_id
        WHERE rb.Email = '$usermail' 
        ORDER BY rb.id DESC";
$result = mysqli_query($conn, $sql);

// Check for SQL errors
if(!$result) {
    die("SQL Error: " . mysqli_error($conn) . "<br>Query: " . $sql);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }
        
        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #0d6efd;
            transition: all 0.3s;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e3f2fd;
        }
        
        .booking-id {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .booking-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .detail-value i {
            color: #0d6efd;
            margin-right: 5px;
        }
        
        .price-tag {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 20px;
            font-weight: 700;
            display: inline-block;
            margin-top: 15px;
        }
        
        .badge {
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #e0e0e0;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if(mysqli_num_rows($result) > 0): ?>
        <?php 
        $row_number = 1; // Đánh số từ 1 (theo thứ tự thời gian)
        while($booking = mysqli_fetch_array($result)): 
            // Tính số ngày nếu không có trong DB
            $noofdays = $booking['nodays'] ?? 0;
            if($noofdays == 0 && !empty($booking['cin']) && !empty($booking['cout'])) {
                $noofdays = (strtotime($booking['cout']) - strtotime($booking['cin'])) / (60 * 60 * 24);
            }
            
            // Xác định trạng thái thanh toán
            $payment_status = $booking['payment_status'] ?? 'pending';
            $payment_badge = 'secondary';
            $payment_text = 'Chưa thanh toán';
            $payment_icon = 'fa-clock';
            
            if($payment_status == 'success' || $payment_status == 'completed') {
                $payment_badge = 'success';
                $payment_text = 'Đã thanh toán';
                $payment_icon = 'fa-check-circle';
            } elseif($payment_status == 'failed') {
                $payment_badge = 'danger';
                $payment_text = 'Thanh toán thất bại';
                $payment_icon = 'fa-times-circle';
            }
        ?>
            <div class="booking-card">
                <div class="booking-header">
                    <div class="booking-id">
                        <i class="fas fa-bed"></i> Booking #<?php echo $row_number++; ?>
                    </div>
                    <div class="booking-status">
                        <span class="badge bg-<?php echo $booking['stat'] == 'Confirm' ? 'success' : 'warning'; ?>">
                            <?php echo $booking['stat']; ?>
                        </span>
                        <?php if($payment_status == 'pending' || empty($payment_status)): ?>
                            <a href="../payment/index.php?id=<?php echo $booking['id']; ?>" 
                               class="badge bg-<?php echo $payment_badge; ?> text-decoration-none"
                               style="cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 5px;"
                               onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)';"
                               onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='';">
                                <i class="fas fa-credit-card"></i>
                                <?php echo $payment_text; ?> →
                            </a>
                        <?php else: ?>
                            <span class="badge bg-<?php echo $payment_badge; ?>">
                                <i class="fas <?php echo $payment_icon; ?>"></i>
                                <?php echo $payment_text; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="booking-details">
                    <div class="detail-item">
                        <div class="detail-label">Loại Phòng</div>
                        <div class="detail-value">
                            <i class="fas fa-door-open"></i>
                            <?php echo $booking['RoomType']; ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Loại Giường</div>
                        <div class="detail-value">
                            <i class="fas fa-bed"></i>
                            <?php echo $booking['Bed']; ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Số Phòng</div>
                        <div class="detail-value">
                            <i class="fas fa-hashtag"></i>
                            <?php echo $booking['NoofRoom']; ?> phòng
                        </div>
                    </div>
                    
                    <?php if(!empty($booking['room_numbers'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Mã Phòng</div>
                        <div class="detail-value" style="color: #0d6efd; font-weight: 600;">
                            <i class="fas fa-key"></i>
                            <?php echo $booking['room_numbers']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <div class="detail-label">Check-In</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar-check"></i>
                            <?php echo date('d/m/Y', strtotime($booking['cin'])); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Check-Out</div>
                        <div class="detail-value">
                            <i class="fas fa-calendar-times"></i>
                            <?php echo date('d/m/Y', strtotime($booking['cout'])); ?>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Số Ngày</div>
                        <div class="detail-value">
                            <i class="fas fa-moon"></i>
                            <?php echo $noofdays; ?> đêm
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Bữa Ăn</div>
                        <div class="detail-value">
                            <i class="fas fa-utensils"></i>
                            <?php echo $booking['Meal'] ?? 'N/A'; ?>
                        </div>
                    </div>
                    
                    <?php if(!empty($booking['payment_date'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Ngày Thanh Toán</div>
                        <div class="detail-value">
                            <i class="fas fa-clock"></i>
                            <?php echo date('d/m/Y H:i', strtotime($booking['payment_date'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($booking['gateway'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Phương Thức</div>
                        <div class="detail-value">
                            <i class="fas fa-credit-card"></i>
                            <?php echo strtoupper($booking['gateway']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if(!empty($booking['finaltotal'])): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <div class="price-tag">
                            <i class="fas fa-tag"></i>
                            Tổng: ₫<?php echo number_format($booking['finaltotal'], 0, ',', '.'); ?>
                        </div>
                        
                        <?php if($payment_status == 'pending' || empty($payment_status)): ?>
                            <a href="../payment/index.php?id=<?php echo $booking['id']; ?>" 
                               class="btn btn-primary"
                               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                                      border: none; 
                                      padding: 12px 30px; 
                                      border-radius: 10px; 
                                      font-weight: 600;
                                      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                                      transition: all 0.3s;"
                               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.6)';"
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.4)';">
                                <i class="fas fa-credit-card"></i> Thanh Toán Ngay
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-bed"></i>
            <h3>Chưa Có Booking</h3>
            <p class="text-muted">Bạn chưa đặt phòng nào</p>
            <a href="../index.php#secondsection" class="btn btn-primary btn-lg mt-3">
                <i class="fas fa-plus-circle"></i> Đặt Phòng Ngay
            </a>
        </div>
    <?php endif; ?>
</body>
</html>

