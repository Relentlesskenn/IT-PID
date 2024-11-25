<?php
require_once('_dbconnect.php');
require_once('includes/authentication.php');

// Get current user ID
$userId = $_SESSION['auth_user']['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Budget amount update handler
    if (isset($_POST['budget_id']) && isset($_POST['new_amount'])) {
        try {
            $budgetId = filter_input(INPUT_POST, 'budget_id', FILTER_VALIDATE_INT);
            $newAmount = filter_input(INPUT_POST, 'new_amount', FILTER_VALIDATE_FLOAT);
            $userId = $_SESSION['auth_user']['user_id'];

            $stmt = $conn->prepare("UPDATE budgets SET amount = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $newAmount, $budgetId, $userId);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to update budget');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Transaction retrieval handler
    if (isset($_POST['action']) && $_POST['action'] === 'get_transactions') {
        try {
            $budgetId = filter_input(INPUT_POST, 'budget_id', FILTER_VALIDATE_INT);
            $userId = $_SESSION['auth_user']['user_id'];
            
            if (!$budgetId) {
                throw new Exception('Invalid budget ID');
            }

            // Verify the budget belongs to the user
            $stmt = $conn->prepare("SELECT id FROM budgets WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $budgetId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Unauthorized');
            }

            // Get recent transactions
            $transactions = getRecentTransactions($conn, $budgetId);
            $transactionsArray = [];
            while ($row = $transactions->fetch_assoc()) {
                $transactionsArray[] = $row;
            }

            echo json_encode([
                'success' => true,
                'transactions' => $transactionsArray
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Budget deletion handler
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        try {
            $budgetId = filter_input(INPUT_POST, 'budget_id', FILTER_VALIDATE_INT);
            $userId = $_SESSION['auth_user']['user_id'];

            if (!$budgetId) {
                throw new Exception('Invalid budget ID');
            }

            $conn->begin_transaction();

            try {
                // Get budget name before deletion
                $stmt = $conn->prepare("SELECT name FROM budgets WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $budgetId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $budgetData = $result->fetch_assoc();
                
                if (!$budgetData) {
                    throw new Exception('Budget not found or unauthorized');
                }
                
                $budgetName = $budgetData['name'];

                // Delete associated expenses
                $stmt = $conn->prepare("DELETE FROM expenses WHERE category_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $budgetId, $userId);
                $stmt->execute();

                // Delete the budget
                $stmt = $conn->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $budgetId, $userId);
                $stmt->execute();

                // Add notification with budget name
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'budget', ?)");
                $message = "Budget '{$budgetName}' has been successfully deleted";
                $stmt->bind_param("is", $userId, $message);
                $stmt->execute();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => "Budget '{$budgetName}' deleted successfully"]);

            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

$page_title = "Dashboard · IT-PID";
include('includes/header.php');
include('includes/navbar.php');

// Check if user is subscribed
require_once('includes/SubscriptionHelper.php');
$subscriptionHelper = new SubscriptionHelper($conn);
$hasActiveSubscription = $subscriptionHelper->hasActiveSubscription($_SESSION['auth_user']['user_id']);

// Check subscription status
$subscriptionHelper = new SubscriptionHelper($conn);
$hasActiveSubscription = $subscriptionHelper->hasActiveSubscription($userId);

// Functions
// Function to get the total expenses for a specific month and year
function getExpensesTotal($userId, $month, $year) {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(e.amount) AS total_expenses FROM expenses e WHERE e.user_id = ? AND MONTH(e.date) = ? AND YEAR(e.date) = ?");
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total_expenses'] ?? 0;
}

// Function to fetch and sum incomes for a specific month and year
function getIncomesTotal($userId, $month, $year) {
    global $conn;
    $stmt = $conn->prepare("SELECT SUM(i.amount) AS total_incomes FROM incomes i WHERE i.user_id = ? AND MONTH(i.date) = ? AND YEAR(i.date) = ?");
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total_incomes'] ?? 0;
}

// Function to update or insert the monthly balance
function getOrUpdateMonthlyBalance($userId, $month, $year, $balance) {
    global $conn;
    $stmt = $conn->prepare("SELECT balance FROM balances WHERE user_id = ? AND month = ? AND year = ?");
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storedBalance = $row['balance'];
        
        if ($storedBalance != $balance) {
            $updateStmt = $conn->prepare("UPDATE balances SET balance = ? WHERE user_id = ? AND month = ? AND year = ?");
            $updateStmt->bind_param("diii", $balance, $userId, $month, $year);
            $updateStmt->execute();
        }
    } else {
        $insertStmt = $conn->prepare("INSERT INTO balances (user_id, year, month, balance) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("iiid", $userId, $year, $month, $balance);
        $insertStmt->execute();
    }
    
    return $balance;
}

// Function to get the number of unread notifications for a user
function getUnreadNotificationsCount($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['unread_count'];
}

// Function to add a notification
function addNotification($userId, $type, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $type, $message);
    $stmt->execute();
}

// Function to check budget status and generate alerts
function checkBudgetStatus($userId, $month, $year) {
    global $conn;
    $alerts = array();

    $stmt = $conn->prepare("SELECT b.id, b.name, b.amount, SUM(e.amount) AS total_expenses 
            FROM budgets b 
            LEFT JOIN expenses e ON b.id = e.category_id AND MONTH(e.date) = ? AND YEAR(e.date) = ?
            WHERE b.user_id = ? AND b.month = ?
            GROUP BY b.id, b.name, b.amount");
    $yearMonth = "$year-$month";
    $stmt->bind_param("iisi", $month, $year, $userId, $yearMonth);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
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
            $alertMessage = "You have reached 100% of your budget for '{$row['name']}'";
        } elseif ($percentageUsed >= 90) {
            $alertType = '90_percent';
            $alertMessage = "You have reached 90% of your budget for '{$row['name']}'";
        } elseif ($percentageUsed >= 70) {
            $alertType = '70_percent';
            $alertMessage = "You have reached 70% of your budget for '{$row['name']}'";
        } elseif ($percentageUsed >= 50) {
            $alertType = '50_percent';
            $alertMessage = "You have reached 50% of your budget for '{$row['name']}'";
        }

        if ($alertType && !isAlertShown($userId, $budgetId, $alertType)) {
            $alerts[] = array(
                'message' => $alertMessage,
                'type' => $alertType == 'exceed' ? 'danger' : 
                         ($alertType == '100_percent' ? 'danger' : 
                         ($alertType == '90_percent' ? 'warning' : 
                         ($alertType == '70_percent' ? 'info' : 'info'))),
                'budgetId' => $budgetId,
                'alertType' => $alertType
            );
            
            addNotification($userId, 'budget_alert', $alertMessage);
        }
    }

    return $alerts;
}

// Function to check if an alert has been shown
function isAlertShown($userId, $budgetId, $alertType) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM budget_alerts WHERE user_id = ? AND budget_id = ? AND alert_type = ?");
    $stmt->bind_param("iis", $userId, $budgetId, $alertType);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to mark an alert as shown
function markAlertAsShown($userId, $budgetId, $alertType) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO budget_alerts (user_id, budget_id, alert_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $userId, $budgetId, $alertType);
    $stmt->execute();
}

function getRecentTransactions($conn, $budgetId, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT amount, comment, DATE_FORMAT(date, '%M %d, %Y') as formatted_date 
        FROM expenses 
        WHERE category_id = ? 
        ORDER BY date DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $budgetId, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Get current month, year, and set default year
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$defaultYear = intval(date('Y'));

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

<link rel="stylesheet" href="./assets/css/dashboard.css">

<!-- HTML content -->
<div class="pt-4 pb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <span style="font-size: 1.2rem;">Hello, <strong><?= htmlspecialchars($_SESSION['auth_user']['username']) ?></strong></span>
            <a id="notificationBtn" class="position-relative btn btn-link text-dark p-0" href="notifications-page.php">
                <i id="notificationIcon" class="bi <?= $unreadNotificationsCount > 0 ? 'bi-bell-fill' : 'bi-bell' ?> fs-5"></i>
                <?php if ($unreadNotificationsCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $unreadNotificationsCount ?>
                    <span class="visually-hidden">unread notifications</span>
                </span>
                <?php endif; ?>
            </a>
        </div>
        
        <!-- Ad Section -->
        <?php
        require_once 'includes/Advertisement.php';
        if (!$hasActiveSubscription) {
            echo Advertisement::render('banner', 'center');
        }
        ?>
        
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

        <!-- Month, Year selection, and Search -->
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
                            echo "<option value='" . $i . "' " . $selected . ">" . htmlspecialchars($monthName) . "</option>";
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
                        $endYear = intval(date('Y')) + 1; 
                        for ($i = $startYear; $i <= $endYear; $i++) {
                            $selected = ($i == $currentYear) ? 'selected' : '';
                            echo "<option value='" . $i . "' " . $selected . ">" . $i . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-view" style="font-size: 0.95rem;">View</button>
                </div>
                <!-- Search input inside the form, but only visible on larger screens -->
                <div class="col-lg-4 d-none d-lg-block">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchBudget" placeholder="Search budgets...">
                        <button type="button" class="btn btn-outline-secondary" id="resetSearch">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </form>
            <!-- Search input for smaller screens, outside the form -->
            <div class="col-12 d-lg-none mt-3">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchBudgetMobile" placeholder="Search budgets...">
                    <button type="button" class="btn btn-outline-secondary" id="resetSearchMobile">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Budget Cards -->
        <div class="row row-cols-2 row-cols-sm-2 row-cols-lg-4 g-3" id="budgetCardsContainer">
            <?php
            // Fetch budget data from the database and calculate the remaining balance
            $userId = $_SESSION['auth_user']['user_id'];
            
            // Modified query to strictly match the month-year format
            $stmt = $conn->prepare("SELECT b.id, b.name, b.amount, b.month, b.color, 
                    COALESCE(SUM(CASE 
                        WHEN MONTH(e.date) = ? AND YEAR(e.date) = ? 
                        THEN e.amount 
                        ELSE 0 
                    END), 0) AS total_expenses 
                    FROM budgets b 
                    LEFT JOIN expenses e ON b.id = e.category_id
                    WHERE b.user_id = ? 
                    AND b.month = ?
                    GROUP BY b.id, b.name, b.amount, b.month, b.color");
                    
            $yearMonth = sprintf('%04d-%02d', $currentYear, $currentMonth);
            $stmt->bind_param("iiss", $currentMonth, $currentYear, $userId, $yearMonth);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
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
                    <div class="card h-100" onclick="showBudgetDetails(this, <?= htmlspecialchars(json_encode([
                            'id' => $budgetId,
                            'name' => $budgetName,
                            'amount' => $budgetAmount,
                            'spent' => $totalExpenses,
                            'remaining' => $remainingBalance,
                            'period' => $monthCreated,
                            'color' => $row['color'],
                            'percentage' => $percentageUsed
                        ])) ?>)">
                            <div class="card-body d-flex flex-column p-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="card-title mb-0" style="font-size: 1rem; font-weight: bold;">
                                        <span class="badge rounded-pill-custom" style="background-color: <?= htmlspecialchars($row['color']) ?>; margin-bottom: 0.2rem;">&nbsp;</span>
                                        <?= htmlspecialchars($budgetName) ?>
                                    </h6>
                                </div>
                                <div class="budget-info mt-1 mb-2">
                                    <p class="card-text mb-0" style="font-size: 0.8rem; line-height: 1.6;">Budget - <strong>₱<?= number_format($budgetAmount, 2) ?></strong></p>
                                    <p class="card-text mb-0" style="font-size: 0.8rem; line-height: 1.6;">Spent - <strong>₱<?= number_format($totalExpenses, 2) ?></strong></p>
                                    <p class="card-text" style="font-size: 0.8rem; line-height: 1.6;">Balance - <strong>₱<?= number_format($remainingBalance, 2) ?></strong></p>
                                </div>
                                <div class="progress mt-auto position-relative" style="height: 0.4rem;">
                                    <div class="progress-bar <?php echo $percentageUsed >= 90 ? 'bg-custom-danger' : ($percentageUsed >= 70 ? 'bg-warning' : 'bg-success'); ?>" 
                                        role="progressbar" 
                                        style="width: <?= $percentageUsed ?>%;" 
                                        aria-valuenow="<?= $percentageUsed ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="col-12 no-results-message">
                        <div class="card">
                            <div class="card-body">
                                <p class="card-text text-center">No budgets found for the selected date.</p>
                            </div>
                        </div>
                    </div>';
            }
            ?>        
        </div>
    </div>

    <!-- Budget Details Modal -->
    <div class="modal fade" id="budgetDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <!-- Header with Category and Date -->
            <div class="modal-header flex-column border-0 bg-gradient-primary p-4">
                <button type="button" class="btn-close opacity-75" data-bs-dismiss="modal"></button>
                
                <div class="category-badge mb-2">
                    <span class="badge bg-white bg-opacity-10 rounded-pill px-3 py-2" id="budgetPeriod"></span> 
                </div>

                <div class="d-flex align-items-center">
                    <span class="badge rounded-circle me-2" id="categoryColor"></span>
                    <h3 class="modal-title text-white mb-0" id="budgetTitle"></h3>
                </div>
            </div>

            <!-- Budget Content -->
            <div class="modal-body p-4">
                <!-- Total Budget Amount -->
                <div class="budget-amount text-center mb-4">
                    <span class="text-muted small">Total Budget</span>
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <h3 class="mb-0 fw-bold" id="budgetAmount"></h3>
                        <button class="btn btn-link p-0 text-muted" onclick="toggleBudgetEdit()">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                    </div>

                    <!-- Edit Budget Form -->
                    <div id="budgetEditForm" class="d-none mt-3">
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" id="newBudgetAmount" class="form-control" step="0.01" min="0">
                            <button class="btn btn-primary px-3" onclick="updateBudget()">Save</button>
                            <button class="btn btn-outline-secondary" onclick="toggleBudgetEdit()">Cancel</button>
                        </div>
                    </div>
                </div>

                <!-- Progress Circle -->
                <div class="progress-circle text-center mb-4">
                    <div class="position-relative" style="width: 200px; height: 200px; margin: 0 auto;">
                        <svg class="w-100 h-100" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#f0f0f0" stroke-width="10"/>
                            <circle id="progressRing" cx="50" cy="50" r="45" fill="none" 
                                stroke="#433878" stroke-width="10" stroke-dasharray="282.74" 
                                stroke-linecap="round" transform="rotate(-90 50 50)"/>
                        </svg>
                        <div class="position-absolute top-50 start-50 translate-middle text-center">
                            <div class="h2 mb-0 fw-bold" id="progressText"></div>
                            <div class="text-muted small">of budget used</div>
                        </div>
                    </div>
                </div>

                <!-- Budget Stats -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-card spent">
                            <div class="d-flex align-items-center">
                                <div class="icon-circle me-3">
                                    <i class="bi bi-arrow-down"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Spent</div>
                                    <div class="stat-value" id="spentAmount"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card remaining">
                            <div class="d-flex align-items-center">
                                <div class="icon-circle me-3">
                                    <i class="bi bi-arrow-up"></i>
                                </div>
                                <div>
                                    <div class="stat-label">Remaining</div>
                                    <div class="stat-value" id="remainingBalance"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions Section -->
                <div class="recent-transactions mt-4">
                    <h6 class="d-flex align-items-center">
                        <i class="bi bi-clock-history me-2"></i>
                        Recent Transactions
                    </h6>
                    <div class="transactions-list" id="transactionsList">
                        <!-- Transactions will be loaded here -->
                    </div>
                </div>

                <!-- Delete Budget Button -->
                <div class="mt-4 d-flex justify-content-end">
                    <button type="button" class="btn btn-danger w-100" onclick="confirmDeleteBudget()">
                        <i class="bi bi-trash-fill me-2"></i>Delete Budget
                    </button>
                </div>

            </div>
        </div>
    </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBudgetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-danger">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Delete Budget
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this budget? This action cannot be undone.</p>
                    <p class="mb-0"><strong>Budget: </strong><span id="deleteBudgetName"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="deleteBudget()">
                        <i class="bi bi-trash-fill me-2"></i>Delete Budget
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast container for budget alerts -->
    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 11">
        <div id="budgetAlertToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Budget Alert</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>
</div>

<script>
// Show budget alerts when the page loads
function showBudgetAlerts(alerts) {
    const toastContainer = document.getElementById('budgetAlertToast');
    const toast = new bootstrap.Toast(toastContainer, {
        animation: true,
        autohide: true,
        delay: 5000
    });

    // Function to show the next alert
    function showNextAlert(index) {
        if (index >= alerts.length) return;

        const alert = alerts[index];
        const toastBody = toastContainer.querySelector('.toast-body');
        toastBody.textContent = alert.message;
        
        toastContainer.classList.remove('border-primary', 'border-warning', 'border-danger');
        
        switch (alert.type) {
            case 'info':
                toastContainer.classList.add('border-primary');
                break;
            case 'warning':
                toastContainer.classList.add('border-warning');
                break;
            case 'danger':
                toastContainer.classList.add('border-danger');
                break;
        }

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
            }, 400);
        }, { once: true });
    }

    showNextAlert(0);
}

let currentBudgetId = null;

// Utility function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(amount);
}

// Function to update progress ring
function updateProgressRing(percentage) {
    const ring = document.getElementById('progressRing');
    const radius = ring.r.baseVal.value;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percentage / 100 * circumference);
    
    ring.style.strokeDasharray = `${circumference} ${circumference}`;
    ring.style.strokeDashoffset = offset;
    
    // Set color based on percentage
    const progressColor = percentage >= 90 ? '#DC2626' : 
                         percentage >= 70 ? '#FBBF24' : 
                         '#16A34A';
    ring.style.stroke = progressColor;
    
    document.getElementById('progressText').textContent = `${percentage.toFixed(1)}%`;
}

// Function to fetch recent transactions
async function fetchRecentTransactions(budgetId) {
    try {
        const response = await fetch('dashboard-page.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_transactions&budget_id=${budgetId}`
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch transactions');
        }
        
        const data = await response.json();
        return data.transactions || [];
    } catch (error) {
        console.error('Error fetching transactions:', error);
        throw error;
    }
}

// Show budget details modal
async function showBudgetDetails(card, data) {
    currentBudgetId = data.id;
    const modal = new bootstrap.Modal(document.getElementById('budgetDetailsModal'));
    
    // Update basic content
    document.getElementById('categoryColor').style.backgroundColor = data.color;
    document.getElementById('budgetTitle').textContent = data.name;
    document.getElementById('budgetAmount').textContent = formatCurrency(data.amount);
    document.getElementById('spentAmount').textContent = formatCurrency(data.spent);
    document.getElementById('remainingBalance').textContent = formatCurrency(data.remaining);
    document.getElementById('budgetPeriod').textContent = new Date(data.period).toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
    });
    
    // Show loading state for transactions
    document.getElementById('transactionsList').innerHTML = `
        <div class="text-center p-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading transactions...</span>
            </div>
        </div>
    `;

    // Update progress ring and show modal
    updateProgressRing(data.percentage);
    modal.show();
    
    // Fetch and display recent transactions
    try {
        const transactions = await fetchRecentTransactions(data.id);
        const transactionsList = document.getElementById('transactionsList');
        
        if (transactions.length === 0) {
            transactionsList.innerHTML = `
                <div class="no-transactions">
                    <i class="bi bi-inbox me-2"></i>No recent transactions
                </div>
            `;
            return;
        }
        
        const transactionsHtml = transactions.map(transaction => `
            <div class="transaction-item">
                <div class="transaction-info">
                    <div class="d-flex justify-content-between">
                        <span class="transaction-amount">${formatCurrency(transaction.amount)}</span>
                        <span class="transaction-date">${transaction.formatted_date}</span>
                    </div>
                    ${transaction.comment ? `
                        <div class="transaction-comment">
                            <small>${transaction.comment}</small>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        transactionsList.innerHTML = transactionsHtml;
    } catch (error) {
        document.getElementById('transactionsList').innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Failed to load transactions. Please try again.
            </div>
        `;
    }
}

// Edit budget amount
function toggleBudgetEdit() {
    const form = document.getElementById('budgetEditForm');
    const currentAmount = document.getElementById('budgetAmount').textContent
        .replace('₱', '').replace(',', '');
    
    document.getElementById('newBudgetAmount').value = parseFloat(currentAmount);
    form.classList.toggle('d-none');
}

// Update budget amount
async function updateBudget() {
    const newAmount = document.getElementById('newBudgetAmount').value;
    const updateButton = document.querySelector('#budgetEditForm .btn-primary');
    
    // Disable button and show loading state
    updateButton.disabled = true;
    updateButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    
    try {
        const response = await fetch('dashboard-page.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `budget_id=${currentBudgetId}&new_amount=${newAmount}`
        });

        const data = await response.json();
        if (data.success) {
            document.getElementById('budgetAmount').textContent = formatCurrency(newAmount);
            toggleBudgetEdit();
            location.reload();
        } else {
            throw new Error(data.message || 'Failed to update budget');
        }
    } catch (error) {
        const toast = new bootstrap.Toast(document.getElementById('budgetAlertToast'));
        const toastBody = document.querySelector('#budgetAlertToast .toast-body');
        toastBody.textContent = error.message;
        document.getElementById('budgetAlertToast').classList.add('border-danger');
        toast.show();
    } finally {
        updateButton.disabled = false;
        updateButton.innerHTML = 'Save';
    }
}

// Budget deletion functions
function confirmDeleteBudget() {
    const budgetTitle = document.getElementById('budgetTitle').textContent;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteBudgetModal'));
    document.getElementById('deleteBudgetName').textContent = budgetTitle;
    
    bootstrap.Modal.getInstance(document.getElementById('budgetDetailsModal')).hide();
    deleteModal.show();
}

// Delete budget function
async function deleteBudget() {
    if (!currentBudgetId) return;

    // Show loading state and disable button
    const deleteButton = document.querySelector('#deleteBudgetModal .btn-danger');
    const originalContent = deleteButton.innerHTML;
    deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
    deleteButton.disabled = true;

    try {
        const response = await fetch('dashboard-page.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&budget_id=${currentBudgetId}`
        });

        const data = await response.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('deleteBudgetModal')).hide();
            
            const toast = new bootstrap.Toast(document.getElementById('budgetAlertToast'));
            const toastBody = document.querySelector('#budgetAlertToast .toast-body');
            toastBody.textContent = data.message || 'Budget deleted successfully.';
            document.getElementById('budgetAlertToast').classList.add('border-primary');
            toast.show();

            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to delete budget');
        }
    } catch (error) {
        const toast = new bootstrap.Toast(document.getElementById('budgetAlertToast'));
        const toastBody = document.querySelector('#budgetAlertToast .toast-body');
        toastBody.textContent = error.message;
        document.getElementById('budgetAlertToast').classList.add('border-danger');
        toast.show();
    } finally {
        deleteButton.innerHTML = originalContent;
        deleteButton.disabled = false;
    }
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    const budgetAlerts = <?php echo json_encode($budgetAlerts); ?>;
    showBudgetAlerts(budgetAlerts);

    const notificationBtn = document.getElementById('notificationBtn');
    const notificationIcon = document.getElementById('notificationIcon');
    const searchInput = document.getElementById('searchBudget');
    const searchInputMobile = document.getElementById('searchBudgetMobile');
    const resetButton = document.getElementById('resetSearch');
    const resetButtonMobile = document.getElementById('resetSearchMobile');
    const budgetCards = document.querySelectorAll('#budgetCardsContainer .col:not(.no-results-message)');
    
    // Notification button click handler
    notificationBtn.addEventListener('click', function(e) {
        e.preventDefault();
        notificationIcon.classList.add('shake-icon');
        setTimeout(() => {
            notificationIcon.classList.remove('shake-icon');
            window.location.href = this.href;
        }, 200);
    });

    // Search functionality
    function performSearch() {
        const searchTerm = (window.innerWidth >= 992 ? searchInput : searchInputMobile).value.toLowerCase();
        let visibleCards = 0;
        
        budgetCards.forEach(card => {
            const budgetName = card.querySelector('.card-title').textContent.toLowerCase();
            if (budgetName.includes(searchTerm)) {
                card.style.display = '';
                visibleCards++;
            } else {
                card.style.display = 'none';
            }
        });

        updateNoResultsMessage(visibleCards);
    }

    // Function to update the no results message when no budgets are found
    function updateNoResultsMessage(visibleCards) {
        let noResultsMessage = document.querySelector('#budgetCardsContainer .no-results-message');
        
        if (visibleCards === 0) {
            if (!noResultsMessage) {
                noResultsMessage = document.createElement('div');
                noResultsMessage.className = 'col-12 no-results-message';
                noResultsMessage.innerHTML = `
                    <div class="card">
                        <div class="card-body">
                            <p class="card-text text-center">No budgets found matching your search.</p>
                        </div>
                    </div>
                `;
                document.getElementById('budgetCardsContainer').appendChild(noResultsMessage);
            } else {
                noResultsMessage.style.display = '';
            }
        } else if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
        }
    }

    // Reset search input
    function resetSearch() {
        searchInput.value = '';
        searchInputMobile.value = '';
        budgetCards.forEach(card => card.style.display = '');
        updateNoResultsMessage(budgetCards.length);
        resetButton.style.display = 'none';
        resetButtonMobile.style.display = 'none';
    }

    // Event listeners for search functionality
    searchInput.addEventListener('input', function() {
        performSearch();
        resetButton.style.display = this.value ? 'block' : 'none';
    });

    // Reset search input on mobile
    searchInputMobile.addEventListener('input', function() {
        performSearch();
        resetButtonMobile.style.display = this.value ? 'block' : 'none';
    });

    // Reset search button click handler
    resetButton.addEventListener('click', resetSearch);
    resetButtonMobile.addEventListener('click', resetSearch);

    // Sync search inputs
    searchInput.addEventListener('input', function() {
        searchInputMobile.value = this.value;
    });

    searchInputMobile.addEventListener('input', function() {
        searchInput.value = this.value;
    });

    // Initial setup
    resetButton.style.display = 'none';
    resetButtonMobile.style.display = 'none';

    // Handle resize events
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            searchInputMobile.value = searchInput.value;
        } else {
            searchInput.value = searchInputMobile.value;
        }
    });
});
</script>

<?php include('includes/footer.php') ?>
