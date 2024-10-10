<?php
session_start();
$page_title = "Register - Step 1";
include('includes/header.php');
?>
<link rel="stylesheet" href="./assets/css/login_register_page.css">

<div class="py-5 vh-100 d-flex flex-column main" style="color: #433878;"> <!-- Set the text color for the entire container -->
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
                        <i class="bi bi-arrow-left-circle hover-icon" style="color: black;"></i>
                    </a> 
                </h1>
                
                <br>
                <br>
                <br>
                <br>
                <form action="registration-page-2.php" method="POST" class="d-flex flex-column flex-grow-1 position-relative">
                    <div class="flex-grow-1 ">
                    <h1 style="color: black;">Register</h1>
                    <br>
                        <div class="form-group mb-3">
                            <label for="f_name">First Name</label>
                            <input type="text" name="f_name" id="f_name" placeholder="Enter Firstname" class="form-control border border-3" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="l_name">Last Name</label>
                            <input type="text" name="l_name" id="l_name" placeholder="Enter Lastname" class="form-control border border-3" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="Enter Email Address" class="form-control border border-3" required autocomplete="on">
                        </div>
                    </div>
                    <!-- Button at the bottom -->
                    <div class="form-group">
                        <button type="submit" name="next_step" class="btn btn-custom-primary w-100 mt-auto">Continue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>