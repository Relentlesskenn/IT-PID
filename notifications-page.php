<?php
$page_title = "Notifications";
include('_dbconnect.php');
include('authentication.php');
include('includes/header.php');

$userId = $_SESSION['auth_user']['user_id'];

// Handle notification deletion
if (isset($_POST['delete_notification'])) {
    $notificationId = mysqli_real_escape_string($conn, $_POST['notification_id']);
    $deleteSql = "DELETE FROM notifications WHERE id = '$notificationId' AND user_id = '$userId'";
    mysqli_query($conn, $deleteSql);
}

// Handle clearing all notifications
if (isset($_POST['clear_all_notifications'])) {
    $clearAllSql = "DELETE FROM notifications WHERE user_id = '$userId'";
    mysqli_query($conn, $clearAllSql);
}

// Fetch notifications
$sql = "SELECT * FROM notifications WHERE user_id = '$userId' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Mark all notifications as read
$updateSql = "UPDATE notifications SET is_read = TRUE WHERE user_id = '$userId'";
mysqli_query($conn, $updateSql);

// Function to determine the icon and title based on the notification type
function getNotificationDetails($type) {
    switch ($type) {
        case 'budget_alert':
            return [
                'icon' => 'bi-exclamation-triangle-fill text-warning',
                'title' => 'Budget Alert'
            ];
        case 'budget':
            return [
                'icon' => 'bi-piggy-bank text-primary',
                'title' => 'Budget Notification'
            ];
        case 'income':
            return [
                'icon' => 'bi-cash-coin text-success',
                'title' => 'Income Notification'
            ];
        case 'expense':
            return [
                'icon' => 'bi-credit-card text-danger',
                'title' => 'Expense Notification'
            ];
        default:
            return [
                'icon' => 'bi-bell-fill text-secondary',
                'title' => 'Notification'
            ];
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="dashboard-page.php" class="btn btn-outline-custom btn-sm">
                            <i class="bi bi-arrow-left"></i> Dashboard
                        </a>
                        <h1 class="h4 mb-0">Notifications</h1>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="clear_all_notifications" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash3"></i> Clear All
                                </button>
                            </form>
                        <?php else: ?>
                            <div></div> <!-- Empty div for layout balance -->
                        <?php endif; ?>
                    </div>

                    <!-- Notifications List -->
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <div class="list-group mb-4">
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                $notificationDetails = getNotificationDetails($row['type']);
                            ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h5 class="mb-1">
                                            <i class="bi <?php echo $notificationDetails['icon']; ?> me-2"></i>
                                            <?php echo $notificationDetails['title']; ?>
                                        </h5>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="notification_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <p class="mb-1 mt-2"><?php echo $row['message']; ?></p>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></small>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i> No notifications found.
                        </div>
                    <?php endif; ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php') ?>