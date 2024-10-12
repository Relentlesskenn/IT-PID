document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Create and append the scroll-to-top button if it doesn't exist
    let scrollToTopBtn = document.getElementById('scrollToTopBtn');
    if (!scrollToTopBtn) {
        scrollToTopBtn = document.createElement('button');
        scrollToTopBtn.id = 'scrollToTopBtn';
        scrollToTopBtn.title = 'Go to top';
        scrollToTopBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
        document.body.appendChild(scrollToTopBtn);
    }

    const navbar = document.querySelector('.bottom-nav');
    const isGraphsPage = document.body.classList.contains('graphs-page');

    // Function to update button visibility and position
    function updateScrollToTopBtn() {
        if (window.pageYOffset > 100) {
            scrollToTopBtn.style.display = 'block';
            if (!isGraphsPage && navbar) {
                // Position above navbar for non-graphs pages
                scrollToTopBtn.style.bottom = (navbar.offsetHeight + 10) + 'px';
            }
            // For graphs page, use the CSS-defined position (20px from bottom)
        } else {
            scrollToTopBtn.style.display = 'none';
        }
    }

    // Initial call and event listeners
    updateScrollToTopBtn();
    window.addEventListener('scroll', updateScrollToTopBtn);
    window.addEventListener('resize', updateScrollToTopBtn);

    // Scroll to top functionality
    scrollToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});