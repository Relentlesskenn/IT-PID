<?php
session_start();

unset($_SESSION['authenticated']);
unset($_SESSION['auth_user']);
header("Location: index.php");

?>