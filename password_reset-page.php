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
<link rel="stylesheet" href="./assets/css/custom-strength-meter.css">

<!-- HTML content -->
<div class="py-5 px-2 vh-100 d-flex flex-column main">
    <div class="container flex-grow-1">
        <div class="row justify-content-center h-100">
            <div class="col-md-6 d-flex flex-column justify-content-between">

                <!-- Change Password Form -->
                <div class="flex-grow-1">
                <h1 style="color: black; font-size: 2rem; margin-top: 10rem;">Reset your password</h1>
                <br>
                    <form action="password_reset-process.php" method="POST" id="passwordResetForm">
                        <input type="hidden" name="password_token" value="<?php if(isset($_GET['token'])){echo $_GET['token'];} ?>">
                                
                        <div class="form-group mb-3">
                            <label for="email" class="form-label label-font">Email Address</label>
                            <input type="email" name="email" id="email" value="<?php if(isset($_GET['email'])){echo $_GET['email'];} ?>" class="form-control form-control-lg" required autocomplete="email">
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_password" class="form-label label-font">New Password</label>
                            <input type="password" name="new_password" id="new_password" placeholder="Enter New Password" class="form-control form-control-lg" required onkeyup="checkPasswordStrength()">
                            <div class="password-strength-meter">
                                <div id="password-strength-meter-fill" class="password-strength-meter-fill"></div>
                            </div>
                            <p id="password-strength-text" class="password-strength-text"></p>
                            <div id="password-requirements" class="password-requirements"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="confirm_password" class="form-label label-font">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Enter Confirm Password" class="form-control form-control-lg" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="password_update_btn" class="btn btn-custom-primary w-100 reset-margin">Update</button>
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

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Transition Script -->
<script src="./assets/js/page_transition.js"></script>
<script>
    // Check password strength and display the strength meter
    function checkPasswordStrength() {
        const password = document.getElementById('new_password').value;
        const strengthMeterFill = document.getElementById('password-strength-meter-fill');
        const strengthText = document.getElementById('password-strength-text');
        const requirementsElement = document.getElementById('password-requirements');
        
        let strength = 0;
        const requirements = [
            { regex: /.{12,}/, text: "At least 12 characters long" },
            { regex: /[A-Z]/, text: "Contains uppercase letters" },
            { regex: /[a-z]/, text: "Contains lowercase letters" },
            { regex: /[0-9]/, text: "Contains numbers" },
            { regex: /[^A-Za-z0-9]/, text: "Contains special characters" }
        ];
        
        let requirementsHTML = "";
        requirements.forEach(requirement => {
            const isMet = requirement.regex.test(password);
            strength += isMet ? 1 : 0;
            requirementsHTML += `<div class="${isMet ? 'requirement-met' : 'requirement-unmet'}">
                ${isMet ? '✓' : '✗'} ${requirement.text}
            </div>`;
        });
        
        requirementsElement.innerHTML = requirementsHTML;
        
        // Update strength meter
        const percentage = (strength / requirements.length) * 100;
        strengthMeterFill.style.width = `${percentage}%`;
        
        // Set color and text based on strength
        let color, text;
        if (strength <= 2) {
            color = '#B8001F';
            text = 'Weak';
        } else if (strength <= 4) {
            color = '#EC8305';
            text = 'Medium';
        } else {
            color = '#347928';
            text = 'Strong';
        }
        
        strengthMeterFill.style.backgroundColor = color;
        strengthText.textContent = `Password Strength: ${text}`;
        strengthText.style.color = color;
    }

    // Add event listener to password reset form
    document.getElementById('passwordResetForm').addEventListener('submit', function(event) {
        var newPassword = document.getElementById('new_password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            event.preventDefault();
            alert('Passwords do not match.');
        }
    });
</script>

<?php include('includes/footer.php') ?>