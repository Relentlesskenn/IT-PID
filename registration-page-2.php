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
                                <i class="bi bi-arrow-left-circle"></i>
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
                                <button type="submit" name="register_btn" class="btn btn-custom-primary w-100">Register</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>