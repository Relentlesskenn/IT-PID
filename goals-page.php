<?php
ob_start(); // Start output buffering
$page_title = "Goals · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');
include('includes/navbar.php');

// Get the current user ID
$userId = $_SESSION['auth_user']['user_id'];

// Check if user is subscribed
require_once('includes/SubscriptionHelper.php');
$subscriptionHelper = new SubscriptionHelper($conn);
$hasActiveSubscription = $subscriptionHelper->hasActiveSubscription($_SESSION['auth_user']['user_id']);

// Check subscription status
$subscriptionHelper = new SubscriptionHelper($conn);
$hasActiveSubscription = $subscriptionHelper->hasActiveSubscription($userId);

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

// Function to check if a user has available balance
function hasAvailableBalance($conn, $userId) {
    $currentBalance = getCurrentBalance($conn, $userId);
    return $currentBalance > 0;
}

function validateGoalAmount($conn, $userId, $amount, $currentGoalId = null) {
    $currentBalance = getCurrentBalance($conn, $userId);
    
    // If updating existing goal, add current goal amount to available balance
    if ($currentGoalId) {
        $sql = "SELECT current_amount FROM goals WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $currentGoalId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentAmount = $result->fetch_assoc()['current_amount'] ?? 0;
        $currentBalance += $currentAmount;
    }
    
    return $amount <= $currentBalance && $amount > 0;
}

// Function to get all goals for a user
function getUserGoals($conn, $userId, $sortBy = 'target_date', $sortOrder = 'ASC', $category = null) {
    $allowedSortFields = ['name', 'target_amount', 'current_amount', 'target_date', 'category'];
    $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'target_date';
    $sortOrder = $sortOrder === 'DESC' ? 'DESC' : 'ASC';

    // Base SQL query
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

// Function to check goals nearing due date
function checkGoalsDueDate($conn, $userId) {
    $alerts = array();
    
    // Get current date without time component
    $currentDate = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $currentDate->setTime(0, 0, 0); // Set time to start of day
    
    // Get all active goals
    $sql = "SELECT id, name, target_date, target_amount, current_amount 
            FROM goals 
            WHERE user_id = ? 
            AND is_archived = FALSE 
            AND current_amount < target_amount";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($goal = $result->fetch_assoc()) {
        // Create target date object and set to start of day
        $targetDate = new DateTime($goal['target_date'], new DateTimeZone('Asia/Manila'));
        $targetDate->setTime(0, 0, 0);
        
        // Calculate days difference
        $interval = $currentDate->diff($targetDate);
        $daysRemaining = $interval->days;
        $isInFuture = $targetDate > $currentDate;
        
        // Calculate progress percentage
        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
        
        // Alert conditions with corrected logic
        if (!$isInFuture) {
            // Goal is overdue
            $alerts[] = array(
                'type' => 'danger',
                'message' => sprintf(
                    "Goal '%s' is overdue! Target: ₱%s, Current: ₱%s (%.1f%%)",
                    $goal['name'],
                    number_format($goal['target_amount'], 2),
                    number_format($goal['current_amount'], 2),
                    $progress
                )
            );
        } elseif ($daysRemaining == 0) {
            // Due today
            $alerts[] = array(
                'type' => 'warning',
                'message' => sprintf(
                    "Goal '%s' is due today! Target: ₱%s, Current: ₱%s (%.1f%%)",
                    $goal['name'],
                    number_format($goal['target_amount'], 2),
                    number_format($goal['current_amount'], 2),
                    $progress
                )
            );
        } elseif ($daysRemaining == 1) {
            // Due tomorrow
            $alerts[] = array(
                'type' => 'warning',
                'message' => sprintf(
                    "Goal '%s' is due tomorrow! Target: ₱%s, Current: ₱%s (%.1f%%)",
                    $goal['name'],
                    number_format($goal['target_amount'], 2),
                    number_format($goal['current_amount'], 2),
                    $progress
                )
            );
        } elseif ($daysRemaining <= 7) {
            // Due within a week
            $alerts[] = array(
                'type' => 'warning',
                'message' => sprintf(
                    "Goal '%s' is due in %d days! Target: ₱%s, Current: ₱%s (%.1f%%)",
                    $goal['name'],
                    $daysRemaining,
                    number_format($goal['target_amount'], 2),
                    number_format($goal['current_amount'], 2),
                    $progress
                )
            );
        } elseif ($daysRemaining <= 30) {
            // Due within a month
            $alerts[] = array(
                'type' => 'primary',
                'message' => sprintf(
                    "Goal '%s' is due in %d days. Target: ₱%s, Current: ₱%s (%.1f%%)",
                    $goal['name'],
                    $daysRemaining,
                    number_format($goal['target_amount'], 2),
                    number_format($goal['current_amount'], 2),
                    $progress
                )
            );
        }
    }
    
    return $alerts;
}

// Handle form submission for adding a new goal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_goal'])) {
    $name = $_POST['goal_name'];
    $targetAmount = $_POST['target_amount'];
    $targetDate = $_POST['target_date'];
    $category = $_POST['category'];

    // Validate the amount
    if (!validateGoalAmount($conn, $userId, $targetAmount)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Invalid amount: Exceeds available balance"));
        exit();
    }

    $sql = "INSERT INTO goals (user_id, name, target_amount, target_date, category) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $userId, $name, $targetAmount, $targetDate, $category);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=goal_added");
        exit();
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error adding goal: " . $conn->error));
        exit();
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
    
    // Validate the amount
    if (!validateGoalAmount($conn, $userId, $newAmount, $goalId)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Invalid amount: Exceeds available balance"));
        exit();
    }
    
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

// Handle due goal actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['handle_due_goal'])) {
    $goalId = $_POST['due_goal_id'];
    $action = $_POST['due_action'];
    
    switch ($action) {
        case 'extend':
            $newDate = $_POST['new_target_date'];
            $sql = "UPDATE goals SET 
                    target_date = ?, 
                    status = 'active',
                    due_action = 'extended',
                    action_date = NOW() 
                    WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $newDate, $goalId, $userId);
            break;
            
        case 'adjust':
            $newAmount = $_POST['new_target_amount'];
            $sql = "UPDATE goals SET 
                    target_amount = ?, 
                    status = 'active',
                    due_action = 'adjusted',
                    action_date = NOW() 
                    WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dii", $newAmount, $goalId, $userId);
            break;
            
        case 'complete':
            $sql = "UPDATE goals SET 
                    status = 'completed',
                    due_action = 'completed',
                    action_date = NOW() 
                    WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $goalId, $userId);
            break;
            
        case 'archive':
            $sql = "UPDATE goals SET 
                    status = 'archived',
                    is_archived = TRUE,
                    due_action = 'archived',
                    action_date = NOW() 
                    WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $goalId, $userId);
            break;
    }
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=due_action_handled");
        exit();
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Error handling due goal: " . $conn->error));
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_progress'])) {
    // Check balance first
    if (!hasAvailableBalance($conn, $userId)) {
        if (!headers_sent()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Insufficient balance to update goals."));
            exit();
        } else {
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "?error=" . urlencode("Insufficient balance to update goals."). "';</script>";
            exit();
        }
    }

    $goalId = $_POST['goal_id'];
    $newAmount = $_POST['current_amount'];
    
    // Proceed with the update only if there's sufficient balance
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

// Check if the user has available balance
$hasBalance = hasAvailableBalance($conn, $userId);

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

// Get due date alerts
$dueDateAlerts = checkGoalsDueDate($conn, $userId);

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

        <!-- Ad Section -->
        <?php
        require_once 'includes/Advertisement.php';
        if (!$hasActiveSubscription) {
            echo Advertisement::render('banner', 'center');
        }
        ?>

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

                                                <?php if (strtotime($goal['target_date']) < strtotime('today') && $goal['status'] === 'active'): ?>
                                                    <div class="overdue-alert">
                                                        <div class="overdue-content">
                                                            <div class="overdue-icon">
                                                                <i class="bi bi-exclamation-circle-fill"></i>
                                                            </div>
                                                            <div class="overdue-text">
                                                                <p class="mb-0">Goal Overdue</p>
                                                                <small>Take action to update this goal</small>
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-overdue" 
                                                                onclick="openDueGoalModal(<?php echo $goal['id']; ?>, 
                                                                        '<?php echo htmlspecialchars($goal['name']); ?>', 
                                                                        <?php echo $goal['current_amount']; ?>, 
                                                                        <?php echo $goal['target_amount']; ?>)">
                                                            <i class="bi bi-arrow-right"></i>
                                                        </button>
                                                    </div>
                                                <?php endif; ?>

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
                                                    <button class="btn btn-update <?php echo !$hasBalance ? 'disabled' : ''; ?>" 
                                                            onclick="openUpdateModal(<?php echo $goal['id']; ?>, <?php echo $goal['current_amount']; ?>)"
                                                            <?php if (!$hasBalance): ?>
                                                            data-bs-toggle="tooltip" 
                                                            title="Insufficient balance to update goals"
                                                            <?php endif; ?>>
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
<div class="modal fade" id="addGoalModal" tabindex="-1" role="dialog" aria-labelledby="addGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-semibold" id="addGoalModalLabel">Create New Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4">
                <form method="POST" id="addGoalForm">
                    <!-- Goal Name -->
                    <div class="mb-4">
                        <label for="goal_name" class="form-label text-secondary fw-medium">Goal Name</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control form-control-lg border-2" 
                                   id="goal_name" 
                                   name="goal_name" 
                                   placeholder="Enter goal name"
                                   required>
                            <span class="input-group-text border-2 bg-white">
                                <i class="bi bi-flag" aria-hidden="true"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Target Amount -->
                    <div class="mb-4">
                        <label for="target_amount" class="form-label text-secondary fw-medium">Target Amount</label>
                        <div class="input-group">
                            <span class="input-group-text border-2 bg-white fw-medium">₱</span>
                            <input type="number" 
                                class="form-control form-control-lg border-2" 
                                id="target_amount" 
                                name="target_amount" 
                                placeholder="0.00"
                                step="0.01" 
                                required 
                                min="0">
                            <button class="input-group-text border-2 bg-white" type="button" aria-label="Copy amount">
                                <i class="bi bi-wallet2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Available Balance Display -->
                    <div class="d-flex align-items-center justify-content-between mb-4 py-3 border-top">
                        <span class="text-secondary fw-medium">Available Balance:</span>
                        <span class="text-primary fw-semibold">₱<?php echo number_format($currentBalance, 2); ?></span>
                    </div>

                    <!-- Target Date -->
                    <div class="mb-4">
                        <label for="target_date" class="form-label text-secondary fw-medium">Target Date</label>
                        <div class="input-group">
                            <input type="date" 
                                   class="form-control form-control-lg border-2" 
                                   id="target_date" 
                                   name="target_date" 
                                   placeholder="dd/mm/yyyy"
                                   required>
                            <span class="input-group-text border-2 bg-white">
                                <i class="bi bi-calendar"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="mb-4">
                        <label for="category" class="form-label text-secondary fw-medium">Category</label>
                        <div class="input-group">
                            <select class="form-select form-select-lg border-2" 
                                    id="category" 
                                    name="category" 
                                    required>
                                <?php foreach ($goalCategories as $category => $description): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" 
                                            data-bs-toggle="tooltip" 
                                            title="<?php echo htmlspecialchars($description); ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="input-group-text border-2 bg-white">
                                <i class="bi bi-tag"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            name="add_goal" 
                            class="btn btn-lg btn-add w-100 py-3">
                        <i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Create Goal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" role="dialog" aria-labelledby="updateProgressModalLabel">
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

<!-- Goal Due Action Modal -->
<div class="modal fade" id="goalDueActionModal" tabindex="-1" role="dialog" aria-labelledby="goalDueActionModalLabel">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="goalDueActionModalLabel">
                    <span class="modal-icon">
                        <i class="bi bi-clock-history"></i> Goal Action Required
                    </span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="goalDueActionForm" method="POST">
                    <input type="hidden" id="due_goal_id" name="due_goal_id">
                    
                    <!-- Goal Status Card -->
                    <div class="goal-status-card">
                        <div class="status-header">
                            <i class="bi bi-exclamation-diamond"></i>
                            <h6 id="dueGoalName"></h6>
                        </div>
                        <div class="status-details">
                            <div class="status-item">
                                <span class="status-label">Progress</span>
                                <span class="status-value"><span id="dueGoalProgress"></span>%</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Amount Saved</span>
                                <span class="status-value">₱<span id="dueGoalAmount"></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Selection -->
                    <div class="action-selection">
                        <h6 class="section-title">Choose an Action</h6>
                        
                        <!-- Extend Deadline Option -->
                        <div class="action-option">
                            <input class="form-check-input" type="radio" name="due_action" id="extend" value="extend">
                            <label class="action-label" for="extend">
                                <div class="action-icon">
                                    <i class="bi bi-calendar-plus"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Extend Deadline</span>
                                    <span class="action-description">Set a new target date for this goal</span>
                                </div>
                            </label>
                            <div class="action-detail extend-detail">
                                <input type="date" class="form-control" name="new_target_date">
                            </div>
                        </div>

                        <!-- Adjust Amount Option -->
                        <div class="action-option">
                            <input class="form-check-input" type="radio" name="due_action" id="adjust" value="adjust">
                            <label class="action-label" for="adjust">
                                <div class="action-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Adjust Amount</span>
                                    <span class="action-description">Modify the target amount</span>
                                </div>
                            </label>
                            <div class="action-detail adjust-detail">
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" name="new_target_amount" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Complete Option -->
                        <div class="action-option">
                            <input class="form-check-input" type="radio" name="due_action" id="complete" value="complete">
                            <label class="action-label" for="complete">
                                <div class="action-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Mark as Complete</span>
                                    <span class="action-description">Complete goal with current amount</span>
                                </div>
                            </label>
                        </div>

                        <!-- Archive Option -->
                        <div class="action-option">
                            <input class="form-check-input" type="radio" name="due_action" id="archive" value="archive">
                            <label class="action-label" for="archive">
                                <div class="action-icon">
                                    <i class="bi bi-archive"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Archive Goal</span>
                                    <span class="action-description">Move to archived goals</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="handle_due_goal" class="btn btn-confirm">
                            Confirm Action
                        </button>
                    </div>
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
// Get PHP variables
const hasAvailableBalance = <?php echo json_encode($hasBalance); ?>;
const availableBalance = <?php echo json_encode($currentBalance); ?>;

// Wait for DOM to be fully loaded before executing any code
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Format currency for display
     * @param {number} amount 
     * @returns {string}
     */
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    };

    /**
     * Validate amount against available balance
     * @param {HTMLInputElement} input 
     * @param {number} currentAmount 
     * @returns {boolean}
     */
    const validateAmount = (input, currentAmount = 0) => {
        const value = parseFloat(input.value) || 0;
        const maxAllowed = availableBalance + currentAmount;
        
        if (value > maxAllowed) {
            input.setCustomValidity(`Amount cannot exceed available balance of ${formatCurrency(maxAllowed)}`);
            showToast(`Cannot exceed available balance of ${formatCurrency(maxAllowed)}`, 'danger');
            return false;
        } else if (value <= 0) {
            input.setCustomValidity('Amount must be greater than 0');
            return false;
        } else {
            input.setCustomValidity('');
            return true;
        }
    };

    /**
     * Initialize Bootstrap tooltips
     */
    const initializeTooltips = () => {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length > 0) {
            tooltipTriggerList.forEach(element => {
                new bootstrap.Tooltip(element);
            });
        }
    };

    /**
     * Show toast notification
     * @param {string} message - Message to display
     * @param {string} type - Type of toast (primary/danger/warning)
     */
    const showToast = (message, type = 'primary') => {
        const toastElement = document.getElementById('liveToast');
        if (!toastElement) return;

        const toastBody = toastElement.querySelector('.toast-body');
        const toastHeader = toastElement.querySelector('.toast-header strong');
        
        if (toastBody && toastHeader) {
            toastBody.textContent = message;
            toastHeader.textContent = type === 'danger' ? 'Error' : 'Notification';
            
            toastElement.classList.remove('border-primary', 'border-danger', 'border-warning');
            toastElement.classList.add(`border-${type}`);
            
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
        }
    };

    /**
     * Handle toast notifications from URL parameters
     */
    const handleToastNotification = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success');
        const errorMsg = urlParams.get('error');

        if (successMsg) {
            showToast(getSuccessMessage(successMsg), 'primary');
        } else if (errorMsg) {
            showToast(decodeURIComponent(errorMsg), 'danger');
        }

        // Clean URL after showing toast
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, '', cleanUrl);
    };

    /**
     * Get success message based on type
     */
    const getSuccessMessage = (type) => {
        const messages = {
            'goal_added': 'Goal added successfully!',
            'goal_archived': 'Goal archived successfully!',
            'progress_updated': 'Goal progress updated successfully!',
            'goal_deleted': 'Goal deleted successfully!',
            'due_action_handled': 'Goal due date action completed successfully!'
        };
        return messages[type] || 'Operation completed successfully!';
    };

    /**
     * Show due date alerts
     */
    function showDueDateAlerts(alerts) {
        if (!alerts || alerts.length === 0) return;
        
        let currentAlertIndex = 0;
        
        const showNextAlert = () => {
            if (currentAlertIndex >= alerts.length) return;
            
            const alert = alerts[currentAlertIndex];
            showToast(alert.message, alert.type);
            
            currentAlertIndex++;
            setTimeout(showNextAlert, 6000);
        };
        
        setTimeout(showNextAlert, 1000);
    }

    /**
     * Setup update form with balance validation
     */
    const setupUpdateForm = () => {
        const updateForm = document.getElementById('updateProgressForm');
        const amountInput = document.getElementById('current_amount');
        let originalAmount = 0;

        if (updateForm && amountInput) {
            // Store original amount when modal opens
            $('#updateProgressModal').on('show.bs.modal', function(e) {
                originalAmount = parseFloat(amountInput.value) || 0;
                
                // Set max attribute
                const maxAllowed = availableBalance + originalAmount;
                amountInput.setAttribute('max', maxAllowed);
                
                // Add helper text
                const helpText = document.createElement('small');
                helpText.className = 'form-text text-muted mt-1';
                helpText.textContent = `Maximum allowed: ${formatCurrency(maxAllowed)}`;
                amountInput.parentNode.appendChild(helpText);
            });

            // Clean up when modal closes
            $('#updateProgressModal').on('hidden.bs.modal', function() {
                const helpText = amountInput.parentNode.querySelector('.form-text');
                if (helpText) {
                    helpText.remove();
                }
            });

            // Real-time validation
            amountInput.addEventListener('input', function() {
                validateAmount(this, originalAmount);
                updateForm.classList.add('was-validated');
            });

            // Form submission validation
            updateForm.addEventListener('submit', function(e) {
                if (!validateAmount(amountInput, originalAmount)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }
    };

    /**
     * Setup add goal form with balance validation
     */
    const setupAddGoalForm = () => {
        const addForm = document.getElementById('addGoalForm');
        const amountInput = document.getElementById('target_amount');

        if (addForm && amountInput) {
            // Set max attribute
            amountInput.setAttribute('max', availableBalance);
            
            // Add helper text
            const helpText = document.createElement('small');
            helpText.className = 'form-text text-muted mt-1';
            amountInput.parentNode.appendChild(helpText);

            // Real-time validation
            amountInput.addEventListener('input', function() {
                validateAmount(this);
                addForm.classList.add('was-validated');
            });

            // Form submission validation
            addForm.addEventListener('submit', function(e) {
                if (!validateAmount(amountInput)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }
    };

    /**
     * Modal Operation Functions
     */
    // Update Progress Modal
    window.openUpdateModal = function(goalId, currentAmount) {
        if (!hasAvailableBalance) {
            showToast("Cannot update goals: Insufficient balance", "danger");
            return;
        }

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

    // Due Goal Modal
    window.openDueGoalModal = function(goalId, goalName, currentAmount, targetAmount) {
        const modal = document.getElementById('goalDueActionModal');
        const goalIdInput = document.getElementById('due_goal_id');
        const goalNameSpan = document.getElementById('dueGoalName');
        const progressSpan = document.getElementById('dueGoalProgress');
        const amountSpan = document.getElementById('dueGoalAmount');
        
        if (modal && goalIdInput && goalNameSpan && progressSpan && amountSpan) {
            try {
                const progress = ((currentAmount / targetAmount) * 100).toFixed(1);
                
                goalIdInput.value = goalId;
                goalNameSpan.textContent = goalName;
                progressSpan.textContent = progress;
                amountSpan.textContent = currentAmount.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
            } catch (error) {
                console.error('Error opening due goal modal:', error);
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
        if (progressBars.length > 0) {
            progressBars.forEach(bar => {
                const progress = parseFloat(bar.getAttribute('aria-valuenow') || '0');
                bar.style.width = `${progress}%`;
                
                bar.classList.remove('bg-success', 'bg-warning', 'bg-danger');
                if (progress >= 75) {
                    bar.classList.add('bg-success');
                } else if (progress >= 50) {
                    bar.classList.add('bg-warning');
                } else {
                    bar.classList.add('bg-danger');
                }
            });
        }
    };

    /**
     * Setup Due Goal Action Form
     */
    const setupDueGoalActionForm = () => {
        const dueActionForm = document.getElementById('goalDueActionForm');
        if (dueActionForm) {
            const actionInputs = dueActionForm.querySelectorAll('input[name="due_action"]');
            const actionDetails = dueActionForm.querySelectorAll('.action-detail');

            // Handle radio button changes
            actionInputs.forEach(radio => {
                radio.addEventListener('change', function() {
                    actionDetails.forEach(detail => detail.style.display = 'none');
                    const detailSection = dueActionForm.querySelector(`.${this.value}-detail`);
                    if (detailSection) {
                        detailSection.style.display = 'block';
                    }
                });
            });

            // Form validation
            dueActionForm.addEventListener('submit', function(e) {
                const selectedAction = this.querySelector('input[name="due_action"]:checked');
                
                if (!selectedAction) {
                    e.preventDefault();
                    showToast('Please select an action.', 'danger');
                    return;
                }

                // Validate specific action requirements
                switch (selectedAction.value) {
                    case 'extend':
                        const dateInput = this.querySelector('input[name="new_target_date"]');
                        if (!dateInput || !dateInput.value) {
                            e.preventDefault();
                            showToast('Please select a new target date.', 'danger');
                        }
                        break;
                    case 'adjust':
                        const amountInput = this.querySelector('input[name="new_target_amount"]');
                        if (!amountInput || !amountInput.value || amountInput.value <= 0) {
                            e.preventDefault();
                            showToast('Please enter a valid target amount.', 'danger');
                        }
                        break;
                }
            });
        }
    };

    /**
     * Setup Update Button States
     */
    const setupUpdateButtons = () => {
        if (!hasAvailableBalance) {
            const updateButtons = document.querySelectorAll('.btn-update');
            updateButtons.forEach(button => {
                button.classList.add('disabled');
                button.setAttribute('title', 'Insufficient balance to update goals');
                new bootstrap.Tooltip(button);
            });
        }
    };

    // Initialize all functions with error handling
    try {
        initializeTooltips();
        handleToastNotification();
        setupAddGoalForm();
        setupUpdateForm();
        setupDateInput();
        updateProgressBars();
        setupDueGoalActionForm();
        setupUpdateButtons();

        // Show due date alerts after other notifications
        const dueDateAlerts = <?php echo json_encode($dueDateAlerts ?? []); ?>;
        if (dueDateAlerts && dueDateAlerts.length > 0) {
            setTimeout(() => {
                showDueDateAlerts(dueDateAlerts);
            }, 6000);
        }
    } catch (error) {
        console.error('Initialization error:', error);
    }

    // Clean up tooltips when the page is unloaded
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