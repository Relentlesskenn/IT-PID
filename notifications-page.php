<?php
$page_title = "Notifications";
include('_dbconnect.php');
include('authentication.php');
include('includes/header.php');

// Fetch notifications
$userId = $_SESSION['auth_user']['user_id'];
$sql = "SELECT * FROM notifications WHERE user_id = '$userId' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Mark all notifications as read
$updateSql = "UPDATE notifications SET is_read = TRUE WHERE user_id = '$userId'";
mysqli_query($conn, $updateSql);
?>

<div class="py-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard-page.php" class="btn btn-outline-dark">
                <- Dashboard
            </a>
        </div>

        <h2 class="mb-3">Notifications</h2>
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="list-group">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo ucfirst($row['type']); ?> Notification</h5>
                            <small><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></small>
                        </div>
                        <p class="mb-1"><?php echo $row['message']; ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No notifications found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include('includes/footer.php') ?>