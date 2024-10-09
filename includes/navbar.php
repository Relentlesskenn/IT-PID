<style>
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background-color: #ffffff;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }
    .nav-content {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 8px 0;
    }
    .nav-item {
        text-align: center;
        flex: 1;
        display: flex;
        justify-content: center;
    }
    .nav-item a {
        color: #5b5b5b;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 0.8rem;
        transition: all 0.3s ease;
    }
    .nav-item a:hover, .nav-item a.active {
        color: #7E60BF;
    }
    .nav-item i {
        font-size: 1.8rem;
        margin-bottom: 4px;
    }
    .nav-item span {
        display: block;
    }
    .create-btn {
        background-color: #433878;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        margin-bottom: 4px;
    }
    .create-btn:hover {
        background-color: #0056b3;
        transform: scale(1.05);
    }
    .create-btn i {
        color: white;
        font-size: 1.75rem;
    }
    body {
        padding-bottom: 80px; /* Adjust based on navbar height */
    }

    /* Responsive styles */
    @media (min-width: 576px) {
        .nav-item a {
            font-size: 0.75rem;
        }
    }

    @media (min-width: 768px) {
        .nav-content {
            max-width: 600px;
            margin: 0 auto;
        }
    }
</style>

<!-- Navbar -->
<nav class="bottom-nav">
    <div class="container">
        <div class="nav-content">
            <div class="nav-item">
                <a href="goals-page.php" <?php echo ($page_title == 'Goals') ? 'class="active"' : ''; ?>>
                    <i class="bi bi-bullseye"></i>
                    <span>Goals</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="reports-page.php" <?php echo ($page_title == 'Reports') ? 'class="active"' : ''; ?>>
                    <i class="bi bi-clipboard2-data"></i>
                    <span>Reports</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="create-page.php" <?php echo ($page_title == 'Create') ? 'class="active"' : ''; ?>>
                    <div class="create-btn">
                        <i class="bi bi-plus"></i>
                    </div>
                </a>
            </div>
            <div class="nav-item">
                <a href="learn-page.php" <?php echo ($page_title == 'Learn') ? 'class="active"' : ''; ?>>
                    <i class="bi bi-lightbulb"></i>
                    <span>Learn</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="settings-page.php" <?php echo ($page_title == 'Settings') ? 'class="active"' : ''; ?>>
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
    </div>
</nav>