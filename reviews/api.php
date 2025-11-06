<?php
/**
 * Reviews API - Backend xử lý đánh giá
 */

session_start();
header('Content-Type: application/json');

// Include configurations
try {
    if (!file_exists('../config.php')) {
        throw new Exception('Database config file not found');
    }
    require_once '../config.php';
    
    // Check database connection
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Auto-create reviews table if it doesn't exist
    $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
    if (mysqli_num_rows($checkTable) == 0) {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `reviews` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `user_email` varchar(100) NOT NULL,
          `username` varchar(100) NOT NULL,
          `avatar` varchar(255) DEFAULT 'default-avatar.png',
          `rating` int(1) NOT NULL,
          `review_text` text NOT NULL,
          `status` enum('pending','approved','rejected') DEFAULT 'approved',
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `user_email` (`user_email`),
          KEY `status` (`status`),
          KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!mysqli_query($conn, $createTableSQL)) {
            throw new Exception('Failed to create reviews table: ' . mysqli_error($conn));
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Configuration error: ' . $e->getMessage()
    ]);
    exit;
}

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET request - Get reviews
if ($method === 'GET') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = isset($_GET['status']) ? $_GET['status'] : 'approved';
    
    $status = mysqli_real_escape_string($conn, $status);
    $limit = max(1, min(100, $limit)); // Limit between 1 and 100
    
    $sql = "SELECT r.*, s.Username, s.avatar 
            FROM reviews r
            LEFT JOIN signup s ON r.user_id = s.UserID
            WHERE r.status = '$status'
            ORDER BY r.created_at DESC
            LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . mysqli_error($conn)
        ]);
        exit;
    }
    
    $reviews = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Use avatar from reviews table or fallback to signup table
        $avatar = !empty($row['avatar']) ? $row['avatar'] : 'default-avatar.png';
        $username = !empty($row['Username']) ? $row['Username'] : $row['username'];
        
        $reviews[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'username' => $username,
            'avatar' => $avatar,
            'rating' => (int)$row['rating'],
            'review_text' => $row['review_text'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'count' => count($reviews)
    ]);
    exit;
}

// Handle POST request - Submit review
if ($method === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['usermail']) || empty($_SESSION['usermail'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Bạn cần đăng nhập để đánh giá'
        ]);
        exit;
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST; // Fallback to form data
    }
    
    $rating = isset($input['rating']) ? (int)$input['rating'] : 0;
    $reviewText = isset($input['review_text']) ? trim($input['review_text']) : '';
    $userEmail = $_SESSION['usermail'];
    
    // Validation
    if ($rating < 1 || $rating > 5) {
        echo json_encode([
            'success' => false,
            'error' => 'Vui lòng chọn số sao từ 1 đến 5'
        ]);
        exit;
    }
    
    if (empty($reviewText) || strlen($reviewText) < 10) {
        echo json_encode([
            'success' => false,
            'error' => 'Nội dung đánh giá phải có ít nhất 10 ký tự'
        ]);
        exit;
    }
    
    // Giới hạn tối đa 10000 ký tự để tránh database quá lớn (không bắt buộc)
    if (strlen($reviewText) > 10000) {
        echo json_encode([
            'success' => false,
            'error' => 'Nội dung đánh giá quá dài. Vui lòng rút gọn lại.'
        ]);
        exit;
    }
    
    // Get user info
    $userEmail = mysqli_real_escape_string($conn, $userEmail);
    $userQuery = "SELECT UserID, Username, avatar FROM signup WHERE Email = '$userEmail'";
    $userResult = mysqli_query($conn, $userQuery);
    
    if (!$userResult || mysqli_num_rows($userResult) === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Không tìm thấy thông tin người dùng'
        ]);
        exit;
    }
    
    $userData = mysqli_fetch_assoc($userResult);
    $userId = (int)$userData['UserID'];
    $username = mysqli_real_escape_string($conn, $userData['Username']);
    $avatar = mysqli_real_escape_string($conn, $userData['avatar'] ?? 'default-avatar.png');
    
    // Check if user already reviewed (optional - can be removed if you want multiple reviews)
    $checkReview = "SELECT id FROM reviews WHERE user_id = $userId AND status = 'approved'";
    $checkResult = mysqli_query($conn, $checkReview);
    
    // Escape data
    $rating = mysqli_real_escape_string($conn, $rating);
    $reviewText = mysqli_real_escape_string($conn, $reviewText);
    
    // Insert review
    $sql = "INSERT INTO reviews (user_id, user_email, username, avatar, rating, review_text, status) 
            VALUES ($userId, '$userEmail', '$username', '$avatar', $rating, '$reviewText', 'approved')";
    
    if (mysqli_query($conn, $sql)) {
        $reviewId = mysqli_insert_id($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cảm ơn bạn đã đánh giá!',
            'review_id' => $reviewId
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lưu đánh giá: ' . mysqli_error($conn)
        ]);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Method not allowed'
]);
?>

