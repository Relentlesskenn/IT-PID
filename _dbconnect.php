<?php
// Database connection constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it-pid');

// Attempt to establish a database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set character set to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting character set: " . $conn->error);
    }
} catch (Exception $e) {
    // Log the error and display a user-friendly message
    error_log("Database connection error: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}
?>