<?php
ob_start(); // Start output buffering
$page_title = "Goals · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');
include('includes/navbar.php');

// Get the current user ID
$userId = $_SESSION['auth_user']['user_id'];

// Function to update total balance for a user
function updateCumulativeBalance($conn, $userId) {
    $currentMonth = date('Y-m-01'); // First day of current month
    
    // Calculate the sum of all balances from previous months
    $sql = "SELECT SUM(balance) as total_balance 
            FROM balances 
            WHERE user_id = ? AND CONCAT(year, '-', LPAD(month, 2, '0'), '-01') < ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $currentMonth);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalBalance = $result->fetch_assoc()['total_balance'] ?? 0;

    // Update or insert the cumulative balance
    $sql = "INSERT INTO cumulative_balance (user_id, total_amount) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE total_amount = VALUES(total_amount)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("id", $userId, $totalBalance);
    $stmt->execute();
}

// Function to get user's available balance (total balance minus goal allocations)
function getCurrentBalance($conn, $userId) {
    // First, update the cumulative balance
    updateCumulativeBalance($conn, $userId);

    // Now, fetch the cumulative balance
    $sql = "SELECT total_amount FROM cumulative_balance WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cumulativeBalance = $result->fetch_assoc()['total_amount'] ?? 0;

    // Subtract goal allocations
    $sql = "SELECT SUM(current_amount) as total_goal_amount FROM goals WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalGoalAmount = $result->fetch_assoc()['total_goal_amount'] ?? 0;

    return $cumulativeBalance - $totalGoalAmount;
}

// Function to get all goals for a user
function getUserGoals($conn, $userId, $sortBy = 'target_date', $sortOrder = 'ASC', $category = null) {
    $allowedSortFields = ['name', 'target_amount', 'current_amount', 'target_date', 'category'];
    $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'target_date';
    $sortOrder = $sortOrder === 'DESC' ? 'DESC' : 'ASC';

    $sql = "SELECT * FROM goals WHERE user_id = ? AND is_archived = FALSE";
    $params = [$userId];
    $types = "i";

    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }

    $sql .= " ORDER BY $sortBy $sortOrder";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

// Handle form submission for adding a new goal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_goal'])) {
    $name = $_POST['goal_name'];
    $targetAmount = $_POST['target_amount'];
    $targetDate = $_POST['target_date'];
    $category = $_POST['category'];

    $sql = "INSERT INTO goals (user_id, name, target_amount, target_date, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $userId, $name, $targetAmount, $targetDate, $category);
    
    if ($stmt->execute()) {
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=goal_added");
            exit();
        } else {
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "?success=goal_added';</script>";
            exit();
        }
    } else {
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error adding goal: " . $conn->error));
            exit();
        } else {
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error adding goal: " . $conn->error) . "';</script>";
            exit();
        }
    }
}

// Handle goal deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_goal'])) {
    $goalId = $_POST['goal_id'];
    
    // Only allow deletion of incomplete goals
    $sql = "DELETE FROM goals WHERE id = ? AND user_id = ? AND current_amount < target_amount";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $goalId, $userId);
    
    if ($stmt->execute()) {
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=goal_deleted");
            exit();
        } else {
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "?success=goal_deleted';</script>";
            exit();
        }
    } else {
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error deleting goal: " . $conn->error));
            exit();
        } else {
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error deleting goal: " . $conn->error) . "';</script>";
            exit();
        }
    }
}

// Handle completed goal archiving
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['archive_goal'])) {
    $goalId = $_POST['goal_id'];
    $sql = "UPDATE goals SET is_archived = TRUE WHERE id = ? AND user_id = ? AND current_amount >= target_amount";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $goalId, $userId);
    
    if ($stmt->execute()) {
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=goal_archived");
            exit();
        } else {
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "?success=goal_archived';</script>";
            exit();
        }
    } else {
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error archiving goal: " . $conn->error));
            exit();
        } else {
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error archiving goal: " . $conn->error) . "';</script>";
            exit();
        }
    }
}

// Handle goal progress update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_progress'])) {
    $goalId = $_POST['goal_id'];
    $newAmount = $_POST['current_amount'];
    
    $sql = "UPDATE goals SET current_amount = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dii", $newAmount, $goalId, $userId);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=progress_updated");
        exit();
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error updating goal progress: " . $conn->error));
        exit();
    }
}

// Check for success or error messages in URL parameters
$successMessage = isset($_GET['success']) ? getSuccessMessage($_GET['success']) : null;
$errorMessage = isset($_GET['error']) ? $_GET['error'] : null;

// Function to get success message based on the success type
function getSuccessMessage($successType) {
    switch ($successType) {
        case 'goal_added':
            return "Goal added successfully!";
        case 'goal_archived':
            return "Goal archived successfully!";
        case 'progress_updated':
            return "Goal progress updated successfully!";
        case 'goal_deleted':
            return "Goal deleted successfully!";
        default:
            return "Operation completed successfully!";
    }
}

// Get current balance and goals
updateCumulativeBalance($conn, $userId);
$currentBalance = getCurrentBalance($conn, $userId);
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'target_date';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$filterCategory = isset($_GET['category']) ? $_GET['category'] : null;
$goals = getUserGoals($conn, $userId, $sortBy, $sortOrder, $filterCategory);

// Separate active and completed goals
$activeGoals = [];
$completedGoals = [];
$totalTargetAmount = 0;
$totalCurrentAmount = 0;

while ($goal = $goals->fetch_assoc()) {
    $totalTargetAmount += $goal['target_amount'];
    $totalCurrentAmount += $goal['current_amount'];
    if ($goal['current_amount'] >= $goal['target_amount']) {
        $completedGoals[] = $goal;
    } else {
        $activeGoals[] = $goal;
    }
}

$totalGoals = count($activeGoals) + count($completedGoals);
$overallProgress = $totalTargetAmount > 0 ? ($totalCurrentAmount / $totalTargetAmount) * 100 : 0;

// List of goal categories
$goalCategories = [
    "Emergency Fund" => "Build a safety net for unexpected expenses",
    "Retirement" => "Save for long-term financial security",
    "Home Purchase" => "Save for a down payment or home improvements",
    "Vehicle Purchase" => "Save for a new car or vehicle maintenance",
    "Debt Repayment" => "Pay off credit cards, loans, or other debts",
    "Education" => "Save for tuition, courses, or professional development",
    "Travel" => "Plan and save for vacations or trips",
    "Wedding" => "Save for wedding expenses or honeymoon",
    "Business Startup" => "Build capital for starting or expanding a business",
    "Technology Purchase" => "Save for new gadgets or tech upgrades",
    "Home Appliances" => "Save for major household appliances",
    "Medical Expenses" => "Prepare for planned medical procedures or treatments",
    "Charity Donation" => "Save to make a significant charitable contribution",
    "Investment" => "Build funds for stocks, bonds, or other investments",
    "Child's Future" => "Save for your child's education or future needs",
    "Pet Expenses" => "Save for pet care, vet bills, or pet-related purchases",
    "Hobby Fund" => "Save for equipment or activities related to your hobbies",
    "Family Support" => "Save to help family members financially",
    "Moving Expenses" => "Prepare for costs associated with relocating",
    "Legal Expenses" => "Save for legal fees or services",
    "Special Occasion" => "Save for birthdays, anniversaries, or other celebrations",
    "Fitness Goals" => "Save for gym membership, equipment, or fitness programs",
    "Emergency Preparedness" => "Build supplies for natural disasters or emergencies",
    "Other" => "Custom goal not listed above"
];

?>

<link rel="stylesheet" href="./assets/css/goals.css">

<!-- HTML content -->
<div class="py-4">
    <div class="container">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Goals</h1>
        </div>

        <!-- Overview Cards Section -->
        <div class="row g-3 mb-4">
            <!-- Balance Card -->
            <div class="col-md-4">
                <div class="card balance-card h-100">
                    <div class="card-body balance-content">
                        <h5 class="card-title">Available Balance</h5>
                        <h2 class="display-4">₱<?php echo number_format($currentBalance, 2); ?></h2>
                        <p class="text-white-50 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Unallocated funds available for goals
                        </p>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics Card -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Goals Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="summary-stat">
                                    <span class="text-muted">Total Goals</span>
                                    <strong><?php echo $totalGoals; ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="summary-stat">
                                    <span class="text-muted">Completed</span>
                                    <strong><?php echo count($completedGoals); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="summary-stat">
                                    <span class="text-muted">Total Target</span>
                                    <strong>₱<?php echo number_format($totalTargetAmount, 2); ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="summary-stat">
                                    <span class="text-muted">Total Saved</span>
                                    <strong>₱<?php echo number_format($totalCurrentAmount, 2); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-4">
                            <div class="progress-bar bg-success" role="progressbar" 
                                style="width: <?php echo $overallProgress; ?>%" 
                                aria-valuenow="<?php echo $overallProgress; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Goal Button -->
        <button type="button" class="btn btn-add w-100 mb-4" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                + New Goal
        </button>

        <!-- Active Goals Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0">Active Goals</h5>
                        <div class="d-flex gap-2">
                            <div class="dropdown">
                                <button class="btn btn-dropdown" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                    <i class="bi bi-sort-down me-1"></i>Sort
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="?sort=target_date&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        <i class="bi bi-calendar me-2"></i>By Date</a>
                                    </li>
                                    <li><a class="dropdown-item" href="?sort=target_amount&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        <i class="bi bi-currency-dollar me-2"></i>By Amount</a>
                                    </li>
                                    <li><a class="dropdown-item" href="?sort=category&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        <i class="bi bi-tag me-2"></i>By Category</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-dropdown" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                                    <i class="bi bi-funnel me-1"></i>Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="?category=">All Categories</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php foreach ($goalCategories as $category => $description): ?>
                                        <li><a class="dropdown-item" href="?category=<?php echo urlencode($category); ?>">
                                            <?php echo htmlspecialchars($category); ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php if (empty($activeGoals)): ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="bi bi-clipboard-plus display-4 text-muted mb-3"></i>
                                        <p class="mb-0 text-muted">No active goals yet. Start by creating a new goal!</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($activeGoals as $goal): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card goal-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h5 class="goal-card-title"><?php echo htmlspecialchars($goal['name']); ?></h5>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?php echo htmlspecialchars($goal['category']); ?>
                                                    </span>
                                                </div>
                                                <div class="goal-details mb-3">
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <small class="text-muted d-block">Target</small>
                                                            <strong class="text-primary">₱<?php echo number_format($goal['target_amount'], 2); ?></strong>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted d-block">Saved</small>
                                                            <strong class="text-success">₱<?php echo number_format($goal['current_amount'], 2); ?></strong>
                                                        </div>
                                                        <div class="col-12 mt-2">
                                                            <small class="text-muted d-block">Due Date</small>
                                                            <strong><?php echo date('M d, Y', strtotime($goal['target_date'])); ?></strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                                $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                                                $progressBarClass = $progress >= 75 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger');
                                                ?>
                                                <div class="progress">
                                                    <div class="progress-bar <?php echo $progressBarClass; ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo $progress; ?>%" 
                                                        aria-valuenow="<?php echo $progress; ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <p class="text-end mb-3">
                                                    <strong><?php echo number_format($progress, 1); ?>%</strong> Complete
                                                </p>
                                                <div class="goal-actions">
                                                    <button class="btn btn-delete" 
                                                            onclick="openDeleteModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['name']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <button class="btn btn-update" 
                                                            onclick="openUpdateModal(<?php echo $goal['id']; ?>, <?php echo $goal['current_amount']; ?>)">
                                                        <i class="bi bi-pencil me-2"></i>Update
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Goals Section -->
        <?php if (!empty($completedGoals)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Completed Goals</h5>
                        <span class="badge bg-success"><?php echo count($completedGoals); ?> Achieved</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($completedGoals as $goal): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card goal-card bg-light h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h5 class="goal-card-title"><?php echo htmlspecialchars($goal['name']); ?></h5>
                                                <span class="badge bg-success rounded-pill">Completed</span>
                                            </div>
                                            <div class="goal-details mb-3">
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Target</small>
                                                        <strong>₱<?php echo number_format($goal['target_amount'], 2); ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Saved</small>
                                                        <strong class="text-success">₱<?php echo number_format($goal['current_amount'], 2); ?></strong>
                                                    </div>
                                                    <div class="col-12 mt-2">
                                                        <small class="text-muted d-block">Completed On</small>
                                                        <strong><?php echo date('M d, Y', strtotime($goal['target_date'])); ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="progress mb-4">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary w-100" 
                                                    onclick="openArchiveModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['name']); ?>')">
                                                <i class="bi bi-archive me-2"></i>Archive Goal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div> <!-- End of container -->

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addGoalForm">
                    <div class="mb-3">
                        <label for="goal_name" class="form-label">Goal Name</label>
                        <input type="text" class="form-control" id="goal_name" name="goal_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="target_amount" class="form-label">Target Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="target_amount" name="target_amount" 
                                   step="0.01" required min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="target_date" class="form-label">Target Date</label>
                        <input type="date" class="form-control" id="target_date" name="target_date" required>
                    </div>
                    <div class="mb-4">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <?php foreach ($goalCategories as $category => $description): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                        data-bs-toggle="tooltip" 
                                        title="<?php echo htmlspecialchars($description); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_goal" class="btn btn-add w-100">
                        <i class="bi bi-plus-circle me-2"></i>Create Goal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Goal Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateProgressForm" method="POST">
                    <input type="hidden" id="update_goal_id" name="goal_id">
                    <div class="mb-4">
                        <label for="current_amount" class="form-label">Current Amount Saved</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="current_amount" 
                                   name="current_amount" step="0.01" required min="0">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="update_progress" class="btn btn-add">
                            <i class="bi bi-check-circle me-2"></i>Update Progress
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Goal Modal -->
<div class="modal fade" id="deleteGoalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-danger">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Goal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete the goal "<span id="deleteGoalName" class="fw-bold"></span>"?</p>
                <p class="text-danger mb-0 mt-2">
                    <small><i class="bi bi-info-circle me-1"></i>This action cannot be undone.</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteGoalForm" class="d-inline">
                    <input type="hidden" name="goal_id" id="deleteGoalId">
                    <button type="submit" name="delete_goal" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Delete Goal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Archive Goal Modal -->
<div class="modal fade" id="archiveGoalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-archive me-2"></i>Archive Goal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Archive the completed goal "<span id="archiveGoalName" class="fw-bold"></span>"?</p>
                <p class="text-muted mb-0 mt-2">
                    <small><i class="bi bi-info-circle me-1"></i>Archived goals are preserved but won't appear in your active list.</small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="archiveGoalForm" class="d-inline">
                    <input type="hidden" name="goal_id" id="archiveGoalId">
                    <button type="submit" name="archive_goal" class="btn btn-archive">
                        <i class="bi bi-archive me-2"></i>Archive Goal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1080">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-info-circle me-2"></i>
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<script>
// Wait for DOM to be fully loaded before executing any code
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Initialize Bootstrap tooltips
     */
    const initializeTooltips = () => {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    };

    /**
     * Format currency to PHP Peso
     * @param {number} amount - The amount to format
     * @returns {string} Formatted currency string
     */
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    };

    /**
     * Get success message based on type
     * @param {string} type - The type of success message
     * @returns {string} The corresponding success message
     */
    const getSuccessMessage = (type) => {
        const messages = {
            'goal_added': 'Goal added successfully!',
            'goal_archived': 'Goal archived successfully!',
            'progress_updated': 'Goal progress updated successfully!',
            'goal_deleted': 'Goal deleted successfully!'
        };
        return messages[type] || 'Operation completed successfully!';
    };

    /**
     * Show toast notification
     */
    const handleToastNotification = () => {
        const toastElement = document.getElementById('liveToast');
        if (!toastElement) return;

        const toast = new bootstrap.Toast(toastElement);
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success');
        const errorMsg = urlParams.get('error');

        if (successMsg || errorMsg) {
            const toastBody = toastElement.querySelector('.toast-body');
            if (!toastBody) return;

            toastElement.classList.remove('border-primary', 'border-danger');

            if (successMsg) {
                toastBody.textContent = getSuccessMessage(successMsg);
                toastElement.classList.add('border-primary');
            } else if (errorMsg) {
                toastBody.textContent = decodeURIComponent(errorMsg);
                toastElement.classList.add('border-danger');
            }

            toast.show();

            // Clean URL after showing toast
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, '', cleanUrl);
        }
    };

    /**
     * Modal Operation Functions
     */
    // Update Progress Modal
    window.openUpdateModal = function(goalId, currentAmount) {
        const modal = document.getElementById('updateProgressModal');
        const goalIdInput = document.getElementById('update_goal_id');
        const amountInput = document.getElementById('current_amount');

        if (modal && goalIdInput && amountInput) {
            try {
                const modalInstance = new bootstrap.Modal(modal);
                goalIdInput.value = goalId;
                amountInput.value = currentAmount;
                modalInstance.show();
            } catch (error) {
                console.error('Error opening update modal:', error);
            }
        }
    };

    // Delete Goal Modal
    window.openDeleteModal = function(goalId, goalName) {
        const modal = document.getElementById('deleteGoalModal');
        const goalIdInput = document.getElementById('deleteGoalId');
        const goalNameSpan = document.getElementById('deleteGoalName');

        if (modal && goalIdInput && goalNameSpan) {
            try {
                const modalInstance = new bootstrap.Modal(modal);
                goalIdInput.value = goalId;
                goalNameSpan.textContent = goalName;
                modalInstance.show();
            } catch (error) {
                console.error('Error opening delete modal:', error);
            }
        }
    };

    // Archive Goal Modal
    window.openArchiveModal = function(goalId, goalName) {
        const modal = document.getElementById('archiveGoalModal');
        const goalIdInput = document.getElementById('archiveGoalId');
        const goalNameSpan = document.getElementById('archiveGoalName');

        if (modal && goalIdInput && goalNameSpan) {
            try {
                const modalInstance = new bootstrap.Modal(modal);
                goalIdInput.value = goalId;
                goalNameSpan.textContent = goalName;
                modalInstance.show();
            } catch (error) {
                console.error('Error opening archive modal:', error);
            }
        }
    };

    /**
     * Form Validation Setup
     */
    const setupFormValidation = () => {
        const addGoalForm = document.getElementById('addGoalForm');
        if (addGoalForm) {
            // Add form validation
            addGoalForm.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                this.classList.add('was-validated');
            });

            // Validate target amount
            const targetAmountInput = document.getElementById('target_amount');
            if (targetAmountInput) {
                targetAmountInput.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    if (value <= 0) {
                        this.setCustomValidity('Amount must be greater than 0');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        }
    };

    /**
     * Date Input Setup
     */
    const setupDateInput = () => {
        const targetDateInput = document.getElementById('target_date');
        if (targetDateInput) {
            // Set minimum date to today
            const today = new Date();
            const formattedDate = today.toISOString().split('T')[0];
            targetDateInput.setAttribute('min', formattedDate);

            // Validate selected date
            targetDateInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                if (selectedDate < today) {
                    this.setCustomValidity('Date cannot be in the past');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    };

    /**
     * Progress Bar Updates
     */
    const updateProgressBars = () => {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const progress = parseFloat(bar.getAttribute('aria-valuenow'));
            bar.style.width = `${progress}%`;
            
            // Update progress bar color based on percentage
            if (progress >= 75) {
                bar.classList.add('bg-success');
            } else if (progress >= 50) {
                bar.classList.add('bg-warning');
            } else {
                bar.classList.add('bg-danger');
            }
        });
    };

    // Initialize all functions
    try {
        initializeTooltips();
        handleToastNotification();
        setupFormValidation();
        setupDateInput();
        updateProgressBars();
    } catch (error) {
        console.error('Initialization error:', error);
    }

    // Clean up function for tooltips when the page is unloaded
    window.addEventListener('pagehide', function() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(element => {
            const tooltip = bootstrap.Tooltip.getInstance(element);
            if (tooltip) {
                tooltip.dispose();
            }
        });
    });
});
</script>

<?php 
include('includes/footer.php');
ob_end_flush(); // End output buffering and flush the contents
?>