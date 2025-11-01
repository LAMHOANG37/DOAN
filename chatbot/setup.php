<?php
/**
 * Chatbot Setup Script
 * Run this ONCE to create chat_history table
 */

require_once '../config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Chatbot Setup</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;} .container{max-width:800px;margin:0 auto;background:white;padding:40px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .success{color:#28a745;font-size:18px;font-weight:bold;} .error{color:#dc3545;font-size:18px;font-weight:bold;} .info{background:#e7f3ff;padding:15px;border-left:4px solid #2196F3;margin:20px 0;} pre{background:#f5f5f5;padding:15px;overflow-x:auto;border-radius:5px;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🤖 Chatbot Setup</h1>";
echo "<hr>";

// Create chat_history table
$sql = "CREATE TABLE IF NOT EXISTS `chat_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_email` varchar(100) DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `user_message` text NOT NULL,
  `bot_response` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_email` (`user_email`),
  KEY `session_id` (`session_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql)) {
    echo "<p class='success'>✅ Table 'chat_history' created successfully!</p>";
} else {
    if (mysqli_errno($conn) == 1050) { // Table already exists
        echo "<p class='success'>✅ Table 'chat_history' already exists!</p>";
    } else {
        echo "<p class='error'>❌ Error creating table: " . mysqli_error($conn) . "</p>";
        echo "</div></body></html>";
        exit;
    }
}

// Verify table exists
$verify = mysqli_query($conn, "SHOW TABLES LIKE 'chat_history'");
if (mysqli_num_rows($verify) > 0) {
    echo "<p class='success'>✅ Table verification passed!</p>";
    
    // Show table structure
    echo "<h3>📋 Table Structure:</h3>";
    $structure = mysqli_query($conn, "DESCRIBE chat_history");
    echo "<pre>";
    echo str_pad("Field", 20) . str_pad("Type", 30) . str_pad("Null", 10) . str_pad("Key", 10) . "\n";
    echo str_repeat("-", 70) . "\n";
    while ($row = mysqli_fetch_assoc($structure)) {
        echo str_pad($row['Field'], 20) . 
             str_pad($row['Type'], 30) . 
             str_pad($row['Null'], 10) . 
             str_pad($row['Key'], 10) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p class='error'>❌ Table verification failed!</p>";
}

// Check Gemini API Key
echo "<h3>🔑 Gemini API Configuration:</h3>";
include 'config.php';
$apiKey = GEMINI_API_KEY;
if (strlen($apiKey) > 20) {
    $maskedKey = substr($apiKey, 0, 10) . '...' . substr($apiKey, -4);
    echo "<p class='success'>✅ API Key configured: <code>$maskedKey</code></p>";
} else {
    echo "<p class='error'>❌ API Key not configured properly!</p>";
}

echo "<div class='info'>";
echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li>Setup completed successfully! ✅</li>";
echo "<li>Go to: <a href='../index.php' target='_blank'><strong>index.php</strong></a></li>";
echo "<li>Look for the chatbot button (bottom-right corner) 💬</li>";
echo "<li>Click and start chatting! 🎉</li>";
echo "</ol>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>📖 Features:</h3>";
echo "<ul>";
echo "<li>✨ AI-powered chatbot using Google Gemini</li>";
echo "<li>🏨 Hotel room recommendations</li>";
echo "<li>💰 Price calculations</li>";
echo "<li>📞 FAQs and support</li>";
echo "<li>💾 Chat history saved to database</li>";
echo "</ul>";
echo "</div>";

echo "<p style='text-align:center;margin-top:30px;'>";
echo "<a href='../index.php' style='background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:bold;'>Go to Homepage →</a>";
echo "</p>";

echo "</div></body></html>";

mysqli_close($conn);
?>

