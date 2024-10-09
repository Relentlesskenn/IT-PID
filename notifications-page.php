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

// Fetch notifications
$userId = $_SESSION['auth_user']['user_id'];
$sql = "SELECT * FROM notifications WHERE user_id = '$userId' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Mark all notifications as read
$updateSql = "UPDATE notifications SET is_read = TRUE WHERE user_id = '$userId'";
mysqli_query($conn, $updateSql);

// Function to determine the alert class based on the message content
function getAlertClass($message) {
    if (strpos($message, 'exceeded') !== false) {
        return 'list-group-item-danger';
    } elseif (strpos($message, '100%') !== false) {
        return 'list-group-item-danger';
    } elseif (strpos($message, '90%') !== false) {
        return 'list-group-item-warning';
    } elseif (strpos($message, '70%') !== false) {
        return 'list-group-item-info';
    }
    return '';
}

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
                    <?php $alertClass = $row['type'] === 'budget_alert' ? getAlertClass($row['message']) : ''; ?>
                    <div class="list-group-item list-group-item-action <?php echo $alertClass; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <?php
                                if ($row['type'] === 'budget_alert') {
                                    echo 'Budget Alert';
                                } else {
                                    echo ucfirst(str_replace('_', ' ', $row['type'])) . ' Notification';
                                }
                                ?>
                            </h5>
                            <form method="POST">
                                <input type="hidden" name="notification_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_notification" class="btn btn-sm btn-danger"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                        <p class="mb-1"><?php echo $row['message']; ?></p>
                        <small><?php echo date('m/d/y H:i', strtotime($row['created_at'])); ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No notifications found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include('includes/footer.php') ?>