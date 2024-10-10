<?php
$page_title = "Settings";
include('authentication.php');
include('includes/header.php');
?>

<div class="py-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard-page.php" class="btn btn-outline-custom btn-sm">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
        <h1 class="h4 mb-0">Settings</h1>
    </div>
    
    <div class="d-flex justify-content-between align-items-center">
        <a class="btn btn-danger btn-sm w-100" href="logout.php">Log out</a>
    </div>
</div>

<?php include('includes/footer.php') ?>