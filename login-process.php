<?php
session_start();
include('_dbconnect.php');

if(isset($_POST['login_btn']))
{
    if(!empty(trim($_POST['username'])) && !empty(trim($_POST['password']))) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];

        // Fetch the user from the database
        $login_query = "SELECT * FROM users WHERE username='$username' LIMIT 1";
        $login_query_run = mysqli_query($conn, $login_query);

        if(mysqli_num_rows($login_query_run) > 0)
        {
            $row = mysqli_fetch_array($login_query_run);
            
            // Verify the password
            if(password_verify($password, $row['password']))
            {
                if($row['verify_status'] == "1")
                {
                    $_SESSION['authenticated'] = TRUE;
                    $_SESSION['auth_user'] = [
                        'l_name' => $row['l_name'],
                        'f_name' => $row['f_name'],
                        'username' => $row['username'],
                        'email' => $row['email']
                    ];
                    header("Location: dashboard-page.php");
                    exit(0);
                }
                else
                {
                    $_SESSION['status'] = "Please Verify your Email Address to Login";
                    header("Location: login-page.php");
                    exit(0);
                }
            }
            else
            {
                $_SESSION['status'] = "Invalid Username or Password!";
                header("Location: login-page.php");
                exit(0);
            }
        }
        else
        {
            $_SESSION['status'] = "Invalid Username or Password!";
            header("Location: login-page.php");
            exit(0);
        }
    }
    else
    {
        $_SESSION['status'] = "All Fields are Required!";
        header("Location: login-page.php");
        exit(0);
    }
}
?>