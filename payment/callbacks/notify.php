<?php
/**
 * Payment IPN Callback Handler
 * BlueBird Hotel Management System
 * Xử lý IPN từ các cổng thanh toán: MoMo, VNPay, ZaloPay
 */

require_once '../../config.php';
require_once '../config.php';

$gateway = $_GET['gateway'] ?? '';

switch($gateway) {
    case 'momo':
        // MoMo callback
        $result = json_decode(file_get_contents('php://input'), true);
        
        if($result['resultCode'] == 0) {
            $orderId = $result['orderId'];
            $update_sql = "UPDATE payment_transactions SET status='completed', updated_at=NOW() 
                          WHERE transaction_id='$orderId'";
            mysqli_query($conn, $update_sql);
        }
        
        // Return response to MoMo
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        break;
    
    case 'vnpay':
        // VNPay IPN Callback (based on Official SDK)
        $inputData = array();
        $returnData = array();
        
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        
        $i = 0;
        $hashData = "";
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
        $vnpTranId = $inputData['vnp_TransactionNo'] ?? ''; // Mã giao dịch tại VNPAY
        $vnp_BankCode = $inputData['vnp_BankCode'] ?? '';   // Ngân hàng thanh toán
        $vnp_Amount = $inputData['vnp_Amount'] / 100;  // Số tiền (chia 100)
        $orderId = $inputData['vnp_TxnRef'];           // Mã đơn hàng: BOOKING_55_1761554008
        
        // Parse booking_id từ TxnRef
        $booking_id_from_txn = '';
        if(preg_match('/^BOOKING_(\d+)_/', $orderId, $matches)) {
            $booking_id_from_txn = $matches[1];
        }
        
        try {
            // Kiểm tra checksum
            if ($secureHash == $vnp_SecureHash) {
                // Lấy thông tin giao dịch từ DB
                $check_sql = "SELECT * FROM payment_transactions WHERE transaction_id='$orderId' LIMIT 1";
                $check_result = mysqli_query($conn, $check_sql);
                $order = mysqli_fetch_assoc($check_result);
                
                if ($order != NULL) {
                    // Kiểm tra số tiền
                    if($order["amount"] == $vnp_Amount) {
                        // Kiểm tra trạng thái chưa cập nhật
                        if ($order["status"] == 'pending') {
                            // Kiểm tra kết quả thanh toán
                            if ($inputData['vnp_ResponseCode'] == '00' && $inputData['vnp_TransactionStatus'] == '00') {
                                // Thanh toán thành công
                                $update_sql = "UPDATE payment_transactions 
                                              SET status='completed', updated_at=NOW() 
                                              WHERE transaction_id='$orderId'";
                                mysqli_query($conn, $update_sql);
                                
                                $returnData['RspCode'] = '00';
                                $returnData['Message'] = 'Confirm Success';
                            } else {
                                // Thanh toán thất bại
                                $update_sql = "UPDATE payment_transactions 
                                              SET status='failed', updated_at=NOW() 
                                              WHERE transaction_id='$orderId'";
                                mysqli_query($conn, $update_sql);
                                
                                $returnData['RspCode'] = '00';
                                $returnData['Message'] = 'Confirm Success';
                            }
                        } else {
                            $returnData['RspCode'] = '02';
                            $returnData['Message'] = 'Order already confirmed';
                        }
                    } else {
                        $returnData['RspCode'] = '04';
                        $returnData['Message'] = 'Invalid amount';
                    }
                } else {
                    $returnData['RspCode'] = '01';
                    $returnData['Message'] = 'Order not found';
                }
            } else {
                $returnData['RspCode'] = '97';
                $returnData['Message'] = 'Invalid signature';
            }
        } catch (Exception $e) {
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Unknown error';
        }
        
        // Trả về JSON cho VNPay
        header('Content-Type: application/json');
        echo json_encode($returnData);
        break;
        
    case 'zalopay':
        // ZaloPay callback (IPN)
        $input = file_get_contents('php://input');
        $result = json_decode($input, true);
        
        if(!$result) {
            header('Content-Type: application/json');
            echo json_encode(["return_code" => -1, "return_message" => "Invalid JSON"]);
            exit;
        }
        
        // Verify MAC using key2
        $key2 = ZALOPAY_KEY2;
        $dataStr = $result["data"] ?? '';
        $reqMac = $result["mac"] ?? '';
        
        if(empty($dataStr) || empty($reqMac)) {
            header('Content-Type: application/json');
            echo json_encode(["return_code" => -1, "return_message" => "Missing data or mac"]);
            exit;
        }
        
        $mac = hash_hmac("sha256", $dataStr, $key2);
        
        if (strcmp($mac, $reqMac) !== 0) {
            // MAC không khớp
            header('Content-Type: application/json');
            echo json_encode(["return_code" => -1, "return_message" => "mac not equal"]);
            exit;
        }
        
        // MAC hợp lệ - parse data
        $dataJson = json_decode($dataStr, true);
        
        if($dataJson && $dataJson['return_code'] == 1) {
            // Thanh toán thành công
            $appTransId = $dataJson['app_trans_id'];
            $zpTransId = $dataJson['zp_trans_id'] ?? '';
            $amount = $dataJson['amount'] ?? 0;
            $booking_id = $_GET['booking_id'] ?? '';
            
            // Update transaction
            $update_sql = "UPDATE payment_transactions 
                          SET status='completed', 
                              updated_at=NOW() 
                          WHERE transaction_id='$appTransId' AND gateway='zalopay'";
            mysqli_query($conn, $update_sql);
            
            // Update booking status if needed
            if($booking_id) {
                $update_booking = "UPDATE roombook SET stat='Confirm' WHERE id='$booking_id'";
                @mysqli_query($conn, $update_booking);
            }
            
            header('Content-Type: application/json');
            echo json_encode(["return_code" => 1, "return_message" => "success"]);
        } else {
            // Thanh toán thất bại
            $appTransId = $dataJson['app_trans_id'] ?? '';
            if($appTransId) {
                $update_sql = "UPDATE payment_transactions 
                              SET status='failed', 
                                  updated_at=NOW() 
                              WHERE transaction_id='$appTransId' AND gateway='zalopay'";
                mysqli_query($conn, $update_sql);
            }
            
            header('Content-Type: application/json');
            echo json_encode(["return_code" => 1, "return_message" => "success"]);
        }
        break;
}
?>

