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

// Check if user is already logged in
if (isset($_SESSION['authenticated'])) {
    $_SESSION['status'] = "You are already logged in!";
    header('Location: dashboard-page.php');
    exit();
}

$page_title = "Login to IT-PID · IT-PID";
include('includes/header.php');
?>
<link rel="stylesheet" href="assets/css/login_register_page.css">
<link rel="stylesheet" href="./assets/css/page_transition.css">

<div class="py-5 px-2 vh-100 d-flex flex-column main">
    <div class="container flex-grow-1">
        <div class="row justify-content-center h-100">
            <div class="col-md-6 d-flex flex-column justify-content-between">
                
                <h1>
                    <a href="index.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left-circle icon-lg" style="color: black;"></i>
                    </a> 
                </h1>
                <!-- Logo Section -->
                <div class="logo-container">
                    <h1 class="logo-text-upper">
                        IT
                    </h1>
                    <h1 class="logo-text-bottom">
                        PID
                    </h1>
                </div>
                <br>
                <!-- Login Form -->
                <form action="login-process.php" method="POST" class="d-flex flex-column">
                    <div class="flex-grow-1">
                        <div class="form-group mb-3">
                            <label for="username" class="label-font">Username</label>
                            <input type="text" name="username" id="username" placeholder="Enter Username" class="form-control form-control-lg input-margin" required autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label for="password" class="label-font">Password</label>
                            <input type="password" name="password" id="password" placeholder="Enter Password" class="form-control form-control-lg" required autocomplete="current-password">
                        </div>
                    </div>
                    
                    <div class="alert-container">
                    <!-- Alert -->
                    <?php
                        include('includes/alert_helper.php');

                        if (isset($_SESSION['status'])) {
                            $status_type = $_SESSION['status_type'] ?? 'primary';
                            echo generate_custom_alert($_SESSION['status'], $status_type);
                            unset($_SESSION['status']);
                            unset($_SESSION['status_type']);
                        }

                        if (isset($_SESSION['success'])) {
                            echo generate_custom_alert($_SESSION['success'], 'success');
                            unset($_SESSION['success']);
                        }

                        if (isset($_SESSION['error'])) {
                            echo generate_custom_alert($_SESSION['error'], 'danger');
                            unset($_SESSION['error']);
                        }
                    ?>
                    </div>
                    
                    <!-- Button at the bottom -->
                    <div class="form-group">
                        <button type="submit" name="login_btn" class="btn btn-custom-primary w-100">Log in</button>
                        <br><br>
                        <a href="forgot_your_password-page.php" class="font-sm text-decoration-none" style="color: #7E60BF;">Forgot your Password?</a>
                    </div>
                </form>
                <p class="mt-1 font-sm" style="color: #433878;">
                    Didn't receive your Verification Email?
                    <a href="resend_email_verification-page.php" class="text-decoration-none" style="color: #7E60BF;">Resend</a>
                </p>
            </div>
        </div>  
    </div>
</div>

<script src="./assets/js/page_transition.js"></script>

<?php include('includes/footer.php'); ?>