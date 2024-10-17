<!-- Navbar -->
<link rel="stylesheet" href=".\assets\css\navbar.css">
<nav class="bottom-nav">
    <div class="container">
        <div class="nav-content">
            <!-- Goals button -->
            <div class="nav-item">
                <a href="goals-page.php" <?php echo ($page_title == 'Goals · IT-PID') ? 'class="active"' : ''; ?> onclick="jumpButton(event, this)">
                    <i class="bi bi-bullseye"></i>
                    <span>Goals</span>
                </a>
            </div>
            <!-- Reports button -->
            <div class="nav-item">
                <a href="reports-page.php" <?php echo ($page_title == 'Reports · IT-PID') ? 'class="active"' : ''; ?> onclick="jumpButton(event, this)">
                    <i class="bi bi-clipboard2-data"></i>
                    <span>Reports</span>
                </a>
            </div>
            <!-- Create button and home button for dashboard -->
            <div class="nav-item">
                <?php if ($page_title == 'Dashboard · IT-PID'): ?>
                    <!-- Create button for dashboard -->
                    <a href="create-page.php" onclick="jumpButton(event, this)">
                        <div class="create-btn">
                            <i class="bi bi-plus"></i>
                        </div>
                    </a>
                <?php else: ?>
                    <!-- Home button for other pages -->
                    <a href="dashboard-page.php" onclick="zoomRotateButton(event, this, 'bi-house-fill', 'bi-plus')">
                        <div class="create-btn">
                            <i class="bi bi-house-fill" style="font-size: 1.5rem;"></i>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
            <!-- Learn button -->
            <div class="nav-item">
                <a href="learn-page.php" <?php echo ($page_title == 'Learn · IT-PID') ? 'class="active"' : ''; ?> onclick="jumpButton(event, this)">
                    <i class="bi bi-lightbulb"></i>
                    <span>Learn</span>
                </a>
            </div>
            <!-- Settings button -->
            <div class="nav-item">
                <a href="settings-page.php" <?php echo ($page_title == 'Settings · IT-PID') ? 'class="active"' : ''; ?> onclick="jumpButton(event, this)">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
// Jump button animation
function jumpButton(event, element) {
    event.preventDefault(); // Prevent default link behavior
    
    const icon = element.querySelector('i');
    const createBtn = element.querySelector('.create-btn');
    
    if (createBtn) {
        createBtn.classList.add('jumping');
    } else if (icon) {
        icon.classList.add('jumping');
    }
    
    setTimeout(() => {
        if (createBtn) {
            createBtn.classList.remove('jumping');
        } else if (icon) {
            icon.classList.remove('jumping');
        }
        window.location.href = element.href;
    }, 200); // Adjust this timing to match your CSS animation duration
}

// Zoom and rotate button animation
function zoomRotateButton(event, element, fromIcon, toIcon) {
    event.preventDefault(); // Prevent default link behavior
    
    const icon = element.querySelector('i');
    const createBtn = element.querySelector('.create-btn');
    
    if (icon && createBtn) {
        createBtn.classList.add('zoom-out');
        icon.classList.add('rotate-out');
        
        setTimeout(() => {
            icon.classList.remove(fromIcon);
            icon.classList.add(toIcon);
            createBtn.classList.remove('zoom-out');
            createBtn.classList.add('zoom-in');
            icon.classList.remove('rotate-out');
            icon.classList.add('rotate-in');
        }, 150);
        
        setTimeout(() => {
            createBtn.classList.remove('zoom-in');
            icon.classList.remove('rotate-in');
            window.location.href = element.href;
        }, 300);
    }
}
</script>