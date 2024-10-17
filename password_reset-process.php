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

// Function to send password reset email
function send_password_reset($get_username, $get_email, $token)
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPAuth = true;

    $mail->Host = "smtp.gmail.com";
    $mail->Username = "it.pid.team@gmail.com";
    $mail->Password = "qlotmbifugeutlyj";

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom("it.pid.team@gmail.com", "IT-PID Team");
    $mail->addAddress($get_email);

    $mail->isHTML(true);
    $mail->Subject = "Reset Your Password - IT-PID";

    // Use the new email template here
    $email_template = file_get_contents('assets/email_templates/password_reset_template.html');
    $email_template = str_replace('$token', urlencode($token), $email_template);
    $email_template = str_replace('$get_email', urlencode($get_email), $email_template);

    $mail->Body = $email_template;
    $mail->send();
}

// Process password reset form submission
if(isset($_POST['password_reset_btn']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $token = md5(rand());

    $check_email = "SELECT email FROM users WHERE email='$email' LIMIT 1";
    $check_email_run = mysqli_query($conn, $check_email);

    if(mysqli_num_rows($check_email_run) > 0)
    {
        $row = mysqli_fetch_array($check_email_run);
        $get_username = $row['username'];
        $get_email = $row['email'];

        $update_token = "UPDATE users SET verify_token='$token' WHERE email='$get_email' LIMIT 1";
        $update_token_run = mysqli_query($conn, $update_token);

        if($update_token_run)
        {
            send_password_reset($get_username, $get_email, $token);
            $_SESSION['status'] = "We emailed you a link to reset your password";
            header("Location: login-page.php");
            exit(0);
        }
        else
        {
            $_SESSION['error'] = "Something went wrong!";
            header("Location: forgot_your_password-page.php");
            exit(0);
        }
    }
    else
    {
        $_SESSION['error'] = "Email not found!";
        header("Location: forgot_your_password-page.php");
        exit(0);
    }
}

// Process password update form submission
if(isset($_POST['password_update_btn']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $token = mysqli_real_escape_string($conn, $_POST['password_token']);

    if(!empty($token))
    {
        if(!empty($email) && !empty($new_password) && !empty($confirm_password))
        {
            //Check if token is valid or not
            $check_token = "SELECT verify_token FROM users WHERE verify_token='$token' LIMIT 1";
            $check_token_run = mysqli_query($conn, $check_token);

            if(mysqli_num_rows($check_token_run) > 0)
            {
                if($new_password == $confirm_password)
                {
                    // Hash the password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_password = "UPDATE users SET password='$hashed_password' WHERE verify_token='$token' LIMIT 1";
                    $update_password_run = mysqli_query($conn, $update_password);
                    
                    if($update_password_run)
                    {
                        $new_token = md5(rand())."itpid";
                        $update_to_new_token = "UPDATE users SET verify_token='$new_token' WHERE verify_token='$token' LIMIT 1";
                        $update_to_new_token_run = mysqli_query($conn, $update_to_new_token);
                        $_SESSION['success'] = "Updated new password successfully!";
                        header("Location: login-page.php");
                        exit(0);
                    }
                    else
                    {
                        $_SESSION['error'] = "Password was not updated. Something went wrong!";
                        header("Location: password_reset-page.php?token=$token&email=$email");
                        exit(0);
                    }
                }
                else
                {
                    $_SESSION['error'] = "Password and Confirm Password does not match!";
                    header("Location: password_reset-page.php?token=$token&email=$email");
                    exit(0);
                }
            }
            else
            {
                $_SESSION['error'] = "Invalid Token!";
                header("Location: password_reset-page.php?token=$token&email=$email");
                exit(0);
            }
        }
        else
        {
            $_SESSION['error'] = "All Fields are Required!";
            header("Location: password_reset-page.php?token=$token&email=$email");
            exit(0);
        }
    }
    else
    {
        $_SESSION['error'] = "No Token Available!";
        header("Location: password_reset-page.php");
        exit(0);
    }
}
?>