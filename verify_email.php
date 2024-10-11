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

    // Check if token is valid or not
    if(isset($_GET['token']))
    {
        $token = $_GET['token'];
        $verify_query = "SELECT verify_token,verify_status FROM users WHERE verify_token='$token' LIMIT 1";
        $verify_query_run = mysqli_query($conn, $verify_query);

        if(mysqli_num_rows($verify_query_run) > 0)
        {
            $row = mysqli_fetch_array($verify_query_run);
            if($row['verify_status'] == "0")
            {
                 $clicked_token = $row['verify_token'];
                 $update_query = "UPDATE users SET verify_status='1' WHERE verify_token='$clicked_token' LIMIT 1";
                 $update_query_run = mysqli_query($conn, $update_query);

                 if($update_query_run)
                 {
                    $_SESSION['status'] = "Your Account has been verified successfully!";
                    header("Location: login-page.php");
                    exit(0);
                 }
                 else
                 {
                    $_SESSION['status'] = "Verification Failed!";
                    header("Location: login-page.php");
                    exit(0);
                 }
            }
            else
            {
                $_SESSION['status'] = "Email Already Verified! Please Login";
                header("Location: login-page.php");
                exit(0);
            }
        }
        else
        {
            $_SESSION['status'] = "This token does not Exists!";
            header("Location: login-page.php");
        }
        
    }
    else
    {
        $_SESSION['status'] = "Not Allowed!";
        header("Location: login-page.php");
    }
?>