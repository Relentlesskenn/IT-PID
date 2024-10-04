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
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5z"/>
                                </svg>
                            </a>
                            Resend Email Verification
                        </h3>
                    </div>
                    <div class="card-body">
                        <form action="resend_email_verification-process.php" method="POST">
                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="Enter Email Address" required autocomplete="email">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="resend_email_verification_btn" class="btn btn-primary w-100">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>