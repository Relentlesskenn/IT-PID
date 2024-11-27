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

// Check for logout message
$logout_message = $_COOKIE['logout_message'] ?? null;
if ($logout_message) {
    // Clear the cookie
    setcookie('logout_message', '', time() - 3600, '/', '', true, true);
    
    // Set the message to be displayed
    $_SESSION['status'] = $logout_message;
    $_SESSION['status_type'] = 'status';
}

$page_title = "Login to IT-PID · IT-PID";
include('includes/header.php');
?>
<link rel="stylesheet" href="assets/css/login_register_page.css">
<link rel="stylesheet" href="./assets/css/page_transition.css">

<!-- HTML content -->
<body class="graphs-page">
<div class="main">
    <div class="container" style="margin-top: 100px;">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="flex-wrapper">
                    <!-- Back Button -->
                    <div class="content-spacing">
                        <h1>
                            <a href="index.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left-circle icon-lg" style="color: white;"></i>
                            </a> 
                        </h1>
                    </div>

                    <!-- Logo Section -->
                    <div class="logo-container">
                        <h1 class="logo-text-upper">
                            IT
                        </h1>
                        <h1 class="logo-text-bottom">
                            PID
                        </h1>
                    </div>

                    <!-- Login Form -->
                    <form action="login-process.php" method="POST" class="register-form-container">
                        <div class="form-content">
                            <div class="form-group mb-3">
                                <label for="username" class="label-font">Username</label>
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       placeholder="Enter Username" 
                                       class="form-control form-control-lg input-margin" 
                                       required 
                                       autocomplete="username">
                            </div>

                            <div class="form-group">
                                <label for="password" class="label-font">Password</label>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       placeholder="Enter Password" 
                                       class="form-control form-control-lg" 
                                       required 
                                       autocomplete="current-password">
                            </div>

                            <!-- Alert Container -->
                            <div class="alert-container">
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
                        </div>

                        <!-- Button and Links Container -->
                        <div class="btn-container" style="margin-bottom: 100px;">
                            <button type="submit" name="login_btn" class="btn register-btn btn-ripple w-100">
                                <span>Log in</span>
                            </button>
                            
                            <div class="mt-3">
                                <a href="forgot_your_password-page.php" 
                                   class="font-sm text-decoration-none d-block mb-2" 
                                   style="color: #7E60BF;">
                                    Forgot your Password?
                                </a>
                                <p class="font-sm mb-0" style="color: white;">
                                    Didn't receive your Verification Email? 
                                    <a href="resend_email_verification-page.php" 
                                       class="text-decoration-none" 
                                       style="color: #7E60BF;">
                                        Resend
                                    </a>
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Transition Script -->
<script src="./assets/js/page_transition.js"></script>

<?php include('includes/footer.php'); ?>