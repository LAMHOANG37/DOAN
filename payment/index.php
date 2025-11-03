<?php
include '../config.php';
session_start();

// Check login
$usermail = $_SESSION['usermail'] ?? '';
if(empty($usermail)){
    $_SESSION['error_message'] = 'Vui lòng đăng nhập để tiếp tục thanh toán.';
    header("location: ../login.php");
    exit();
}

// Get payment details
$id = $_GET['id'] ?? '';

if(empty($id) || !is_numeric($id)) {
    $_SESSION['error_message'] = 'Thông tin thanh toán không hợp lệ.';
    header("location: ../index.php");
    exit();
}

$id = intval($id);
$sql = "SELECT * FROM payment WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if(!$result) {
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi truy vấn thông tin thanh toán.';
    header("location: ../index.php");
    exit();
}

$row = mysqli_fetch_array($result);

if(!$row) {
    $_SESSION['error_message'] = 'Không tìm thấy thông tin thanh toán. Vui lòng thử lại hoặc liên hệ hỗ trợ.';
    header("location: ../index.php");
    exit();
}

$Name = $row['Name'];
$Email = $row['Email'];
$RoomType = $row['RoomType'];
$Bed = $row['Bed'];
$NoofRoom = $row['NoofRoom'];
$cin = $row['cin'];
$cout = $row['cout'];
$Meal = $row['meal'];
$roomtotal = $row['roomtotal'];
$bedtotal = $row['bedtotal'];
$mealtotal = $row['mealtotal'];
$finaltotal = $row['finaltotal'];
$noofdays = $row['noofdays'];

// Calculate price per unit (VND)
// ⚠️ Đồng bộ với giá trong process_booking.php
// ⚠️ GIÁ TEST (Tiền Trăm) - Phù hợp cho test MoMo Sandbox
$type_of_room = 0;
if ($RoomType == "Phòng Cao Cấp") {
    $type_of_room = 500000; // 500k VND (test) - Production: 3,000,000
} else if ($RoomType == "Phòng Sang Trọng") {
    $type_of_room = 300000; // 300k VND (test) - Production: 2,000,000
} else if ($RoomType == "Nhà Khách") {
    $type_of_room = 200000; // 200k VND (test) - Production: 1,500,000
} else if ($RoomType == "Phòng Đơn") {
    $type_of_room = 100000; // 100k VND (test) - Production: 1,000,000
}

// Tính giá bed (nếu Bed là empty string hoặc không có, mặc định = 0)
$type_of_bed = 0;
if ($Bed == "Đơn") {
    $type_of_bed = $type_of_room * 1 / 100;
} else if ($Bed == "Đôi") {
    $type_of_bed = $type_of_room * 2 / 100;
} else if ($Bed == "Ba") {
    $type_of_bed = $type_of_room * 3 / 100;
} else if ($Bed == "Bốn") {
    $type_of_bed = $type_of_room * 4 / 100;
} else if ($Bed == "Không" || $Bed == "" || empty($Bed)) {
    $type_of_bed = 0; // Không có bed charge
}

// Tính giá meal (dựa trên type_of_room thay vì type_of_bed vì đã bỏ bed)
$type_of_meal = 0;
if ($Meal == "Chỉ phòng") {
    $type_of_meal = 0;
} else if ($Meal == "Bữa sáng") {
    $type_of_meal = $type_of_room * 0.1; // 10% giá phòng
} else if ($Meal == "Nửa suất") {
    $type_of_meal = $type_of_room * 0.2; // 20% giá phòng
} else if ($Meal == "Toàn bộ") {
    $type_of_meal = $type_of_room * 0.3; // 30% giá phòng
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - BlueBird Hotel</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"/>
    <!-- Sweet Alert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .booking-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .booking-info h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6c757d;
            font-weight: 500;
        }
        .info-value {
            color: #212529;
            font-weight: 600;
        }
        .price-breakdown {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .price-breakdown h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px dashed #dee2e6;
        }
        .price-item:last-child {
            border-bottom: none;
        }
        .price-label {
            color: #495057;
        }
        .price-value {
            font-weight: 600;
            color: #212529;
        }
        .price-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 3px solid #667eea;
            display: flex;
            justify-content: space-between;
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .payment-methods {
            margin-bottom: 30px;
        }
        .payment-methods h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .method-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .method-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .method-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .method-card.active {
            border-color: #667eea;
            background: #f0f3ff;
        }
        .method-card i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .method-card p {
            margin: 0;
            color: #495057;
            font-weight: 500;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn-pay {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 50px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-pay:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-back:hover {
            background: #667eea;
            color: white;
        }
        .success-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="header">
                <i class="fas fa-check-circle success-icon"></i>
                <h1>Booking Confirmed!</h1>
                <p>Booking ID: #<?php echo $id; ?></p>
            </div>
            
            <div class="content">
                <!-- Booking Information -->
                <div class="booking-info">
                    <h3><i class="fas fa-info-circle"></i> Booking Details</h3>
                    <div class="info-row">
                        <span class="info-label">Guest Name:</span>
                        <span class="info-value"><?php echo $Name; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo $Email; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Room Type:</span>
                        <span class="info-value"><?php echo $RoomType; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Bed Type:</span>
                        <span class="info-value"><?php echo $Bed; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Number of Rooms:</span>
                        <span class="info-value"><?php echo $NoofRoom; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Meal Plan:</span>
                        <span class="info-value"><?php echo $Meal; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-In:</span>
                        <span class="info-value"><?php echo date('d M Y', strtotime($cin)); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-Out:</span>
                        <span class="info-value"><?php echo date('d M Y', strtotime($cout)); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Duration:</span>
                        <span class="info-value"><?php echo $noofdays; ?> Night(s)</span>
                    </div>
                </div>

                <!-- Price Breakdown -->
                <div class="price-breakdown">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Price Breakdown</h3>
                    <div class="price-item">
                        <span class="price-label"><?php echo $RoomType; ?> (₫<?php echo number_format($type_of_room, 0, ',', '.'); ?>/night × <?php echo $noofdays; ?> nights × <?php echo $NoofRoom; ?> room)</span>
                        <span class="price-value">₫<?php echo number_format($roomtotal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="price-item">
                        <span class="price-label">Bed Charges (₫<?php echo number_format($type_of_bed, 0, ',', '.'); ?>/night × <?php echo $noofdays; ?> nights)</span>
                        <span class="price-value">₫<?php echo number_format($bedtotal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="price-item">
                        <span class="price-label">Meal - <?php echo $Meal; ?> (₫<?php echo number_format($type_of_meal, 0, ',', '.'); ?>/day × <?php echo $noofdays; ?> days)</span>
                        <span class="price-value">₫<?php echo number_format($mealtotal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="price-total">
                        <span>Total Amount:</span>
                        <span>₫<?php echo number_format($finaltotal, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="payment-methods">
                    <h3><i class="fas fa-credit-card"></i> Select Payment Method</h3>
                    <div class="method-grid">
                        <div class="method-card active" data-method="momo" data-gateway="gateways/momo.php">
                            <i class="fas fa-mobile-alt"></i>
                            <p>MoMo Wallet</p>
                        </div>
                        <div class="method-card" data-method="vnpay" data-gateway="gateways/vnpay.php">
                            <i class="fas fa-credit-card"></i>
                            <p>VNPay</p>
                        </div>
                        <div class="method-card" data-method="zalopay" data-gateway="gateways/zalopay.php">
                            <i class="fas fa-wallet"></i>
                            <p>ZaloPay</p>
                        </div>
                        <div class="method-card" data-method="paypal" data-gateway="gateways/paypal.php">
                            <i class="fab fa-paypal"></i>
                            <p>PayPal</p>
                        </div>
                        <div class="method-card" data-method="cash" data-gateway="cash">
                            <i class="fas fa-money-bill-wave"></i>
                            <p>Pay at Hotel</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="../index.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                    <button class="btn-pay" onclick="processPayment()">
                        <i class="fas fa-lock"></i> Proceed to Pay ₫<?php echo number_format($finaltotal, 0, ',', '.'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Payment method selection
        document.querySelectorAll('.method-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.method-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Process payment
        function processPayment() {
            const selectedCard = document.querySelector('.method-card.active');
            const gateway = selectedCard.dataset.gateway;
            const method = selectedCard.dataset.method;
            
            if(method === 'cash') {
                swal({
                    title: "Payment Confirmed!",
                    text: "You can pay at the hotel during check-in. Your booking is confirmed!",
                    icon: "success",
                    button: "OK",
                }).then(() => {
                    window.location.href = "../index.php";
                });
            } else {
                swal({
                    title: "Redirecting...",
                    text: "Redirecting to " + method.toUpperCase() + " payment gateway...",
                    icon: "info",
                    timer: 1500,
                    buttons: false,
                }).then(() => {
                    // Redirect to payment gateway
                    window.location.href = gateway + "?id=<?php echo $id; ?>";
                });
            }
        }

        // Print invoice
        function printInvoice() {
            window.print();
        }
    </script>
</body>
</html>

