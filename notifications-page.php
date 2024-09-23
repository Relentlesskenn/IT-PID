<?php
include('authentication.php');
$page_title = "Notifications";
include('includes/header.php'); 
?>
<div class="py-3">
    <div class="container">
        <a class="btn btn-danger btn-sm mb-3" href="dashboard-page.php"><</a>
        <?php
        if (isset($_SESSION['notification'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['notification'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION['notification']); 
        }
        ?>
    </div>
</div>


<?php include('includes/footer.php') ?>
