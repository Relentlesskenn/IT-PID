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
$page_title = "Join IT-PID · IT-PID";
include('includes/header.php');
?>
<link rel="stylesheet" href="./assets/css/login_register_page.css">
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

                    <!-- Registration Form -->
                    <form action="registration-page-2.php" method="POST" class="register-form-container">
                        <div class="form-content">
                            <h1 class="text-white mb-4" style="font-size: calc(1.8rem + 1vw); margin-top: 50px;">Register</h1>
                            
                            <div class="form-group mb-3">
                                <label for="f_name" class="label-font">First Name</label>
                                <input type="text" 
                                       name="f_name" 
                                       id="f_name" 
                                       placeholder="Enter First Name" 
                                       class="form-control form-control-lg input-margin" 
                                       required 
                                       onblur="capitalizeFirstLetter(this)">
                            </div>

                            <div class="form-group mb-3">
                                <label for="l_name" class="label-font">Last Name</label>
                                <input type="text" 
                                       name="l_name" 
                                       id="l_name" 
                                       placeholder="Enter Last Name" 
                                       class="form-control form-control-lg input-margin" 
                                       required 
                                       onblur="capitalizeFirstLetter(this)">
                            </div>

                            <div class="form-group mb-3">
                                <label for="email" class="label-font">Email Address</label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       placeholder="Enter Email Address" 
                                       class="form-control form-control-lg" 
                                       required 
                                       autocomplete="email">
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

                                    if (isset($_SESSION['error'])) {
                                        echo generate_custom_alert($_SESSION['error'], 'danger');
                                        unset($_SESSION['error']);
                                    }
                                ?>
                            </div>
                        </div>

                        <!-- Button Container -->
                        <div class="btn-container">
                            <button type="submit" name="register_btn" class="btn register-btn btn-ripple w-100" style="margin-bottom: 100px;">
                                <span>Continue</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Transition Script -->
<script src="./assets/js/page_transition.js"></script>
<script>
// Capitalize first letter of input
function capitalizeFirstLetter(input) {
    input.value = input.value.replace(/\b\w/g, function(l){ return l.toUpperCase() });
}
</script>

<?php include('includes/footer.php'); ?>