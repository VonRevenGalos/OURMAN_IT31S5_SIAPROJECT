<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "127.0.0.1";
$port = "3306";
$dbname = "u585057361_shoe";
$username = "u585057361_rizz";
$password = "Astron_202";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Database connection successful!";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
