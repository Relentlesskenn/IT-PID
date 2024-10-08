<?php
session_start();
require_once('_dbconnect.php');

if (isset($_POST['login_btn'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        setSessionStatus("All Fields are Required!");
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
                setSessionStatus("Please Verify your Email Address to Login");
                redirectToLogin();
            }
        } else {
            setSessionStatus("Invalid Username or Password!");
            redirectToLogin();
        }
    } else {
        setSessionStatus("Invalid Username or Password!");
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