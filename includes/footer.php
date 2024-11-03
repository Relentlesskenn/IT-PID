<?php
/**
 * Main footer file for IT-PID Budget Tracking App
 * Contains closing tags, scripts, and common footer elements
 */
?>
    </main> <!-- Close mainContent -->

    <!-- Scroll to top button - Only show if not on graphs page -->
    <?php if (!isset($is_graphs_page)): ?>
        <button id="scrollToTopBtn" 
                class="btn btn-primary rounded-circle shadow-sm d-none" 
                title="Go to top"
                aria-label="Scroll to top">
            <i class="bi bi-arrow-up"></i>
        </button>
    <?php endif; ?>

    <!-- Toast container for notifications -->
    <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3">
        <div id="generalToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-info-circle me-2"></i>
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <!-- Core Scripts -->
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/smooth-scroll.js"></script>
    <script src="assets/js/alert-handler.js"></script>

    <!-- Page-specific scripts -->
    <?php if (isset($pageSpecificJS)): ?>
        <?php foreach ($pageSpecificJS as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hide loading indicator and show content
        const loadingIndicator = document.getElementById('pageLoadingIndicator');
        const mainContent = document.getElementById('mainContent');
        
        if (loadingIndicator && mainContent) {
            loadingIndicator.classList.add('hidden');
            mainContent.classList.add('content-loaded');
            // Remove the loading indicator from the DOM after transition
            setTimeout(() => {
                loadingIndicator.remove();
            }, 300);
        }

        // Initialize scroll to top button
        const scrollToTopBtn = document.getElementById('scrollToTopBtn');
        if (scrollToTopBtn) {
            // Show button after scrolling down 100px
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    scrollToTopBtn.classList.remove('d-none');
                    scrollToTopBtn.classList.add('d-flex');
                } else {
                    scrollToTopBtn.classList.remove('d-flex');
                    scrollToTopBtn.classList.add('d-none');
                }
            });

            // Smooth scroll to top
            scrollToTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Initialize all tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });

        // Handle session timeout
        let sessionTimeout;
        function resetSessionTimeout() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(function() {
                showSessionTimeoutWarning();
            }, 25 * 60 * 1000); // 25 minutes
        }

        // Reset timeout on user activity
        ['click', 'mousemove', 'keypress'].forEach(event => {
            document.addEventListener(event, resetSessionTimeout);
        });

        function showSessionTimeoutWarning() {
            const toast = new bootstrap.Toast(document.getElementById('generalToast'));
            document.querySelector('#generalToast .toast-body').textContent = 
                'Your session will expire soon. Please save your work.';
            toast.show();
        }

        // Start initial session timeout
        resetSessionTimeout();
    });
    </script>
</body>
</html>