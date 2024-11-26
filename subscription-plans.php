<?php
session_start();
include('_dbconnect.php');
include('includes/authentication.php');

$page_title = "Subscription Plans · IT-PID";
include('includes/header.php');

// Initialize subscription helper
require_once('includes/SubscriptionHelper.php');
$subscriptionHelper = new SubscriptionHelper($conn);

// Get current subscription if any
$currentSubscription = $subscriptionHelper->getCurrentSubscription($_SESSION['auth_user']['user_id']);

// Get all plans
$plans = $subscriptionHelper->getSubscriptionPlans();

// Handle subscription purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe'])) {
    $planId = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT);
    
    if ($subscriptionHelper->subscribeToPlan($_SESSION['auth_user']['user_id'], $planId)) {
        $_SESSION['message'] = "Subscription activated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error activating subscription. Please try again.";
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: subscription-plans.php");
    exit();
}
?>

<link rel="stylesheet" href="./assets/css/subscription.css">

<body class="graphs-page">
<div class="pt-4 pb-5">
    <div class="container">
        
        <!-- Back button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button onclick="goBack()" class="btn btn-custom-primary-rounded">
                <i class="bi bi-arrow-left me-2"></i>Back
            </button>
            <div class="ms-auto">
                <!-- If you want to add any right-aligned elements -->
            </div>
        </div>

        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-3">Upgrade Your Experience</h1>
            <p class="lead text-muted">Choose the plan that best fits your needs</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Current Subscription Status -->
        <?php if ($currentSubscription): ?>
        <div class="alert alert-info mb-4">
            <h5 class="alert-heading">
                <i class="bi bi-info-circle-fill me-2"></i>Current Subscription
            </h5>
            <p class="mb-0">
                You are currently subscribed to <strong><?php echo htmlspecialchars($currentSubscription['plan_name']); ?></strong>.
                Your subscription will expire on <?php echo date('F j, Y', strtotime($currentSubscription['end_date'])); ?>.
            </p>
        </div>
        <?php endif; ?>

        <!-- Subscription Plans -->
        <div class="row row-cols-1 row-cols-md-2 mb-3 text-center">
            <?php foreach ($plans as $plan): ?>
            <div class="col">
                <div class="card mb-4 rounded-3 shadow-sm">
                    <div class="card-header py-3">
                        <h4 class="my-0 fw-normal"><?php echo htmlspecialchars($plan['name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <h1 class="card-title pricing-card-title">
                            ₱<?php echo number_format($plan['price'], 2); ?>
                            <small class="text-muted fw-light">
                                /<?php echo $plan['duration_months'] === 1 ? 'month' : 'year'; ?>
                            </small>
                        </h1>
                        <ul class="list-unstyled mt-3 mb-4">
                            <?php 
                            $features = explode(',', $plan['features']);
                            foreach ($features as $feature):
                            ?>
                            <li>
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <?php echo htmlspecialchars($feature); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if (!$currentSubscription): ?>
                        <form method="post">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                            <button type="submit" name="subscribe" class="w-100 btn btn-lg btn-custom-primary">
                                Subscribe Now
                            </button>
                        </form>
                        <?php else: ?>
                        <button class="w-100 btn btn-lg btn-outline-secondary" disabled>
                            Currently Subscribed
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Features Comparison -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Features Comparison</h2>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Feature</th>
                                <th class="text-center">Free</th>
                                <th class="text-center">Premium</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Custom Budgets</td>
                                <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                <td class="text-center"><i class="bi bi-check-circle text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Number of Goals</td>
                                <td class="text-center">Up to 3</td>
                                <td class="text-center">Unlimited</td>
                            </tr>
                            <tr>
                                <td>PDF Reports</td>
                                <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                <td class="text-center"><i class="bi bi-check-circle text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Access to Financial Graphs</td>
                                <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                <td class="text-center"><i class="bi bi-check-circle text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Ad-Free Experience</td>
                                <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                <td class="text-center"><i class="bi bi-check-circle text-success"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- FAQs Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Frequently Asked Questions</h2>
                <div class="accordion" id="faqAccordion">
                    <!-- Billing Questions -->
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#faq1">
                                How will I be billed?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                You'll be billed once at the beginning of your subscription period. For monthly plans, 
                                you'll be billed every month on the same date. For yearly plans, you'll be billed 
                                once a year. All payments are processed securely through our payment gateway.
                            </div>
                        </div>
                    </div>

                    <!-- Cancellation Policy -->
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#faq2">
                                Can I cancel my subscription?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, you can cancel your subscription at any time. Your premium features will 
                                remain active until the end of your current billing period. After that, your 
                                account will revert to the free tier.
                            </div>
                        </div>
                    </div>

                    <!-- Feature Access -->
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#faq3">
                                What happens to my data if I downgrade?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Your data is always safe! If you downgrade to the free tier, you'll maintain access 
                                to all your existing data. However, some premium features will become unavailable, 
                                and if you exceed the free tier limits (e.g., more than 5 goals), you'll need to 
                                reduce them to continue making changes.
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#faq4">
                                What payment methods do you accept?
                            </button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We accept all major digital wallets. All payments 
                                are processed securely through our payment gateway partner.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Need Help Section -->
        <div class="text-center mt-5">
            <h3>Need Help?</h3>
            <p class="mb-4">Our support team is here to assist you with any questions about our subscription plans.</p>
            <a href="mailto:support@it-pid.com" class="btn btn-outline-primary">
                <i class="bi bi-envelope me-2"></i>Contact Support
            </a>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle subscription form submission with confirmation
    const subscriptionForms = document.querySelectorAll('form[name="subscribe"]');
    subscriptionForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to subscribe to this plan?')) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-info)');
    alerts.forEach(alert => {
        setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }, 5000);
    });

    // Back button navigation function
    window.goBack = function() {
        // Check if there's a previous page in the browser history
        if (document.referrer) {
            window.location.href = document.referrer;
        } else {
            // Fallback to dashboard if no previous page
            window.location.href = 'dashboard-page.php';
        }
    };

    // Handle keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Support Escape key for back navigation
        if (e.key === 'Escape') {
            goBack();
        }
    });

    // Initialize accordions with smooth transitions
    const accordionItems = document.querySelectorAll('.accordion-item');
    accordionItems.forEach(item => {
        const button = item.querySelector('.accordion-button');
        const collapse = item.querySelector('.accordion-collapse');
        
        if (button && collapse) {
            const bsCollapse = new bootstrap.Collapse(collapse, {
                toggle: false
            });

            button.addEventListener('click', function() {
                // Smooth scroll to the accordion item when opened
                if (collapse.classList.contains('collapsed')) {
                    item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }
    });

    // Handle subscription card hover effects
    const subscriptionCards = document.querySelectorAll('.card');
    subscriptionCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Form validation for subscription forms
    const validateSubscriptionForm = function(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        return isValid;
    };

    // Add form validation to subscription forms
    subscriptionForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateSubscriptionForm(this)) {
                e.preventDefault();
                showToast('Please fill in all required fields', 'danger');
            }
        });
    });

    // Toast notification function
    function showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `toast align-items-center border-${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Pause any animations or non-essential operations when page is not visible
            document.querySelectorAll('.animation').forEach(element => {
                element.style.animationPlayState = 'paused';
            });
        } else {
            // Resume animations when page becomes visible
            document.querySelectorAll('.animation').forEach(element => {
                element.style.animationPlayState = 'running';
            });
        }
    });

    // Cleanup function for page unload
    window.addEventListener('beforeunload', function() {
        // Dispose of all tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(element => {
            const tooltip = bootstrap.Tooltip.getInstance(element);
            if (tooltip) {
                tooltip.dispose();
            }
        });
    });
});
</script>