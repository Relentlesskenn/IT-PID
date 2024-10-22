<?php
$page_title = "Dashboard · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');
include('includes/navbar.php');

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
            $alertMessage = "You have reached 100% of your budget for {$row['name']}";
        } elseif ($percentageUsed >= 90) {
            $alertType = '90_percent';
            $alertMessage = "You have reached 90% of your budget for {$row['name']}";
        } elseif ($percentageUsed >= 70) {
            $alertType = '70_percent';
            $alertMessage = "You have reached 70% of your budget for {$row['name']}";
        } elseif ($percentageUsed >= 50) {
            $alertType = '50_percent';
            $alertMessage = "You have reached 50% of your budget for {$row['name']}";
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
<div class="py-4">
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
        $stmt = $conn->prepare("SELECT b.id, b.name, b.amount, b.month, b.color, SUM(e.amount) AS total_expenses 
                FROM budgets b 
                LEFT JOIN expenses e ON b.id = e.category_id AND MONTH(e.date) = ? AND YEAR(e.date) = ?
                WHERE b.user_id = ? AND b.month = ?
                GROUP BY b.id, b.name, b.amount, b.month, b.color");
        $yearMonth = "$currentYear-$currentMonth";
        $stmt->bind_param("iisi", $currentMonth, $currentYear, $userId, $yearMonth);
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
                    <div class="card h-100">
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
            // Short delay before showing the next toast for smoother transition
            setTimeout(() => {
                showNextAlert(index + 1);
            }, 400);
        }, { once: true });
    }

    showNextAlert(0);
}

// Show budget alerts when the page loads
document.addEventListener('DOMContentLoaded', () => {
    const budgetAlerts = <?php echo json_encode($budgetAlerts); ?>;
    showBudgetAlerts(budgetAlerts);
});

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationIcon = document.getElementById('notificationIcon');
    const searchInput = document.getElementById('searchBudget');
    const searchInputMobile = document.getElementById('searchBudgetMobile');
    const resetButton = document.getElementById('resetSearch');
    const resetButtonMobile = document.getElementById('resetSearchMobile');
    const budgetCards = document.querySelectorAll('#budgetCardsContainer .col:not(.no-results-message)');
    
    notificationBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent the default link behavior
        
        // Add the shake-icon class to trigger the animation on the icon
        notificationIcon.classList.add('shake-icon');
        
        // Remove the shake-icon class after the animation completes
        setTimeout(() => {
            notificationIcon.classList.remove('shake-icon');
            // Navigate to the notifications page after the animation
            window.location.href = this.href;
        }, 200); // Adjust this timing to match your CSS animation duration
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

    // Function to update the no results message
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

    // Reset search
    function resetSearch() {
        searchInput.value = '';
        searchInputMobile.value = '';
        budgetCards.forEach(card => card.style.display = '');
        updateNoResultsMessage(budgetCards.length);
        resetButton.style.display = 'none';
        resetButtonMobile.style.display = 'none';
    }

    // Perform search on input
    searchInput.addEventListener('input', function() {
        performSearch();
        resetButton.style.display = this.value ? 'block' : 'none';
    });

    // Reset search input on mobile
    searchInputMobile.addEventListener('input', function() {
        performSearch();
        resetButtonMobile.style.display = this.value ? 'block' : 'none';
    });

    // Reset search input
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
