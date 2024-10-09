<?php
session_start();

$page_title = "Forgot Your Password?";
include('includes/header.php');
?>
<link rel="stylesheet" href="./assets/css/bg.css">
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">

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
                
                <div class="card shadow">
                    <div class="card-header">
                        <h3>
                            <a href="login-page.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left-circle"></i>
                            </a>
                            Reset your password
                        </h3>
                    </div>
                    <div class="card-body">
                        <form action="password_reset-process.php" method="POST">
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" required autocomplete="email">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="password_reset_btn" class="btn btn-custom-primary w-100">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>