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
include('_dbconnect.php');
require_once('includes/password_policy.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendemail_verify($f_name, $email, $verify_token)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = "smtp.gmail.com";
        $mail->Username = "it.pid.team@gmail.com";
        $mail->Password = "qlotmbifugeutlyj";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom("it.pid.team@gmail.com", "IT-PID Team");
        $mail->addAddress($email, $f_name);

        $mail->isHTML(true);
        $mail->Subject = "Verify Your Email - IT-PID";

        // Use the new email template here
        $email_template = file_get_contents('assets/email_templates/email_verification_template.html');
        $email_template = str_replace('$f_name', $f_name, $email_template);
        $email_template = str_replace('$verify_token', urlencode($verify_token), $email_template);

        $mail->Body = $email_template;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

if(isset($_POST['register_btn'])) 
{
    $f_name = filter_input(INPUT_POST, 'f_name');
    $l_name = filter_input(INPUT_POST, 'l_name');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $username = filter_input(INPUT_POST, 'username');
    $password = $_POST['password'];
    $c_password = $_POST['c_password'];
    $verify_token = bin2hex(random_bytes(16))."itpid";

    // Store form data in session for repopulating the form if needed
    $_SESSION['registration_data'] = [
        'f_name' => $f_name,
        'l_name' => $l_name,
        'email' => $email,
        'username' => $username
    ];

    // Check if email exists
    $check_email_query = "SELECT email FROM users WHERE email=? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_email_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if(mysqli_stmt_num_rows($stmt) > 0)
    {
        $_SESSION['error'] = "Email address already exists!";
        header("Location: registration-page-1.php");
        exit();
    }

    // Check if username exists
    $check_username_query = "SELECT username FROM users WHERE username=? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_username_query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if(mysqli_stmt_num_rows($stmt) > 0)
    {
        $_SESSION['error'] = "Username already exists!";
        header("Location: registration-page-2.php");
        exit();
    }

    if($password !== $c_password)
    {
        $_SESSION['error'] = "Password and Confirm Password do not match!";
        header("Location: registration-page-2.php");
        exit();
    }

    // Check password strength
    $password_strength = is_password_strong($password);
    if ($password_strength !== true) {
        $_SESSION['error'] = "Password is not strong enough!";
        header("Location: registration-page-2.php");
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (username, f_name, l_name, email, password, verify_token) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssss", $username, $f_name, $l_name, $email, $hashed_password, $verify_token);
    $query_run = mysqli_stmt_execute($stmt);

    if($query_run)
    {
        if(sendemail_verify($f_name, $email, $verify_token))
        {
            unset($_SESSION['registration_data']);
            $_SESSION['success'] = "You've successfully registered! Kindly confirm your email address";
            header("Location: login-page.php");
            exit();
        }
        else
        {
            $_SESSION['error'] = "Email verification failed, but registration was successful. Kindly get in touch with support";
            header("Location: login-page.php");
            exit();
        }
    }
    else
    {
        $_SESSION['error'] = "Registration Failed!";
        header("Location: registration-page-2.php");
        exit();
    }
}
else
{
    header("Location: registration-page-1.php");
    exit();
}
?>