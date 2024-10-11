<!-- Navbar -->
 <link rel="stylesheet" href=".\assets\css\navbar.css">
<nav class="bottom-nav">
    <div class="container">
        <div class="nav-content">
            <div class="nav-item">
                <a href="goals-page.php" <?php echo ($page_title == 'Goals · IT-PID') ? 'class="active"' : ''; ?>>
                    <i class="bi bi-bullseye"></i>
                    <span>Goals</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="reports-page.php" <?php echo ($page_title == 'Reports · IT-PID') ? 'class="active"' : ''; ?>>
                    <i class="bi bi-clipboard2-data"></i>
                    <span>Reports</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="create-page.php" <?php echo ($page_title == 'Create · IT-PID') ? 'class="active"' : ''; ?>>
                    <div class="create-btn">
                        <i class="bi bi-plus"></i>
                    </div>
                </a>
            </div>
            <div class="nav-item">
                <a href="learn-page.php" <?php echo ($page_title == 'Learn · IT-PID') ? 'class="active"' : ''; ?>>
                    <i class="bi bi-lightbulb"></i>
                    <span>Learn</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="settings-page.php" <?php echo ($page_title == 'Settings · IT-PID') ? 'class="active"' : ''; ?>>
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
    </div>
</nav>