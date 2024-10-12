<?php
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/twbs/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/fonts.css">
    <link rel="shortcut icon" href="assets\imgs\logo\favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href=".\assets\css\global.css">
    <link rel="stylesheet" href="./assets/css/custom-alerts.css">
    <title>
        <?php if(isset($page_title)){echo "$page_title"; }?>
    </title>
</head>
<body>
    
<?php    
// Set the timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');
?>