<?php
session_start();

if (isset($_SESSION['authenticated'])) {
    $_SESSION['status'] = "You are already logged in!";
    header('Location: dashboard-page.php');
    exit();
}

$page_title = "Login";
include('includes/header.php');
?>
<link rel="stylesheet" href="assets/css/bg.css">
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php
                if (isset($_SESSION['status'])) {
                    echo '<div class="alert alert-primary"><h5>' . htmlspecialchars($_SESSION['status']) . '</h5></div>';
                    unset($_SESSION['status']);
                }
                ?>
                <div class="card shadow">
                    <div class="card-header">
                        <h3>
                            <a href="landing-page.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5z"/>
                                </svg>
                            </a> 
                            Login
                        </h3>
                    </div>
                    <div class="card-body">
                        <form action="login-process.php" method="POST">
                            <div class="form-group mb-3">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" class="form-control" required autocomplete="username">
                            </div>
                            <div class="form-group mb-3">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="login_btn" class="btn btn-primary w-100">Login</button>
                                <br><br>
                                <a href="forgot_your_password-page.php" class="font-sm text-decoration-none">Forgot your Password?</a>
                            </div>
                        </form>
                        <p class="mt-1 font-sm">
                            Did not receive your Verification Email?
                            <a href="resend_email_verification-page.php" class="text-decoration-none">Resend</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>