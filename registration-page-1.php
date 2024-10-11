<?php
session_start();
$page_title = "Register - Step 1";
include('includes/header.php');
?>
<link rel="stylesheet" href="./assets/css/login_register_page.css">
<link rel="stylesheet" href="./assets/css/page_transition.css">

<div class="py-5 px-2 vh-100 d-flex flex-column main">
    <div class="container flex-grow-1">
        <div class="row justify-content-center h-100">
            <div class="col-md-6 d-flex flex-column justify-content-between">

                <!-- Alert -->
                <?php
                if(isset($_SESSION['status']))
                {
                    echo '<div class="alert alert-primary"><h4>' . htmlspecialchars($_SESSION['status']) . '</h4></div>';
                    unset($_SESSION['status']);
                }
                ?>

                <!-- Registration Form -->
                <h1>
                    <a href="index.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left-circle icon-lg" style="color: black;"></i>
                    </a> 
                </h1>
                <br>
                <br>
                <br>
                <br>
                <form action="registration-page-2.php" method="POST" class="d-flex flex-column flex-grow-1 position-relative">
                    <div class="flex-grow-1 ">
                    <h1 style="color: black; font-size: 2.5rem;">Register</h1>
                    <br>
                        <div class="form-group mb-3">
                            <label for="f_name" class="label-font">First Name</label>
                            <input type="text" name="f_name" id="f_name" placeholder="Enter First Name" class="form-control form-control-lg input-margin" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="l_name" class="label-font">Last Name</label>
                            <input type="text" name="l_name" id="l_name" placeholder="Enter Last Name" class="form-control form-control-lg input-margin" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email" class="label-font">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="Enter Email Address" class="form-control form-control-lg" required autocomplete="on">
                        </div>
                    </div>
                    <!-- Button at the bottom -->
                    <div class="form-group">
                        <button type="submit" name="next_step" class="btn btn-custom-primary w-100 mt-auto register-btn">Continue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="./assets/js/page_transition.js"></script>

<?php include('includes/footer.php'); ?>