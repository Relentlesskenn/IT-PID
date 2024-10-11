<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self'; form-action 'self'; frame-ancestors 'none';");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="vendor/twbs/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/fonts.css">
    <link rel="stylesheet" href=".\assets\css\global.css">
    <title>
        <?php if(isset($page_title)){echo "$page_title"; }?>
    </title>
</head>
<body>
    
<?php    
// Set the timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');
?>