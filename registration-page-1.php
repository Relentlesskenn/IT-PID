<?php
session_start();
$page_title = "Register - Step 1";
include('includes/header.php');
?>
<link rel="stylesheet" href="./assets/css/bg.css">
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">

                <!-- Alert -->
                <?php
                if(isset($_SESSION['status']))
                {
                    echo '<div class="alert alert-primary"><h4>' . htmlspecialchars($_SESSION['status']) . '</h4></div>';
                    unset($_SESSION['status']);
                }
                ?>

                <div class="card shadow">
                    <div class="card-header">
                        <h3>
                            <a href="index.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left-circle"></i>
                            </a> 
                            Create an Account
                        </h3>
                    </div>
                    <!-- Registration Form -->
                    <div class="card-body">
                        <form action="registration-page-2.php" method="POST">
                            <div class="form-group mb-3">
                                <label for="f_name">First Name</label>
                                <input type="text" name="f_name" id="f_name" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="l_name">Last Name</label>
                                <input type="text" name="l_name" id="l_name" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" required autocomplete="on">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="next_step" class="btn btn-custom-primary w-100">Continue</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>