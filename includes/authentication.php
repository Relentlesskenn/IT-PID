<?php
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings before starting the session
    $secure = true; // Set to true if using HTTPS
    $httponly = true; // Prevent JavaScript access to session cookie
    $samesite = 'Strict'; // Control how cookie is sent with cross-site requests
    
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie expires when browser closes
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);

    // Start the session
    session_start();
}

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Check if user is authenticated
if (!isset($_SESSION['authenticated'])) {
    $_SESSION['status'] = "Please Login!";
    header('Location: login-page.php');
    exit(0);
}
?>