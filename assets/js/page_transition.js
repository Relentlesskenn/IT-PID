
// Add this script at the end of your body tag
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a:not([target="_blank"])');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.add('page-exit');
            setTimeout(() => {
                window.location = this.href;
            }, 300); // Match this to your animation duration
        });
    });
});