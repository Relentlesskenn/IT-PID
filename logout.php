<?php
session_start();

// Destroy the session variables
unset($_SESSION['authenticated']);
unset($_SESSION['auth_user']);
header("Location: index.php");

?>