document.addEventListener('DOMContentLoaded', function() {
    // Create loading indicator element
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading-indicator';
    loadingIndicator.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loadingIndicator);

    const links = document.querySelectorAll('a:not([target="_blank"])');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.add('page-exit');
            loadingIndicator.style.display = 'block'; // Show loading indicator
            setTimeout(() => {
                window.location = this.href;
            }, 300); // Match this to your animation duration
        });
    });

    // Hide loading indicator when page is fully loaded
    window.addEventListener('load', function() {
        loadingIndicator.style.display = 'none';
    });
});