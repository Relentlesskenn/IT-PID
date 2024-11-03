<?php
$page_title = "IT-PID · Smart Budgeting for Modern Life";
include('includes/header.php');

$meta_description = "Transform your financial future with IT-PID - The smart budgeting companion for modern life. Track expenses, set goals, and achieve financial freedom.";
$meta_keywords = "personal finance, budgeting app, expense tracker, financial goals, money management, savings tracker";
?>

<link rel="stylesheet" href="./assets/css/landing_page.css">

<!-- Main Content -->
<main>
<body class="graphs-page">
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="logo-container fade-up">
                    <h1 class="logo-text">IT-PID</h1>
                    <p class="lead">Your personal finance companion for smarter budgeting and better living</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="registration-page-1.php" class="btn btn-hero btn-primary-gradient">Get Started</a>
                        <a href="login-page.php" class="btn btn-hero btn-outline-light">Log In</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="text-center mb-5 fade-up">
                <h2 class="display-4 fw-bold mb-3">Why Choose IT-PID?</h2>
                <p class="text-muted">Experience the future of personal finance management</p>
            </div>
            <div class="row g-4">
                <!-- Smart Tracking -->
                <div class="col-md-4">
                    <div class="feature-card fade-up">
                        <i class="bi bi-graph-up-arrow feature-icon"></i>
                        <h3>Smart Tracking</h3>
                        <p>Monitor your finances in real-time with intuitive dashboards and visual insights</p>
                    </div>
                </div>
                <!-- Goal Setting -->
                <div class="col-md-4">
                    <div class="feature-card fade-up">
                        <i class="bi bi-bullseye feature-icon"></i>
                        <h3>Goal Setting</h3>
                        <p>Create and track financial goals with personalized milestones and progress tracking</p>
                    </div>
                </div>
                <!-- Financial Learning -->
                <div class="col-md-4">
                    <div class="feature-card fade-up">
                        <i class="bi bi-lightbulb feature-icon"></i>
                        <h3>Financial Learning</h3>
                        <p>Access curated financial tips and resources to boost your money management skills</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <div class="text-center mb-5 fade-up">
                <h2 class="display-4 fw-bold mb-3">How It Works</h2>
                <p class="text-muted">Get started in four simple steps</p>
            </div>
            <div class="row g-4">
                <!-- Step 1 -->
                <div class="col-md-3">
                    <div class="step-card fade-up">
                        <div class="step-number"><span>1</span></div>
                        <h4>Create Account</h4>
                        <p>Quick signup process with easy verification</p>
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="col-md-3">
                    <div class="step-card fade-up">
                        <div class="step-number"><span>2</span></div>
                        <h4>Set Budget</h4>
                        <p>Define custom categories and spending limits</p>
                    </div>
                </div>
                <!-- Step 3 -->
                <div class="col-md-3">
                    <div class="step-card fade-up">
                        <div class="step-number"><span>3</span></div>
                        <h4>Track Expenses</h4>
                        <p>Log transactions and monitor spending</p>
                    </div>
                </div>
                <!-- Step 4 -->
                <div class="col-md-3">
                    <div class="step-card fade-up">
                        <div class="step-number"><span>4</span></div>
                        <h4>Monitor Progress</h4>
                        <p>View insights and achieve your goals</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center fade-up">
                    <h2>Start Your Financial Journey Today</h2>
                    <p class="mb-5">Join thousands of users who are taking control of their finances and building a better future</p>
                    <div class="cta-buttons">
                        <a href="registration-page-1.php" class="btn btn-hero btn-light btn-lg">Create Free Account</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Preloader -->
<div class="preloader">
    <div class="spinner"></div>
</div>

<!-- Scroll Animations & Preloader Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preloader handler
    const preloader = document.querySelector('.preloader');
    window.addEventListener('load', () => {
        preloader.classList.add('fade-out');
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500);
    });

    // Intersection Observer for fade animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Stagger child animations if present
                const animatedChildren = entry.target.querySelectorAll('.feature-card, .step-card');
                if (animatedChildren.length) {
                    animatedChildren.forEach((child, index) => {
                        setTimeout(() => {
                            child.classList.add('visible');
                        }, index * 100);
                    });
                }
            }
        });
    }, observerOptions);

    // Observe all fade-up elements
    document.querySelectorAll('.fade-up').forEach(element => {
        observer.observe(element);
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId !== '#') {
                document.querySelector(targetId).scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero');
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        if (heroSection) {
            heroSection.style.backgroundPositionY = scrolled * 0.5 + 'px';
        }
    });
});
</script>

<!-- Additional CSS for preloader and animations -->
<style>
/* Preloader styles */
.preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--midnight-purple);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.5s ease-in-out;
}

.preloader.fade-out {
    opacity: 0;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid var(--purple-light);
    border-top-color: var(--purple-accent);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Staggered animation for cards */
.feature-card,
.step-card {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.feature-card.visible,
.step-card.visible {
    opacity: 1;
    transform: translateY(0);
}

/* Hover effects for interactive elements */
.btn-hero {
    position: relative;
    overflow: hidden;
}

.btn-hero::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.btn-hero:hover::after {
    width: 300px;
    height: 300px;
}

/* Enhanced accessibility */
@media (prefers-reduced-motion: reduce) {
    .feature-card,
    .step-card,
    .fade-up,
    .btn-hero::after {
        transition: none;
    }
    
    .spinner {
        animation: none;
    }
}
</style>

<?php include('includes/footer.php'); ?>