<?php
$page_title = "Create · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php'); 

$selected_page = isset($_POST['page']) ? $_POST['page'] : (isset($_GET['page']) ? $_GET['page'] : 'budget');

// Functions
function showIncome() {
    echo"
        <form method='post'>
            <div class='mb-3'>
                <label for='income_name' class='form-label'>Income Type</label>
                <select class='form-select form-select-lg' name='income_name' id='income_name'>
                    <option value='Salary'>Salary</option>
                    <option value='Bonus'>Bonus</option>
                    <option value='Commission'>Commission</option>
                    <option value='Overtime Pay'>Overtime Pay</option>
                    <option value='Tips'>Tips</option>
                    <option value='Freelance Payment'>Freelance Payment</option>
                    <option value='Allowance'>Allowance</option>
                </select>
            </div>
            <div class='mb-4'>
                <label for='income_amount' class='form-label'>Amount</label>
                <div class='input-group input-group-lg'>
                    <span class='input-group-text'>₱</span>
                    <input type='number' step='0.01' class='form-control' name='income_amount' id='income_amount' required inputmode='decimal'>
                </div>
            </div>
            <button type='submit' class='btn btn-custom-primary btn-lg w-100 mb-2' name='income_btn'>
                + Add Income
            </button>
            <a class='btn btn-outline-secondary btn-lg w-100 mb-2' href='dashboard-page.php'>
                Back
            </a>
        </form>
        ";
}

// Predefined budget categories with colors
$budgetCategories = [
    'General' => '#640D6B',             // *Purple
    'Food' => '#90D26D',                // *Green
    'Groceries' => '#808836',           // *Sage
    'Rent' => '#2B2B2B',                // *Black
    'Transportation' => '#F3C623',      // *Yellow
    'Health' => '#B31312',              // *Red
    'Utilities' => '#2B3499',           // *Blue
    'Entertainment' => '#EE7214'        // *Orange
];

function showBudget() {
    global $budgetCategories;
    $current_month = date('Y-m');
    echo "   
        <form method='post'>
        <input type='hidden' name='budget_month' value='$current_month'>
            <div class='mb-3'>
                <label for='budget_name' class='form-label'>Budget Category</label>
                <div class='input-group input-group-lg'>
                    <select class='form-select' name='budget_name' id='budget_name' required>
                        <option value=''>Select a category</option>";
    foreach ($budgetCategories as $category => $color) {
        echo "<option value='$category' data-color='$color'>$category</option>";
    }
    echo "          </select>
                    <button type='button' class='btn btn-outline-secondary' id='add-category-btn'>
                        +
                    </button>
                </div>
            </div>
            <div class='mb-4'>
                <label for='budget_amount' class='form-label'>Amount</label>
                <div class='input-group input-group-lg'>
                    <span class='input-group-text'>₱</span>
                    <input type='number' step='0.01' class='form-control' name='budget_amount' id='budget_amount' required inputmode='decimal'>
                </div>
            </div>
            <button type='submit' class='btn btn-custom-primary btn-lg w-100 mb-2' name='budget_btn'>
                + Add Budget
            </button>
            <a class='btn btn-outline-secondary btn-lg w-100 mb-2' href='dashboard-page.php'>
                Back
            </a>
        </form>
        ";
}

function showExpense() {
    $current_month = date('Y-m');
    echo"
        <form method='post'>
            <div class='mb-3'>
                <label for='budget_category' class='form-label'>Budget Category</label>
                <select class='form-select form-select-lg' name='budget_category' id='budget_category'>
                    ";
                    fetchBudgetCategories();
                    echo "
                </select>
            </div>
            <div class='mb-3'>
                <label for='expense_amount' class='form-label'>Amount</label>
                <div class='input-group input-group-lg'>
                    <span class='input-group-text'>₱</span>
                    <input type='number' step='0.01' class='form-control' name='expense_amount' id='expense_amount' required inputmode='decimal'>
                </div>
            </div>
            <div class='mb-4'>
                <label for='expense_comment' class='form-label'>Comment</label>
                <textarea class='form-control' name='expense_comment' id='expense_comment' rows='3' maxlength='100' placeholder='Max 100 characters'></textarea>
            </div>
            <button type='submit' class='btn btn-custom-primary btn-lg w-100 mb-2' name='expense_btn'>
                + Add Expense
            </button>
            <a class='btn btn-outline-secondary btn-lg w-100 mb-2' href='dashboard-page.php'>
                Back
            </a>
        </form>
        ";
}

// Fetch Budget Categories
function fetchBudgetCategories() {
    global $conn;
    $userId = $_SESSION['auth_user']['user_id'];
    $current_month = date('Y-m');
    $sql = "SELECT id, name FROM budgets WHERE user_id = '$userId' AND month = '$current_month'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value=''>No categories found</option>";
    }
}

// Function to add a notification
function addNotification($userId, $type, $message) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $type, $message);
    $stmt->execute();
    $stmt->close();
}

$toast_message = '';
$toast_type = '';

// Process
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Add New Budget Process
    if (isset($_POST['budget_btn'])) {
        $budgetName = $_POST['budget_name'];
        $budgetAmount = $_POST['budget_amount'];
        $budgetMonth = $_POST['budget_month'];
        $userId = $_SESSION['auth_user']['user_id']; 
    
        // Get the color from predefined categories, or use a default color
        $budgetColor = isset($budgetCategories[$budgetName]) ? $budgetCategories[$budgetName] : '#6c757d';
    
        $checkSql = "SELECT * FROM budgets WHERE user_id = '$userId' AND name = '$budgetName' AND month = '$budgetMonth'";
        $checkResult = mysqli_query($conn, $checkSql);
    
        if (mysqli_num_rows($checkResult) > 0) {
            $toast_message = 'A budget with this name already exists for the current month!';
            $toast_type = 'warning';
        } else {
            $sql = "INSERT INTO budgets (user_id, name, amount, month, color) VALUES ('$userId', '$budgetName', '$budgetAmount', '$budgetMonth', '$budgetColor')";
            $result = mysqli_query($conn, $sql);
    
            if ($result) {
                $toast_message = "A budget of ₱" . number_format($budgetAmount, 2) . " for '$budgetName' has been set for " . date('F Y', strtotime($budgetMonth)) . "!";
                $toast_type = 'primary';
                $notificationMessage = sprintf("A new budget '%s' of ₱%.2f has been successfully set for %s", 
                    mysqli_real_escape_string($conn, $budgetName),
                    $budgetAmount,
                    date('F Y', strtotime($budgetMonth))
                );
                addNotification($userId, 'budget', $notificationMessage);
            } else {
                $toast_message = 'Error adding budget: ' . mysqli_error($conn);
                $toast_type = 'danger';
            }
        }
    }

    // Add New Category Process
    if (isset($_POST['addCategoryBtn'])) {
        $newCategoryName = mysqli_real_escape_string($conn, $_POST['newCategoryName']);
        $newCategoryAmount = floatval($_POST['newCategoryAmount']);
        $newCategoryColor = $_POST['newCategoryColor'];
        $userId = $_SESSION['auth_user']['user_id'];
        $currentMonth = date('Y-m');
    
        $stmt = $conn->prepare("INSERT INTO budgets (user_id, name, amount, month, color) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $userId, $newCategoryName, $newCategoryAmount, $currentMonth, $newCategoryColor);
        $result = $stmt->execute();
        $stmt->close();
    
        if ($result) {
            $toast_message = "A custom Budget of ₱" . number_format($newCategoryAmount, 2) . " for '$newCategoryName' has been set for " . date('F Y', strtotime($currentMonth)) . "!";
            $toast_type = 'primary';
            $notificationMessage = sprintf("A new custom budget '%s' of ₱%.2f has been successfully set for %s", 
                $newCategoryName,
                $newCategoryAmount,
                date('F Y', strtotime($currentMonth))
            );
            addNotification($userId, 'budget', $notificationMessage);
        } else {
            $toast_message = 'Error adding category!';
            $toast_type = 'danger';
        }
    }

    // Add New Expense Process
    if (isset($_POST['expense_btn'])) {
        $budgetCategory = $_POST['budget_category'];
        $expenseAmount = $_POST['expense_amount'];
        $expenseComment = substr($_POST['expense_comment'], 0, 100);
        $userId = $_SESSION['auth_user']['user_id'];
        $currentDate = date('Y-m-d');
        $currentMonth = date('Y-m');

        // Fetch the category name
        $categoryNameSql = "SELECT name FROM budgets WHERE id = '$budgetCategory' AND user_id = '$userId'";
        $categoryNameResult = mysqli_query($conn, $categoryNameSql);
        $categoryName = '';
        if ($categoryNameRow = mysqli_fetch_assoc($categoryNameResult)) {
            $categoryName = $categoryNameRow['name'];
        }

        $checkBudgetSql = "SELECT id FROM budgets WHERE id = '$budgetCategory' AND user_id = '$userId' AND month = '$currentMonth'";
        $checkBudgetResult = mysqli_query($conn, $checkBudgetSql);

        if (mysqli_num_rows($checkBudgetResult) > 0) {
            $sql = "INSERT INTO expenses (user_id, amount, category_id, date, comment) VALUES ('$userId', '$expenseAmount', '$budgetCategory', '$currentDate', '$expenseComment')";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                $toast_message = "A expense of ₱" . number_format($expenseAmount, 2) . " has been recorded for '$categoryName' category on " . date('F j, Y', strtotime($currentDate)) . "!";
                $toast_type = 'primary';
                $notificationMessage = sprintf("A new expense of ₱%.2f has been successfully recorded to '%s' category",
                    $expenseAmount,
                    mysqli_real_escape_string($conn, $categoryName)
                );
                addNotification($userId, 'expense', $notificationMessage);
            } else {
                $toast_message = 'Error adding expense!';
                $toast_type = 'danger';
            }
        } else {
            $toast_message = 'Error: The selected budget category does not exist for the current month.';
            $toast_type = 'warning';
        }
        $selected_page = 'expense';
    }

    // Add New Income Process
    if (isset($_POST['income_btn'])) {
        $incomeName = $_POST['income_name'];
        $incomeAmount = $_POST['income_amount'];
        $userId = $_SESSION['auth_user']['user_id'];
        $currentDate = date('Y-m-d');

        $sql = "INSERT INTO incomes (user_id, name, amount, date) VALUES ('$userId', '$incomeName', '$incomeAmount', '$currentDate')";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $toast_message = "A income of ₱" . number_format($incomeAmount, 2) . " has been successfully recorded for '$incomeName' on " . date('F j, Y', strtotime($currentDate)) . "!";
            $toast_type = 'primary';
            $notificationMessage = sprintf("A new income of ₱%.2f has been successfully recorded for '%s'",
                $incomeAmount,
                mysqli_real_escape_string($conn, $incomeName)
            );
            addNotification($userId, 'income', $notificationMessage);
        } else {
            $toast_message = 'Error adding income!';
            $toast_type = 'danger';
        }
        $selected_page = 'income';
    }
}
?>

<!-- HTML content -->
<div class="container-fluid vh-100 d-flex flex-column justify-content-center align-items-center">
    <div class="card shadow-sm" style="width: 100%; max-width: 400px;">
        <div class="card-body">
            <form method="post" action="" id="pageForm" class="mt-2 mb-4">
                <input type="hidden" name="submitted_page" value="<?php echo $selected_page; ?>">
                <div class="btn-group w-100" role="group" aria-label="Page Selection">
                    <input type="radio" class="btn-check" name="page" id="income" value="income" autocomplete="off" <?php echo ($selected_page == 'income') ? 'checked' : ''; ?>>
                    <label class="btn btn-outline-primary" for="income">Income</label>

                    <input type="radio" class="btn-check" name="page" id="budget" value="budget" autocomplete="off" <?php echo ($selected_page == 'budget') ? 'checked' : ''; ?>>
                    <label class="btn btn-outline-success" for="budget">Budget</label>

                    <input type="radio" class="btn-check" name="page" id="expense" value="expense" autocomplete="off" <?php echo ($selected_page == 'expense') ? 'checked' : ''; ?>>
                    <label class="btn btn-outline-danger" for="expense">Expense</label>
                </div>
            </form>
            
            <div class="content">
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

<!-- Modal for adding custom category -->
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
                <input type="color" class="form-control form-control-color" id="newCategoryColor" name="newCategoryColor" value="#6c757d">
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-custom-primary btn-lg" name="addCategoryBtn">+ Add Budget</button>
                <button type="button" class="btn btn-outline-secondary btn-lg w-100 mb-1" data-bs-dismiss="modal" aria-label="Close">Close</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('input[name="page"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('pageForm').submit();
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
        <?php if (!empty($toast_message)): ?>
        const toastBody = document.querySelector('.toast-body');
        const toastElement = document.querySelector('.toast');
        
        toastBody.textContent = '<?php echo addslashes($toast_message); ?>';
        toastElement.classList.remove('border-primary', 'border-warning', 'border-danger');
        
        switch ('<?php echo $toast_type; ?>') {
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
        <?php endif; ?>
    }
});
</script>

<?php include('includes/footer.php') ?>