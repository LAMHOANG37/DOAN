<?php

include 'config.php';
include 'admin/room_manager.php';  // Hệ thống quản lý phòng

session_start();

// Clear session khi vào trang home (trừ khi vừa đăng nhập xong hoặc đang submit form)
// Lưu error_message và usermail trước khi clear (nếu cần)
$error_message = $_SESSION['error_message'] ?? '';
$saved_usermail = $_SESSION['usermail'] ?? '';

// Kiểm tra nếu không phải redirect từ login.php hoặc admin/admin.php thì clear session
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$isFromLogin = strpos($referer, 'login.php') !== false;
$isFromAdmin = strpos($referer, 'admin/admin.php') !== false;
$isSubmittingForm = isset($_POST['guestdetailsubmit']);
$isFromBooking = strpos($referer, 'booking/') !== false;
$isFromPayment = strpos($referer, 'payment/') !== false;

// KHÔNG clear session nếu có error_message (để hiển thị lỗi) hoặc đang redirect từ booking/payment
if (!$isFromLogin && !$isFromAdmin && !$isSubmittingForm && empty($error_message) && !$isFromBooking && !$isFromPayment) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        session_destroy();
        session_start();
        // Khôi phục usermail nếu cần
        if (!empty($saved_usermail)) {
            $_SESSION['usermail'] = $saved_usermail;
        }
    }
}

// Check if user is logged in (but don't force redirect)
$usermail = $_SESSION['usermail'] ?? '';
$isLoggedIn = !empty($usermail);

// Get user info if logged in
$username = 'Khách';
$avatar = 'default-avatar.png';
$user_address = '';

$user_id = null;
if($isLoggedIn) {
    $user_sql = "SELECT UserID, Username, avatar, address FROM signup WHERE Email = '$usermail'";
    $user_result = mysqli_query($conn, $user_sql);
    if($user_result && mysqli_num_rows($user_result) > 0) {
        $user_data = mysqli_fetch_array($user_result);
        $user_id = $user_data['UserID'] ?? null;
        $username = $user_data['Username'] ?? 'Khách';
        $avatar = $user_data['avatar'] ?? 'default-avatar.png';
        $user_address = $user_data['address'] ?? '';
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/home.css">
    <title>Khách Sạn BlueBird - Trang Chủ</title>
    <!-- boot -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <!-- fontowesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <!-- sweet alert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <link rel="stylesheet" href="./admin/modules/booking/booking.css">
    <style>
      #guestdetailpanel.show{
        display: flex !important;
      }
      
      /* Fix Navigation Avatar */
      .nav-avatar {
        width: 32px !important;
        height: 32px !important;
        min-width: 32px !important;
        max-width: 32px !important;
        min-height: 32px !important;
        max-height: 32px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 2px solid rgba(255, 255, 255, 0.8) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
        display: inline-block !important;
        vertical-align: middle !important;
        flex-shrink: 0 !important;
      }
      
      .user-menu .user-link {
        white-space: nowrap !important;
        overflow: hidden !important;
      }
      
      .user-link span {
        display: inline !important;
        white-space: nowrap !important;
        max-width: 120px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        font-size: 13px !important;
        font-weight: 500 !important;
      }
      
      .user-link strong {
        font-weight: 600 !important;
      }
      
      /* Dropdown Icon */
      .dropdown-icon {
        font-size: 10px;
        margin-left: 3px;
        transition: transform 0.3s ease;
        opacity: 0.8;
      }
      
      .user-link.active .dropdown-icon {
        transform: rotate(180deg);
      }
      
      .user-link:hover .dropdown-icon {
        opacity: 1;
      }
      
      /* User Dropdown Menu */
      .user-dropdown {
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        background: white;
        border-radius: 14px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        min-width: 260px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px) scale(0.95);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.05);
      }
      
      .user-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
      }
      
      /* Dropdown Header */
      .dropdown-header {
        padding: 18px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;
      }
      
      .dropdown-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2.5px solid white;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        flex-shrink: 0;
      }
      
      .dropdown-user-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        overflow: hidden;
      }
      
      .dropdown-user-info strong {
        font-size: 15px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      
      .dropdown-user-info small {
        font-size: 11px;
        opacity: 0.9;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      
      /* Dropdown Divider */
      .dropdown-divider {
        height: 1px;
        background: #e0e0e0;
        margin: 0;
      }
      
      /* Dropdown Items */
      .dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        color: #2c3e50;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        font-size: 14px;
      }
      
      .dropdown-item:hover {
        background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
        color: #667eea;
        padding-left: 22px;
      }
      
      .dropdown-item i {
        font-size: 16px;
        width: 18px;
        text-align: center;
        transition: transform 0.2s ease;
      }
      
      .dropdown-item:hover i {
        transform: scale(1.1);
      }
      
      .dropdown-item span {
        font-size: 13px;
        font-weight: 500;
      }
      
      .dropdown-item.logout {
        color: #e74c3c;
        border-top: 1px solid #e0e0e0;
      }
      
      /* Styling cho phòng đã được đặt trong dropdown */
      #roomNumbersSelect option:disabled {
        color: #dc3545 !important;
        font-style: italic;
        background-color: #fff3cd !important;
      }
      
      #roomNumbersSelect option:not(:disabled) {
        color: #2c3e50;
      }
      
      .dropdown-item.logout:hover {
        background: #fee;
        color: #c0392b;
      }
      
      /* Close dropdown when clicking outside */
      .user-menu {
        position: relative;
      }
      
      /* Smooth scroll */
      html {
        scroll-behavior: smooth;
      }
    </style>
</head>

<body>
  <nav>
    <div class="logo">
      <img class="bluebirdlogo" src="./image/bluebirdlogo.png" alt="logo">
      <p>BLUEBIRD</p>
    </div>
    <ul>
      <li><a href="#firstsection">Trang Chủ</a></li>
      <li><a href="#secondsection">Phòng</a></li>
      <li><a href="#thirdsection">Tiện Nghi</a></li>
      <li><a href="#contactus">Liên Hệ</a></li>
      
      <?php if($isLoggedIn): ?>
      <!-- Logged In: User Dropdown -->
      <li class="user-menu">
        <a href="javascript:void(0)" class="user-link" onclick="toggleUserDropdown()">
          <img src="./user/uploads/avatars/<?php echo htmlspecialchars($avatar); ?>" 
               alt="Avatar" 
               class="nav-avatar"
               onerror="this.src='./image/Profile.png'">
          <span>Xin chào, <strong><?php echo htmlspecialchars($username); ?></strong></span>
          <i class="fas fa-chevron-down dropdown-icon"></i>
        </a>
        
        <!-- Dropdown Menu -->
        <div class="user-dropdown" id="userDropdown">
          <div class="dropdown-header">
            <img src="./user/uploads/avatars/<?php echo htmlspecialchars($avatar); ?>" 
                 alt="Avatar" 
                 class="dropdown-avatar"
                 onerror="this.src='./image/Profile.png'">
            <div class="dropdown-user-info">
              <strong><?php echo htmlspecialchars($username); ?></strong>
              <small><?php echo htmlspecialchars($usermail); ?></small>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <a href="./user/profile.php" class="dropdown-item">
            <i class="fas fa-user-circle"></i>
            <span>Tài Khoản Của Tôi</span>
          </a>
          <a href="./logout.php" class="dropdown-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Đăng Xuất</span>
          </a>
        </div>
      </li>
      
      <?php else: ?>
      <!-- Not Logged In: Login/Register Button -->
      <li>
        <a href="login.php" class="login-btn" style="
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          padding: 10px 24px;
          border-radius: 25px;
          font-weight: 600;
          font-size: 14px;
          transition: all 0.3s ease;
          box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
          display: inline-flex;
          align-items: center;
          gap: 8px;
        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.5)';" 
           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.4)';">
          <i class="fas fa-sign-in-alt"></i>
          <span>Đăng Nhập</span>
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </nav>

  <section id="firstsection" class="carousel slide carousel-fade carousel_section" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="false">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img class="carousel-image" src="./image/hotel1.jpg">
            </div>
            <div class="carousel-item">
                <img class="carousel-image" src="./image/hotel2.jpg">
            </div>
            <div class="carousel-item">
                <img class="carousel-image" src="./image/hotel3.jpg">
            </div>
            <div class="carousel-item">
                <img class="carousel-image" src="./image/hotel4.jpg">
            </div>

        <div class="welcomeline">
          <h1 class="welcometag">Welcome to heaven on earth</h1>
        </div>

      <!-- bookbox -->
      <div id="guestdetailpanel">
        <form action="booking/process_booking.php" method="POST" class="guestdetailpanelform" onsubmit="enableSelectBeforeSubmit()">
            <div class="head">
                <h3>ĐẶT PHÒNG</h3>
                <i class="fa-solid fa-circle-xmark" onclick="closebox()"></i>
        </div>
            <div class="middle">
                <div class="guestinfo">
                    <h4>Thông Tin Khách Hàng</h4>
                    
                    <!-- Tên -->
                    <input type="text" name="Name" placeholder="Nhập họ tên *" value="" autocomplete="off" required>
                    
                    <!-- Email -->
                    <input type="email" name="Email" placeholder="Nhập email *" value="" autocomplete="off" required>

                    <?php
                    $countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
                    ?>

                    <!-- Quốc gia (có thể chọn) -->
                    <select name="Country" class="selectinput" required>
						<option value="">Chọn quốc gia *</option>
                        <?php
							foreach($countries as $key => $value):
							    $selected = ($value == "Vietnam") ? "selected" : "";
							    echo '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
							endforeach;
						?>
                    </select>
                </div>

                <div class="line"></div>

                <div class="reservationinfo">
                    <h4>Thông Tin Đặt Phòng</h4>
                    <!-- 1. Loại Phòng -->
                    <select name="RoomType" id="roomTypeSelect" class="selectinput" required onchange="calculatePrice()">
						<option value="">Loại Phòng *</option>
                        <option value="Phòng Cao Cấp">PHÒNG CAO CẤP (3,000,000 ₫/đêm)</option>
                        <option value="Phòng Sang Trọng">PHÒNG SANG TRỌNG (2,000,000 ₫/đêm)</option>
						<option value="Nhà Khách">NHÀ KHÁCH (1,500,000 ₫/đêm)</option>
						<option value="Phòng Đơn">PHÒNG ĐƠN (1,000,000 ₫/đêm)</option>
                    </select>
                    
                    <!-- 2. Chọn Phòng -->
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                        Chọn Phòng *
                    </label>
                    <select name="RoomNumber" id="roomNumbersSelect" class="selectinput" required disabled>
                        <option value="">-- Chọn phòng --</option>
                    </select>
                    
                    <!-- Hidden input để lưu số lượng phòng (luôn là 1 vì chỉ chọn 1 phòng) -->
                    <input type="hidden" name="NoofRoom" id="noofRoomHidden" value="1">
                    
                    <!-- 3. Dịch Vụ -->
                    <select name="Service" id="serviceSelect" class="selectinput" required onchange="calculatePrice()">
						<option value="">Dịch Vụ *</option>
                        <option value="Chỉ phòng">Chỉ phòng</option>
                        <option value="Bữa sáng">Bữa sáng (+10% giá phòng)</option>
						<option value="Nửa suất">Nửa suất (+20% giá phòng)</option>
						<option value="Toàn bộ">Toàn bộ (+30% giá phòng)</option>
					</select>
                    
                    <!-- 4. Ngày -->
                    <div class="datesection">
                        <span>
                            <label for="cin"> Ngày Nhận Phòng *</label>
                            <input name="cin" id="checkinDate" type="date" required min="<?php echo date('Y-m-d'); ?>" onchange="calculatePrice()">
                        </span>
                        <span>
                            <label for="cout"> Ngày Trả Phòng *</label>
                            <input name="cout" id="checkoutDate" type="date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="calculatePrice()">
                        </span>
                    </div>
                    
                    <!-- 5. Hiển thị giá -->
                    <div id="priceSummary" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; border: 2px solid #667eea;">
                        <h4 style="margin-bottom: 15px; color: #667eea; font-size: 16px;">
                            <i class="fa-solid fa-calculator"></i> Tóm tắt giá
                        </h4>
                        <div style="font-size: 14px; line-height: 1.8;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Giá phòng/đêm:</span>
                                <strong id="roomPricePerNight">0</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Số đêm:</span>
                                <strong id="numberOfNights">0</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Giá phòng (tổng):</span>
                                <strong id="totalRoomPrice" style="color: #667eea;">0 ₫</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Giá dịch vụ/ngày:</span>
                                <strong id="servicePricePerDay">0</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span>Giá dịch vụ (tổng):</span>
                                <strong id="totalServicePrice" style="color: #667eea;">0 ₫</strong>
                            </div>
                            <hr style="margin: 10px 0; border: 1px solid #dee2e6;">
                            <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; color: #667eea;">
                                <span>TỔNG CỘNG:</span>
                                <strong id="finalTotalPrice" style="font-size: 20px;">0 ₫</strong>
                            </div>
                        </div>
                    </div>
                    </div>
                    </div>
            <div class="footer">
                <button class="btn btn-success" name="guestdetailsubmit">Xác Nhận Đặt Phòng</button>
                    </div>
                </form>

          </div>

    </div>
  </section>
    
  <section id="secondsection"> 
    <img src="./image/homeanimatebg.svg">
    <div class="ourroom">
      <h1 class="head">≼ Phòng Của Chúng Tôi ≽</h1>
      <div class="roomselect">
        <div class="roombox">
          <div class="hotelphoto h1"></div>
          <div class="roomdata">
            <div class="room-content">
              <h2>Phòng Cao Cấp</h2>
              <div class="services">
                <i class="fa-solid fa-wifi"></i>
                <i class="fa-solid fa-burger"></i>
                <i class="fa-solid fa-spa"></i>
                <i class="fa-solid fa-dumbbell"></i>
                <i class="fa-solid fa-person-swimming"></i>
              </div>
            </div>
            <button class="btn btn-primary bookbtn" onclick="openbookbox('Phòng Cao Cấp')">Đặt Phòng</button>
          </div>
        </div>
        <div class="roombox">
          <div class="hotelphoto h2"></div>
          <div class="roomdata">
            <div class="room-content">
              <h2>Phòng Sang Trọng</h2>
              <div class="services">
                <i class="fa-solid fa-wifi"></i>
                <i class="fa-solid fa-burger"></i>
                <i class="fa-solid fa-spa"></i>
                <i class="fa-solid fa-dumbbell"></i>
              </div>
            </div>
            <button class="btn btn-primary bookbtn" onclick="openbookbox('Phòng Sang Trọng')">Đặt Phòng</button>
          </div>
        </div>
        <div class="roombox">
          <div class="hotelphoto h3"></div>
          <div class="roomdata">
            <div class="room-content">
              <h2>Nhà Khách</h2>
              <div class="services">
                <i class="fa-solid fa-wifi"></i>
                <i class="fa-solid fa-burger"></i>
                <i class="fa-solid fa-spa"></i>
              </div>
            </div>
            <button class="btn btn-primary bookbtn" onclick="openbookbox('Nhà Khách')">Đặt Phòng</button>
          </div>
        </div>
        <div class="roombox">
          <div class="hotelphoto h4"></div>
          <div class="roomdata">
            <div class="room-content">
              <h2>Phòng Đơn</h2>
              <div class="services">
                <i class="fa-solid fa-wifi"></i>
                <i class="fa-solid fa-burger"></i>
              </div>
            </div>
            <button class="btn btn-primary bookbtn" onclick="openbookbox('Phòng Đơn')">Đặt Phòng</button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="thirdsection">
    <h1 class="head">≼ Tiện Nghi ≽</h1>
    <div class="facility">
      <div class="box" data-aos="facility">
        <div class="facility-icon">
          <i class="fas fa-swimming-pool"></i>
        </div>
        <div class="facility-content">
          <h2>Hồ Bơi</h2>
          <p>Hồ bơi ngoài trời rộng rãi với view tuyệt đẹp</p>
        </div>
      </div>
      <div class="box" data-aos="facility">
        <div class="facility-icon">
          <i class="fas fa-spa"></i>
        </div>
        <div class="facility-content">
          <h2>Spa</h2>
          <p>Dịch vụ spa cao cấp với đội ngũ chuyên nghiệp</p>
        </div>
      </div>
      <div class="box" data-aos="facility">
        <div class="facility-icon">
          <i class="fas fa-utensils"></i>
        </div>
        <div class="facility-content">
          <h2>Nhà Hàng 24/7</h2>
          <p>Ẩm thực đa dạng phục vụ suốt ngày đêm</p>
        </div>
      </div>
      <div class="box" data-aos="facility">
        <div class="facility-icon">
          <i class="fas fa-dumbbell"></i>
                    </div>
        <div class="facility-content">
          <h2>Phòng Gym 24/7</h2>
          <p>Phòng tập gym hiện đại với thiết bị cao cấp</p>
                    </div>
                    </div>
      <div class="box" data-aos="facility">
        <div class="facility-icon">
          <i class="fas fa-helicopter"></i>
                    </div>
        <div class="facility-content">
          <h2>Dịch Vụ Trực Thăng</h2>
          <p>Trải nghiệm bay trực thăng sang trọng VIP</p>
                    </div>
            </div>
        </div>
    </section>

  <section id="contactus">
    <div class="social">
      <i class="fa-brands fa-instagram"></i>
      <i class="fa-brands fa-facebook"></i>
      <i class="fa-solid fa-envelope"></i>
    </div>
    <div class="createdby">
      <h5>Created by LAM</h5>
    </div>
  </section>
</body>

<script>
    // Check if user is logged in (from PHP)
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    
    // Hiển thị thông báo lỗi nếu có
    <?php if(isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
    swal({
        title: 'Thông báo',
        text: '<?php echo addslashes($_SESSION['error_message']); ?>',
        icon: 'error',
        button: 'OK'
    });
    <?php 
    // Xóa error_message sau khi hiển thị
    unset($_SESSION['error_message']);
    endif; 
    ?>

    var bookbox = document.getElementById("guestdetailpanel");

    openbookbox = (roomType = '') => {
      // Check if user is logged in
      if (!isLoggedIn) {
        // Show alert and redirect to login
        swal({
          title: 'Yêu cầu đăng nhập',
          text: 'Vui lòng đăng nhập để đặt phòng',
          icon: 'warning',
          buttons: {
            cancel: 'Hủy',
            confirm: {
              text: 'Đăng nhập ngay',
              value: true,
            }
          }
        }).then((willLogin) => {
          if (willLogin) {
            window.location.href = 'login.php';
          }
        });
        return;
      }
      
      // If logged in, open booking form
      bookbox.classList.add("show");
      
      // Clear Name and Email fields to prevent auto-fill
      const nameInput = bookbox.querySelector('input[name="Name"]');
      const emailInput = bookbox.querySelector('input[name="Email"]');
      if (nameInput) {
        nameInput.value = '';
        nameInput.setAttribute('autocomplete', 'off');
      }
      if (emailInput) {
        emailInput.value = '';
        emailInput.setAttribute('autocomplete', 'off');
      }
      
      // Set room type if provided
      if (roomType) {
        const roomTypeSelect = document.getElementById('roomTypeSelect');
        if (roomTypeSelect) {
          // Set the selected value
          roomTypeSelect.value = roomType;
          
          // Add a visual highlight to show it's pre-selected
          roomTypeSelect.style.background = 'linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%)';
          roomTypeSelect.style.fontWeight = '600';
          roomTypeSelect.style.color = '#2e7d32';
          
          // Remove highlight after 2 seconds
          setTimeout(() => {
            roomTypeSelect.style.background = '';
            roomTypeSelect.style.fontWeight = '';
            roomTypeSelect.style.color = '';
          }, 2000);
          
          // Trigger load rooms for this room type
          loadAvailableRooms();
        }
      }
    }
    
    closebox = () => {
      bookbox.classList.remove("show");
      
      // Reset form
      const roomTypeSelect = document.getElementById('roomTypeSelect');
      const roomSelect = document.getElementById('roomNumbersSelect');
      const nameInput = bookbox.querySelector('input[name="Name"]');
      const emailInput = bookbox.querySelector('input[name="Email"]');
      
      if (roomTypeSelect) {
        roomTypeSelect.value = '';
      }
      if (roomSelect) {
        roomSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';
        roomSelect.value = '';
        roomSelect.disabled = true;
      }
      if (nameInput) {
        nameInput.value = '';
      }
      if (emailInput) {
        emailInput.value = '';
      }
      
      document.getElementById('noofRoomHidden').value = '0';
    }
    
    // Load available rooms based on RoomType
    function loadAvailableRooms() {
        const roomType = document.getElementById('roomTypeSelect').value;
        const roomSelect = document.getElementById('roomNumbersSelect');
        const checkinDate = document.getElementById('checkinDate');
        const checkoutDate = document.getElementById('checkoutDate');
        
        // Reset
        roomSelect.innerHTML = '';
        roomSelect.disabled = true;
        document.getElementById('noofRoomHidden').value = '0';
        
        // Check if room type is selected
        if (!roomType) {
            roomSelect.innerHTML = '<option value="">-- Chọn phòng --</option>';
            return;
        }
        
        // Show loading
        roomSelect.innerHTML = '<option value="">Đang tải...</option>';
        
        // Lấy ngày check-in và check-out nếu có
        const checkIn = checkinDate ? checkinDate.value : '';
        const checkOut = checkoutDate ? checkoutDate.value : '';
        
        // Build query string
        let queryString = `admin/get_available_rooms.php?roomType=${encodeURIComponent(roomType)}`;
        if (checkIn) {
            queryString += `&checkIn=${encodeURIComponent(checkIn)}`;
        }
        if (checkOut) {
            queryString += `&checkOut=${encodeURIComponent(checkOut)}`;
        }
        
        // Fetch all rooms (including booked ones)
        fetch(queryString)
            .then(response => response.json())
            .then(data => {
                roomSelect.innerHTML = '';
                
                if (!data.success || data.rooms.length === 0) {
                    let errorMsg = 'Không có phòng';
                    if (data.message) {
                        errorMsg = data.message;
                    }
                    roomSelect.innerHTML = `<option value="">${errorMsg}</option>`;
                    roomSelect.disabled = true;
                    return;
                }
                
                let hasAvailableRoom = false;
                
                // Populate room options
                data.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.room_number;
                    
                    if (room.is_booked) {
                        // Phòng đã được đặt - hiển thị nhưng disable
                        option.textContent = `Phòng ${room.room_number} - Đã được đặt ❌`;
                        option.disabled = true;
                        option.style.color = '#dc3545';
                        option.style.fontStyle = 'italic';
                        option.style.backgroundColor = '#fff3cd';
                        option.setAttribute('title', 'Phòng này đã được đặt trong khoảng thời gian bạn chọn');
                    } else {
                        // Phòng còn trống
                        option.textContent = `Phòng ${room.room_number} ✓`;
                        hasAvailableRoom = true;
                    }
                    
                    roomSelect.appendChild(option);
                });
                
                // Chỉ enable select nếu có ít nhất 1 phòng trống
                roomSelect.disabled = !hasAvailableRoom;
                
                // Update count when selection changes
                roomSelect.addEventListener('change', function() {
                    document.getElementById('noofRoomHidden').value = this.value ? '1' : '0';
                });
            })
            .catch(error => {
                console.error('Error:', error);
                roomSelect.innerHTML = '<option value="">Lỗi khi tải danh sách phòng</option>';
                roomSelect.disabled = true;
            });
    }
    
    // Enable select before form submit
    function enableSelectBeforeSubmit() {
        const roomSelect = document.getElementById('roomNumbersSelect');
        if (roomSelect && roomSelect.disabled) {
            roomSelect.disabled = false;
        }
        return true;
    }
    
    // ============ Smooth Scroll to Section ============
    
    // Check if there's a hash in URL and scroll to it
    if(window.location.hash) {
      setTimeout(function() {
        const target = document.querySelector(window.location.hash);
        if(target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 100);
    }
    
    // Bảng giá phòng (Production)
    const roomPrices = {
        'Phòng Cao Cấp': 3000000,
        'Phòng Sang Trọng': 2000000,
        'Nhà Khách': 1500000,
        'Phòng Đơn': 1000000
    };
    
    // Tính toán và hiển thị giá
    function calculatePrice() {
        const roomTypeSelect = document.getElementById('roomTypeSelect');
        const serviceSelect = document.getElementById('serviceSelect');
        const checkinDate = document.getElementById('checkinDate');
        const checkoutDate = document.getElementById('checkoutDate');
        const priceSummary = document.getElementById('priceSummary');
        
        const roomType = roomTypeSelect ? roomTypeSelect.value : '';
        const service = serviceSelect ? serviceSelect.value : '';
        const checkin = checkinDate ? checkinDate.value : '';
        const checkout = checkoutDate ? checkoutDate.value : '';
        
        // Nếu chưa đủ thông tin, ẩn phần hiển thị giá
        if (!roomType || !service || !checkin || !checkout) {
            if (priceSummary) {
                priceSummary.style.display = 'none';
            }
            return;
        }
        
        // Lấy giá phòng
        const roomPricePerNight = roomPrices[roomType] || 0;
        
        // Tính số đêm
        const checkinDateObj = new Date(checkin);
        const checkoutDateObj = new Date(checkout);
        const diffTime = checkoutDateObj - checkinDateObj;
        const diffDays = Math.max(0, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
        
        if (diffDays <= 0) {
            if (priceSummary) {
                priceSummary.style.display = 'none';
            }
            return;
        }
        
        // Tính giá dịch vụ mỗi ngày
        let servicePricePerDay = 0;
        if (service === 'Chỉ phòng') {
            servicePricePerDay = 0;
        } else if (service === 'Bữa sáng') {
            servicePricePerDay = roomPricePerNight * 0.1; // 10%
        } else if (service === 'Nửa suất') {
            servicePricePerDay = roomPricePerNight * 0.2; // 20%
        } else if (service === 'Toàn bộ') {
            servicePricePerDay = roomPricePerNight * 0.3; // 30%
        }
        
        // Tính tổng giá
        const totalRoomPrice = roomPricePerNight * diffDays;
        const totalServicePrice = servicePricePerDay * diffDays;
        const finalTotal = totalRoomPrice + totalServicePrice;
        
        // Hiển thị giá
        if (priceSummary) {
            document.getElementById('roomPricePerNight').textContent = formatPrice(roomPricePerNight) + ' ₫/đêm';
            document.getElementById('numberOfNights').textContent = diffDays + ' đêm';
            document.getElementById('totalRoomPrice').textContent = formatPrice(totalRoomPrice) + ' ₫';
            document.getElementById('servicePricePerDay').textContent = formatPrice(servicePricePerDay) + ' ₫/ngày';
            document.getElementById('totalServicePrice').textContent = formatPrice(totalServicePrice) + ' ₫';
            document.getElementById('finalTotalPrice').textContent = formatPrice(finalTotal) + ' ₫';
            priceSummary.style.display = 'block';
        }
    }
    
    // Format giá tiền VND
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price);
    }
    
    // Add event listeners - load rooms when room type is selected
    document.addEventListener('DOMContentLoaded', function() {
        const roomTypeSelect = document.getElementById('roomTypeSelect');
        const serviceSelect = document.getElementById('serviceSelect');
        
        if (roomTypeSelect) {
            roomTypeSelect.addEventListener('change', function() {
                loadAvailableRooms();
                calculatePrice();
            });
        }
        
        if (serviceSelect) {
            serviceSelect.addEventListener('change', calculatePrice);
        }
        
        // Tính giá và reload danh sách phòng khi người dùng thay đổi ngày
        const checkinDate = document.getElementById('checkinDate');
        const checkoutDate = document.getElementById('checkoutDate');
        if (checkinDate) {
            checkinDate.addEventListener('change', function() {
                calculatePrice();
                // Reload danh sách phòng để cập nhật trạng thái đặt phòng
                if (roomTypeSelect && roomTypeSelect.value) {
                    loadAvailableRooms();
                }
            });
        }
        if (checkoutDate) {
            checkoutDate.addEventListener('change', function() {
                calculatePrice();
                // Reload danh sách phòng để cập nhật trạng thái đặt phòng
                if (roomTypeSelect && roomTypeSelect.value) {
                    loadAvailableRooms();
                }
            });
        }
    });
    
    // ============ User Dropdown Menu ============
    
    // Toggle User Dropdown
    function toggleUserDropdown() {
      const dropdown = document.getElementById('userDropdown');
      const userLink = document.querySelector('.user-link');
      
      dropdown.classList.toggle('show');
      userLink.classList.toggle('active');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const userMenu = document.querySelector('.user-menu');
      const dropdown = document.getElementById('userDropdown');
      const userLink = document.querySelector('.user-link');
      
      if (userMenu && !userMenu.contains(event.target)) {
        dropdown.classList.remove('show');
        userLink.classList.remove('active');
      }
    });
    
    // Prevent dropdown from closing when clicking inside
    const userDropdown = document.getElementById('userDropdown');
    if(userDropdown) {
      userDropdown.addEventListener('click', function(event) {
        event.stopPropagation();
      });
    }
</script>

<!-- ==================== AI CHATBOT WIDGET ==================== -->
<?php include 'chatbot/widget.php'; ?>

</html>