<?php
// Secure cookie settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

session_start();

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

$page_title = "Forgot Your Password? · IT-PID";
include('includes/header.php');
?>
<link rel="stylesheet" href="./assets/css/login_register_page.css">
<link rel="stylesheet" href="./assets/css/page_transition.css">

<div class="py-5 px-2 vh-100 d-flex flex-column main">
    <div class="container flex-grow-1">
        <div class="row justify-content-center h-100">
            <div class="col-md-6 d-flex flex-column justify-content-between">

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

                    <!-- Alert -->
                    <div class="alert-container">
                    <?php
                        include('includes/alert_helper.php');

                        if (isset($_SESSION['status'])) {
                            $status_type = $_SESSION['status_type'] ?? 'primary';
                            echo generate_custom_alert($_SESSION['status'], $status_type);
                            unset($_SESSION['status']);
                            unset($_SESSION['status_type']);
                        }

                        if (isset($_SESSION['error'])) {
                            echo generate_custom_alert($_SESSION['error'], 'danger');
                            unset($_SESSION['error']);
                        }
                    ?>
                    </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="./assets/js/page_transition.js"></script>

<?php include('includes/footer.php'); ?>