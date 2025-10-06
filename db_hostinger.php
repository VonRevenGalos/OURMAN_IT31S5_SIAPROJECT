<?php
// Alternative database configuration for Hostinger hosting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Hostinger typically uses these settings:
// Option 1: Standard Hostinger settings
$host = "localhost";  // Hostinger usually uses localhost, not 127.0.0.1
$port = "3306";
$dbname = "u585057361_shoe";
$username = "u585057361_rizz";
$password = "Astron_202";

echo "<!DOCTYPE html><html><head><title>Hostinger DB Test</title></head><body>";
echo "<h1>Hostinger Database Connection Test</h1>";

echo "<h2>Testing with localhost...</h2>";
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Connection successful with localhost!</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Found {$count} products in database</p>";
    
    echo "<h3>✅ Use this configuration in your db.php:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars('<?php
$host = "localhost";
$port = "3306";
$dbname = "u585057361_shoe";
$username = "u585057361_rizz";
$password = "Astron_202";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>');
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ localhost failed: " . $e->getMessage() . "</p>";
    
    echo "<h2>Testing with 127.0.0.1...</h2>";
    $host = "127.0.0.1";
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color: green;'>✓ Connection successful with 127.0.0.1!</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $count = $stmt->fetchColumn();
        echo "<p style='color: green;'>✓ Found {$count} products in database</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ 127.0.0.1 also failed: " . $e->getMessage() . "</p>";
        
        echo "<h2>Possible Issues:</h2>";
        echo "<ul>";
        echo "<li><strong>Database doesn't exist:</strong> Create database 'u585057361_shoe' in Hostinger cPanel</li>";
        echo "<li><strong>User doesn't exist:</strong> Create user 'u585057361_rizz' in Hostinger cPanel</li>";
        echo "<li><strong>Wrong password:</strong> Check the password for user 'u585057361_rizz'</li>";
        echo "<li><strong>Permissions:</strong> Grant all privileges to user on database</li>";
        echo "<li><strong>Import data:</strong> Import the products.sql file into the database</li>";
        echo "</ul>";
        
        echo "<h3>Steps to fix in Hostinger cPanel:</h3>";
        echo "<ol>";
        echo "<li>Go to <strong>MySQL Databases</strong></li>";
        echo "<li>Create database: <code>u585057361_shoe</code></li>";
        echo "<li>Create user: <code>u585057361_rizz</code> with password: <code>Astron_202</code></li>";
        echo "<li>Add user to database with <strong>All Privileges</strong></li>";
        echo "<li>Go to <strong>phpMyAdmin</strong></li>";
        echo "<li>Select the database and import <code>products.sql</code></li>";
        echo "</ol>";
    }
}

echo "</body></html>";
?>
