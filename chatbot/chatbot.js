/**
 * Chatbot JavaScript
 * Handles user interactions and API calls
 */

// Session ID (persists across page reloads)
let sessionId = getOrCreateSessionId();

// Chat state
let isChatOpen = false;
let isTyping = false;

// ==================== INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Chatbot initialized!');
    
    // Focus input when chat opens
    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('focus', function() {
            scrollToBottom();
        });
    }
    
    // Auto-open chatbot if user just logged in
    const config = window.chatbotConfig || {};
    if (config.isLoggedIn && config.justLoggedIn) {
        // Wait a bit for page to fully load, then open chatbot
        setTimeout(function() {
            toggleChatbot();
            // Scroll to bottom to show welcome message
            setTimeout(scrollToBottom, 300);
        }, 1000); // Wait 1 second after page load
    }
});

// ==================== CHAT CONTROLS ====================

/**
 * Toggle chatbot open/close
 */
function toggleChatbot() {
    const chatWindow = document.getElementById('chat-window');
    isChatOpen = !isChatOpen;
    
    if (isChatOpen) {
        chatWindow.classList.add('show');
        chatWindow.classList.remove('minimized');
        document.getElementById('chat-input').focus();
        resetUnreadBadge();
    } else {
        chatWindow.classList.remove('show');
    }
}

/**
 * Close chatbot
 */
function closeChatbot() {
    const chatWindow = document.getElementById('chat-window');
    chatWindow.classList.remove('show');
    isChatOpen = false;
}

/**
 * Minimize chatbot
 */
function minimizeChatbot() {
    const chatWindow = document.getElementById('chat-window');
    chatWindow.classList.toggle('minimized');
}

// ==================== MESSAGE HANDLING ====================

/**
 * Send message from input
 */
function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message || isTyping) return;
    
    // Clear input
    input.value = '';
    
    // Add user message to UI
    addMessage(message, 'user');
    
    // Send to API
    sendToAPI(message);
}

/**
 * Send quick action
 */
function sendQuickAction(action) {
    // Show typing indicator
    showTypingIndicator();
    
    // Send to API
    sendToAPI('', action);
}

/**
 * Send message to API
 */
async function sendToAPI(message, action = '') {
    isTyping = true;
    showTypingIndicator();
    
    try {
        const response = await fetch('./chatbot/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                action: action,
                session_id: sessionId
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        hideTypingIndicator();
        
        if (data.success) {
            // Add bot response
            addMessage(data.response, 'bot');
            
            // Update quick replies if provided
            if (data.quick_replies) {
                updateQuickReplies(data.quick_replies);
            }
        } else {
            // Show error message
            let errorMsg = 'Xin lỗi, đã có lỗi xảy ra. Vui lòng thử lại sau. 😔';
            if (data.debug && data.debug.message) {
                errorMsg += '<br><br><small style="color:#999">Debug: ' + escapeHtml(data.debug.message) + '</small>';
            }
            addMessage(errorMsg, 'bot');
            console.error('API Error:', data);
        }
        
    } catch (error) {
        hideTypingIndicator();
        addMessage('Không thể kết nối với server. Vui lòng kiểm tra kết nối internet. 🌐', 'bot');
        console.error('Fetch Error:', error);
    } finally {
        isTyping = false;
    }
}

/**
 * Add message to chat UI
 */
function addMessage(text, type = 'bot') {
    const messagesContainer = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}-message`;
    
    const now = new Date();
    const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0');
    
    if (type === 'bot') {
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
            </div>
            <div class="message-content">
                <div class="message-bubble">${formatMessage(text)}</div>
                <div class="message-time">${timeString}</div>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="message-content">
                <div class="message-bubble">${escapeHtml(text)}</div>
                <div class="message-time">${timeString}</div>
            </div>
        `;
    }
    
    messagesContainer.appendChild(messageDiv);
    scrollToBottom();
    
    // Show badge if chat is closed
    if (!isChatOpen && type === 'bot') {
        incrementUnreadBadge();
    }
}

/**
 * Format bot message (preserve line breaks, bold, etc.)
 */
function formatMessage(text) {
    // Convert **text** to <strong>text</strong>
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Convert line breaks
    text = text.replace(/\n/g, '<br>');
    
    return text;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show typing indicator
 */
function showTypingIndicator() {
    const indicator = document.getElementById('typing-indicator');
    indicator.style.display = 'block';
    scrollToBottom();
}

/**
 * Hide typing indicator
 */
function hideTypingIndicator() {
    const indicator = document.getElementById('typing-indicator');
    indicator.style.display = 'none';
}

/**
 * Scroll to bottom of chat
 */
function scrollToBottom() {
    setTimeout(() => {
        const messagesContainer = document.getElementById('chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
}

/**
 * Handle Enter key press
 */
function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

// ==================== QUICK REPLIES ====================

/**
 * Update quick replies
 */
function updateQuickReplies(replies) {
    const container = document.getElementById('quick-replies');
    container.innerHTML = '';
    
    replies.forEach(reply => {
        const button = document.createElement('button');
        button.className = 'quick-reply-btn';
        button.textContent = reply.text;
        button.onclick = () => sendQuickAction(reply.action);
        container.appendChild(button);
    });
}

// ==================== UNREAD BADGE ====================

/**
 * Increment unread badge
 */
function incrementUnreadBadge() {
    const badge = document.getElementById('unread-badge');
    let count = parseInt(badge.textContent) || 0;
    count++;
    badge.textContent = count;
    badge.classList.add('show');
}

/**
 * Reset unread badge
 */
function resetUnreadBadge() {
    const badge = document.getElementById('unread-badge');
    badge.textContent = '0';
    badge.classList.remove('show');
}

// ==================== SESSION MANAGEMENT ====================

/**
 * Get or create session ID
 */
function getOrCreateSessionId() {
    let sid = localStorage.getItem('chatbot_session_id');
    
    if (!sid) {
        sid = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('chatbot_session_id', sid);
    }
    
    return sid;
}

// ==================== UTILITY FUNCTIONS ====================

/**
 * Get current timestamp
 */
function getCurrentTime() {
    const now = new Date();
    return now.getHours().toString().padStart(2, '0') + ':' + 
           now.getMinutes().toString().padStart(2, '0');
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ==================== AUTO-OPEN ON FIRST VISIT (Optional) ====================

// Uncomment to auto-open chatbot on first visit
/*
window.addEventListener('load', function() {
    const hasVisited = localStorage.getItem('chatbot_visited');
    if (!hasVisited) {
        setTimeout(function() {
            toggleChatbot();
            localStorage.setItem('chatbot_visited', 'true');
        }, 3000); // Open after 3 seconds
    }
});
*/

console.log('✅ Chatbot loaded successfully!');
console.log('Session ID:', sessionId);

