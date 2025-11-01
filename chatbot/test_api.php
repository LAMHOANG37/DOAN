<?php
/**
 * Test Chatbot API
 */

echo "<h1>Testing Chatbot API</h1>";
echo "<hr>";

// Test 1: Check if files exist
echo "<h2>1. File Existence Check:</h2>";
$files = [
    '../config.php',
    'config.php',
    'api.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ <strong>$file</strong> exists<br>";
    } else {
        echo "❌ <strong>$file</strong> NOT FOUND<br>";
    }
}

echo "<hr>";

// Test 2: Include config files
echo "<h2>2. Config Loading:</h2>";
try {
    require_once '../config.php';
    echo "✅ Database config loaded<br>";
    echo "Database: " . ($conn ? "Connected" : "Not connected") . "<br>";
} catch (Exception $e) {
    echo "❌ Error loading database config: " . $e->getMessage() . "<br>";
}

try {
    require_once 'config.php';
    echo "✅ Chatbot config loaded<br>";
    echo "API Key: " . (defined('GEMINI_API_KEY') ? substr(GEMINI_API_KEY, 0, 10) . '...' : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "❌ Error loading chatbot config: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 3: Test API Call
echo "<h2>3. Test API Call:</h2>";
echo "<p>Calling API with test message...</p>";

$testData = [
    'message' => 'Xin chào',
    'action' => '',
    'session_id' => 'test_' . time()
];

$ch = curl_init('http://localhost/Hotel-Management-System-main/Hotel-Management-System-main/chatbot/api.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<strong>HTTP Code:</strong> $httpCode<br>";

if ($curlError) {
    echo "<strong style='color:red'>cURL Error:</strong> $curlError<br>";
}

echo "<strong>Response:</strong><br>";
echo "<pre style='background:#f5f5f5;padding:15px;overflow-x:auto;'>";
echo htmlspecialchars($response);
echo "</pre>";

// Try to decode JSON
$decoded = json_decode($response, true);
if ($decoded) {
    echo "<strong>Decoded JSON:</strong><br>";
    echo "<pre style='background:#e7f3ff;padding:15px;overflow-x:auto;'>";
    print_r($decoded);
    echo "</pre>";
}

echo "<hr>";
echo "<p><a href='../index.php'>← Back to Home</a></p>";
?>



