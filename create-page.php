<?php
// Start the session at the very beginning
session_start();

// Include database connection
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');

// Check if user is logged in
if (!isset($_SESSION['auth_user'])) {
    // Not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Initialize redirect URL before using it
$redirect_url = 'create-page.php?page=' . (isset($_GET['page']) ? $_GET['page'] : 'budget');

// Get current user ID
$userId = $_SESSION['auth_user']['user_id'];

// Check if user is subscribed
require_once('includes/SubscriptionHelper.php');
$subscriptionHelper = new SubscriptionHelper($conn);
$hasActiveSubscription = $subscriptionHelper->hasActiveSubscription($_SESSION['auth_user']['user_id']);

// Edit Income Amount
if (isset($_POST['edit_income'])) {
    $incomeId = intval($_POST['income_id']);
    $newAmount = floatval($_POST['new_income_amount']);
    
    // Validate user owns this income
    $checkStmt = $conn->prepare("SELECT id FROM incomes WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $incomeId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE incomes SET amount = ? WHERE id = ? AND user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
        $stmt->bind_param("dii", $newAmount, $incomeId, $userId);
        
        if ($stmt->execute()) {
            $redirect_url .= '&message=' . urlencode("Income has been successfully updated to ₱" . number_format($newAmount, 2)) . '&type=primary';
            addNotification($userId, 'income', "Income amount has been updated to ₱" . number_format($newAmount, 2));
        } else {
            $redirect_url .= '&message=' . urlencode('Error updating income') . '&type=danger';
        }
        $stmt->close();
    }
    $checkStmt->close();
}

// Edit Expense Amount and Comment
if (isset($_POST['edit_expense'])) {
    $expenseId = intval($_POST['expense_id']);
    $newAmount = floatval($_POST['new_expense_amount']);
    $newComment = substr(mysqli_real_escape_string($conn, $_POST['new_expense_comment']), 0, 100);
    
    // Validate user owns this expense
    $checkStmt = $conn->prepare("SELECT id FROM expenses WHERE id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $expenseId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE expenses SET amount = ?, comment = ? WHERE id = ? AND user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
        $stmt->bind_param("dsii", $newAmount, $newComment, $expenseId, $userId);
        
        if ($stmt->execute()) {
            $redirect_url .= '&message=' . urlencode("Expense has been successfully updated to ₱" . number_format($newAmount, 2)) . '&type=primary';
            addNotification($userId, 'expense', "Expense has been updated to ₱" . number_format($newAmount, 2));
        } else {
            $redirect_url .= '&message=' . urlencode('Error updating expense') . '&type=danger';
        }
        $stmt->close();
    }
    $checkStmt->close();
}

// Add Category Button - Only for premium users
if (isset($_POST['addCategoryBtn']) && !$hasActiveSubscription) {
    $_SESSION['message'] = "Custom categories are only available for premium users.";
    $_SESSION['message_type'] = "warning";
    header("Location: create-page.php");
    exit();
}

// Initialize variables
$selected_page = isset($_GET['page']) ? $_GET['page'] : 'budget';
$toast_message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
$toast_type = isset($_GET['type']) ? $_GET['type'] : '';

// Predefined budget categories with colors
$budgetCategories = [
    'General' => '#640D6B',
    'Food' => '#90D26D',
    'Groceries' => '#808836',
    'Rent' => '#2B2B2B',
    'Transportation' => '#F3C623',
    'Health' => '#B31312',
    'Electricity' => '#2B3499',
    'Water' => '#00B8D9',
    'Internet' => '#6554C0',
    'Entertainment' => '#EE7214',
    'Education' => '#4C9AFF',
    'Shopping' => '#FF8F73',
    'Insurance' => '#6554C0',
    'Savings' => '#36B37E',
    'Investments' => '#00B8D9',
    'Dining Out' => '#FF5630',
    'Personal Care' => '#998DD9',
    'Household' => '#79B1B0',
    'Pets' => '#FF991F',
    'Gifts' => '#FF7A9B', 
    'Fitness' => '#57D9A3',
    'Technology' => '#6B778C',
    'Books' => '#8993A4',
    'Clothing' => '#C054BE',
    'Car Maintenance' => '#5243AA',
    'Home Repairs' => '#B79638',
    'Subscriptions' => '#DE350B',
    'Hobbies' => '#00875A',
    'Travel' => '#FF8B00',    
    'Charity' => '#36B37E'
];

// Functions
// Function to show the income form
function showIncome() {
    global $conn;
    $userId = $_SESSION['auth_user']['user_id'];
    echo '
        <form method="post">
            <div class="mb-3">
                <label for="income_name" class="form-label">Income Type</label>
                <select class="form-select form-select-lg" name="income_name" id="income_name">
                    <option value="Salary">Salary</option>
                    <option value="Bonus">Bonus</option>
                    <option value="Commission">Commission</option>
                    <option value="Overtime Pay">Overtime Pay</option>
                    <option value="Tips">Tips</option>
                    <option value="Freelance Payment">Freelance Payment</option>
                    <option value="Allowance">Allowance</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="income_amount" class="form-label">Amount</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" class="form-control" name="income_amount" id="income_amount" required inputmode="decimal">
                </div>
            </div>
            <button type="submit" class="btn btn-custom-primary-rounded btn-lg w-100 mb-2" name="income_btn">
                + Add Income
            </button>
            <a class="btn btn-outline-secondary btn-lg w-100 mb-2" href="dashboard-page.php">
                Back
            </a>
        </form>
    ';
    echo '
        <div class="accordion mt-4" id="recentIncomesAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recentIncomesCollapse">
                        Recently Added Incomes
                    </button>
                </h2>
                <div id="recentIncomesCollapse" class="accordion-collapse collapse" data-bs-parent="#recentIncomesAccordion">
                    <div class="accordion-body">
                        <div class="recent-items">';
                        $recentIncomes = getRecentIncomes($conn, $userId);
                        if ($recentIncomes->num_rows > 0) {
                            while ($income = $recentIncomes->fetch_assoc()) {
                                echo '<div class="recent-item">
                                        <div class="recent-item-details">
                                            <span class="recent-item-name">' . htmlspecialchars($income['name']) . '</span>
                                            <span class="recent-item-amount">₱' . number_format($income['amount'], 2) . '</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">' . date('M d, Y', strtotime($income['date'])) . '</small>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="openEditIncomeModal(' . $income['id'] . ', ' . $income['amount'] . ')">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </div>
                                      </div>';
                            }
                        } else {
                            echo '<p class="text-muted mb-0">No recent incomes found.</p>';
                        }
    echo '          </div>
                </div>
            </div>
        </div>';
}

// Function to show the budget form
function showBudget() {
    global $conn, $budgetCategories;
    $userId = $_SESSION['auth_user']['user_id'];
    $current_month = date('Y-m');

    // Check subscription status
    $subscriptionHelper = new SubscriptionHelper($conn);
    $hasActiveSubscription = $subscriptionHelper->hasActiveSubscription($userId);
    
    echo '   
        <form method="post">
            <input type="hidden" name="budget_month" value="' . $current_month . '">
            <div class="mb-3">
                <label for="budget_name" class="form-label">Budget Category</label>
                <div class="input-group input-group-lg">
                    <select class="form-select" name="budget_name" id="budget_name" required>
                        <option value="">Select Category</option>';
                        
    // Add predefined categories
    foreach ($budgetCategories as $category => $color) {
        echo '<option value="' . htmlspecialchars($category) . '" data-color="' . htmlspecialchars($color) . '">' 
            . htmlspecialchars($category) . '</option>';
    }

    // Add custom categories if user has subscription
    if ($hasActiveSubscription) {
        $stmt = $conn->prepare("
            SELECT name, color 
            FROM budgets 
            WHERE user_id = ? 
            AND name NOT IN ('" . implode("','", array_keys($budgetCategories)) . "')
            AND month = ?
            ORDER BY name
        ");
        $stmt->bind_param("is", $userId, $current_month);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo '<optgroup label="Custom Categories">';
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . htmlspecialchars($row['name']) . '" data-color="' . htmlspecialchars($row['color']) . '">' 
                    . htmlspecialchars($row['name']) . '</option>';
            }
            echo '</optgroup>';
        }
    }

    echo '          </select>';

    // Add Category Button - Show tooltip for non-subscribers
    if ($hasActiveSubscription) {
        echo '
            <button type="button" class="btn btn-plus" id="add-category-btn">
                <i class="bi bi-plus-lg"></i>
            </button>';
    } else {
        echo '
            <button type="button" class="btn btn-plus" 
                    data-bs-toggle="tooltip" 
                    data-bs-placement="top" 
                    title="Subscribe to Premium to add custom categories">
                <i class="bi bi-plus-lg"></i>
                <i class="bi bi-lock-fill ms-1"></i>
            </button>';
    }

    echo '      </div>
                <div class="form-text">';
    if (!$hasActiveSubscription) {
        echo '<i class="bi bi-info-circle me-1"></i>Upgrade to Premium to create custom budget categories';
    }
    echo '      </div>
            </div>
            <div class="mb-4">
                <label for="budget_amount" class="form-label">Amount</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">₱</span>
                    <input type="number" 
                           step="0.01" 
                           class="form-control" 
                           name="budget_amount" 
                           id="budget_amount" 
                           required 
                           inputmode="decimal"
                           min="0.01"
                           placeholder="Enter budget amount">
                </div>
            </div>
            <button type="submit" class="btn btn-custom-primary-rounded btn-lg w-100 mb-2" name="budget_btn">
                <i class="bi bi-plus-circle me-2"></i>Add Budget
            </button>
            <a class="btn btn-outline-secondary btn-lg w-100 mb-2" href="dashboard-page.php">
                Back
            </a>
        </form>';

    // Recent Budgets Section
    echo '
        <div class="accordion mt-4" id="recentBudgetsAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#recentBudgetsCollapse">
                        <i class="bi bi-clock-history me-2"></i>Recently Added Budgets
                    </button>
                </h2>
                <div id="recentBudgetsCollapse" class="accordion-collapse collapse" data-bs-parent="#recentBudgetsAccordion">
                    <div class="accordion-body">
                        <div class="recent-items">';
                        
    // Fetch recent budgets
    $recentBudgets = getRecentBudgets($conn, $userId);
    if ($recentBudgets->num_rows > 0) {
        while ($budget = $recentBudgets->fetch_assoc()) {
            $isCustomCategory = !array_key_exists($budget['name'], $budgetCategories);
            echo '<div class="recent-item">
                    <div class="recent-item-details">
                        <span class="recent-item-name">' 
                            . htmlspecialchars($budget['name']) 
                            . ($isCustomCategory && !$hasActiveSubscription ? ' <i class="bi bi-lock-fill text-muted"></i>' : '') 
                            . '</span>
                        <span class="recent-item-amount">₱' . number_format($budget['amount'], 2) . '</span>
                    </div>
                    <small class="text-muted">' . date('M d, Y', strtotime($budget['date'])) . '</small>
                </div>';
        }
    } else {
        echo '<p class="text-muted mb-0">No recent budgets found.</p>';
    }
    echo '          </div>
                </div>
            </div>
        </div>';
}

// Function to show the expense form
function showExpense() {
    global $conn;
    $userId = $_SESSION['auth_user']['user_id'];
    $current_month = date('Y-m');
    echo '
        <form method="post">
            <div class="mb-3">
                <label for="budget_category" class="form-label">Budget Category</label>
                <select class="form-select form-select-lg" name="budget_category" id="budget_category">
                    ';
                    fetchBudgetCategories();
                    echo '
                </select>
            </div>
            <div class="mb-3">
                <label for="expense_amount" class="form-label">Amount</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" class="form-control" name="expense_amount" id="expense_amount" required inputmode="decimal">
                </div>
            </div>
            <div class="mb-4">
                <label for="expense_comment" class="form-label">Comment</label>
                <textarea class="form-control" name="expense_comment" id="expense_comment" rows="3" maxlength="100" placeholder="Max 100 characters"></textarea>
            </div>
            <button type="submit" class="btn btn-custom-primary-rounded btn-lg w-100 mb-2" name="expense_btn">
                + Add Expense
            </button>
            <a class="btn btn-outline-secondary btn-lg w-100 mb-2" href="dashboard-page.php">
                Back
            </a>
        </form>
    ';
    echo '
        <div class="accordion mt-4" id="recentExpensesAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recentExpensesCollapse">
                        Recently Added Expenses
                    </button>
                </h2>
                <div id="recentExpensesCollapse" class="accordion-collapse collapse" data-bs-parent="#recentExpensesAccordion">
                    <div class="accordion-body">
                        <div class="recent-items">';
                        $recentExpenses = getRecentExpenses($conn, $userId);
                        if ($recentExpenses->num_rows > 0) {
                            while ($expense = $recentExpenses->fetch_assoc()) {
                                echo '<div class="recent-item expense">
                                        <div class="recent-item-details">
                                            <span class="recent-item-name">' . htmlspecialchars($expense['category_name']) . '</span>
                                            <span class="recent-item-amount">₱' . number_format($expense['amount'], 2) . '</span>
                                        </div>';
                                if (!empty($expense['comment'])) {
                                    echo '<div class="recent-item-comment">
                                            <small>' . htmlspecialchars($expense['comment']) . '</small>
                                          </div>';
                                }
                                echo '<div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">' . date('M d, Y', strtotime($expense['date'])) . '</small>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="openEditExpenseModal(' . $expense['id'] . ', ' . $expense['amount'] . ', \'' . htmlspecialchars(addslashes($expense['comment']), ENT_QUOTES) . '\')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                      </div>
                                    </div>';
                            }
                        } else {
                            echo '<p class="text-muted mb-0">No recent expenses found.</p>';
                        }
    echo '          </div>
                </div>
            </div>
        </div>';
}

// Fetch Budget Categories
function fetchBudgetCategories() {
    global $conn;
    $userId = $_SESSION['auth_user']['user_id'];
    $current_month = date('Y-m');
    $stmt = $conn->prepare("SELECT id, name FROM budgets WHERE user_id = ? AND month = ?");
    $stmt->bind_param("is", $userId, $current_month);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['name']) . '</option>';
        }
    } else {
        echo '<option value="">No categories found</option>';
    }
    $stmt->close();
}

// Function to add a notification
function addNotification($userId, $type, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $type, $message);
    $stmt->execute();
    $stmt->close();
}

// Function to fetch recent budgets
function getRecentBudgets($conn, $userId, $limit = 5) {
    $current_month = date('Y-m');
    $stmt = $conn->prepare("SELECT name, amount, date 
                           FROM budgets 
                           WHERE user_id = ? AND month = ? 
                           ORDER BY date DESC 
                           LIMIT ?");
    $stmt->bind_param("isi", $userId, $current_month, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to fetch recent incomes
function getRecentIncomes($conn, $userId, $limit = 5) {
    $stmt = $conn->prepare("SELECT id, name, amount, date 
                           FROM incomes 
                           WHERE user_id = ? 
                           AND MONTH(date) = MONTH(CURRENT_DATE()) 
                           AND YEAR(date) = YEAR(CURRENT_DATE())
                           ORDER BY date DESC 
                           LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to fetch recent expenses
function getRecentExpenses($conn, $userId, $limit = 5) {
    $stmt = $conn->prepare("SELECT e.id, e.amount, e.date, e.comment, b.name as category_name 
                           FROM expenses e 
                           JOIN budgets b ON e.category_id = b.id 
                           WHERE e.user_id = ? 
                           AND MONTH(e.date) = MONTH(CURRENT_DATE()) 
                           AND YEAR(e.date) = YEAR(CURRENT_DATE())
                           ORDER BY e.date DESC 
                           LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to get income by ID
function getIncomeById($conn, $incomeId, $userId) {
    $stmt = $conn->prepare("SELECT * FROM incomes WHERE id = ? AND user_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
    $stmt->bind_param("ii", $incomeId, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get expense by ID
function getExpenseById($conn, $expenseId, $userId) {
    $stmt = $conn->prepare("SELECT e.*, b.name as category_name 
                           FROM expenses e 
                           JOIN budgets b ON e.category_id = b.id 
                           WHERE e.id = ? AND e.user_id = ? 
                           AND MONTH(e.date) = MONTH(CURRENT_DATE()) 
                           AND YEAR(e.date) = YEAR(CURRENT_DATE())");
    $stmt->bind_param("ii", $expenseId, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $redirect_url = 'create-page.php?page=' . $selected_page;

    // Add New Budget Process
    if (isset($_POST['budget_btn'])) {
        $budgetName = mysqli_real_escape_string($conn, $_POST['budget_name']);
        $budgetAmount = floatval($_POST['budget_amount']);
        $budgetMonth = mysqli_real_escape_string($conn, $_POST['budget_month']);
        $userId = $_SESSION['auth_user']['user_id'];

        $budgetColor = isset($budgetCategories[$budgetName]) ? $budgetCategories[$budgetName] : '#6c757d';

        $stmt = $conn->prepare("SELECT * FROM budgets WHERE user_id = ? AND name = ? AND month = ?");
        $stmt->bind_param("iss", $userId, $budgetName, $budgetMonth);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $redirect_url .= '&message=' . urlencode('A budget with this name already exists for the current month!') . '&type=warning';
        } else {
            $stmt = $conn->prepare("INSERT INTO budgets (user_id, name, amount, month, color) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdss", $userId, $budgetName, $budgetAmount, $budgetMonth, $budgetColor);
            
            if ($stmt->execute()) {
                $redirect_url .= '&message=' . urlencode("A budget of ₱" . number_format($budgetAmount, 2) . " for '" . $budgetName . "' has been set for " . date('F Y', strtotime($budgetMonth)) . "!") . '&type=primary';
                $notificationMessage = sprintf("A new budget '%s' of ₱%.2f has been successfully set for %s", 
                    $budgetName,
                    $budgetAmount,
                    date('F Y', strtotime($budgetMonth))
                );
                addNotification($userId, 'budget', $notificationMessage);
            } else {
                $redirect_url .= '&message=' . urlencode('Error adding budget: ' . $stmt->error) . '&type=danger';
            }
        }
        $stmt->close();
    }

    // Add New Category Process 
    if (isset($_POST['addCategoryBtn'])) {
        // First verify subscription status
        if (!$hasActiveSubscription) {
            $redirect_url = 'create-page.php?page=budget&message=' . urlencode('Custom categories are only available for premium users.') . '&type=warning';
            header("Location: $redirect_url");
            exit();
        }

        // Validate and sanitize inputs
        $newCategoryName = trim(mysqli_real_escape_string($conn, $_POST['newCategoryName']));
        $newCategoryAmount = floatval($_POST['newCategoryAmount']);
        $newCategoryColor = mysqli_real_escape_string($conn, $_POST['newCategoryColor']);
        $currentMonth = mysqli_real_escape_string($conn, $_POST['current_month']);

        // Input validation
        if (empty($newCategoryName) || strlen($newCategoryName) < 3 || strlen($newCategoryName) > 50) {
            $_SESSION['message'] = 'Category name must be between 3 and 50 characters.';
            $_SESSION['message_type'] = 'danger';
            header("Location: create-page.php?page=budget");
            exit();
        }

        if ($newCategoryAmount <= 0) {
            $_SESSION['message'] = 'Amount must be greater than 0.';
            $_SESSION['message_type'] = 'danger';
            header("Location: create-page.php?page=budget");
            exit();
        }

        try {
            // Begin transaction
            $conn->begin_transaction();

            // Check if category already exists for this month
            $checkStmt = $conn->prepare("SELECT id FROM budgets WHERE user_id = ? AND name = ? AND month = ?");
            $checkStmt->bind_param("iss", $userId, $newCategoryName, $currentMonth);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception('A category with this name already exists for the current month.');
            }

            // Insert new category
            $stmt = $conn->prepare("INSERT INTO budgets (user_id, name, amount, month, color) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdss", $userId, $newCategoryName, $newCategoryAmount, $currentMonth, $newCategoryColor);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add category: ' . $stmt->error);
            }

            // Add notification
            $notificationMessage = sprintf(
                "New custom category '%s' with budget ₱%.2f has been created for %s",
                $newCategoryName,
                $newCategoryAmount,
                date('F Y', strtotime($currentMonth))
            );
            addNotification($userId, 'budget', $notificationMessage);

            // Commit transaction
            $conn->commit();

            $_SESSION['message'] = sprintf(
                "Custom category '%s' with budget ₱%s has been created successfully.",
                $newCategoryName,
                number_format($newCategoryAmount, 2)
            );
            $_SESSION['message_type'] = 'success';

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['message'] = $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        header("Location: create-page.php?page=budget");
        exit();
    }

    // Add New Expense Process
    if (isset($_POST['expense_btn'])) {
        $budgetCategory = intval($_POST['budget_category']);
        $expenseAmount = floatval($_POST['expense_amount']);
        $expenseComment = substr(mysqli_real_escape_string($conn, $_POST['expense_comment']), 0, 100);
        $userId = $_SESSION['auth_user']['user_id'];
        $currentDate = date('Y-m-d');
        $currentMonth = date('Y-m');

        $stmt = $conn->prepare("SELECT name FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $budgetCategory, $userId);
        $stmt->execute();
        $categoryNameResult = $stmt->get_result();
        $categoryName = '';
        if ($categoryNameRow = $categoryNameResult->fetch_assoc()) {
            $categoryName = $categoryNameRow['name'];
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT id FROM budgets WHERE id = ? AND user_id = ? AND month = ?");
        $stmt->bind_param("iis", $budgetCategory, $userId, $currentMonth);
        $stmt->execute();
        $checkBudgetResult = $stmt->get_result();

        if ($checkBudgetResult->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO expenses (user_id, amount, category_id, date, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idiss", $userId, $expenseAmount, $budgetCategory, $currentDate, $expenseComment);
            
            if ($stmt->execute()) {
                $redirect_url .= '&message=' . urlencode("An expense of ₱" . number_format($expenseAmount, 2) . " has been recorded for '" . $categoryName . "' category on " . date('F j, Y', strtotime($currentDate)) . "!") . '&type=primary';
                $notificationMessage = sprintf("A new expense of ₱%.2f has been successfully recorded to '%s' category",
                    $expenseAmount,
                    $categoryName
                );
                addNotification($userId, 'expense', $notificationMessage);
            } else {
                $redirect_url .= '&message=' . urlencode('Error adding expense: ' . $stmt->error) . '&type=danger';
            }
        } else {
            $redirect_url .= '&message=' . urlencode('Error: The selected budget category does not exist for the current month.') . '&type=warning';
        }
        $stmt->close();
    }

    // Add New Income Process
    if (isset($_POST['income_btn'])) {
        $incomeName = mysqli_real_escape_string($conn, $_POST['income_name']);
        $incomeAmount = floatval($_POST['income_amount']);
        $userId = $_SESSION['auth_user']['user_id'];
        $currentDate = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO incomes (user_id, name, amount, date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $userId, $incomeName, $incomeAmount, $currentDate);
        
        if ($stmt->execute()) {
            $redirect_url .= '&message=' . urlencode("An income of ₱" . number_format($incomeAmount, 2) . " has been successfully recorded for '" . $incomeName . "' on " . date('F j, Y', strtotime($currentDate)) . "!") . '&type=primary';
            $notificationMessage = sprintf("A new income of ₱%.2f has been successfully recorded for '%s'",
                $incomeAmount,
                $incomeName
            );
            addNotification($userId, 'income', $notificationMessage);
        } else {
            $redirect_url .= '&message=' . urlencode('Error adding income: ' . $stmt->error) . '&type=danger';
        }
        $stmt->close();
    }

    // Redirect after processing
    header("Location: $redirect_url");
    exit();
}
?>

<link rel="stylesheet" href="./assets/css/create.css">

<!-- HTML content -->
<body class="graphs-page">
<div class="create-page-container">
    <div class="create-card-wrapper">
        <div class="card create-card shadow-sm">
            <div class="card-body">
                <form method="post" action="" id="pageForm" class="mt-2 mb-4">
                    <input type="hidden" name="submitted_page" value="<?php echo htmlspecialchars($selected_page); ?>">
                    <div class="btn-group w-100" role="group" aria-label="Page Selection">
                        <input type="radio" class="btn-check" name="page" id="income" value="income" autocomplete="off" <?php echo ($selected_page == 'income') ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="income">Income</label>

                        <input type="radio" class="btn-check" name="page" id="budget" value="budget" autocomplete="off" <?php echo ($selected_page == 'budget') ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-success" for="budget">Budget</label>

                        <input type="radio" class="btn-check" name="page" id="expense" value="expense" autocomplete="off" <?php echo ($selected_page == 'expense') ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-danger" for="expense">Expense</label>
                    </div>
                </form>
                
                <div class="form-content">
                    <?php
                        if ($selected_page == 'income') {
                            showIncome();
                        } elseif ($selected_page == 'budget') {
                            showBudget();
                        } elseif ($selected_page == 'expense') {
                            showExpense();
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ad Section -->
    <?php
    require_once 'includes/Advertisement.php';
    if (!$hasActiveSubscription) {
        echo Advertisement::render('banner', 'center');
    }
    ?>

</div>

<!-- Toast container -->
<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 11">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal custom-modal" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Add Custom Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="addCategoryForm" class="needs-validation" novalidate>
                    <!-- Add a hidden input for the current month -->
                    <input type="hidden" name="current_month" value="<?php echo date('Y-m'); ?>">
                    
                    <div class="mb-3">
                        <label for="newCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control form-control-lg" 
                               name="newCategoryName" id="newCategoryName" 
                               required maxlength="50" 
                               pattern="[A-Za-z0-9\s]{3,50}"
                               placeholder="Enter category name">
                        <div class="invalid-feedback">
                            Category name must be 3-50 characters, letters and numbers only.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newCategoryAmount" class="form-label">Initial Budget Amount</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" 
                                   name="newCategoryAmount" id="newCategoryAmount" 
                                   required min="0.01"
                                   placeholder="Enter amount">
                            <div class="invalid-feedback">
                                Please enter a valid amount greater than 0.
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="newCategoryColor" class="form-label">Category Color</label>
                        <input type="color" class="form-control form-control-color w-100" 
                               id="newCategoryColor" name="newCategoryColor" 
                               value="#6c757d">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="addCategoryBtn" class="btn btn-lg btn-custom-primary-rounded">
                            <i class="bi bi-plus-circle me-2"></i>Add Category
                        </button>
                        <button type="button" class="btn btn-lg btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Income Modal -->
<div class="modal fade" id="editIncomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Income</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="income_id" id="edit_income_id">
                    <div class="mb-3">
                        <label for="new_income_amount" class="form-label">Amount</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" name="new_income_amount" id="new_income_amount" required inputmode="decimal">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="edit_income" class="btn btn-custom-primary-rounded btn-lg">Update Income</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="expense_id" id="edit_expense_id">
                    <div class="mb-3">
                        <label for="new_expense_amount" class="form-label">Amount</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" name="new_expense_amount" id="new_expense_amount" required inputmode="decimal">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="new_expense_comment" class="form-label">Comment</label>
                        <textarea class="form-control" name="new_expense_comment" id="new_expense_comment" rows="3" maxlength="100"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="edit_expense" class="btn btn-custom-primary-rounded btn-lg">Update Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Pass subscription status to JavaScript -->
<script>
    const hasActiveSubscription = <?php echo json_encode($hasActiveSubscription); ?>;
</script>

<script>
// Initialize all Bootstrap components when document loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

    // Initialize all modals with proper options
    const existingModals = document.querySelectorAll('.modal');
    existingModals.forEach(modal => {
        const instance = bootstrap.Modal.getInstance(modal);
        if (instance) {
            instance.dispose();
        }
        new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: false,
            focus: true
        });
    });

    // Check for URL parameters for toast messages
    handleUrlParameters();

    // Initialize form validations
    initializeFormValidations();
});

// Radio button event listeners for page selection
document.querySelectorAll('input[name="page"]').forEach(radio => {
    radio.addEventListener('change', function() {
        window.location.href = 'create-page.php?page=' + this.value;
    });
});

// Category management scripts
const addCategoryBtn = document.getElementById('add-category-btn');
if (addCategoryBtn) {
    addCategoryBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        if (!hasActiveSubscription) {
            showSubscriptionPrompt();
            return;
        }

        const modalElement = document.getElementById('addCategoryModal');
        if (!modalElement) return;

        // Get existing modal instance or create new one
        let modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            });
        }

        // Reset form before showing modal
        const form = modalElement.querySelector('#addCategoryForm');
        if (form) {
            form.reset();
            form.classList.remove('was-validated');
            
            // Reset any custom validity
            form.querySelectorAll('input').forEach(input => {
                input.setCustomValidity('');
                input.classList.remove('is-valid', 'is-invalid');
            });
        }

        // Show modal
        modalInstance.show();
    });
}

// Initialize form validations
function initializeFormValidations() {
    // Add category form validation
    const addCategoryForm = document.getElementById('addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Custom validation
            const nameInput = this.querySelector('#newCategoryName');
            const amountInput = this.querySelector('#newCategoryAmount');
            
            // Validate name
            const nameValue = nameInput.value.trim();
            const nameRegex = /^[A-Za-z0-9\s]{3,50}$/;
            if (!nameRegex.test(nameValue)) {
                nameInput.setCustomValidity('Category name must be 3-50 characters and contain only letters, numbers, and spaces.');
                e.preventDefault();
            } else {
                nameInput.setCustomValidity('');
            }
            
            // Validate amount
            const amount = parseFloat(amountInput.value);
            if (isNaN(amount) || amount <= 0) {
                amountInput.setCustomValidity('Please enter a valid amount greater than 0');
                e.preventDefault();
            } else {
                amountInput.setCustomValidity('');
            }
            
            this.classList.add('was-validated');
        });

        // Real-time validation for category name
        const categoryNameInput = document.getElementById('newCategoryName');
        if (categoryNameInput) {
            categoryNameInput.addEventListener('input', function() {
                const namePattern = /^[A-Za-z0-9\s]{3,50}$/;
                if (!namePattern.test(this.value)) {
                    this.setCustomValidity('Category name must be 3-50 characters and contain only letters, numbers, and spaces.');
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        }

        // Real-time validation for amount
        const categoryAmountInput = document.getElementById('newCategoryAmount');
        if (categoryAmountInput) {
            categoryAmountInput.addEventListener('input', function() {
                const amount = parseFloat(this.value);
                if (isNaN(amount) || amount <= 0) {
                    this.setCustomValidity('Please enter a valid amount greater than 0');
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        }
    }
}

// Function to show subscription prompt
function showSubscriptionPrompt() {
    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
    const toastBody = document.querySelector('#liveToast .toast-body');
    const toastElement = document.querySelector('#liveToast');
    
    toastElement.classList.remove('border-primary', 'border-warning', 'border-danger');
    toastElement.classList.add('border-warning');
    
    toastBody.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-lock-fill me-2"></i>
            <span>Custom categories are a premium feature. </span>
            <a href="subscription-plans.php" class="btn btn-sm btn-custom-primary-rounded ms-2">Upgrade Now</a>
        </div>
    `;
    
    toast.show();
}

// Income modal functions
function openEditIncomeModal(id, amount) {
    document.getElementById('edit_income_id').value = id;
    document.getElementById('new_income_amount').value = parseFloat(amount).toFixed(2);
    const modalElement = document.getElementById('editIncomeModal');
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

// Expense modal functions
function openEditExpenseModal(id, amount, comment) {
    document.getElementById('edit_expense_id').value = id;
    document.getElementById('new_expense_amount').value = parseFloat(amount).toFixed(2);
    document.getElementById('new_expense_comment').value = comment ? comment.replace(/\\'/g, "'") : '';
    const modalElement = document.getElementById('editExpenseModal');
    const modalInstance = new bootstrap.Modal(modalElement);
    modalInstance.show();
}

// Color picker enhancement
const colorPicker = document.getElementById('newCategoryColor');
if (colorPicker) {
    // Set initial background color
    colorPicker.style.backgroundColor = colorPicker.value;
    
    // Update background color on change
    colorPicker.addEventListener('input', function() {
        this.style.backgroundColor = this.value;
    });
    
    // Update background color on load
    colorPicker.addEventListener('load', function() {
        this.style.backgroundColor = this.value;
    });
}

// Toast notification handler
function showToast(message, type = 'primary') {
    const toastElement = document.getElementById('liveToast');
    if (toastElement) {
        const toast = new bootstrap.Toast(toastElement, {
            animation: true,
            autohide: true,
            delay: 5000
        });

        const toastBody = toastElement.querySelector('.toast-body');
        
        toastElement.classList.remove('border-primary', 'border-warning', 'border-danger');
        toastElement.classList.add(`border-${type}`);
        
        if (typeof message === 'string') {
            toastBody.textContent = message;
        } else {
            toastBody.innerHTML = message;
        }
        
        toast.show();
    }
}

// URL parameter handling for toasts
function handleUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const toastMessage = urlParams.get('message');
    const toastType = urlParams.get('type');

    if (toastMessage) {
        showToast(decodeURIComponent(toastMessage), toastType || 'primary');

        // Clean URL after showing toast
        urlParams.delete('message');
        urlParams.delete('type');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        history.replaceState(null, '', newUrl);
    }
}

// Handle subscription-required features
document.querySelectorAll('[data-requires-subscription="true"]').forEach(element => {
    element.addEventListener('click', function(e) {
        if (!hasActiveSubscription) {
            e.preventDefault();
            showSubscriptionPrompt();
        }
    });
});

// Clean up on modal close
['addCategoryModal', 'editIncomeModal', 'editExpenseModal'].forEach(modalId => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
            }
            this.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                el.classList.remove('is-invalid', 'is-valid');
            });
        });
    }
});

// Handle modal keyboard events
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance && !modal.classList.contains('static')) {
                modalInstance.hide();
            }
        });
    }
});

// Form input formatting
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', function() {
        if (this.value.length > 0) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });
});

// Add feedback classes to form inputs
document.querySelectorAll('.needs-validation').forEach(form => {
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });
});

// Format currency values
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

<?php include('includes/footer.php') ?>