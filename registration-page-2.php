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

// Check if the user came from step 1 or if there's stored registration data
if (!isset($_POST['f_name']) && !isset($_SESSION['registration_data'])) {
    header("Location: registration-page-1.php");
    exit();
}

// Use stored data if available, otherwise use POST data
$f_name = $_SESSION['registration_data']['f_name'] ?? $_POST['f_name'];
$l_name = $_SESSION['registration_data']['l_name'] ?? $_POST['l_name'];
$email = $_SESSION['registration_data']['email'] ?? $_POST['email'];
$username = $_SESSION['registration_data']['username'] ?? '';

// Sanitize input
$f_name = filter_var($f_name);
$l_name = filter_var($l_name);
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$username = filter_var($username);
?>

<link rel="stylesheet" href="./assets/css/login_register_page.css">
<link rel="stylesheet" href="./assets/css/page_transition.css">
<link rel="stylesheet" href="./assets/css/custom-strength-meter.css">

<!-- HTML content -->
<body class="graphs-page">
<div class="main">
    <div class="container" style="margin-top: 100px;">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="flex-wrapper">
                    <!-- Back Button -->
                    <div class="content-spacing">
                        <h1>
                            <a href="registration-page-1.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left-circle icon-lg" style="color: white;"></i>
                            </a> 
                        </h1>
                    </div>

                    <!-- Registration Form -->
                    <form action="registration-process.php" method="POST" class="register-form-container">
                        <!-- Hidden Fields -->
                        <input type="hidden" name="f_name" value="<?= htmlspecialchars($f_name) ?>">
                        <input type="hidden" name="l_name" value="<?= htmlspecialchars($l_name) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                        <div class="form-content">
                            <h1 class="text-white mb-4" style="font-size: calc(1.8rem + 1vw); margin-top: 50px;">Register</h1>

                            <div class="form-group mb-3">
                                <label for="username" class="label-font">Username</label>
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       placeholder="Enter Username" 
                                       class="form-control form-control-lg input-margin" 
                                       required 
                                       autocomplete="username" 
                                       value="<?= htmlspecialchars($username) ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="password" class="label-font">Password</label>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       placeholder="Enter Password" 
                                       class="form-control form-control-lg input-margin" 
                                       required 
                                       onkeyup="checkPasswordStrength()">
                                <div class="password-strength-meter">
                                    <div id="password-strength-meter-fill" class="password-strength-meter-fill"></div>
                                </div>
                                <p id="password-strength-text" class="password-strength-text"></p>
                                <div id="password-requirements" class="password-requirements"></div>
                            </div>

                            <div class="form-group mb-1">
                                <label for="c_password" class="label-font">Confirm Password</label>
                                <input type="password" 
                                       name="c_password" 
                                       id="c_password" 
                                       placeholder="Enter Confirm Password" 
                                       class="form-control form-control-lg" 
                                       required>
                            </div>

                            <!-- Alert Container -->
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

                        <!-- Button Container -->
                        <div class="btn-container">
                            <button type="submit" name="register_btn" class="btn register-btn btn-ripple w-100" style="margin-bottom: 100px;">
                                <span>Register</span>
                            </button>
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
        const password = document.getElementById('password').value;
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
</script>

<?php include('includes/footer.php'); ?>