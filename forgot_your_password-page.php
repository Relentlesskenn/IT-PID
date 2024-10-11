<?php
session_start();

$page_title = "Forgot Your Password? · IT-PID";
include('includes/header.php');
?>
<link rel="stylesheet" href="./assets/css/login_register_page.css">
<link rel="stylesheet" href="./assets/css/page_transition.css">

<div class="py-5 px-2 vh-100 d-flex flex-column main">
    <div class="container flex-grow-1">
        <div class="row justify-content-center h-100">
            <div class="col-md-6 d-flex flex-column justify-content-between">

                <!-- Alert -->
                <?php
                if (isset($_SESSION['status'])) {
                    $status_class = ($_SESSION['status_type'] ?? 'primary');
                    echo "<div class='alert alert-{$status_class} alert-dismissible fade show' role='alert'>
                            <h5>{$_SESSION['status']}</h5>
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                          </div>";
                    unset($_SESSION['status']);
                    unset($_SESSION['status_type']);
                }
                ?>

                <!-- Change Password Form -->
                <div class="flex-grow-1">
                    <a href="login-page.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left-circle icon-lg" style="color: black;"></i>
                    </a>
                    <h1 style="color: black; font-size: 2rem; margin-top: 10rem;">Reset your password</h1>
                    <br>
                    <form action="password_reset-process.php" method="POST">
                        <div class="form-group mb-3">
                            <label for="email" class="form-label label-font">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="Enter Email Address" class="form-control form-control-lg" required autocomplete="email">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="password_reset_btn" class="btn btn-custom-primary w-100 reset-margin">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="./assets/js/page_transition.js"></script>

<?php include('includes/footer.php'); ?>