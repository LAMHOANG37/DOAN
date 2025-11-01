# 💳 Hướng Dẫn Cấu Hình Cổng Thanh Toán
## BlueBird Hotel Management System

---

## 📋 **Tổng Quan**

Hệ thống tích hợp 4 cổng thanh toán phổ biến:
- 📱 **MoMo** - Ví điện tử MoMo
- 💳 **VNPay** - Cổng thanh toán VNPay
- 👛 **ZaloPay** - Ví điện tử ZaloPay
- 🌐 **PayPal** - Thanh toán quốc tế

---

## 🔧 **Cấu Hình Payment Config**

Mở file `payment_config.php` và cập nhật các thông tin sau:

### **1. MoMo Configuration**

```php
define('MOMO_PARTNER_CODE', 'YOUR_MOMO_PARTNER_CODE');
define('MOMO_ACCESS_KEY', 'YOUR_MOMO_ACCESS_KEY');
define('MOMO_SECRET_KEY', 'YOUR_MOMO_SECRET_KEY');
```

**Đăng ký:** https://business.momo.vn/
- Tạo tài khoản MoMo Business
- Đăng ký API integration
- Lấy Partner Code, Access Key, Secret Key từ Dashboard

**Test Sandbox:**
- Endpoint: `https://test-payment.momo.vn/v2/gateway/api/create`
- Tài liệu: https://developers.momo.vn/

---

### **2. VNPay Configuration**

```php
define('VNPAY_TMN_CODE', 'YOUR_VNPAY_TMN_CODE');
define('VNPAY_HASH_SECRET', 'YOUR_VNPAY_HASH_SECRET');
```

**Đăng ký:** https://vnpay.vn/
- Đăng ký tài khoản doanh nghiệp
- Đăng ký API
- Lấy TMN Code và Hash Secret

**Test Sandbox:**
- URL: `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html`
- Tài liệu: https://sandbox.vnpayment.vn/apis/docs/

**Test Card:**
- Card Number: 9704198526191432198
- Card Holder: NGUYEN VAN A
- Expiry Date: 07/15
- OTP: 123456

---

### **3. ZaloPay Configuration**

```php
define('ZALOPAY_APP_ID', YOUR_APP_ID);
define('ZALOPAY_KEY1', 'YOUR_KEY1');
define('ZALOPAY_KEY2', 'YOUR_KEY2');
```

**Đăng ký:** https://docs.zalopay.vn/
- Đăng ký ZaloPay Business
- Tạo App mới
- Lấy App ID, Key1, Key2

**Test Sandbox:**
- Endpoint: `https://sb-openapi.zalopay.vn/v2/create`
- App ID Sandbox: 2553
- Tài liệu: https://docs.zalopay.vn/v2/

---

### **4. PayPal Configuration**

```php
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_CLIENT_SECRET', 'YOUR_PAYPAL_SECRET');
define('PAYPAL_MODE', 'sandbox'); // hoặc 'live'
```

**Đăng ký:** https://developer.paypal.com/
- Tạo tài khoản Developer
- Tạo App mới
- Lấy Client ID và Secret từ Dashboard

**Test Sandbox:**
- API: `https://api-m.sandbox.paypal.com`
- Test Account: Tạo tại https://developer.paypal.com/dashboard/accounts

**Test Account:**
- Email: sb-buyer@personal.example.com
- Password: (tạo trong sandbox)

---

## 🗄️ **Cấu Trúc Database**

Bảng `payment_transactions` đã được thêm vào `bluebirdhotel.sql`:

```sql
CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','cancelled'),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

---

## 📂 **Các File Thanh Toán**

```
payment_config.php          → Cấu hình các cổng
payment_momo.php           → Xử lý MoMo
payment_vnpay.php          → Xử lý VNPay
payment_zalopay.php        → Xử lý ZaloPay
payment_paypal.php         → Xử lý PayPal
payment_return.php         → Xử lý callback return
payment_notify.php         → Xử lý webhook notify
user_payment.php           → Trang thanh toán
```

---

## 🚀 **Luồng Hoạt Động**

```
1. User đặt phòng (home.php)
   ↓
2. Tự động tạo payment record
   ↓
3. Chuyển đến user_payment.php
   ↓
4. Chọn phương thức: MoMo/VNPay/ZaloPay/PayPal
   ↓
5. Click "Proceed to Pay"
   ↓
6. Redirect đến payment_{gateway}.php?id=xxx
   ↓
7. Tạo transaction record (status=pending)
   ↓
8. Redirect đến cổng thanh toán
   ↓
9. User thanh toán
   ↓
10. Gateway callback về payment_return.php
   ↓
11. Cập nhật status=completed
   ↓
12. Hiển thị kết quả
```

---

## 🔒 **Bảo Mật**

### **SSL/HTTPS**
- **Bắt buộc** khi deploy production
- Cấu hình SSL certificate cho domain
- Update tất cả URL từ `http://` sang `https://`

### **Webhook Security**
- MoMo, ZaloPay sử dụng HMAC-SHA256
- VNPay sử dụng SHA512
- Luôn verify signature trước khi cập nhật DB

### **Database**
- Không lưu thông tin thẻ
- Chỉ lưu transaction_id
- Encrypt sensitive data nếu cần

---

## 🧪 **Test Sandbox**

### **MoMo Sandbox**
```
Test Phone: 0999999999
Test OTP: Mọi số
```

### **VNPay Sandbox**
```
Card: 9704198526191432198
Name: NGUYEN VAN A
Date: 07/15
OTP: 123456
```

### **ZaloPay Sandbox**
```
Sử dụng App ZaloPay Sandbox
```

### **PayPal Sandbox**
```
Tạo buyer account trong dashboard
```

---

## 🌐 **URL Production**

Khi deploy lên production, cập nhật các URL:

```php
// payment_config.php

// Return URLs
define('MOMO_RETURN_URL', 'https://yourdomain.com/payment_return.php');
define('VNPAY_RETURN_URL', 'https://yourdomain.com/payment_return.php');
define('ZALOPAY_RETURN_URL', 'https://yourdomain.com/payment_return.php');
define('PAYPAL_RETURN_URL', 'https://yourdomain.com/payment_return.php');

// Notify URLs
define('MOMO_NOTIFY_URL', 'https://yourdomain.com/payment_notify.php');
define('ZALOPAY_CALLBACK_URL', 'https://yourdomain.com/payment_notify.php');

// PayPal
define('PAYPAL_MODE', 'live'); // Chuyển từ sandbox sang live
```

---

## 📝 **Checklist Deploy**

- [ ] Đăng ký tài khoản các cổng thanh toán
- [ ] Lấy API credentials (production)
- [ ] Cập nhật `payment_config.php`
- [ ] Cài đặt SSL certificate
- [ ] Update tất cả URLs sang HTTPS
- [ ] Test từng cổng thanh toán
- [ ] Cấu hình webhook URLs tại gateway dashboard
- [ ] Kiểm tra callback/notify hoạt động
- [ ] Test rollback khi thanh toán failed

---

## ❓ **Troubleshooting**

### **Lỗi: cURL error**
```bash
# Enable cURL trong php.ini
extension=curl
```

### **Lỗi: Invalid signature**
- Kiểm tra Secret Key
- Kiểm tra encoding (UTF-8)
- Kiểm tra thứ tự parameters

### **Lỗi: Callback không nhận được**
- Kiểm tra firewall
- Kiểm tra webhook URL accessible từ internet
- Kiểm tra logs tại gateway dashboard

### **Lỗi: Database**
```sql
-- Kiểm tra bảng đã tạo
SHOW TABLES LIKE 'payment_transactions';

-- Kiểm tra structure
DESCRIBE payment_transactions;
```

---

## 📞 **Support**

- **MoMo:** https://business.momo.vn/support
- **VNPay:** https://vnpay.vn/lien-he
- **ZaloPay:** https://zalopay.vn/support
- **PayPal:** https://developer.paypal.com/support

---

## 🎉 **Hoàn Thành!**

Hệ thống thanh toán đã sẵn sàng!
- ✅ 4 cổng thanh toán
- ✅ Callback/Webhook handling
- ✅ Transaction tracking
- ✅ Security implemented

**Happy Coding! 🚀**

