<?php
$page_title = "Create · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php'); 

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
    'Utilities' => '#2B3499',
    'Entertainment' => '#EE7214'
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
                                    <small class="text-muted">' . date('M d, Y g:i A', strtotime($income['date'])) . '</small>
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
    echo '   
        <form method="post">
        <input type="hidden" name="budget_month" value="' . $current_month . '">
            <div class="mb-3">
                <label for="budget_name" class="form-label">Budget Category</label>
                <div class="input-group input-group-lg">
                    <select class="form-select" name="budget_name" id="budget_name" required>
                        ';
    foreach ($budgetCategories as $category => $color) {
        echo '<option value="' . htmlspecialchars($category) . '" data-color="' . htmlspecialchars($color) . '">' . htmlspecialchars($category) . '</option>';
    }
    echo '          </select>
                    <button type="button" class="btn btn-plus" id="add-category-btn">
                        +
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label for="budget_amount" class="form-label">Amount</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" class="form-control" name="budget_amount" id="budget_amount" required inputmode="decimal">
                </div>
            </div>
            <button type="submit" class="btn btn-custom-primary-rounded btn-lg w-100 mb-2" name="budget_btn">
                + Add Budget
            </button>
            <a class="btn btn-outline-secondary btn-lg w-100 mb-2" href="dashboard-page.php">
                Back
            </a>
        </form>
    ';
    echo '
        <div class="accordion mt-4" id="recentBudgetsAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recentBudgetsCollapse">
                        Recently Added Budgets
                    </button>
                </h2>
                <div id="recentBudgetsCollapse" class="accordion-collapse collapse" data-bs-parent="#recentBudgetsAccordion">
                    <div class="accordion-body">
                        <div class="recent-items">';
                        $recentBudgets = getRecentBudgets($conn, $userId);
                        if ($recentBudgets->num_rows > 0) {
                            while ($budget = $recentBudgets->fetch_assoc()) {
                                echo '<div class="recent-item">
                                        <div class="recent-item-details">
                                            <span class="recent-item-name">' . htmlspecialchars($budget['name']) . '</span>
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
                            echo    '<small class="text-muted">' . date('M d, Y g:i A', strtotime($expense['date'])) . '</small>
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
    $stmt = $conn->prepare("SELECT name, amount, date 
                           FROM incomes 
                           WHERE user_id = ? 
                           ORDER BY date DESC 
                           LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to fetch recent expenses
function getRecentExpenses($conn, $userId, $limit = 5) {
    $stmt = $conn->prepare("SELECT e.amount, e.date, e.comment, b.name as category_name 
                           FROM expenses e 
                           JOIN budgets b ON e.category_id = b.id 
                           WHERE e.user_id = ? 
                           ORDER BY e.date DESC 
                           LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result();
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
        $newCategoryName = mysqli_real_escape_string($conn, $_POST['newCategoryName']);
        $newCategoryAmount = floatval($_POST['newCategoryAmount']);
        $newCategoryColor = mysqli_real_escape_string($conn, $_POST['newCategoryColor']);
        $userId = $_SESSION['auth_user']['user_id'];
        $currentMonth = date('Y-m');
    
        $stmt = $conn->prepare("INSERT INTO budgets (user_id, name, amount, month, color) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $userId, $newCategoryName, $newCategoryAmount, $currentMonth, $newCategoryColor);
        
        if ($stmt->execute()) {
            $redirect_url .= '&message=' . urlencode("A custom Budget of ₱" . number_format($newCategoryAmount, 2) . " for '" . $newCategoryName . "' has been set for " . date('F Y', strtotime($currentMonth)) . "!") . '&type=primary';
            $notificationMessage = sprintf("A new custom budget '%s' of ₱%.2f has been successfully set for %s", 
                $newCategoryName,
                $newCategoryAmount,
                date('F Y', strtotime($currentMonth))
            );
            addNotification($userId, 'budget', $notificationMessage);
        } else {
            $redirect_url .= '&message=' . urlencode('Error adding category: ' . $stmt->error) . '&type=danger';
        }
        $stmt->close();
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

<!-- Adding Custom Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCategoryModalLabel">Add Custom Budget</h5>
      </div>
      <div class="modal-body">
        <form method="post">
            <div class="mb-3">
                <label for="newCategoryName" class="form-label">Custom Budget Name</label>
                <input type="text" class="form-control form-control-lg" name="newCategoryName" id="newCategoryName" required>
            </div>
            <div class="mb-3">
                <label for="newCategoryAmount" class="form-label">Amount</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">₱</span>
                    <input type="number" step="0.01" class="form-control" name="newCategoryAmount" id="newCategoryAmount" required inputmode="decimal">
                </div>
            </div>
            <div class="mb-4">
                <label for="newCategoryColor" class="form-label">Color</label>
                <input type="color" class="form-control form-control-color w-100" id="newCategoryColor" name="newCategoryColor" value="#6c757d">
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-custom-primary-rounded btn-lg" name="addCategoryBtn">+ Add Budget</button>
                <button type="button" class="btn btn-outline-secondary btn-lg w-100 mb-1" data-bs-dismiss="modal" aria-label="Close">Close</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Add event listener to radio buttons
document.querySelectorAll('input[name="page"]').forEach(radio => {
    radio.addEventListener('change', function() {
        window.location.href = 'create-page.php?page=' + this.value;
    });
});

// Add New Category Modal Script
const addCategoryBtn = document.getElementById('add-category-btn');
if (addCategoryBtn) {
    addCategoryBtn.addEventListener('click', function() {
        var myModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
        myModal.show();
    });
}

// Toast notification script
window.addEventListener('DOMContentLoaded', (event) => {
    const toastLiveExample = document.getElementById('liveToast');
    if (toastLiveExample) {
        const toast = new bootstrap.Toast(toastLiveExample, {
            animation: true,
            autohide: true,
            delay: 5000
        });

        const urlParams = new URLSearchParams(window.location.search);
        const toastMessage = urlParams.get('message');
        const toastType = urlParams.get('type');

        if (toastMessage) {
            const toastBody = document.querySelector('.toast-body');
            const toastElement = document.querySelector('.toast');
            
            toastBody.textContent = decodeURIComponent(toastMessage);
            toastElement.classList.remove('border-primary', 'border-warning', 'border-danger');
            
            switch (toastType) {
                case 'primary':
                    toastElement.classList.add('border-primary');
                    break;
                case 'warning':
                    toastElement.classList.add('border-warning');
                    break;
                case 'danger':
                    toastElement.classList.add('border-danger');
                    break;
            }
            
            toast.show();

            // Remove the message and type from the URL
            urlParams.delete('message');
            urlParams.delete('type');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            history.replaceState(null, '', newUrl);
        }
    }
});
</script>

<?php include('includes/footer.php') ?>