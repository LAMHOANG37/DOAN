<?php
require_once '../../config.php';
require_once '../config.php';
session_start();

$gateway = '';
$booking_id = '';

// Detect gateway từ parameters
if(isset($_GET['vnp_TxnRef'])) {
    $gateway = 'vnpay';
    // Parse booking_id từ TxnRef: BOOKING_55_1761554008
    $vnp_TxnRef = $_GET['vnp_TxnRef'];
    if(preg_match('/^BOOKING_(\d+)_/', $vnp_TxnRef, $matches)) {
        $booking_id = $matches[1];
    }
} elseif(isset($_GET['orderId'])) {
    $gateway = 'momo';
    // Parse booking_id từ orderId
    if(preg_match('/_(\d+)$/', $_GET['orderId'], $matches)) {
        $booking_id = $matches[1];
    }
} elseif(isset($_GET['apptransid'])) {
    $gateway = 'zalopay';
    $booking_id = $_GET['booking_id'] ?? '';
} elseif(isset($_GET['paymentId'])) {
    $gateway = 'paypal';
    $booking_id = $_GET['booking_id'] ?? '';
}

$success = false;
$message = "";

switch($gateway) {
    case 'momo':
        // MoMo return
        $partnerCode = $_GET['partnerCode'] ?? '';
        $orderId = $_GET['orderId'] ?? '';
        $resultCode = $_GET['resultCode'] ?? '';
        
        if($resultCode == 0) {
            $success = true;
            $message = "Thanh toán MoMo thành công!";
            
            // Update transaction
            $update_sql = "UPDATE payment_transactions SET status='completed', updated_at=NOW() 
                          WHERE booking_id='$booking_id' AND gateway='momo'";
            mysqli_query($conn, $update_sql);
        } else {
            $message = "Thanh toán MoMo thất bại!";
        }
        break;
        
    case 'vnpay':
        // VNPay return
        $vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
        $vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
        
        // Verify signature
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                // URLENCODE theo VNPay sample code
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);
        
        if($secureHash == $vnp_SecureHash && $vnp_ResponseCode == '00') {
            $success = true;
            $message = "Thanh toán VNPay thành công!";
            
            $update_sql = "UPDATE payment_transactions SET status='completed', updated_at=NOW() 
                          WHERE booking_id='$booking_id' AND gateway='vnpay'";
            mysqli_query($conn, $update_sql);
        } else {
            $message = "Thanh toán VNPay thất bại!";
        }
        break;
        
    case 'zalopay':
        // ZaloPay return
        $status = $_GET['status'] ?? '';
        
        if($status == '1') {
            $success = true;
            $message = "Thanh toán ZaloPay thành công!";
            
            $update_sql = "UPDATE payment_transactions SET status='completed', updated_at=NOW() 
                          WHERE booking_id='$booking_id' AND gateway='zalopay'";
            mysqli_query($conn, $update_sql);
        } else {
            $message = "Thanh toán ZaloPay thất bại!";
        }
        break;
        
    case 'paypal':
        // PayPal return
        $token = $_GET['token'] ?? '';
        
        if($token) {
            $success = true;
            $message = "Thanh toán PayPal thành công!";
            
            $update_sql = "UPDATE payment_transactions SET status='completed', updated_at=NOW() 
                          WHERE booking_id='$booking_id' AND gateway='paypal'";
            mysqli_query($conn, $update_sql);
        } else {
            $message = "Thanh toán PayPal đã bị hủy!";
        }
        break;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Thanh Toán - Khách Sạn BlueBird</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .result-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 500px;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 18px;
            margin-top: 30px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-home:hover {
            transform: scale(1.05);
            color: white;
        }
    </style>
</head>
<body>
    <div class="result-card">
        <?php if($success): ?>
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Thanh Toán Thành Công!</h1>
            <p>Đặt phòng của bạn đã được xác nhận.</p>
            <p>Mã Đặt Phòng: <strong>#<?php echo $booking_id; ?></strong></p>
            <p>Phương Thức Thanh Toán: <strong><?php echo strtoupper($gateway); ?></strong></p>
            <p>Vui lòng kiểm tra email để xem chi tiết xác nhận.</p>
        <?php else: ?>
            <i class="fas fa-times-circle error-icon"></i>
            <h1>Thanh Toán Thất Bại!</h1>
            <p><?php echo $message; ?></p>
            <p>Vui lòng thử lại hoặc liên hệ bộ phận hỗ trợ.</p>
        <?php endif; ?>
        
        <a href="../../index.php" class="btn-home">
            <i class="fas fa-home"></i> Quay Về Trang Chủ
        </a>
    </div>

    <script>
        <?php if($success): ?>
        swal({
            title: "Thành Công!",
            text: "Thanh toán hoàn tất thành công!",
            icon: "success",
            button: false,
            timer: 2000
        });
        <?php endif; ?>
    </script>
</body>
</html>

