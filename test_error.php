<?php
// test_error.php - Check what's causing the 500 error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing PHP Setup</h2>";

// Test 1: Check if config.php exists
echo "<h3>Test 1: Config File</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    require_once "config.php";
    echo "✅ config.php loaded successfully<br>";
} else {
    echo "❌ config.php NOT FOUND<br>";
}

// Test 2: Check database connection
echo "<h3>Test 2: Database Connection</h3>";
if (isset($conn)) {
    echo "✅ Database connected<br>";
} else {
    echo "❌ No database connection<br>";
}

// Test 3: Check PHPMailer
echo "<h3>Test 3: PHPMailer</h3>";
if (file_exists('PHPMailer/src/PHPMailer.php')) {
    echo "✅ PHPMailer found<br>";
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    echo "✅ PHPMailer loaded successfully<br>";
} else {
    echo "❌ PHPMailer NOT FOUND<br>";
}

// Test 4: Check if admins table has required columns
echo "<h3>Test 4: Database Table Structure</h3>";
if (isset($conn)) {
    $result = $conn->query("DESCRIBE admins");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        echo "Columns in admins table:<br>";
        echo "- " . implode("<br>- ", $columns) . "<br><br>";
        
        if (in_array('reset_token_hash', $columns)) {
            echo "✅ reset_token_hash column exists<br>";
        } else {
            echo "❌ reset_token_hash column MISSING - Run the migration SQL!<br>";
        }
        
        if (in_array('reset_token_expires_at', $columns)) {
            echo "✅ reset_token_expires_at column exists<br>";
        } else {
            echo "❌ reset_token_expires_at column MISSING - Run the migration SQL!<br>";
        }
    }
}

echo "<hr>";
echo "<p><strong>If all tests pass, the forgot password should work!</strong></p>";
echo "<p><a href='admin.php'>Go to Admin Login</a></p>";
?>