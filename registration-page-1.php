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
<div class="py-5 px-2 vh-100 d-flex flex-column main">
    <div class="container flex-grow-1">
        <div class="row justify-content-center h-100">
            <div class="col-md-6 d-flex flex-column justify-content-between">

                <!-- Registration Form -->
                <h1>
                    <a href="index.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left-circle icon-lg" style="color: white;"></i>
                    </a> 
                </h1>
                <br>
                <br>
                <br>
                <br>
                <form action="registration-page-2.php" method="POST" class="d-flex flex-column flex-grow-1 position-relative">
                    <div class="flex-grow-1 ">
                    <h1 style="color: white; font-size: 2.5rem;">Register</h1>
                    <br>
                        <div class="form-group mb-3">
                            <label for="f_name" class="label-font">First Name</label>
                            <input type="text" name="f_name" id="f_name" placeholder="Enter First Name" class="form-control form-control-lg input-margin" required onblur="capitalizeFirstLetter(this)">
                        </div>
                        <div class="form-group mb-3">
                            <label for="l_name" class="label-font">Last Name</label>
                            <input type="text" name="l_name" id="l_name" placeholder="Enter Last Name" class="form-control form-control-lg input-margin" required onblur="capitalizeFirstLetter(this)">
                        </div>
                        <div class="form-group mb-3">
                            <label for="email" class="label-font">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="Enter Email Address" class="form-control form-control-lg" required autocomplete="on">
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
                        
                    </div>
                    <!-- Button at the bottom -->
                    <div class="form-group">
                    <button type="submit" name="register_btn" class="btn register-btn btn-ripple w-100">
                        <span>Continue</span>
                    </button>
                    </div>
                </form>
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