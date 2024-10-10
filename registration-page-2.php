<?php
session_start();
$page_title = "Register - Step 2";
include('includes/header.php');

// Check if the user came from step 1
if (!isset($_POST['f_name']) || !isset($_POST['l_name']) || !isset($_POST['email'])) {
    header("Location: registration-page-1.php");
    exit();
}

// Sanitize input
$f_name = filter_input(INPUT_POST, 'f_name');
$l_name = filter_input(INPUT_POST, 'l_name');
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
?>

<link rel="stylesheet" href="./assets/css/login_register_page.css">

<div class="py-5 vh-100 d-flex flex-column main" style="color: #433878;"> <!-- Set text color for the entire container -->
    <div class="container flex-grow-1">
        <div class="row justify-content-center h-100">
            <div class="col-md-6 d-flex flex-column justify-content-between">

                <!-- Alert -->
                <?php
                if (isset($_SESSION['status'])) {
                    echo '<div class="alert alert-primary"><h4>' . htmlspecialchars($_SESSION['status']) . '</h4></div>';
                    unset($_SESSION['status']);
                }
                ?>

                <!-- Registration Form -->
                <h1>
                    <a href="registration-page-1.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left-circle hover-icon" style= "color:black;"></i>
                    </a> 
                </h1>
                <br>
                <form action="registration-process.php" method="POST" class="d-flex flex-column flex-grow-1">
                    <input type="hidden" name="f_name" value="<?= htmlspecialchars($f_name) ?>">
                    <input type="hidden" name="l_name" value="<?= htmlspecialchars($l_name) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <br>
                <br>
                <br>
                    <div class="flex-grow-1">
                        <h1><span style="color: black;">Register</span></h1>
                        <br>
                        <div class="form-group mb-3">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" placeholder="Enter Username" class="form-control border border-3" required autocomplete="on">
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" placeholder="Enter Password" class="form-control border border-3" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="c_password">Confirm Password</label>
                            <input type="password" name="c_password" id="c_password" placeholder="Enter Password" class="form-control border border-3" required>
                        </div>
                    </div>
                    <!-- Button at the bottom -->
                    <div class="form-group">
                        <button type="submit" name="register_btn" class="btn btn-custom-primary w-100 mt-auto">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>