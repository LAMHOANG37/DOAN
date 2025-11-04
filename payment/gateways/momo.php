<?php
/**
 * MoMo Payment Gateway Integration
 * BlueBird Hotel Management System
 * 
 * Flow:
 * 1. Lấy thông tin booking từ database
 * 2. Tạo chữ ký (signature) theo chuẩn MoMo
 * 3. Gửi request đến MoMo API
 * 4. Lưu transaction vào database
 * 5. Redirect user đến trang thanh toán MoMo
 */

header('Content-type: text/html; charset=utf-8');

require_once '../../config.php';
require_once '../config.php';
session_start();

// ===================== FUNCTION GỬI REQUEST =====================
function execPostRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    // Execute POST request
    $result = curl_exec($ch);
    
    // Check for cURL errors
    if(curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return json_encode(['error' => $error]);
    }
    
    curl_close($ch);
    return $result;
}

// ===================== LẤY THÔNG TIN BOOKING =====================
$booking_id = $_GET['id'] ?? '';

if(empty($booking_id)) {
    die("Booking ID không hợp lệ!");
}

$sql = "SELECT * FROM payment WHERE id = '$booking_id'";
$result = mysqli_query($conn, $sql);
$payment = mysqli_fetch_array($result);

if(!$payment) {
    die("Không tìm thấy thông tin thanh toán!");
}

// ===================== CẤU HÌNH MOMO =====================
$endpoint = MOMO_ENDPOINT;
$partnerCode = MOMO_PARTNER_CODE;
$accessKey = MOMO_ACCESS_KEY;
$secretKey = MOMO_SECRET_KEY;

// ===================== THÔNG TIN ĐƠN HÀNG =====================
// Xử lý amount: Phải là string số nguyên, không có dấu phân cách, không có decimal
$rawAmount = $payment['finaltotal'];

// Chuyển sang float trước (xử lý decimal), rồi mới sang integer (loại bỏ .00)
// Ví dụ: "33600.00" → 33600.0 → 33600
$amount = (string)intval(floatval($rawAmount));

// Debug: Hiển thị để kiểm tra
// echo "Raw Amount from DB: " . $rawAmount . "<br>";
// echo "Amount send to MoMo: " . $amount . "<br>";
// echo "Formatted: " . number_format($amount, 0, ',', '.') . " VND<br>";
// exit(); // Uncomment để test

// Validate amount
if(empty($amount) || $amount == '0') {
    die("Lỗi: Số tiền không hợp lệ! Amount = " . $amount);
}

// Kiểm tra giới hạn của MoMo (Sandbox: 1,000 - 50,000,000 VND)
$amountInt = intval($amount);
if($amountInt < 1000) {
    die("Lỗi: Số tiền tối thiểu là 1,000 VND! Số tiền hiện tại: " . number_format($amountInt, 0, ',', '.') . " VND");
}
if($amountInt > 50000000) {
    // Hiển thị thông báo lỗi đẹp
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <title>Lỗi Giới Hạn Thanh Toán</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet'>
        <script src='https://unpkg.com/sweetalert/dist/sweetalert.min.js'></script>
        <style>
            body { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .error-card {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                max-width: 600px;
                text-align: center;
            }
            .error-icon {
                font-size: 80px;
                color: #dc3545;
                margin-bottom: 20px;
            }
            h2 { color: #dc3545; margin-bottom: 20px; }
            .amount-info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: left;
            }
            .btn-home {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                padding: 15px 40px;
                border-radius: 50px;
                font-size: 18px;
                margin-top: 20px;
                text-decoration: none;
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class='error-card'>
            <div class='error-icon'>⚠️</div>
            <h2>Vượt Quá Giới Hạn Thanh Toán MoMo</h2>
            <p style='font-size: 18px;'>Số tiền của bạn vượt quá giới hạn cho phép của MoMo Sandbox!</p>
            
            <div class='amount-info'>
                <table style='width: 100%;'>
                    <tr>
                        <td><strong>Số tiền đơn hàng:</strong></td>
                        <td style='text-align: right; color: #dc3545; font-size: 20px;'>" . number_format($amountInt, 0, ',', '.') . " VND</td>
                    </tr>
                    <tr><td colspan='2'><hr></td></tr>
                    <tr>
                        <td><strong>Giới hạn tối đa (Sandbox):</strong></td>
                        <td style='text-align: right; color: #28a745; font-size: 20px;'>50.000.000 VND</td>
                    </tr>
                    <tr>
                        <td><strong>Vượt quá:</strong></td>
                        <td style='text-align: right; color: #dc3545;'>" . number_format($amountInt - 50000000, 0, ',', '.') . " VND</td>
                    </tr>
                </table>
            </div>

            <div class='alert alert-warning' style='text-align: left;'>
                <strong>💡 Giải pháp:</strong>
                <ul style='margin-top: 10px; margin-bottom: 0;'>
                    <li>Môi trường <strong>Sandbox</strong> chỉ cho phép tối đa <strong>50 triệu VND</strong></li>
                    <li>Để test, vui lòng đặt phòng với tổng tiền dưới 50 triệu</li>
                    <li>Hoặc sử dụng phương thức thanh toán khác (VNPay, ZaloPay...)</li>
                    <li>Khi lên Production, giới hạn có thể cao hơn</li>
                </ul>
            </div>

            <a href='../../index.php' class='btn-home'>← Quay Về Trang Chủ</a>
        </div>
        
        <script>
            swal({
                title: 'Vượt Giới Hạn!',
                text: 'Số tiền thanh toán vượt quá 50 triệu VND',
                icon: 'warning',
                button: 'Đã hiểu'
            });
        </script>
    </body>
    </html>";
    exit();
}

// ===================== KIỂM TRA PHƯƠNG THỨC THANH TOÁN =====================
// Kiểm tra nếu đã chọn phương thức thanh toán (từ form POST hoặc GET)
$payment_method = $_POST['payment_method'] ?? $_GET['method'] ?? '';

// Nếu đã chọn phương thức, thực hiện thanh toán
if($payment_method == 'qr' || $payment_method == 'account') {
    // Xác định requestType dựa trên phương thức
    $requestType = ($payment_method == 'qr') ? 'captureWallet' : 'payWithATM';
    
    // OrderInfo: Chỉ dùng ký tự ASCII đơn giản
    $orderInfo = "Thanh toan dat phong " . $booking_id;
    
    // ⚠️ FIX: Thêm microseconds để tránh trùng orderId khi test nhanh
    $orderId = time() . substr((string)microtime(), 2, 6); // Ví dụ: 1761728863123456
    $redirectUrl = MOMO_RETURN_URL . "?gateway=momo&booking_id=" . $booking_id;
    $ipnUrl = MOMO_NOTIFY_URL . "?gateway=momo";
    $extraData = ""; // Bắt buộc phải có, dù là chuỗi rỗng
    
    $requestId = time() . substr((string)microtime(), 2, 6); // Unique request ID
    
    // ===================== TẠO CHỮ KÝ (SIGNATURE) =====================
    // ⚠️ QUAN TRỌNG: Các tham số phải theo thứ tự alphabet!
    $rawHash = "accessKey=" . $accessKey . 
               "&amount=" . $amount . 
               "&extraData=" . $extraData . 
               "&ipnUrl=" . $ipnUrl . 
               "&orderId=" . $orderId . 
               "&orderInfo=" . $orderInfo . 
               "&partnerCode=" . $partnerCode . 
               "&redirectUrl=" . $redirectUrl . 
               "&requestId=" . $requestId . 
               "&requestType=" . $requestType;
    
    // Ký bằng HMAC SHA256
    $signature = hash_hmac("sha256", $rawHash, $secretKey);
    
    // ===================== CHUẨN BỊ DỮ LIỆU GỬI =====================
    // Đảm bảo tất cả các giá trị không có khoảng trắng thừa
    $data = array(
        'partnerCode' => trim($partnerCode),
        'partnerName' => "Test",
        'storeId' => "MomoTestStore",
        'requestId' => trim($requestId),
        'amount' => trim($amount),
        'orderId' => trim($orderId),
        'orderInfo' => trim($orderInfo),
        'redirectUrl' => trim($redirectUrl),
        'ipnUrl' => trim($ipnUrl),
        'lang' => 'vi',
        'extraData' => $extraData,
        'requestType' => trim($requestType),
        'signature' => trim($signature)
    );
    
    // ===================== GỬI REQUEST ĐẾN MOMO =====================
    $result = execPostRequest($endpoint, json_encode($data));
    $jsonResult = json_decode($result, true);
    
    // ===================== XỬ LÝ KẾT QUẢ =====================
    if(isset($jsonResult['payUrl']) && !empty($jsonResult['payUrl'])) {
        // Lưu thông tin transaction vào database
        $trans_sql = "INSERT INTO payment_transactions (booking_id, gateway, transaction_id, amount, status, created_at) 
                      VALUES ('$booking_id', 'momo', '$orderId', '$amount', 'pending', NOW())";
        
        if(mysqli_query($conn, $trans_sql)) {
            // Redirect đến trang thanh toán MoMo
            header('Location: ' . $jsonResult['payUrl']);
            exit();
        } else {
            echo "<h3>Lỗi: Không thể lưu thông tin giao dịch!</h3>";
            echo "<p>Error: " . mysqli_error($conn) . "</p>";
            exit();
        }
    } else {
        // Hiển thị lỗi từ MoMo
        echo "<!DOCTYPE html>
        <html lang='vi'>
        <head>
            <meta charset='UTF-8'>
            <title>Lỗi thanh toán MoMo</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' rel='stylesheet'>
            <style>
                body { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .error-card {
                    background: white;
                    padding: 40px;
                    border-radius: 20px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class='error-card'>
                <h2 style='color: #dc3545;'>❌ Lỗi Thanh Toán MoMo</h2>
                <p><strong>Message:</strong> " . ($jsonResult['message'] ?? 'Unknown error') . "</p>
                <p><strong>ResultCode:</strong> " . ($jsonResult['resultCode'] ?? 'N/A') . "</p>
                <hr>
                <h4>Chi tiết request:</h4>
                <pre style='text-align: left; background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
        echo "Partner Code: $partnerCode\n";
        echo "Order ID: $orderId\n";
        echo "Amount: " . number_format($amount, 0, ',', '.') . " VND\n";
        echo "Request Type: $requestType\n";
        echo "\nFull Response:\n";
        print_r($jsonResult);
        echo "</pre>
                <a href='../../index.php' class='btn btn-primary mt-3'>Quay lại trang chủ</a>
            </div>
        </body>
        </html>";
        exit();
    }
}

// ===================== NẾU CHƯA CHỌN PHƯƠNG THỨC, HIỂN THỊ TRANG CHỌN =====================
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn Phương Thức Thanh Toán - BlueBird Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payment-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .payment-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .payment-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .payment-header .amount {
            font-size: 32px;
            font-weight: 700;
            margin-top: 10px;
        }
        .payment-body {
            padding: 40px;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        .method-card {
            border: 3px solid #e0e0e0;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            position: relative;
        }
        .method-card:hover {
            border-color: #ff6b6b;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.2);
        }
        .method-card.qr-method {
            background: #f5f5f5;
        }
        .method-card.account-method {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        .method-card.account-method:hover {
            border-color: #ff6b6b;
        }
        .method-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .method-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        .method-card.account-method .method-title {
            color: white;
        }
        .method-description {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }
        .method-card.account-method .method-description {
            color: rgba(255, 255, 255, 0.9);
        }
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: left;
        }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }
        .method-card.selected {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
        }
        .method-card.qr-method.selected {
            background: #d4edda;
        }
        .method-card.account-method.selected {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        #submitBtn {
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 50px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h2><i class="fas fa-mobile-alt"></i> Thanh Toán MoMo</h2>
            <div class="amount"><?php echo number_format($amountInt, 0, ',', '.'); ?> VND</div>
        </div>
        
        <div class="payment-body">
            <div class="order-info">
                <p><strong>Mã đơn hàng:</strong> #<?php echo $booking_id; ?></p>
                <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($payment['Name']); ?></p>
            </div>
            
            <h3 style="text-align: center; margin-bottom: 10px; color: #333;">
                Thanh toán theo những gì bạn muốn
            </h3>
            
            <form method="POST" action="">
                <input type="hidden" name="payment_method" id="payment_method" value="">
                
                <div class="payment-methods">
                    <!-- Phương thức 1: QR Code -->
                    <div class="method-card qr-method" id="qrMethod" onclick="selectMethod('qr')">
                        <div class="method-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="method-title">Thanh toán bằng mã QR</div>
                        <div class="method-description">
                            Quét mã QR bằng app MoMo hoặc ứng dụng ngân hàng để thanh toán nhanh chóng
                        </div>
                    </div>
                    
                    <!-- Phương thức 2: Tài khoản/ATM -->
                    <div class="method-card account-method" id="accountMethod" onclick="selectMethod('account')">
                        <div class="method-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="method-title">Thanh toán bằng tài khoản</div>
                        <div class="method-description">
                            Nhập số điện thoại và OTP để thanh toán bằng ví MoMo hoặc tài khoản ngân hàng
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled style="display: none;">
                        <i class="fas fa-arrow-right"></i> Tiếp tục thanh toán
                    </button>
                    <br>
                    <a href="../../index.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Về Trang Chủ
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function selectMethod(method) {
            // Set hidden input value
            document.getElementById('payment_method').value = method;
            
            // Enable submit button
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = false;
            submitBtn.style.display = 'inline-block';
            
            // Highlight selected card
            document.querySelectorAll('.method-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            if(method === 'qr') {
                document.getElementById('qrMethod').classList.add('selected');
            } else {
                document.getElementById('accountMethod').classList.add('selected');
            }
        }
    </script>
</body>
</html>
<?php
exit();
?>

