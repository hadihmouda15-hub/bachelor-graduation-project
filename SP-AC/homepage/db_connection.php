<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'root'); // Replace with your database username
define('DB_PASSWORD', ''); // Replace with your database password
define('DB_NAME', 'homecare');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 to support full Unicode
$conn->set_charset("utf8mb4");

// Function to sanitize input data
function sanitize_input($data, $conn) {
    return htmlspecialchars(strip_tags($conn->real_escape_string(trim($data))));
}

// Error reporting (only for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>