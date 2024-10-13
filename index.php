<?php
$page_title = "IT-PID · Budget Smart, Live Well";
include('includes/header.php');

$meta_description = "IT-PID helps you budget smarter and live better. Start your journey to financial wellness today!";
$meta_keywords = "budgeting, personal finance, financial planning, money management";
?>
<link rel="stylesheet" href="./assets/css/landing_page.css">
<link rel="stylesheet" href="./assets/css/page_transition.css">

<main class="main">
    <div class="logo-container">
        <h1 class="logo-text-upper">
            IT
        </h1>
        <h1 class="logo-text-bottom">
            PID
        </h1>
        <p class="tagline anim-typewriter">Budget Smart, Live Well</p>
    </div>
    <div class="cta-buttons">
        <a class="btn btn-custom-primary" href="registration-page-1.php">Create an Account</a>
        <a class="btn btn-light" href="login-page.php">Log in</a>
    </div>
</main>

<script src="./assets/js/page_transition.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tagline = document.querySelector('.tagline');
    const logoContainer = document.querySelector('.logo-container');
    
    // Calculate the total duration of the typewriter animation
    const fadeInDuration = 900; // 0.9s
    const typewriterDelay = 1500; // 1.5s
    const typewriterDuration = 4000; // 4s
    const totalDuration = fadeInDuration + typewriterDelay + typewriterDuration;
    
    // Set a timeout to add the zoom-in class after the animation is complete
    setTimeout(() => {
        logoContainer.classList.add('zoom-in');
    }, totalDuration);
});
</script>

<?php include('includes/footer.php'); ?>