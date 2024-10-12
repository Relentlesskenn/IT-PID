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
require_once '_dbconnect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function resend_email_verify($f_name, $email, $verify_token)
{
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'it.pid.team@gmail.com';
        $mail->Password   = 'qlotmbifugeutlyj'; // Consider using environment variables for sensitive data
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('it.pid.team@gmail.com', 'IT-PID Team');
        $mail->addAddress($email, $f_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Resend - Email Verification from IT-PID";
        $mail->Body    = "
            <h2>You have Registered with IT-PID!</h2>
            <h5>Verify your email address to Login with the link given below</h5>
            <br/><br/>
            <a href='http://localhost/IT-PID/verify_email.php?token=" . urlencode($verify_token) . "'>Verify Email Address</a>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_email_verification_btn'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if ($email) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['verify_status'] == "0") {
                if (resend_email_verify($user['f_name'], $user['email'], $user['verify_token'])) {
                    $_SESSION['status'] = "An email link for verification has been sent to your email address!";
                    header("Location: login-page.php");
                } else {
                    $_SESSION['error'] = "The email could not be sent. Please try again later!";
                    header("Location: resend_email_verification-page.php");
                }
            } else {
                $_SESSION['status'] = "Email already verified. Please login";
                header("Location: login-page.php");
            }
        } else {
            $_SESSION['error'] = "The email address is not registered. Please register!";
            header("Location: registration-page-1.php");
        }
    } else {
        $_SESSION['error'] = "Please enter a valid email address!";
        header("Location: resend_email_verification-page.php");
    }
    exit();
}
?>