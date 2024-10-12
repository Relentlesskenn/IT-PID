<?php
// Secure cookie settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
require_once('_dbconnect.php');
require_once('includes/rate_limiting.php');

if (isset($_POST['login_btn'])) {

    // Check rate limit
    if (!check_rate_limit('login', 5, 300)) {
        $_SESSION['error'] = "Too many login attempts! Please try again later";
        header("Location: login-page.php");
        exit();
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = ("All Fields are Required!");
        redirectToLogin();
    }

    $username = mysqli_real_escape_string($conn, $username);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            if ($user['verify_status'] == "1") {
                $_SESSION['authenticated'] = TRUE;
                $_SESSION['auth_user'] = [
                    'user_id' => $user['user_id'],
                    'l_name' => $user['l_name'],
                    'f_name' => $user['f_name'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ];
                header("Location: dashboard-page.php");
                exit();
            } else {
                setSessionStatus("Please verify your email address to login");
                redirectToLogin();
            }
        } else {
            $_SESSION['error'] = ("Invalid Username or Password!");
            redirectToLogin();
        }
    } else {
        $_SESSION['error'] = ("Invalid Username or Password!");
        redirectToLogin();
    }
}

function setSessionStatus($message) {
    $_SESSION['status'] = $message;
}

function redirectToLogin() {
    header("Location: login-page.php");
    exit();
}
?>