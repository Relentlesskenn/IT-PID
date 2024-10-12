// Function to show alerts with a fade-in effect
function showAlerts() {
    const alerts = document.querySelectorAll('.custom-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('show');
        }, 100);
    });
}

// Function to handle closing alerts
function setupAlertClosing() {
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('custom-alert-close')) {
            const alert = e.target.closest('.custom-alert');
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }
    });
}

// Call these functions when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    showAlerts();
    setupAlertClosing();
});