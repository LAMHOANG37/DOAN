<?php
include '../config.php';
include '../admin/room_manager.php';
session_start();

// Check if user is logged in
$usermail = $_SESSION['usermail'] ?? '';
$isLoggedIn = !empty($usermail);

// Get room type from URL parameter
$roomType = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : '';

// Validate room type
$validRoomTypes = ['Phòng Cao Cấp', 'Phòng Sang Trọng', 'Nhà Khách', 'Phòng Đơn'];
if (!in_array($roomType, $validRoomTypes)) {
    header("Location: ../index.php");
    exit;
}

// Get user info if logged in
$username = 'Khách';
$avatar = 'default-avatar.png';
$user_address = '';
$user_country = 'Vietnam'; // Default country

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
        // Default to Vietnam if no country stored
        $user_country = 'Vietnam';
    }
}

// Room details configuration
$roomDetails = [
    'Phòng Cao Cấp' => [
        'price' => 3000000,
        'price_formatted' => '3.000.000',
        'image_class' => 'h1',
        'description' => 'Phòng cao cấp với không gian rộng rãi, nội thất sang trọng và đầy đủ tiện nghi hiện đại. Phù hợp cho các chuyến công tác hoặc kỳ nghỉ thư giãn.',
        'amenities' => [
            ['icon' => 'fa-wifi', 'name' => 'WiFi miễn phí'],
            ['icon' => 'fa-burger', 'name' => 'Dịch vụ ăn uống 24/7'],
            ['icon' => 'fa-spa', 'name' => 'Spa & Massage'],
            ['icon' => 'fa-dumbbell', 'name' => 'Phòng gym'],
            ['icon' => 'fa-person-swimming', 'name' => 'Hồ bơi'],
            ['icon' => 'fa-tv', 'name' => 'TV màn hình phẳng'],
            ['icon' => 'fa-snowflake', 'name' => 'Điều hòa'],
            ['icon' => 'fa-shower', 'name' => 'Phòng tắm sang trọng'],
            ['icon' => 'fa-bed', 'name' => 'Giường King Size'],
            ['icon' => 'fa-bath', 'name' => 'Bồn tắm']
        ],
        'size' => '45 m²',
        'capacity' => '2-3 người',
        'bed' => '1 giường King Size'
    ],
    'Phòng Sang Trọng' => [
        'price' => 2000000,
        'price_formatted' => '2.000.000',
        'image_class' => 'h2',
        'description' => 'Phòng sang trọng với thiết kế tinh tế, không gian thoáng đãng và tiện nghi cao cấp. Lý tưởng cho các cặp đôi hoặc gia đình nhỏ.',
        'amenities' => [
            ['icon' => 'fa-wifi', 'name' => 'WiFi miễn phí'],
            ['icon' => 'fa-burger', 'name' => 'Dịch vụ ăn uống 24/7'],
            ['icon' => 'fa-spa', 'name' => 'Spa & Massage'],
            ['icon' => 'fa-dumbbell', 'name' => 'Phòng gym'],
            ['icon' => 'fa-tv', 'name' => 'TV màn hình phẳng'],
            ['icon' => 'fa-snowflake', 'name' => 'Điều hòa'],
            ['icon' => 'fa-shower', 'name' => 'Phòng tắm hiện đại'],
            ['icon' => 'fa-bed', 'name' => 'Giường Queen Size']
        ],
        'size' => '35 m²',
        'capacity' => '2 người',
        'bed' => '1 giường Queen Size'
    ],
    'Nhà Khách' => [
        'price' => 1500000,
        'price_formatted' => '1.500.000',
        'image_class' => 'h3',
        'description' => 'Nhà khách ấm cúng với không gian rộng rãi, phù hợp cho gia đình hoặc nhóm bạn. Đầy đủ tiện nghi cần thiết cho kỳ nghỉ thoải mái.',
        'amenities' => [
            ['icon' => 'fa-wifi', 'name' => 'WiFi miễn phí'],
            ['icon' => 'fa-burger', 'name' => 'Dịch vụ ăn uống 24/7'],
            ['icon' => 'fa-spa', 'name' => 'Spa & Massage'],
            ['icon' => 'fa-tv', 'name' => 'TV màn hình phẳng'],
            ['icon' => 'fa-snowflake', 'name' => 'Điều hòa'],
            ['icon' => 'fa-shower', 'name' => 'Phòng tắm'],
            ['icon' => 'fa-bed', 'name' => 'Giường đôi']
        ],
        'size' => '30 m²',
        'capacity' => '2-4 người',
        'bed' => '2 giường đôi'
    ],
    'Phòng Đơn' => [
        'price' => 1000000,
        'price_formatted' => '1.000.000',
        'image_class' => 'h4',
        'description' => 'Phòng đơn tiện nghi với không gian gọn gàng, phù hợp cho khách du lịch một mình hoặc công tác ngắn ngày. Giá cả hợp lý, chất lượng đảm bảo.',
        'amenities' => [
            ['icon' => 'fa-wifi', 'name' => 'WiFi miễn phí'],
            ['icon' => 'fa-burger', 'name' => 'Dịch vụ ăn uống 24/7'],
            ['icon' => 'fa-tv', 'name' => 'TV màn hình phẳng'],
            ['icon' => 'fa-snowflake', 'name' => 'Điều hòa'],
            ['icon' => 'fa-shower', 'name' => 'Phòng tắm'],
            ['icon' => 'fa-bed', 'name' => 'Giường đơn']
        ],
        'size' => '20 m²',
        'capacity' => '1 người',
        'bed' => '1 giường đơn'
    ]
];

$room = $roomDetails[$roomType] ?? null;
if (!$room) {
    header("Location: ../index.php");
    exit;
}

// Get available rooms for this type
$roomsSql = "SELECT room_number, status FROM rooms WHERE room_type = '$roomType' ORDER BY room_number ASC";
$roomsResult = mysqli_query($conn, $roomsSql);
$availableRooms = [];
if ($roomsResult) {
    while ($row = mysqli_fetch_assoc($roomsResult)) {
        $availableRooms[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($roomType); ?> - BlueBird Hotel</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    
    <!-- Sweet Alert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="room-details.css">
    <style>
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
        z-index: 20003;
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
      
      .dropdown-item.logout:hover {
        background: #fee;
        color: #c0392b;
      }
      
      /* Close dropdown when clicking outside */
      .user-menu {
        position: relative;
      }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="logo">
            <img class="bluebirdlogo" src="../image/bluebirdlogo.png" alt="logo">
            <p>BLUEBIRD</p>
        </div>
        <ul>
            <li><a href="../index.php#firstsection">Trang Chủ</a></li>
            <li><a href="../index.php#secondsection">Phòng</a></li>
            <li><a href="../index.php#thirdsection">Tiện Nghi</a></li>
            <li><a href="../index.php#contactus">Liên Hệ</a></li>
            
            <?php if($isLoggedIn): ?>
            <!-- Logged In: User Dropdown -->
            <li class="user-menu">
                <a href="javascript:void(0)" class="user-link" onclick="toggleUserDropdown()">
                    <img src="../user/uploads/avatars/<?php echo htmlspecialchars($avatar); ?>" 
                         alt="Avatar" 
                         class="nav-avatar"
                         onerror="this.src='../image/Profile.png'">
                    <span>Xin chào, <strong><?php echo htmlspecialchars($username); ?></strong></span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </a>
                
                <!-- Dropdown Menu -->
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <img src="../user/uploads/avatars/<?php echo htmlspecialchars($avatar); ?>" 
                             alt="Avatar" 
                             class="dropdown-avatar"
                             onerror="this.src='../image/Profile.png'">
                        <div class="dropdown-user-info">
                            <strong><?php echo htmlspecialchars($username); ?></strong>
                            <small><?php echo htmlspecialchars($usermail); ?></small>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="../user/profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Tài Khoản Của Tôi</span>
                    </a>
                    <a href="../logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng Xuất</span>
                    </a>
                </div>
            </li>
            
            <?php else: ?>
            <!-- Not Logged In: Login/Register Button -->
            <li>
                <a href="../login.php" class="login-btn" style="
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

    <!-- Room Details Section -->
    <div class="room-details-container">
        <!-- Room Image -->
        <div class="room-hero">
            <div class="hotelphoto <?php echo $room['image_class']; ?>"></div>
            <div class="room-hero-overlay">
                <div class="container">
                    <h1 class="room-title"><?php echo htmlspecialchars($roomType); ?></h1>
                    <p class="room-subtitle">Từ <span class="price-highlight"><?php echo $room['price_formatted']; ?> VNĐ</span> / đêm</p>
                </div>
            </div>
        </div>

        <div class="container py-5">
            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Description -->
                    <section class="room-section">
                        <h2 class="section-title">Mô Tả</h2>
                        <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                    </section>

                    <!-- Room Info -->
                    <section class="room-section">
                        <h2 class="section-title">Thông Tin Phòng</h2>
                        <div class="room-info-grid">
                            <div class="info-item">
                                <i class="fas fa-ruler-combined"></i>
                                <div>
                                    <strong>Diện tích</strong>
                                    <p><?php echo $room['size']; ?></p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-users"></i>
                                <div>
                                    <strong>Sức chứa</strong>
                                    <p><?php echo $room['capacity']; ?></p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-bed"></i>
                                <div>
                                    <strong>Giường</strong>
                                    <p><?php echo $room['bed']; ?></p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Amenities -->
                    <section class="room-section">
                        <h2 class="section-title">Tiện Nghi</h2>
                        <div class="amenities-grid">
                            <?php foreach($room['amenities'] as $amenity): ?>
                            <div class="amenity-item">
                                <i class="fas <?php echo $amenity['icon']; ?>"></i>
                                <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Reviews Section -->
                    <section class="room-section" id="reviewsSection">
                        <h2 class="section-title">
                            <i class="fas fa-star"></i> Đánh Giá Khách Hàng
                            <span class="reviews-count" id="reviewsCount"></span>
                        </h2>
                        
                        <!-- Reviews List -->
                        <div id="reviewsList" class="reviews-list">
                            <div class="loading-reviews">
                                <i class="fas fa-spinner fa-spin"></i> Đang tải đánh giá...
                            </div>
                        </div>

                        <!-- Review Form (Only for logged in users) -->
                        <?php if($isLoggedIn): ?>
                        <div class="review-form-section">
                            <h3 class="review-form-title">Viết Đánh Giá Của Bạn</h3>
                            <form id="reviewForm" class="review-form">
                                <input type="hidden" name="room_type" value="<?php echo htmlspecialchars($roomType); ?>">
                                
                                <div class="form-group">
                                    <label>Đánh giá của bạn *</label>
                                    <div class="rating-input">
                                        <input type="radio" id="star5" name="rating" value="5" required>
                                        <label for="star5" class="star-label"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4" class="star-label"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3" class="star-label"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2" class="star-label"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1" class="star-label"><i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="reviewText">Nội dung đánh giá *</label>
                                    <textarea
                                        id="reviewText"
                                        name="review_text"
                                        class="review-textarea"
                                        placeholder="Chia sẻ trải nghiệm của bạn tại <?php echo htmlspecialchars($roomType); ?>..."
                                        required
                                        minlength="10"
                                        rows="5"
                                    ></textarea>
                                    <small class="char-count"><span id="charCount">0</span> ký tự</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary submit-review-btn">
                                    <i class="fas fa-paper-plane"></i> Gửi Đánh Giá
                                </button>
                            </form>
                            <div id="reviewFormMessage" class="review-form-message"></div>
                        </div>
                        <?php else: ?>
                        <div class="review-login-prompt">
                            <i class="fas fa-info-circle"></i>
                            <p>Bạn muốn chia sẻ trải nghiệm của mình?</p>
                            <a href="../login.php" class="btn btn-primary">Đăng Nhập Để Đánh Giá</a>
                        </div>
                        <?php endif; ?>
                    </section>
                </div>

                <!-- Booking Sidebar -->
                <div class="col-lg-4">
                    <div class="booking-card">
                        <div class="booking-header">
                            <h3>Đặt Phòng</h3>
                            <div class="price-box">
                                <span class="price-label">Giá mỗi đêm</span>
                                <span class="price-value"><?php echo $room['price_formatted']; ?> VNĐ</span>
                            </div>
                        </div>

                        <?php if(!$isLoggedIn): ?>
                        <div class="booking-login-prompt">
                            <i class="fas fa-info-circle"></i>
                            <p>Vui lòng đăng nhập để đặt phòng</p>
                            <a href="../login.php" class="btn btn-primary w-100">Đăng Nhập</a>
                        </div>
                        <?php else: ?>
                        <form action="process_booking.php" method="POST" class="booking-form" id="bookingForm" onsubmit="enableSelectBeforeSubmit()">
                            <input type="hidden" name="RoomType" value="<?php echo htmlspecialchars($roomType); ?>">
                            
                            <!-- Guest Info -->
                            <div class="form-group">
                                <label>Họ và Tên *</label>
                                <input type="text" name="Name" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="Email" class="form-control" value="<?php echo htmlspecialchars($usermail); ?>" required readonly>
                            </div>

                            <div class="form-group">
                                <label>Quốc Gia *</label>
                                <select name="Country" class="form-control" required>
                                    <option value="">Chọn quốc gia *</option>
                                    <?php
                                    $countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
                                    foreach($countries as $country):
                                        $selected = ($country == $user_country) ? "selected" : "";
                                        echo '<option value="'.htmlspecialchars($country).'" '.$selected.'>'.htmlspecialchars($country).'</option>';
                                    endforeach;
                                    ?>
                                </select>
                            </div>

                            <!-- Dates -->
                            <div class="form-group">
                                <label>Ngày Nhận Phòng *</label>
                                <input type="date" name="cin" id="checkinDate" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label>Ngày Trả Phòng *</label>
                                <input type="date" name="cout" id="checkoutDate" class="form-control" required>
                            </div>

                            <!-- Service -->
                            <div class="form-group">
                                <label>Dịch Vụ *</label>
                                <select name="Service" class="form-control" required>
                                    <option value="Chỉ phòng">Chỉ phòng</option>
                                    <option value="Bữa sáng">Bữa sáng (+10%)</option>
                                    <option value="Nửa suất">Nửa suất (+20%)</option>
                                    <option value="Toàn bộ">Toàn bộ (+30%)</option>
                                </select>
                            </div>

                            <!-- Room Selection -->
                            <div class="form-group">
                                <label>Chọn Phòng *</label>
                                <select name="RoomNumber" id="roomSelect" class="form-control" required>
                                    <option value="">-- Chọn phòng --</option>
                                </select>
                                <small class="text-muted">Vui lòng chọn ngày để xem phòng trống</small>
                            </div>

                            <!-- Price Summary -->
                            <div class="price-summary" id="priceSummary" style="display: none;">
                                <div class="summary-row">
                                    <span>Giá phòng (x <span id="nights">0</span> đêm)</span>
                                    <span id="roomTotal">0 VNĐ</span>
                                </div>
                                <div class="summary-row">
                                    <span>Dịch vụ</span>
                                    <span id="serviceTotal">0 VNĐ</span>
                                </div>
                                <div class="summary-row total">
                                    <span><strong>Tổng cộng</strong></span>
                                    <span id="finalTotal"><strong>0 VNĐ</strong></span>
                                </div>
                            </div>

                            <button type="submit" name="guestdetailsubmit" class="btn btn-success btn-lg w-100 mt-3">
                                <i class="fas fa-check"></i> Xác Nhận Đặt Phòng
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <p>&copy; 2024 BlueBird Hotel. All rights reserved.</p>
    </footer>

    <script>
        const roomPrice = <?php echo $room['price']; ?>;
        const roomType = '<?php echo $roomType; ?>';
        const checkinInput = document.getElementById('checkinDate');
        const checkoutInput = document.getElementById('checkoutDate');
        const roomSelect = document.getElementById('roomSelect');
        const priceSummary = document.getElementById('priceSummary');

        // Set minimum checkout date
        checkinInput.addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            checkoutInput.min = checkinDate.toISOString().split('T')[0];
            if (checkoutInput.value && checkoutInput.value <= this.value) {
                checkoutInput.value = '';
            }
            loadRooms();
        });

        checkoutInput.addEventListener('change', function() {
            if (checkinInput.value) {
                loadRooms();
                calculatePrice();
            }
        });

        // Load available rooms
        function loadRooms() {
            const cin = checkinInput.value;
            const cout = checkoutInput.value;

            if (!cin || !cout) {
                roomSelect.innerHTML = '<option value="">-- Chọn ngày trước --</option>';
                roomSelect.disabled = true;
                return;
            }

            if (new Date(cout) <= new Date(cin)) {
                roomSelect.innerHTML = '<option value="">-- Ngày trả phòng phải sau ngày nhận --</option>';
                roomSelect.disabled = true;
                return;
            }

            // Show loading
            roomSelect.innerHTML = '<option value="">Đang tải...</option>';
            roomSelect.disabled = true;
            
            const queryString = `../admin/get_available_rooms.php?roomType=${encodeURIComponent(roomType)}&checkIn=${encodeURIComponent(cin)}&checkOut=${encodeURIComponent(cout)}`;
            
            console.log('Loading rooms with query:', queryString);
            
            fetch(queryString)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        console.log('Response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e, 'Text:', text);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    roomSelect.innerHTML = '';
                    
                    if (!data || !data.success) {
                        let errorMsg = 'Không thể tải danh sách phòng';
                        if (data && data.message) {
                            errorMsg = data.message;
                        }
                        roomSelect.innerHTML = `<option value="">${errorMsg}</option>`;
                        roomSelect.disabled = true;
                        return;
                    }
                    
                    if (!data.rooms || data.rooms.length === 0) {
                        roomSelect.innerHTML = '<option value="">Không có phòng nào</option>';
                        roomSelect.disabled = true;
                        return;
                    }
                    
                    let hasAvailableRoom = false;
                    data.rooms.forEach(room => {
                        const option = document.createElement('option');
                        option.value = room.room_number;
                        
                        if (room.is_booked) {
                            option.textContent = `Phòng ${room.room_number} - Đã được đặt ❌`;
                            option.disabled = true;
                            option.style.color = '#dc3545';
                            option.style.fontStyle = 'italic';
                        } else {
                            option.textContent = `Phòng ${room.room_number} ✓`;
                            hasAvailableRoom = true;
                        }
                        
                        roomSelect.appendChild(option);
                    });
                    
                    roomSelect.disabled = !hasAvailableRoom;
                })
                .catch(error => {
                    console.error('Error loading rooms:', error);
                    roomSelect.innerHTML = '<option value="">Lỗi tải danh sách phòng. Vui lòng thử lại.</option>';
                    roomSelect.disabled = true;
                });
        }

        // Calculate price
        function calculatePrice() {
            const cin = checkinInput.value;
            const cout = checkoutInput.value;
            const serviceSelect = document.querySelector('select[name="Service"]');

            if (!cin || !cout || new Date(cout) <= new Date(cin)) {
                priceSummary.style.display = 'none';
                return;
            }

            const checkin = new Date(cin);
            const checkout = new Date(cout);
            const nights = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
            
            if (nights <= 0) {
                priceSummary.style.display = 'none';
                return;
            }

            const roomTotal = roomPrice * nights;
            let serviceMultiplier = 0;
            
            if (serviceSelect.value === 'Bữa sáng') serviceMultiplier = 0.1;
            else if (serviceSelect.value === 'Nửa suất') serviceMultiplier = 0.2;
            else if (serviceSelect.value === 'Toàn bộ') serviceMultiplier = 0.3;

            const serviceTotal = roomTotal * serviceMultiplier;
            const finalTotal = roomTotal + serviceTotal;

            document.getElementById('nights').textContent = nights;
            document.getElementById('roomTotal').textContent = formatPrice(roomTotal);
            document.getElementById('serviceTotal').textContent = formatPrice(serviceTotal);
            document.getElementById('finalTotal').innerHTML = '<strong>' + formatPrice(finalTotal) + '</strong>';
            
            priceSummary.style.display = 'block';
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
        }

        // Recalculate when service changes
        document.querySelector('select[name="Service"]')?.addEventListener('change', calculatePrice);

        // Enable select before submit
        function enableSelectBeforeSubmit() {
            if (roomSelect.disabled) {
                roomSelect.disabled = false;
            }
        }

        // ==================== REVIEWS FUNCTIONALITY ====================
        const currentRoomType = '<?php echo htmlspecialchars($roomType); ?>';
        
        // Load reviews for this room type
        async function loadRoomReviews() {
            try {
                const response = await fetch(`../reviews/api.php?limit=20&status=approved&room_type=${encodeURIComponent(currentRoomType)}`);
                const data = await response.json();
                
                const reviewsList = document.getElementById('reviewsList');
                const reviewsCount = document.getElementById('reviewsCount');
                
                if (data.success && data.reviews && data.reviews.length > 0) {
                    reviewsCount.textContent = `(${data.count})`;
                    reviewsList.innerHTML = '';
                    
                    data.reviews.forEach(review => {
                        const reviewCard = createReviewCard(review);
                        reviewsList.appendChild(reviewCard);
                    });
                } else {
                    reviewsCount.textContent = '(0)';
                    reviewsList.innerHTML = '<div class="no-reviews"><i class="fas fa-comment-slash"></i><p>Chưa có đánh giá nào cho loại phòng này. Hãy là người đầu tiên đánh giá!</p></div>';
                }
            } catch (error) {
                console.error('Error loading reviews:', error);
                document.getElementById('reviewsList').innerHTML = '<div class="error-reviews"><i class="fas fa-exclamation-triangle"></i><p>Không thể tải đánh giá. Vui lòng thử lại sau.</p></div>';
            }
        }

        function createReviewCard(review) {
            const card = document.createElement('div');
            card.className = 'review-card-item';
            
            // Generate stars
            let starsHTML = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= review.rating) {
                    starsHTML += '<i class="fas fa-star"></i>';
                } else {
                    starsHTML += '<i class="far fa-star"></i>';
                }
            }
            
            // Format date
            const reviewDate = new Date(review.created_at);
            const formattedDate = formatDate(reviewDate);
            
            card.innerHTML = `
                <div class="review-card-header">
                    <img src="../user/uploads/avatars/${review.avatar}" alt="${escapeHtml(review.username)}" class="review-avatar" onerror="this.src='../image/Profile.png'">
                    <div class="review-user-info">
                        <div class="review-username">${escapeHtml(review.username)}</div>
                        <div class="review-date"><i class="far fa-clock"></i> ${formattedDate}</div>
                    </div>
                </div>
                <div class="review-stars">${starsHTML}</div>
                <div class="review-text">${escapeHtml(review.review_text)}</div>
            `;
            
            return card;
        }

        function formatDate(date) {
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) return 'Hôm nay';
            if (diffDays === 1) return 'Hôm qua';
            if (diffDays < 7) return `${diffDays} ngày trước`;
            if (diffDays < 30) return `${Math.floor(diffDays / 7)} tuần trước`;
            if (diffDays < 365) return `${Math.floor(diffDays / 30)} tháng trước`;
            return `${Math.floor(diffDays / 365)} năm trước`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Handle review form submission
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            // Character counter
            const reviewText = document.getElementById('reviewText');
            const charCount = document.getElementById('charCount');
            
            if (reviewText && charCount) {
                reviewText.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                });
            }
            
            // Form submit
            reviewForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('.submit-review-btn');
                const messageDiv = document.getElementById('reviewFormMessage');
                const formData = new FormData(this);
                
                // Get rating
                const rating = formData.get('rating');
                const reviewText = formData.get('review_text');
                const roomType = formData.get('room_type');
                
                if (!rating || !reviewText || reviewText.length < 10) {
                    messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Vui lòng chọn số sao và nhập ít nhất 10 ký tự</div>';
                    return;
                }
                
                // Disable button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
                messageDiv.innerHTML = '';
                
                try {
                    const response = await fetch('../reviews/api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            rating: parseInt(rating),
                            review_text: reviewText,
                            room_type: roomType
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                        reviewForm.reset();
                        charCount.textContent = '0';
                        
                        // Reload reviews
                        setTimeout(() => {
                            loadRoomReviews();
                            messageDiv.innerHTML = '';
                        }, 2000);
                    } else {
                        messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + (data.error || 'Có lỗi xảy ra') + '</div>';
                    }
                } catch (error) {
                    console.error('Error submitting review:', error);
                    messageDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Không thể gửi đánh giá. Vui lòng thử lại sau.</div>';
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi Đánh Giá';
                }
            });
        }

        // Load reviews on page load
        loadRoomReviews();

        // ==================== USER DROPDOWN FUNCTIONALITY ====================
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
    </script>
</body>
</html>

