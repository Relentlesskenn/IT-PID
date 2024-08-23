<?php
session_start();
include('_dbconnect.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function send_password_reset($get_username, $get_email, $token)
{
    $mail = new PHPMailer(true);
    //$mail->SMTPDebug = 2;
    $mail->isSMTP();
    $mail->SMTPAuth = true;

    $mail->Host = "smtp.gmail.com";
    $mail->Username = "it.pid.team@gmail.com";
    $mail->Password = "qlotmbifugeutlyj";

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom("it.pid.team@gmail.com",$get_username);
    $mail->addAddress($get_email);

    $mail->isHTML(true);
    $mail->Subject = "Reset Password - IT-PID";

    $email_template = "
        <h2>Greetings!</h2>
        <h5>You recieved the email because we recieved a password reset request for your account.</h5>
        <br/><br/>
        <a href='localhost/IT-PID/password_reset-page.php?token=$token&email=$get_email'> Reset Password </a>
    ";

    $mail->Body = $email_template;
    $mail->send();
}

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
            $_SESSION['status'] = "We sent you a password reset link";
            header("Location: login-page.php");
            exit(0);
        }
        else
        {
            $_SESSION['status'] = "Something went wrong! #1";
            header("Location: forgot_your_password-page.php");
            exit(0);
        }
    }
    else
    {
        $_SESSION['status'] = "No Email Found!";
        header("Location: forgot_your_password-page.php");
        exit(0);
    }
}

if(isset($_POST['password_update_btn']))
{
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

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
                    $update_password = "UPDATE users SET password='$new_password' WHERE verify_token='$token' LIMIT 1";
                    $update_password_run = mysqli_query($conn, $update_password);

                    if($update_password_run)
                    {
                        $new_token = md5(rand())."itpid";
                        $update_to_new_token = "UPDATE users SET verify_token='$new_token' WHERE verify_token='$token' LIMIT 1";
                        $update_to_new_token_run = mysqli_query($conn, $update_to_new_token);
                        $_SESSION['status'] = "New Password Successfully Updated!";
                        header("Location: login-page.php");
                        exit(0);
                    }
                    else
                    {
                        $_SESSION['status'] = "Did not update password. Something went wrong!";
                        header("Location: password_reset-page.php?token=$token&email=$email");
                        exit(0);
                    }
                }
                else
                {
                    $_SESSION['status'] = "Password and Confirm Password does not match!";
                    header("Location: password_reset-page.php?token=$token&email=$email");
                    exit(0);
                }
            }
            else
            {
                $_SESSION['status'] = "Invalid Token";
                header("Location: password_reset-page.php?token=$token&email=$email");
                exit(0);
            }
        }
        else
        {
            $_SESSION['status'] = "All Fields are Required!";
            header("Location: password_reset-page.php?token=$token&email=$email");
            exit(0);
        }
    }
    else
    {
        $_SESSION['status'] = "No Token Available!";
        header("Location: password_reset-page.php");
        exit(0);
    }
}
?>