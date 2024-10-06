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

<link rel="stylesheet" href="./assets/css/bg.css">
<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">

                <!-- Alert -->
                <?php
                if(isset($_SESSION['status']))
                {
                    echo '<div class="alert alert-primary"><h4>' . htmlspecialchars($_SESSION['status']) . '</h4></div>';
                    unset($_SESSION['status']);
                }
                ?>

                <div class="card shadow">
                    <div class="card-header">
                        <h3>
                            <a href="registration-page-1.php" class="text-decoration-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5z"/>
                                </svg>
                            </a> 
                            Create an Account
                        </h3>
                    </div>
                    <!-- Registration Form -->
                    <div class="card-body">
                        <form action="registration-process.php" method="POST">
                            <input type="hidden" name="f_name" value="<?= htmlspecialchars($f_name) ?>">
                            <input type="hidden" name="l_name" value="<?= htmlspecialchars($l_name) ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                            <div class="form-group mb-3">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" class="form-control" required autocomplete="on">
                            </div>
                            <div class="form-group mb-3">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="c_password">Confirm Password</label>
                                <input type="password" name="c_password" id="c_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="register_btn" class="btn btn-primary w-100">Register</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>