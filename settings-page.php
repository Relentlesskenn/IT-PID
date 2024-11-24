<?php
ob_start();
session_start();
include('_dbconnect.php');
include('includes/authentication.php');

// Check if user is logged in
if (!isset($_SESSION['auth_user'])) {
    header("Location: login.php");
    exit();
}

// Get user ID early
$userId = $_SESSION['auth_user']['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Profile Update
    if(isset($_POST['update_profile'])) {
        $firstName = htmlspecialchars(trim($_POST['f_name']));
        $lastName = htmlspecialchars(trim($_POST['l_name']));
        
        $stmt = $conn->prepare("UPDATE users SET f_name = ?, l_name = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $firstName, $lastName, $userId);
        
        if($stmt->execute()) {
            // Update session data
            $_SESSION['auth_user']['f_name'] = $firstName;
            $_SESSION['auth_user']['l_name'] = $lastName;
            
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating profile: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
        header("Location: settings-page.php");
        exit();
    }
    
    // Handle Password Change
    if(isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Password validation
        if ($newPassword !== $confirmPassword) {
            $_SESSION['message'] = "New passwords do not match.";
            $_SESSION['message_type'] = "danger";
            header("Location: settings-page.php");
            exit();
        }

        // Check password strength
        include('includes/password_policy.php');
        $passwordStrength = is_password_strong($newPassword);
        if ($passwordStrength !== true) {
            $_SESSION['message'] = "Password is not strong enough: " . implode(", ", $passwordStrength);
            $_SESSION['message_type'] = "danger";
            header("Location: settings-page.php");
            exit();
        }
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if(password_verify($currentPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            
            if($stmt->execute()) {
                $_SESSION['message'] = "Password changed successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error changing password: " . $conn->error;
                $_SESSION['message_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Current password is incorrect.";
            $_SESSION['message_type'] = "danger";
        }
        header("Location: settings-page.php");
        exit();
    }
    
    // Handle Account Deletion
    if(isset($_POST['delete_account'])) {
        $password = $_POST['confirm_password'];
        
        // Verify password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if(password_verify($password, $user['password'])) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Delete related records first
                $tables = ['expenses', 'incomes', 'budgets', 'goals', 'notifications', 'balances', 'budget_alerts', 'goal_alerts', 'cumulative_balance'];
                foreach($tables as $table) {
                    $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                }
                
                // Delete user account
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                
                $conn->commit();
                session_destroy();
                header("Location: login.php?message=account_deleted");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Account deletion error: " . $e->getMessage());
                $_SESSION['message'] = "Error deleting account. Please try again.";
                $_SESSION['message_type'] = "danger";
                header("Location: settings-page.php");
                exit();
            }
        } else {
            $_SESSION['message'] = "Invalid password.";
            $_SESSION['message_type'] = "danger";
            header("Location: settings-page.php");
            exit();
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

// Set page title and include header files
$page_title = "Settings · IT-PID";
include('includes/header.php');
include('includes/navbar.php');
?>

<link rel="stylesheet" href="./assets/css/settings.css">

<!-- HTML content -->
<div class="pt-4 pb-5">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4">Settings</h1>
        </div>

        <!-- Display Messages -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Settings Accordion -->
        <div class="accordion settings-accordion mb-4" id="settingsAccordion">
            <!-- Account Settings -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#accountSettings" aria-expanded="false" aria-controls="accountSettings">
                        <i class="bi bi-person-circle me-2"></i>Account Settings
                    </button>
                </h2>
                <div id="accountSettings" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($userData['username']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                                        <input type="text" name="f_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($userData['f_name']); ?>" required
                                               pattern="[A-Za-z ]{2,50}">
                                        <div class="invalid-feedback">
                                            Please enter a valid first name (2-50 characters, letters only).
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                                        <input type="text" name="l_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($userData['l_name']); ?>" required
                                               pattern="[A-Za-z ]{2,50}">
                                        <div class="invalid-feedback">
                                            Please enter a valid last name (2-50 characters, letters only).
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="update_profile" class="btn btn-custom-primary w-100">
                                        <i class="bi bi-check-circle me-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#securitySettings" aria-expanded="false" aria-controls="securitySettings">
                        <i class="bi bi-shield-lock me-2"></i>Security Settings
                    </button>
                </h2>
                <div id="securitySettings" class="accordion-collapse collapse" data-bs-parent="#settingsAccordion">
                    <div class="accordion-body">
                        <form method="POST" action="" id="passwordForm" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="password" name="current_password" class="form-control" required
                                            minlength="8">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Please enter your current password.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" name="new_password" class="form-control" required
                                            minlength="12" id="newPassword" onkeyup="checkPasswordStrength()">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <!-- Password Strength Meter -->
                                    <div class="password-strength-meter mt-2">
                                        <div id="password-strength-meter-fill" class="password-strength-meter-fill"></div>
                                    </div>
                                    <p id="password-strength-text" class="password-strength-text mt-1"></p>
                                    <div id="password-requirements" class="password-requirements mt-2"></div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" name="confirm_password" class="form-control" required
                                            minlength="12">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Passwords do not match.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="change_password" class="btn btn-custom-primary w-100"
                                            id="changePasswordBtn" disabled>
                                        <i class="bi bi-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Actions -->
            <div class="col-12">
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-transparent">
                        <h5 class="card-title mb-0 text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>Account Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="logout.php" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                    <i class="bi bi-trash me-2"></i>Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>Warning!</strong> This action cannot be undone. All your data will be permanently deleted.
                </div>
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Enter your password to confirm:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" name="confirm_password" class="form-control" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">
                                Please enter your password to confirm.
                            </div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="delete_account" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Yes, Delete My Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(() => {
    'use strict';
    
    // Fetch all forms that need validation
    const forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Additional password match validation for password form
            if (form.id === 'passwordForm') {
                const newPassword = form.querySelector('[name="new_password"]').value;
                const confirmPassword = form.querySelector('[name="confirm_password"]').value;
                
                if (newPassword !== confirmPassword) {
                    event.preventDefault();
                    form.querySelector('[name="confirm_password"]').setCustomValidity('Passwords do not match');
                } else {
                    form.querySelector('[name="confirm_password"]').setCustomValidity('');
                }
            }
            
            form.classList.add('was-validated');
        }, false);
        
        // Clear custom validity when user types in confirm password field
        if (form.id === 'passwordForm') {
            const confirmPasswordInput = form.querySelector('[name="confirm_password"]');
            confirmPasswordInput.addEventListener('input', () => {
                confirmPasswordInput.setCustomValidity('');
            });
        }
    });
})();

// Password strength checker function
function checkPasswordStrength() {
    const password = document.getElementById('newPassword').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]');
    const strengthMeterFill = document.getElementById('password-strength-meter-fill');
    const strengthText = document.getElementById('password-strength-text');
    const requirementsElement = document.getElementById('password-requirements');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    
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

    // Enable/disable submit button based on password strength
    changePasswordBtn.disabled = strength < 5;

    // Update confirm password validation
    if (confirmPassword.value) {
        if (password !== confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords don't match");
            changePasswordBtn.disabled = true;
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
}

// Password visibility toggle
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
});

// Add event listener for confirm password
document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
    const newPassword = document.getElementById('newPassword').value;
    if (this.value !== newPassword) {
        this.setCustomValidity("Passwords don't match");
        document.getElementById('changePasswordBtn').disabled = true;
    } else {
        this.setCustomValidity('');
        // Re-check password strength to enable/disable button
        checkPasswordStrength();
    }
});

// Toast notification for messages
document.addEventListener('DOMContentLoaded', function() {
    // Initialize password validation
    checkPasswordStrength();
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Add fade out animation before closing
    document.querySelectorAll('.alert .btn-close').forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert');
            alert.style.opacity = '0';
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 150);
        });
    });
});

// Enable tooltips
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Prevent accordion collapse when clicking inside forms
document.querySelectorAll('.accordion-body form').forEach(form => {
    form.addEventListener('click', event => {
        event.stopPropagation();
    });
});

// Show confirmation dialog before account deletion
document.querySelector('button[name="delete_account"]').addEventListener('click', function(e) {
    if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.')) {
        e.preventDefault();
    }
});

// Common weak password patterns check
function hasCommonPatterns(password) {
    const commonPatterns = [
        /123/,
        /abc/i,
        /qwerty/i,
        /password/i,
        /admin/i,
        new RegExp(new Date().getFullYear()),
        /test/i
    ];
    
    return commonPatterns.some(pattern => pattern.test(password));
}

// Check for keyboard sequence patterns
function hasKeyboardSequence(password) {
    const sequences = [
        'qwertyuiop',
        'asdfghjkl',
        'zxcvbnm'
    ];
    
    return sequences.some(sequence => {
        for (let i = 0; i < sequence.length - 2; i++) {
            if (password.toLowerCase().includes(sequence.substring(i, i + 3))) {
                return true;
            }
        }
        return false;
    });
}

// Reset form when accordion is closed
document.querySelector('#securitySettings').addEventListener('hidden.bs.collapse', function () {
    const form = this.querySelector('form');
    if (form) {
        form.reset();
        checkPasswordStrength();
        document.getElementById('password-requirements').innerHTML = '';
        document.getElementById('password-strength-text').textContent = '';
        document.getElementById('password-strength-meter-fill').style.width = '0%';
    }
});

// Handle escape key to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    }
});
</script>

<?php 
include('includes/footer.php');
ob_end_flush(); 
?>