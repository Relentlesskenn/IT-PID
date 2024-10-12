<?php
$page_title = "Dashboard · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');
include('includes/navbar.php');

// Functions
// Function to fetch and sum expenses for a specific month and year
function getExpensesTotal($userId, $month, $year) {
    global $conn;
    $sql = "SELECT SUM(e.amount) AS total_expenses FROM expenses e WHERE e.user_id = '$userId' AND MONTH(e.date) = '$month' AND YEAR(e.date) = '$year'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total_expenses'] ?? 0;
}

// Function to fetch and sum incomes for a specific month and year
function getIncomesTotal($userId, $month, $year) {
    global $conn;
    $sql = "SELECT SUM(i.amount) AS total_incomes FROM incomes i WHERE i.user_id = '$userId' AND MONTH(i.date) = '$month' AND YEAR(i.date) = '$year'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total_incomes'] ?? 0;
}

// Function to update or insert the monthly balance
function getOrUpdateMonthlyBalance($userId, $month, $year, $balance) {
    global $conn;
    $sql = "SELECT balance FROM balances WHERE user_id = '$userId' AND month = '$month' AND year = '$year'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $storedBalance = $row['balance'];
        
        if ($storedBalance != $balance) {
            $updateSql = "UPDATE balances SET balance = '$balance' WHERE user_id = '$userId' AND month = '$month' AND year = '$year'";
            mysqli_query($conn, $updateSql);
        }
    } else {
        $insertSql = "INSERT INTO balances (user_id, year, month, balance) VALUES ('$userId', '$year', '$month', '$balance')";
        mysqli_query($conn, $insertSql);
    }
    
    return $balance;
}

// Function to get the number of unread notifications for a user
function getUnreadNotificationsCount($userId) {
    global $conn;
    $sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = '$userId' AND is_read = 0";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['unread_count'];
}

// Function to add a notification
function addNotification($userId, $type, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $type, $message);
    $stmt->execute();
    $stmt->close();
}

// Function to check budget status and generate alerts
function checkBudgetStatus($userId, $month, $year) {
    global $conn;
    $alerts = array();

    $sql = "SELECT b.id, b.name, b.amount, SUM(e.amount) AS total_expenses 
            FROM budgets b 
            LEFT JOIN expenses e ON b.id = e.category_id AND MONTH(e.date) = '$month' AND YEAR(e.date) = '$year'
            WHERE b.user_id = '$userId' AND b.month = '$year-$month'
            GROUP BY b.id, b.name, b.amount";
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        $budgetId = $row['id'];
        $budgetAmount = $row['amount'];
        $totalExpenses = $row['total_expenses'] ?? 0;
        $percentageUsed = ($totalExpenses / $budgetAmount) * 100;

        $alertType = '';
        $alertMessage = '';

        if ($percentageUsed > 100) {
            $alertType = 'exceed';
            $alertMessage = "You have exceeded your budget for {$row['name']}!";
        } elseif ($percentageUsed == 100) {
            $alertType = '100_percent';
            $alertMessage = "You have reached 100% of your budget for {$row['name']}.";
        } elseif ($percentageUsed >= 90) {
            $alertType = '90_percent';
            $alertMessage = "You have reached 90% of your budget for {$row['name']}.";
        } elseif ($percentageUsed >= 70) {
            $alertType = '70_percent';
            $alertMessage = "You have reached 70% of your budget for {$row['name']}.";
        }

        if ($alertType && !isAlertShown($userId, $budgetId, $alertType)) {
            $alerts[] = array(
                'message' => $alertMessage,
                'type' => $alertType == 'exceed' ? 'danger' : 
                         ($alertType == '100_percent' ? 'danger' : 
                         ($alertType == '90_percent' ? 'warning' : 'info')),
                'budgetId' => $budgetId,
                'alertType' => $alertType
            );
            
            // Add notification
            addNotification($userId, 'budget_alert', $alertMessage);
        }
    }

    return $alerts;
}

// Function to check if an alert has been shown
function isAlertShown($userId, $budgetId, $alertType) {
    global $conn;
    $sql = "SELECT * FROM budget_alerts WHERE user_id = '$userId' AND budget_id = '$budgetId' AND alert_type = '$alertType'";
    $result = mysqli_query($conn, $sql);
    return mysqli_num_rows($result) > 0;
}

// Function to mark an alert as shown
function markAlertAsShown($userId, $budgetId, $alertType) {
    global $conn;
    $sql = "INSERT INTO budget_alerts (user_id, budget_id, alert_type) VALUES ('$userId', '$budgetId', '$alertType')";
    mysqli_query($conn, $sql);
}

// Get current month, year, and set default year
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$defaultYear = date('Y');

$userId = $_SESSION['auth_user']['user_id'];
$totalExpenses = getExpensesTotal($userId, $currentMonth, $currentYear);
$totalIncomes = getIncomesTotal($userId, $currentMonth, $currentYear);
$balance = $totalIncomes - $totalExpenses;

// Store or update the monthly balance
$balance = getOrUpdateMonthlyBalance($userId, $currentMonth, $currentYear, $balance);

// Get the number of unread notifications
$unreadNotificationsCount = getUnreadNotificationsCount($userId);

// Check budget status and get alerts
$budgetAlerts = checkBudgetStatus($userId, $currentMonth, $currentYear);

?>

<link rel="stylesheet" href=".\assets\css\dashboard.css">

<!-- HTML content -->
<div class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <span style="font-size: 1.1rem;">Hello, <?= $_SESSION['auth_user']['username']?>!</span>
            <a class="btn btn-custom-primary btn-sm position-relative" href="notifications-page.php">
                <i class="bi bi-bell-fill"></i>
                <?php if ($unreadNotificationsCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $unreadNotificationsCount ?>
                    <span class="visually-hidden">unread notifications</span>
                </span>
                <?php endif; ?>
            </a>
        </div>
        
        <!--Expenses, Incomes, and Balance-->
        <div class="card my-4 card-custom">
            <div class="p-2">
                <div class="finance-summary">
                    <div class="finance-item">
                        <div class="finance-label">Income</div>
                        <div class="finance-value">₱<?= number_format($totalIncomes, 2) ?></div>
                    </div>
                    <div class="finance-item">
                        <div class="finance-label">Expenses</div>
                        <div class="finance-value">₱<?= number_format($totalExpenses, 2) ?></div>
                    </div>
                    <div class="finance-item">
                        <div class="finance-label">Balance</div>
                        <div class="finance-value">₱<?= number_format($balance, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Month and Year selection -->
        <div class="mb-4">
            <form action="" method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label for="month" class="col-form-label" style="font-size: 0.95rem;">Month:</label>
                </div>
                <div class="col-auto">
                    <select name="month" id="month" class="form-select">
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $monthName = date('F', mktime(0, 0, 0, $i, 1));
                            $selected = ($i == $currentMonth) ? 'selected' : '';
                            echo "<option value='{$i}' {$selected}>{$monthName}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label for="year" class="col-form-label" style="font-size: 0.95rem;">Year:</label>
                </div>
                <div class="col-auto">
                    <select name="year" id="year" class="form-select">
                        <?php
                        $startYear = 2024;
                        $endYear = date('Y') + 1; 
                        for ($i = $startYear; $i <= $endYear; $i++) {
                            $selected = ($i == $currentYear) ? 'selected' : '';
                            echo "<option value='{$i}' {$selected}>{$i}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-custom-primary" style="font-size: 0.95rem;">View</button>
                </div>
            </form>
        </div>
        
        <!-- Budget Cards -->
        <div class="row row-cols-2 row-cols-sm-2 row-cols-lg-4 g-4">

        <?php
        // Fetch budget data from the database and calculate the remaining balance
        $userId = $_SESSION['auth_user']['user_id'];
        $sql = "SELECT b.id, b.name, b.amount, b.month, b.color, SUM(e.amount) AS total_expenses 
                FROM budgets b 
                LEFT JOIN expenses e ON b.id = e.category_id AND MONTH(e.date) = '$currentMonth' AND YEAR(e.date) = '$currentYear'
                WHERE b.user_id = '$userId' AND b.month = '$currentYear-$currentMonth'
                GROUP BY b.id, b.name, b.amount, b.month, b.color";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $budgetId = $row['id'];
                $budgetName = $row['name'];
                $budgetAmount = $row['amount'];
                $monthCreated = $row['month'];
                $totalExpenses = $row['total_expenses'] ?? 0;
                $remainingBalance = $budgetAmount - $totalExpenses;
                $percentageUsed = ($totalExpenses / $budgetAmount) * 100;
        ?>

                <!-- Budget Card Content -->
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="card-title mb-0" style="font-size: 1rem; font-weight: bold;">
                                    <!-- Add the colored badge here -->
                                    <span class="badge rounded-pill-custom" style="background-color: <?= $row['color'] ?>;">&nbsp;</span>
                                    <?= $budgetName ?>
                                </h6>
                            </div>
                            <div class="budget-info">
                                <p class="card-text mb-0" style="font-size: 0.85rem; line-height: 1.6;">Budget - ₱<?= number_format($budgetAmount, 2) ?></p>
                                <p class="card-text mb-0" style="font-size: 0.85rem; line-height: 1.6;">Spent - ₱<?= number_format($totalExpenses, 2) ?></p>
                                <p class="card-text mb-1" style="font-size: 0.85rem; line-height: 1.6;">Remaining - ₱<?= number_format($remainingBalance, 2) ?></p>
                            </div>
                            <div class="progress mt-auto position-relative">
                                <div class="progress-bar <?php if ($percentageUsed >= 90) { echo 'bg-custom-danger'; } elseif ($percentageUsed >= 70) { echo 'bg-warning'; } else { echo 'bg-success'; } ?>" 
                                    role="progressbar" 
                                    style="width: <?= $percentageUsed ?>%;" 
                                    aria-valuenow="<?= $percentageUsed ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                                <span class="position-absolute top-50 start-50 translate-middle <?php echo ($percentageUsed >= 90) ? 'text-white' : 'text-dark'; ?>" style="font-size: 0.8rem; font-weight: bold;">
                                <?= number_format($percentageUsed, 1) ?>%
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
        <?php
            }
        } else {
        ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="card-text text-center">No budgets found for the selected date.</p>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
        </div>
    </div>

    <!-- Toast container for budget alerts -->
    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1050">
        <div id="budgetAlertToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Budget Alert</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>
</div>

<script>
// Function to show budget alert toasts
function showBudgetAlerts(alerts) {
    const toastContainer = document.getElementById('budgetAlertToast');
    const toast = new bootstrap.Toast(toastContainer);

    function showNextAlert(index) {
        if (index >= alerts.length) return;

        const alert = alerts[index];
        toastContainer.querySelector('.toast-body').textContent = alert.message;
        toastContainer.classList.remove('bg-info', 'bg-warning', 'bg-danger');
        toastContainer.classList.add(`bg-${alert.type}`, 'text-white');
        toast.show();

        // Mark the alert as shown
        fetch('mark_alert_shown.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `userId=<?php echo $userId; ?>&budgetId=${alert.budgetId}&alertType=${alert.alertType}`
        });

        // Wait for the toast to hide before showing the next one
        toastContainer.addEventListener('hidden.bs.toast', () => {
            setTimeout(() => {
                showNextAlert(index + 1);
            }, 500);
        }, { once: true });
    }

    showNextAlert(0);
}

// Show budget alerts when the page loads
document.addEventListener('DOMContentLoaded', () => {
    const budgetAlerts = <?php echo json_encode($budgetAlerts); ?>;
    showBudgetAlerts(budgetAlerts);
});
</script>

<?php include('includes/footer.php') ?>