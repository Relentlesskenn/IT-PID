<?php
$page_title = "Notifications · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');

$userId = $_SESSION['auth_user']['user_id'];

// Handle notification deletion
if (isset($_POST['delete_notification'])) {
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
}

// Handle clearing all notifications
if (isset($_POST['clear_all_notifications'])) {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

// Fetch notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Mark all notifications as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// Function to determine the icon and title based on the notification type
function getNotificationDetails($type, $message) {
    $budgetAlerts = [
        '50%' => ['icon' => 'bi-exclamation-triangle-fill text-warning', 'title' => 'Budget Alert'],
        '70%' => ['icon' => 'bi-exclamation-triangle-fill text-warning', 'title' => 'Budget Alert'],
        '90%' => ['icon' => 'bi-exclamation-triangle-fill text-warning', 'title' => 'Budget Alert'],
        '100%' => ['icon' => 'bi-exclamation-triangle-fill text-danger', 'title' => 'Budget Alert'],
        'exceeded' => ['icon' => 'bi-exclamation-triangle-fill text-danger', 'title' => 'Budget Alert'],
    ];

    if ($type === 'budget_alert') {
        foreach ($budgetAlerts as $keyword => $details) {
            if (strpos($message, $keyword) !== false) {
                return $details;
            }
        }
    }
    
    $notificationTypes = [
        'budget' => ['icon' => 'bi-piggy-bank text-primary', 'title' => 'Budget Notification'],
        'income' => ['icon' => 'bi-cash-coin text-success', 'title' => 'Income Notification'],
        'expense' => ['icon' => 'bi-credit-card text-danger', 'title' => 'Expense Notification'],
    ];

    return $notificationTypes[$type] ?? ['icon' => 'bi-bell-fill text-secondary', 'title' => 'Notification'];
}
?>

<!-- HTML content -->
<body class="notifications-page">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="dashboard-page.php" class="btn btn-custom-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
                <h1 class="h4 mb-0">Notifications</h1>
                <?php if ($result->num_rows > 0): ?>
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
            <?php if ($result->num_rows > 0): ?>
                <div class="list-group mb-4">
                    <?php while ($row = $result->fetch_assoc()): 
                        $notificationDetails = getNotificationDetails($row['type'], $row['message']);
                    ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h5 class="mb-1">
                                    <i class="bi <?= htmlspecialchars($notificationDetails['icon']) ?> me-2"></i>
                                    <?= htmlspecialchars($notificationDetails['title']) ?>
                                </h5>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="notification_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                            <p class="mb-1 mt-2"><?= htmlspecialchars($row['message']) ?></p>
                            <small class="text-muted"><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></small>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-custom-info" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> No notifications found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php') ?>
</body>