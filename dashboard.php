<?php
include('authentication.php');
$page_title = "Dashboard";
include('includes/header.php'); 
?>

<div class="py-5">
    <div class="container ">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Dashboard</h4>
                        <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
                    </div>
                    <div class="card-body">
                        <h5>Welcome to IT-PID, <?= $_SESSION['auth_user']['username']?>!</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php') ?>