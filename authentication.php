<?php
session_start();

// Check if user is authenticated
if(!isset($_SESSION['authenticated']))
{
    $_SESSION['status'] = "Please Login!";
    header('Location: login-page.php');
    exit(0);
}

?>