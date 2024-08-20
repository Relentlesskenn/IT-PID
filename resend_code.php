<?php
session_start();
include('db_conn.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function resend_email_verify($f_name,$email,$verify_token)
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

    $mail->setFrom("it.pid.team@gmail.com",$f_name);
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Resend - Email Verification from IT-PID";

    $email_template = "
        <h2>You have Registered with IT-PID!</h2>
        <h5>Verify your email address to Login with the link given below</h5>
        <br/><br/>
        <a href='http://localhost/IT-PID/verify_email.php?token=$verify_token'> Verify Email Address </a>
    ";

    $mail->Body = $email_template;
    $mail->send();
}

if(isset($_POST['resend_email_verification_btn']))
{
    if(!empty(trim($_POST['email'])))
    {
        $email = mysqli_real_escape_string($conn, $_POST['email']);

        $checkemail_query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
        $checkemail_query_run = mysqli_query($conn, $checkemail_query);

        if(mysqli_num_rows($checkemail_query_run) > 0)
        {
            $row = mysqli_fetch_array($checkemail_query_run);
            if($row['verify_status'] == "0")
            {
                $f_name = $row['f_name'];
                $email = $row['email'];
                $verify_token = $row['verify_token'];

                resend_email_verify($f_name,$email,$verify_token);
                $_SESSION['status'] = "Verification Email Link has been sent to your email address!";
                header("Location: login.php");
                exit(0);
            }
            else
            {
                $_SESSION['status'] = "Email already verified. Please Login!";
                header("Location: resend_email_verification.php");
                exit(0);
            }
        }
        else
        {
            $_SESSION['status'] = "Email is not registered. Please Register!";
            header("Location: register.php");
            exit(0);
        }
    }
    else
    {
        $_SESSION['status'] = "Please Enter an Email Address";
        header("Location: resend_email_verification.php");
        exit(0);
    }
}

?>