<link rel="stylesheet" href=".\assets\css\navbar.css">
<style>
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        background-color: white;
        box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
    }
    .nav-content {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 3px 0;
    }
    .nav-item {
        text-align: center;
    }
    .nav-item a {
        color: black;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 0.8rem;
    }
    body {
        padding-bottom: 80px; /* Adjust this value based on the height of your navbar */
    }
</style>

<!-- Navbar -->
<nav class="bottom-nav">
    <div class="container">
        <div class="nav-content">
            <div class="nav-item">
                <a href="goals-page.php">
                    <i class="bi bi-bullseye fs-3"></i>
                    <span>Goals</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="reports-page.php">
                    <i class="bi bi-clipboard2-data fs-3"></i>
                    <span>Reports</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="btn btn-primary" href="create-page.php">
                    <i class="bi bi-plus fs-1" style="color: white;"></i>
                </a>
            </div>
            <div class="nav-item">
                <a href="learn-page.php">
                    <i class="bi bi-book fs-3"></i>
                    <span>Learn</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="settings-page.php">
                    <i class="bi bi-gear fs-3"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
    </div>
</nav>