<?php
/**
 * Chatbot API - Backend xử lý chat và gọi Gemini AI
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Include configurations with error handling
try {
    if (!file_exists('../config.php')) {
        throw new Exception('Database config file not found');
    }
    require_once '../config.php';
    
    if (!file_exists('config.php')) {
        throw new Exception('Chatbot config file not found');
    }
    require_once 'config.php';
    
    // Check database connection
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Configuration error: ' . $e->getMessage(),
        'debug' => [
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
    exit;
}

// CORS headers (if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$action = $input['action'] ?? '';
$sessionId = $input['session_id'] ?? session_id();

// Validate input
if (empty($userMessage) && empty($action)) {
    echo json_encode(['error' => 'Message hoặc action là bắt buộc']);
    exit;
}

// Get user info if logged in
$userEmail = $_SESSION['usermail'] ?? null;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Handle quick actions
if (!empty($action)) {
    $response = handleQuickAction($action);
    echo json_encode([
        'success' => true,
        'response' => $response,
        'quick_replies' => getQuickReplies()
    ]);
    exit;
}

// Process user message with Gemini AI
try {
    $botResponse = callGeminiAPI($userMessage, $sessionId);
    
    // Save to database
    saveToDatabase($userEmail, $sessionId, $userMessage, $botResponse, $ipAddress);
    
    // Return response
    echo json_encode([
        'success' => true,
        'response' => $botResponse,
        'quick_replies' => getQuickReplies()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Xin lỗi, tôi đang gặp sự cố. Vui lòng thử lại sau.',
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}

// ==================== FUNCTIONS ====================

/**
 * Call Gemini API
 */
function callGeminiAPI($userMessage, $sessionId) {
    global $conn;
    
    // Get conversation history
    $history = getConversationHistory($sessionId);
    
    // Build conversation context
    $contents = [];
    
    // Add system prompt as first message
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => getSystemPrompt()]]
    ];
    $contents[] = [
        'role' => 'model',
        'parts' => [['text' => 'Chào anh/chị! Tôi là BlueBird Assistant, trợ lý ảo của Khách Sạn BlueBird. Tôi có thể giúp gì cho anh/chị hôm nay? 😊']]
    ];
    
    // Add conversation history
    foreach ($history as $msg) {
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $msg['user_message']]]
        ];
        $contents[] = [
            'role' => 'model',
            'parts' => [['text' => $msg['bot_response']]]
        ];
    }
    
    // Add current message
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $userMessage]]
    ];
    
    // Prepare API request
    $requestData = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 500,
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ]
        ]
    ];
    
    // Make API call
    $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_TIMEOUT, RESPONSE_TIMEOUT);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Handle errors
    if ($curlError) {
        throw new Exception("cURL Error: $curlError");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("API Error (HTTP $httpCode): $response");
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Invalid API response format");
    }
    
    return trim($result['candidates'][0]['content']['parts'][0]['text']);
}

/**
 * Get conversation history from database
 */
function getConversationHistory($sessionId) {
    global $conn;
    
    $sessionId = mysqli_real_escape_string($conn, $sessionId);
    $limit = MAX_HISTORY_LENGTH;
    
    $sql = "SELECT user_message, bot_response 
            FROM chat_history 
            WHERE session_id = '$sessionId' 
            ORDER BY id DESC 
            LIMIT $limit";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        return [];
    }
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    return array_reverse($history); // Oldest first
}

/**
 * Save chat to database
 */
function saveToDatabase($userEmail, $sessionId, $userMessage, $botResponse, $ipAddress) {
    global $conn;
    
    $userEmail = $userEmail ? mysqli_real_escape_string($conn, $userEmail) : NULL;
    $sessionId = mysqli_real_escape_string($conn, $sessionId);
    $userMessage = mysqli_real_escape_string($conn, $userMessage);
    $botResponse = mysqli_real_escape_string($conn, $botResponse);
    $ipAddress = mysqli_real_escape_string($conn, $ipAddress);
    
    $emailValue = $userEmail ? "'$userEmail'" : "NULL";
    
    $sql = "INSERT INTO chat_history (user_email, session_id, user_message, bot_response, ip_address) 
            VALUES ($emailValue, '$sessionId', '$userMessage', '$botResponse', '$ipAddress')";
    
    mysqli_query($conn, $sql);
}

/**
 * Handle quick action buttons
 */
function handleQuickAction($action) {
    global $HOTEL_INFO;
    
    switch ($action) {
        case 'show_rooms':
            $response = "🏨 **CÁC LOẠI PHÒNG TẠI BLUEBIRD HOTEL:**\n\n";
            foreach ($HOTEL_INFO['rooms'] as $room) {
                $response .= "✨ **{$room['type']}**: {$room['price_display']}/đêm\n";
                $response .= "   {$room['description']}\n\n";
            }
            $response .= "Anh/chị quan tâm loại phòng nào ạ? 😊";
            return $response;
            
        case 'show_prices':
            $response = "💰 **BẢNG GIÁ PHÒNG:**\n\n";
            foreach ($HOTEL_INFO['rooms'] as $room) {
                $response .= "• {$room['type']}: **{$room['price_display']}**/đêm\n";
            }
            $response .= "\n📝 *Giá đã bao gồm VAT*\n";
            $response .= "💳 *Chấp nhận: Tiền mặt, Thẻ, MoMo, VNPay*\n\n";
            $response .= "Anh/chị muốn đặt phòng không ạ? 🏨";
            return $response;
            
        case 'show_facilities':
            $response = "✨ **TIỆN NGHI TẠI BLUEBIRD HOTEL:**\n\n";
            foreach ($HOTEL_INFO['facilities'] as $facility) {
                $response .= "🔹 **{$facility['name']}**\n";
                $response .= "   {$facility['description']}\n";
                $response .= "   ⏰ {$facility['hours']}\n\n";
            }
            $response .= "Anh/chị muốn biết thêm về tiện nghi nào không ạ? 😊";
            return $response;
            
        case 'show_contact':
            $contact = $HOTEL_INFO['contact'];
            $response = "📞 **THÔNG TIN LIÊN HỆ:**\n\n";
            $response .= "☎️ Hotline: {$contact['phone']}\n";
            $response .= "📧 Email: {$contact['email']}\n";
            $response .= "📍 Địa chỉ: {$contact['address']}\n\n";
            $response .= "Chúng tôi sẵn sàng phục vụ 24/7! 🏨";
            return $response;
            
        case 'book_now':
            $response = "🎯 **ĐẶT PHÒNG NGAY:**\n\n";
            $response .= "Để đặt phòng, anh/chị vui lòng:\n\n";
            $response .= "1️⃣ Click nút \"Đặt Phòng\" ở menu trên\n";
            $response .= "2️⃣ Điền thông tin vào form\n";
            $response .= "3️⃣ Chọn phương thức thanh toán\n\n";
            $response .= "Hoặc tôi có thể tư vấn thêm về loại phòng phù hợp với anh/chị! 😊\n\n";
            $response .= "Anh/chị cần tư vấn gì không ạ?";
            return $response;
            
        default:
            return "Xin lỗi, tôi không hiểu yêu cầu này. Anh/chị có thể hỏi lại được không? 😊";
    }
}

/**
 * Get quick reply buttons
 */
function getQuickReplies() {
    global $QUICK_REPLIES;
    return $QUICK_REPLIES;
}
?>

