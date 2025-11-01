# 👤 User Account Management System
## BlueBird Hotel - Hệ Thống Quản Lý Tài Khoản Người Dùng

---

## 📋 **Tổng Quan**

Hệ thống quản lý tài khoản người dùng đầy đủ với các tính năng:
- 🖼️ Upload và quản lý ảnh đại diện (Avatar)
- ✏️ Chỉnh sửa thông tin cá nhân
- 📋 Xem lịch sử đặt phòng
- 🔐 Đổi mật khẩu
- 📊 Thống kê booking và chi tiêu

---

## 🗂️ **Cấu Trúc Folder**

```
user/
├── profile.php              # Trang chính - User Dashboard
├── update-profile.php       # Xử lý cập nhật thông tin
├── change-password.php      # Xử lý đổi mật khẩu
├── upload-avatar.php        # Xử lý upload avatar
├── my-bookings.php         # Hiển thị lịch sử booking
├── index.php               # Redirect to profile
├── css/
│   └── profile.css         # CSS cho user profile
└── uploads/
    └── avatars/            # Thư mục chứa avatar
        ├── .htaccess       # Bảo mật
        └── default-avatar.png
```

---

## 💾 **Database Schema**

### Bảng `signup` đã được cập nhật:

```sql
CREATE TABLE `signup` (
  `UserID` int(100) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Password` varchar(50) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🎯 **Các Tính Năng**

### **1️⃣ Dashboard (Tổng Quan)**
- ✅ Hiển thị thống kê:
  - Tổng số booking
  - Tổng chi tiêu
  - Ngày đăng ký
- ✅ Danh sách booking gần đây (5 booking mới nhất)
- ✅ Link nhanh đến các chức năng

### **2️⃣ Sửa Thông Tin Cá Nhân**
- ✅ Chỉnh sửa:
  - Họ tên
  - Số điện thoại
  - Địa chỉ
- ✅ Email không thể thay đổi (dùng để đăng nhập)
- ✅ Validation và thông báo lỗi

### **3️⃣ Upload Avatar**
- ✅ Upload ảnh đại diện
- ✅ Preview trước khi upload
- ✅ Giới hạn:
  - Kích thước: Max 2MB
  - Định dạng: JPG, PNG, GIF
- ✅ Tự động xóa ảnh cũ khi upload mới

### **4️⃣ Lịch Sử Booking**
- ✅ Hiển thị tất cả booking
- ✅ Thông tin chi tiết:
  - Loại phòng, giường
  - Ngày check-in/out
  - Số ngày, số phòng
  - Bữa ăn
  - Tổng tiền
  - Trạng thái
- ✅ Responsive design

### **5️⃣ Đổi Mật Khẩu**
- ✅ Yêu cầu mật khẩu hiện tại
- ✅ Xác nhận mật khẩu mới
- ✅ Validation và bảo mật

---

## 🔒 **Bảo Mật**

### **1. Session Management**
```php
// Tất cả trang đều check session
$usermail = $_SESSION['usermail'] ?? '';
if(empty($usermail)){
    header("location: ../index.php");
    exit();
}
```

### **2. Upload Security**
- ✅ Kiểm tra MIME type
- ✅ Giới hạn kích thước file
- ✅ Chỉ cho phép ảnh (JPG, PNG, GIF)
- ✅ Tạo tên file unique (uniqid)
- ✅ .htaccess bảo vệ thư mục upload

### **3. SQL Injection Prevention**
```php
// Sử dụng mysqli_real_escape_string
$username = mysqli_real_escape_string($conn, $_POST['username']);
```

---

## 🎨 **UI/UX Features**

### **Responsive Design**
- ✅ Mobile-friendly
- ✅ Tablet-friendly
- ✅ Desktop optimized

### **Modern UI**
- ✅ Gradient backgrounds
- ✅ Smooth animations
- ✅ Hover effects
- ✅ Card-based layout
- ✅ Font Awesome icons

### **User Feedback**
- ✅ SweetAlert notifications
- ✅ Success/Error messages
- ✅ Loading states
- ✅ Form validation

---

## 📱 **Cách Sử Dụng**

### **Cho Người Dùng:**

1. **Đăng nhập** vào hệ thống
2. **Vào trang chủ** → Click **"Tài Khoản"** ở navigation
3. **Dashboard** sẽ hiển thị:
   - Avatar (click để đổi)
   - Thống kê
   - Booking gần đây
4. **Menu bên trái** để chọn chức năng:
   - Tổng Quan
   - Sửa Thông Tin
   - Phòng Đã Đặt
   - Đổi Mật Khẩu

### **Cho Developer:**

**Access URL:**
```
http://localhost/Hotel-Management-System-main/Hotel-Management-System-main/user/profile.php
```

**Hoặc từ home.php:**
```php
<a href="./user/profile.php">Tài Khoản</a>
```

---

## 🚀 **Cài Đặt**

### **1. Database đã được cập nhật tự động**
Các cột `avatar`, `phone`, `address`, `created_at` đã được thêm vào bảng `signup`.

### **2. Folder structure đã tạo**
```
user/
├── css/
├── uploads/
│   └── avatars/
```

### **3. Permissions**
Đảm bảo folder `user/uploads/avatars/` có quyền ghi (755):
```bash
chmod 755 user/uploads/avatars/
```

---

## 🐛 **Troubleshooting**

### **Lỗi: Cannot upload avatar**
```
✓ Kiểm tra permission folder uploads/avatars/
✓ Kiểm tra php.ini: upload_max_filesize, post_max_size
✓ Kiểm tra file_uploads = On
```

### **Lỗi: Session not found**
```
✓ Đảm bảo đã đăng nhập
✓ Kiểm tra session_start() ở đầu file
✓ Xóa cache browser
```

### **Lỗi: Avatar không hiển thị**
```
✓ Kiểm tra path: ./uploads/avatars/filename.jpg
✓ Kiểm tra file tồn tại
✓ Kiểm tra .htaccess không block
```

---

## 🎯 **Tính Năng Mở Rộng (Future)**

- [ ] Crop avatar trước khi upload
- [ ] Reset password qua email
- [ ] Two-factor authentication (2FA)
- [ ] Email notifications cho booking
- [ ] Export booking history to PDF
- [ ] Loyalty points system
- [ ] Wishlist/Favorite rooms
- [ ] Review system
- [ ] Social media login

---

## 📝 **Code Examples**

### **Get User Info**
```php
$sql = "SELECT * FROM signup WHERE Email = '$usermail'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_array($result);

echo $user['Username'];
echo $user['avatar'];
echo $user['phone'];
```

### **Update Profile**
```php
$sql = "UPDATE signup 
        SET Username='$username', phone='$phone', address='$address' 
        WHERE Email='$usermail'";
mysqli_query($conn, $sql);
```

### **Upload Avatar**
```php
$filename = uniqid('avatar_') . '.' . $ext;
$upload_path = __DIR__ . '/uploads/avatars/' . $filename;
move_uploaded_file($avatar['tmp_name'], $upload_path);

$sql = "UPDATE signup SET avatar='$filename' WHERE Email='$usermail'";
```

---

## 👥 **Credits**

- **Design:** Modern, Gradient-based UI
- **Icons:** Font Awesome 6.2
- **Framework:** Bootstrap 5
- **Alerts:** SweetAlert
- **Database:** MySQL/MariaDB

---

## 📄 **License**

Part of BlueBird Hotel Management System
© 2025 BlueBird Hotel

---

**🎉 Hệ thống đã sẵn sàng sử dụng!**

Truy cập: `http://localhost/.../user/profile.php`

