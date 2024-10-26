<?php
$page_title = "Settings · IT-PID";
include('includes/authentication.php');
include('includes/header.php');
include('includes/navbar.php');
?>

<!-- HTML content -->
<div class="pt-4 pb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4">Settings</h1>
    </div>
    
    <!-- Logout Button -->
    <div class="d-flex justify-content-between align-items-center">
        <a class="btn btn-danger w-100" href="logout.php">Log out</a>
    </div>
</div>

<?php include('includes/footer.php') ?>