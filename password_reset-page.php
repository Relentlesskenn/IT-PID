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

$page_title = "Reset Password · IT-PID";
include('includes/header.php');
?>
<link rel="stylesheet" href="./assets/css/login_register_page.css">
<link rel="stylesheet" href="./assets/css/page_transition.css">

<div class="py-5 px-2 vh-100 d-flex flex-column main">
    <div class="container flex-grow-1">
        <div class="row justify-content-center h-100">
            <div class="col-md-6 d-flex flex-column justify-content-between">

                <!--Alerts-->
                <?php
                    if(isset($_SESSION['status']))
                    {
                        ?>
                        <div class="alert alert-primary">
                            <h5><?= $_SESSION['status']; ?></h5>
                        </div>
                <?php
                    unset($_SESSION['status']);
                    }
                ?>

                <!-- Change Password Form -->
                <div class="flex-grow-1">
                <h1 style="color: black; font-size: 2rem; margin-top: 10rem;">Reset your password</h1>
                <br>
                    <form action="password_reset-process.php" method="POST">
                        <input type="hidden" name="password_token" value="<?php if(isset($_GET['token'])){echo $_GET['token'];} ?>">
                                
                        <div class="form-group mb-3">
                            <label for="email" class="form-label label-font">Email Address</label>
                            <input type="email" name="email" id="email" value="<?php if(isset($_GET['email'])){echo $_GET['email'];} ?>" class="form-control form-control-lg" required autocomplete="email">
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_password" class="form-label label-font">New Password</label>
                            <input type="password" name="new_password" id="new_password" placeholder="Enter New Password" class="form-control form-control-lg" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="confirm_password" class="form-label label-font">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Enter Confirm Password" class="form-control form-control-lg" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="password_update_btn" class="btn btn-custom-primary w-100 reset-margin">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="./assets/js/page_transition.js"></script>

<?php include('includes/footer.php') ?>