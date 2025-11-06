<?php
/**
 * Reviews Setup Script
 * Run this ONCE to create reviews table
 */

require_once '../config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Reviews Setup</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;} .container{max-width:800px;margin:0 auto;background:white;padding:40px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);} .success{color:#28a745;font-size:18px;font-weight:bold;} .error{color:#dc3545;font-size:18px;font-weight:bold;} .info{background:#e7f3ff;padding:15px;border-left:4px solid #2196F3;margin:20px 0;} pre{background:#f5f5f5;padding:15px;overflow-x:auto;border-radius:5px;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>⭐ Reviews Setup</h1>";
echo "<hr>";

// Create reviews table
$sql = "CREATE TABLE IF NOT EXISTS `reviews` (
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

// Add check constraint separately if supported
$checkSql = "ALTER TABLE `reviews` ADD CONSTRAINT `chk_rating` CHECK (`rating` >= 1 AND `rating` <= 5)";

if (mysqli_query($conn, $sql)) {
    echo "<p class='success'>✅ Table 'reviews' created successfully!</p>";
    
    // Try to add check constraint (may fail if already exists or not supported)
    @mysqli_query($conn, $checkSql);
} else {
    if (mysqli_errno($conn) == 1050) { // Table already exists
        echo "<p class='success'>✅ Table 'reviews' already exists!</p>";
    } else {
        echo "<p class='error'>❌ Error creating table: " . mysqli_error($conn) . "</p>";
        echo "</div></body></html>";
        exit;
    }
}

// Verify table exists
$verify = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
if (mysqli_num_rows($verify) > 0) {
    echo "<p class='success'>✅ Table verification passed!</p>";
    
    // Show table structure
    echo "<h3>📋 Table Structure:</h3>";
    $structure = mysqli_query($conn, "DESCRIBE reviews");
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

echo "<div class='info'>";
echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li>Setup completed successfully! ✅</li>";
echo "<li>Go to: <a href='../index.php' target='_blank'><strong>index.php</strong></a></li>";
echo "<li>Scroll to Reviews section</li>";
echo "<li>If logged in, you can submit a review! ⭐</li>";
echo "</ol>";
echo "</div>";

echo "<p style='text-align:center;margin-top:30px;'>";
echo "<a href='../index.php' style='background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:bold;'>Go to Homepage →</a>";
echo "</p>";

echo "</div></body></html>";

mysqli_close($conn);
?>

