<?php
session_start();

$page_title = "Reset Password";
include('includes/header.php');
?>

<div class="py-5">
    <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">

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

                    <div class="card shadow">
                        <div class="card-header">
                            <h3><a href="index.php"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5z"/>
                            </svg></a> Reset Password</h3>
                        </div>
                        <div class="card-body">
                            <form action="password_reset_code.php" method="POST">
                                <div class="form-group mb-3">
                                    <label for="">Email</label>
                                    <input type="text" name="email" class="form-control" placeholder="Enter Email Address">
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="password_reset_btn" class="btn btn-primary">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

<?php include('includes/footer.php') ?>