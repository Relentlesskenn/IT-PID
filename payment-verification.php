<?php
session_start();
include('_dbconnect.php');
include('includes/authentication.php');

$page_title = "Payment Verification · IT-PID";
include('includes/header.php');

// Get plan details from session (set in subscription-plans.php)
if (!isset($_SESSION['selected_plan'])) {
    header("Location: subscription-plans.php");
    exit();
}

$planDetails = $_SESSION['selected_plan'];

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $referenceNumber = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
    $userId = $_SESSION['auth_user']['user_id'];
    $planId = $planDetails['id'];
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert pending subscription
        $stmt = $conn->prepare("
            INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status, payment_status) 
            VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MONTH), 'pending', 'pending')
        ");
        $stmt->bind_param("iii", $userId, $planId, $planDetails['duration_months']);
        $stmt->execute();
        $subscriptionId = $conn->insert_id;
        
        // Insert payment record
        $stmt = $conn->prepare("
            INSERT INTO subscription_payments (
                user_id, subscription_id, amount, payment_method, 
                reference_number, payment_status
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iidss", $userId, $subscriptionId, $planDetails['price'], 
                         $paymentMethod, $referenceNumber);
        $stmt->execute();
        
        $conn->commit();
        
        // Clear session plan data
        unset($_SESSION['selected_plan']);
        
        $_SESSION['message'] = "Payment verification submitted successfully! We'll process your payment within 24 hours.";
        $_SESSION['message_type'] = "success";
        header("Location: subscription-plans.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error processing payment. Please try again.";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<link rel="stylesheet" href="./assets/css/payment.css">

<body class="graphs-page">
<div class="payment-container py-5">
    <div class="bento-card">
        <!-- Header -->
        <div class="bento-header">
            <h5 class="mb-0">
            <i class="bi bi-wallet2"></i>    
                Payment Verification</h5>
        </div>

        <div class="bento-body">
            <!-- Plan Details -->
            <div class="plan-details mb-4">
                <div class="detail-row">
                    <span class="text-muted">Plan Type</span>
                    <span class="text-primary"><?php echo htmlspecialchars($planDetails['name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Duration</span>
                    <span class="text-primary"><?php echo $planDetails['duration_months']; ?> month/s</span>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Amount</span>
                    <span class="text-primary">₱<?php echo number_format($planDetails['price'], 2); ?></span>
                </div>
            </div>

            <form method="POST" class="needs-validation" novalidate>
                <!-- Payment Methods -->
                <div class="mb-3">
                    <label class="d-block mb-2">Select Payment Method</label>
                    <div class="payment-grid">
                        <div class="payment-option">
                            <input type="radio" class="btn-check" name="payment_method" 
                                   id="gcash" value="gcash" required>
                            <label for="gcash" class="payment-label">
                                <img src="assets/imgs/payment/gcash-logo.png" alt="GCash">
                                <span>GCash</span>
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" class="btn-check" name="payment_method" 
                                   id="paymaya" value="paymaya" required>
                            <label for="paymaya" class="payment-label">
                                <img src="assets/imgs/payment/paymaya-logo.png" alt="PayMaya">
                                <span>PayMaya</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- QR Code Sections - Hidden by default -->
                <div class="qr-sections" style="display: none;">
                    <div id="gcash-qr" class="qr-section">
                        <div class="text-center py-3">
                            <img src="assets/imgs/payment/gcash-qr.png" alt="GCash QR" class="qr-code">
                            <p class="mb-1 mt-2">Account Name: KENNETH ANDRE LAZO</p>
                            <p class="mb-0">Account Number: 0927-873-9654</p>
                        </div>
                    </div>

                    <div id="paymaya-qr" class="qr-section">
                        <div class="text-center py-3">
                            <img src="assets/imgs/payment/paymaya-qr.png" alt="PayMaya QR" class="qr-code">
                            <p class="mb-1 mt-2">Account Name: KENNETH ANDRE LAZO</p>
                            <p class="mb-0">Account Number: 0927-873-9654</p>
                        </div>
                    </div>
                </div>

                <!-- Reference Number -->
                <div class="mb-3">
                    <label class="form-label">Reference Number</label>
                    <input type="text" class="form-control" name="reference_number" 
                           required pattern="[A-Za-z0-9-]{6,}"
                           placeholder="Enter payment reference number">
                    <div class="form-text">Please enter the reference number from your payment receipt</div>
                </div>

                <!-- Action Buttons -->
                <button type="submit" name="submit_payment" class="btn btn-primary w-100 mb-2">
                    Submit Payment for Verification
                </button>
                <button type="button" class="btn btn-light w-100" onclick="window.history.back()">
                    Cancel
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const qrSections = document.querySelector('.qr-sections');
    const gcashQR = document.getElementById('gcash-qr');
    const paymayaQR = document.getElementById('paymaya-qr');
    const form = document.querySelector('form');
    const referenceInput = document.getElementById('reference_number');

    // Hide all QR sections
    function hideAllQRSections() {
        qrSections.style.display = 'none';
        [gcashQR, paymayaQR].forEach(section => {
            section.style.display = 'none';
            section.classList.remove('active');
        });
    }

    // Show selected QR section with animation
    function showQRSection(section) {
        if (!section) return;
        
        qrSections.style.display = 'block';
        section.style.display = 'block';
        
        // Trigger reflow for animation
        section.offsetHeight;
        section.classList.add('active');
    }

    // Handle payment method selection
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all QR sections first
            hideAllQRSections();

            // Show selected QR section
            const selectedSection = this.value === 'gcash' ? gcashQR : 
                                  this.value === 'paymaya' ? paymayaQR : 
                                  null;
            showQRSection(selectedSection);
        });
    });

    // Form validation
    form.addEventListener('submit', function(event) {
        // Check if form is valid
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Show validation feedback
            const invalidFields = form.querySelectorAll(':invalid');
            if (invalidFields.length > 0) {
                invalidFields[0].focus();
            }
        }
        
        this.classList.add('was-validated');
    });

    // Reference number validation
    if (referenceInput) {
        referenceInput.addEventListener('input', function() {
            const isValid = this.checkValidity();
            this.classList.toggle('is-valid', isValid);
            this.classList.toggle('is-invalid', !isValid && this.value.length > 0);
        });
    }

    // Clean up function
    function cleanup() {
        // Reset form state
        form.classList.remove('was-validated');
        hideAllQRSections();
        
        // Clear any selected payment method
        paymentMethods.forEach(method => {
            method.checked = false;
        });
        
        // Clear reference number
        if (referenceInput) {
            referenceInput.value = '';
            referenceInput.classList.remove('is-valid', 'is-invalid');
        }
    }

    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Optionally pause any animations or operations
        }
    });

    // Handle page unload
    window.addEventListener('beforeunload', cleanup);
});
</script>

<?php include('includes/footer.php'); ?>