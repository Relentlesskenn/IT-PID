<?php
session_start();

if(!isset($_SESSION['authenticated']))
{
    $_SESSION['status'] = "Please Login!";
    header('Location: login-page.php');
    exit(0);
}

?>