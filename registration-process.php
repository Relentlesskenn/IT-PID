<?php
session_start();
include('_dbconnect.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendemail_verify($f_name, $email, $verify_token)
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPAuth = true;

    $mail->Host = "smtp.gmail.com";
    $mail->Username = "it.pid.team@gmail.com";
    $mail->Password = "qlotmbifugeutlyj";

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom("it.pid.team@gmail.com", $f_name);
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Email Verification from IT-PID";

    $email_template = "
        <h2>You have Registered with IT-PID!</h2>
        <h5>Verify your email address to Login with the link given below</h5>
        <br/><br/>
        <a href='http://localhost/IT-PID/verify_email.php?token=$verify_token'> Verify Email Address </a>
    ";

    $mail->Body = $email_template;
    $mail->send();
}

if(isset($_POST['register_btn'])) 
{
    $f_name = $_POST['f_name'];
    $l_name = $_POST['l_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $c_password = $_POST['c_password'];
    $verify_token = md5(rand());

    // Check if email exists
    $check_email_query = "SELECT email FROM users WHERE email='$email' LIMIT 1";
    $check_email_query_run = mysqli_query($conn, $check_email_query);

    if(mysqli_num_rows($check_email_query_run) > 0)
    {
        $_SESSION['status'] = "Email Address already Exists!";
        header("Location: registration-page-1.php");
        exit();
    }

    // Check if username exists
    $check_username_query = "SELECT username FROM users WHERE username='$username' LIMIT 1";
    $check_username_query_run = mysqli_query($conn, $check_username_query);

    if(mysqli_num_rows($check_username_query_run) > 0)
    {
        $_SESSION['status'] = "Username already Exists!";
        header("Location: registration-page-2.php");
        exit();
    }

    if($password !== $c_password)
    {
        $_SESSION['status'] = "Password and Confirm Password do not match!";
        header("Location: registration-page-2.php");
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (username, f_name, l_name, email, password, verify_token) VALUES ('$username', '$f_name', '$l_name', '$email', '$hashed_password', '$verify_token')";
    $query_run = mysqli_query($conn, $query);

    if($query_run)
    {
        sendemail_verify("$f_name", "$email", "$verify_token");
        $_SESSION['status'] = "Registered Successfully! Please verify your Email Address.";
        header("Location: login-page.php");
        exit();
    }
    else
    {
        $_SESSION['status'] = "Registration Failed!";
        header("Location: registration-page-1.php");
        exit();
    }
}
else
{
    header("Location: registration-page-1.php");
    exit();
}
?>