<?php
session_start();

$page_title = "Forgot Your Password?";
include('includes/header.php');
?>
<link rel="stylesheet" href=".\assets\css\bg.css">
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
                            <h3>Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form action="password_reset-process.php" method="POST">
                                <input type="hidden" name="password_token" value="<?php if(isset($_GET['token'])){echo $_GET['token'];} ?>">
                                
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" name="email" id="email" value="<?php if(isset($_GET['email'])){echo $_GET['email'];} ?>" class="form-control">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                                </div>
                                <div class="form-group">
                                    <button type="submit" name="password_update_btn" class="btn btn-primary w-100">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

<?php include('includes/footer.php') ?>