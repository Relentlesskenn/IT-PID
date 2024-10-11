<?php
// Create a new file named password_policy.php in your includes directory

function is_password_strong($password) {
    // Initialize an array to store any error messages
    $errors = [];

    // Check password length (minimum 12 characters recommended)
    if (strlen($password) < 12) {
        $errors[] = "Password must be at least 12 characters long.";
    }

    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }

    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }

    // Check for at least one number
    if (!preg_match('/\d/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }

    // Check for at least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character.";
    }

    // Check for common weak patterns
    $weak_patterns = [
        '/123/',                 // Sequential numbers
        '/abc/i',                // Sequential letters
        '/qwerty/i',             // Keyboard patterns
        '/password/i',           // Common weak passwords
        '/'.preg_quote(date('Y')).'/'  // Current year
    ];

    foreach ($weak_patterns as $pattern) {
        if (preg_match($pattern, $password)) {
            $errors[] = "Password contains a common weak pattern.";
            break;
        }
    }

    // If there are no errors, the password is considered strong
    if (empty($errors)) {
        return true;
    } else {
        // Return the array of error messages
        return $errors;
    }
}

// Function to generate a password strength message
function get_password_strength_message($password) {
    $result = is_password_strong($password);
    
    if ($result === true) {
        return "Password is strong!";
    } else {
        return "Password is weak. Please address the following:\n- " . implode("\n- ", $result);
    }
}
?>