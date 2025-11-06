<!-- AI Chatbot Widget -->
<div id="chatbot-container">
    <!-- Chat Button (Floating) -->
    <div id="chat-button" onclick="toggleChatbot()">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span class="chat-badge" id="unread-badge">0</span>
    </div>

    <!-- Chat Window -->
    <div id="chat-window" class="chat-window">
        <!-- Header -->
        <div class="chat-header">
            <div class="chat-header-left">
                <div class="bot-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                <div class="bot-info">
                    <div class="bot-name">BlueBird Assistant</div>
                    <div class="bot-status">
                        <span class="status-dot"></span>
                        Đang hoạt động
                    </div>
                </div>
            </div>
            <div class="chat-header-right">
                <button class="chat-action-btn" onclick="minimizeChatbot()" title="Thu nhỏ">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
                <button class="chat-action-btn" onclick="closeChatbot()" title="Đóng">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div id="chat-messages" class="chat-messages">
            <!-- Welcome Message -->
            <div class="message bot-message">
                <div class="message-avatar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                <div class="message-content">
                    <div class="message-bubble">
                        Xin chào! 👋 Tôi là <strong>BlueBird Assistant</strong>, trợ lý ảo của Khách Sạn BlueBird.
                        <br><br>
                        Tôi có thể giúp gì cho bạn hôm nay? 😊
                    </div>
                    <div class="message-time">Bây giờ</div>
                </div>
            </div>
        </div>

        <!-- Quick Replies -->
        <div id="quick-replies" class="quick-replies">
            <button class="quick-reply-btn" onclick="sendQuickAction('show_rooms')">
                🏨 Xem loại phòng
            </button>
            <button class="quick-reply-btn" onclick="sendQuickAction('show_prices')">
                💰 Bảng giá
            </button>
            <button class="quick-reply-btn" onclick="sendQuickAction('show_facilities')">
                ✨ Tiện nghi
            </button>
            <button class="quick-reply-btn" onclick="sendQuickAction('book_now')">
                🎯 Đặt phòng ngay
            </button>
        </div>

        <!-- Typing Indicator -->
        <div id="typing-indicator" class="typing-indicator" style="display: none;">
            <div class="message bot-message">
                <div class="message-avatar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                <div class="message-content">
                    <div class="message-bubble typing">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Box -->
        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <input 
                    type="text" 
                    id="chat-input" 
                    class="chat-input" 
                    placeholder="Nhập tin nhắn của bạn..."
                    onkeypress="handleKeyPress(event)"
                    autocomplete="off"
                >
                <button id="send-button" class="send-button" onclick="sendMessage()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
            <div class="chat-footer-text">
                Powered by <strong>Google Gemini AI</strong> ⚡
            </div>
        </div>
    </div>
</div>

<!-- Include Chatbot CSS -->
<link rel="stylesheet" href="./chatbot/chatbot.css">

<!-- Pass login status to JavaScript -->
<script>
    // Check if user just logged in
    window.chatbotConfig = {
        isLoggedIn: <?php echo isset($_SESSION['usermail']) && !empty($_SESSION['usermail']) ? 'true' : 'false'; ?>,
        justLoggedIn: <?php 
            $justLoggedIn = isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'] === true;
            // Clear flag after reading
            if ($justLoggedIn) {
                unset($_SESSION['just_logged_in']);
            }
            echo $justLoggedIn ? 'true' : 'false';
        ?>
    };
</script>

<!-- Include Chatbot JS -->
<script src="./chatbot/chatbot.js"></script>

