<?php
session_start();

$page_title = "Resend Email Verification";
include('includes/header.php');
?>
<link rel="stylesheet" href="assets/css/bg.css">
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php
                if (isset($_SESSION['status'])) {
                    $alertClass = ($_SESSION['status_code'] ?? '') === 'success' ? 'alert-success' : 'alert-danger';
                    echo "<div class='alert {$alertClass}' role='alert'>";
                    echo "<h5>" . htmlspecialchars($_SESSION['status']) . "</h5>";
                    echo "</div>";
                    unset($_SESSION['status']);
                    unset($_SESSION['status_code']);
                }
                ?>

                <div class="card shadow">
                    <div class="card-header">
                        <h3>
                            <a href="login-page.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left-circle"></i>
                            </a>
                            Resend Email Verification
                        </h3>
                    </div>
                    <div class="card-body">
                        <form action="resend_email_verification-process.php" method="POST">
                            <div class="form-group mb-3">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" required autocomplete="email">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="resend_email_verification_btn" class="btn btn-custom-primary w-100">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>