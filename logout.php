<?php
// Start session
session_start();

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Regenerate the session ID to prevent session fixation attacks on the next login
session_start();
session_regenerate_id(true);
session_destroy();

// Clear any potential sensitive data in PHP's output buffer
ob_clean();

// Set a logout message in a temporary cookie (will be cleared after being read once)
setcookie('logout_message', 'You have been successfully logged out.', [
    'expires' => time() + 30,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Redirect to login page
header("Location: login-page.php");
exit();
?>