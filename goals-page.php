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
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h4">Goals</h1>
            </div>

            <!-- Balance and Summary Row -->
            <div class="row mb-3">
                <!-- Balance Card -->
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="card balance-card h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h5 class="card-title text-white-50">Balance</h5>
                            <h2 class="card-text display-4">₱<?php echo number_format($currentBalance, 2); ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Goal Summary Card -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <!-- Mobile Header (Visible only on small screens) -->
                        <div class="card-header bg-white d-md-none">
                            <button class="btn btn-link text-decoration-none text-dark p-0 w-100 d-flex justify-content-between align-items-center" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#goalSummaryCollapse" 
                                    aria-expanded="true"
                                    aria-controls="goalSummaryCollapse">
                                <h5 class="mb-0">Goal Summary</h5>
                                <i class="bi bi-chevron-up"></i> <!-- Changed from bi-chevron-down to bi-chevron-up -->
                            </button>
                        </div>

                        <!-- Desktop Header (Visible only on medium screens and up) -->
                        <div class="card-header bg-white d-none d-md-block">
                            <h5 class="mb-0">Goal Summary</h5>
                        </div>

                        <!-- Mobile Content (Collapsible on small screens) -->
                        <div class="collapse show d-md-none" id="goalSummaryCollapse"> <!-- Added 'show' class -->
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 col-sm-3 mb-3">
                                        <strong>Total Goals:</strong> <?php echo $totalGoals; ?>
                                    </div>
                                    <div class="col-6 col-sm-3 mb-3">
                                        <strong>Completed Goals:</strong> <?php echo count($completedGoals); ?>
                                    </div>
                                    <div class="col-6 col-sm-3 mb-3">
                                        <strong>Total Target:</strong> ₱<?php echo number_format($totalTargetAmount, 2); ?>
                                    </div>
                                    <div class="col-6 col-sm-3 mb-3">
                                        <strong>Total Saved:</strong> ₱<?php echo number_format($totalCurrentAmount, 2); ?>
                                    </div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                        style="width: <?php echo $overallProgress; ?>%" 
                                        aria-valuenow="<?php echo $overallProgress; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Content (Always visible on medium screens and up) -->
                        <div class="card-body d-none d-md-block">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <strong>Total Goals:</strong> <?php echo $totalGoals; ?>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <strong>Completed Goals:</strong> <?php echo count($completedGoals); ?>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <strong>Total Target:</strong> ₱<?php echo number_format($totalTargetAmount, 2); ?>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <strong>Total Saved:</strong> ₱<?php echo number_format($totalCurrentAmount, 2); ?>
                                </div>
                            </div>
                            <div class="progress">
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

            <!-- Add New Goal Row -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Add New Goal</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="goal_name" class="form-label">Goal Name</label>
                                    <input type="text" class="form-control" id="goal_name" name="goal_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="target_amount" class="form-label">Target Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="target_amount" name="target_amount" step="0.01" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="target_date" class="form-label">Target Date</label>
                                    <input type="date" class="form-control" id="target_date" name="target_date" required>
                                </div>
                                <div class="mb-4">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <?php foreach ($goalCategories as $category => $description): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($description); ?>">
                                                <?php echo htmlspecialchars($category); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_goal" class="btn btn-custom-primary-rounded w-100">Add Goal</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Goals Section -->
            <div class="card mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Goals</h5>
                    <div class="d-flex">
                        <div class="dropdown me-2">
                            <button class="btn btn-dropdown dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Sort Goals
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                <li><a class="dropdown-item" href="?sort=target_date&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">By Date</a></li>
                                <li><a class="dropdown-item" href="?sort=target_amount&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">By Amount</a></li>
                                <li><a class="dropdown-item" href="?sort=category&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">By Category</a></li>
                            </ul>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-dropdown dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Filter Category
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item" href="?category=">All Categories</a></li>
                                <?php foreach ($goalCategories as $category => $description): ?>
                                    <li><a class="dropdown-item" href="?category=<?php echo urlencode($category); ?>"><?php echo htmlspecialchars($category); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($activeGoals as $goal): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card goal-card h-100">
                                    <div class="card-body">
                                        <h5 class="goal-card-title"><strong><?php echo htmlspecialchars($goal['name']); ?></strong></h5>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($goal['category']); ?></h6>
                                        <p class="card-text">
                                            <strong>Target:</strong> ₱<?php echo number_format($goal['target_amount'], 2); ?><br>
                                            <strong>Current:</strong> ₱<?php echo number_format($goal['current_amount'], 2); ?><br>
                                            <strong>Due:</strong> <?php echo date('M d, Y', strtotime($goal['target_date'])); ?>
                                        </p>
                                        <?php
                                        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                                        $progressBarClass = $progress >= 75 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <div class="progress mb-2">
                                            <div class="progress-bar <?php echo $progressBarClass; ?>" role="progressbar" 
                                                 style="width: <?php echo $progress; ?>%" 
                                                 aria-valuenow="<?php echo $progress; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <p class="text-end mb-4"><strong><?php echo number_format($progress, 1); ?>%</strong> Complete</p>
                                        <div class="d-flex justify-content-between">
                                            <button class="btn btn-sm btn-update" onclick="openUpdateModal(<?php echo $goal['id']; ?>, <?php echo $goal['current_amount']; ?>)">Update Progress</button>
                                            <button class="btn btn-sm btn-delete" onclick="openDeleteModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['name']); ?>')">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Completed Goals Section -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Completed Goals</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($completedGoals as $goal): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card goal-card h-100 bg-light" data-goal-id="<?php echo $goal['id']; ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($goal['name']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($goal['category']); ?></h6>
                                        <p class="card-text">
                                            <strong>Target:</strong> ₱<?php echo number_format($goal['target_amount'], 2); ?><br>
                                            <strong>Saved:</strong> ₱<?php echo number_format($goal['current_amount'], 2); ?><br>
                                            <strong>Completed:</strong> <?php echo date('M d, Y', strtotime($goal['target_date'])); ?>
                                        </p>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: 100%" 
                                                 aria-valuenow="100" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <p class="text-end mb-4"><strong>100%</strong> Complete</p>
                                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="openArchiveModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['name']); ?>')">
                                            Archive
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
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" aria-labelledby="updateProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateProgressModalLabel">Update Goal Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateProgressForm" method="POST">
                    <input type="hidden" id="update_goal_id" name="goal_id">
                    <div class="mb-3">
                        <label for="current_amount" class="form-label">Current Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="current_amount" name="current_amount" step="0.01" required>
                        </div>
                    </div>
                    <button type="submit" name="update_progress" class="btn btn-custom-primary-rounded w-100">Update Progress</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Goal Modal -->
<div class="modal fade" id="deleteGoalModal" tabindex="-1" aria-labelledby="deleteGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteGoalModalLabel">Delete Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the goal "<span id="deleteGoalName"></span>"? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteGoalForm">
                    <input type="hidden" name="goal_id" id="deleteGoalId">
                    <button type="submit" name="delete_goal" class="btn btn-danger">Delete Goal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Archive Goal Modal -->
<div class="modal fade" id="archiveGoalModal" tabindex="-1" aria-labelledby="archiveGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="archiveGoalModalLabel">Archive Completed Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to archive the completed goal "<span id="archiveGoalName"></span>"? It will no longer appear in your list, but will be preserved in the database.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="archiveGoalForm">
                    <input type="hidden" name="goal_id" id="archiveGoalId">
                    <button type="submit" name="archive_goal" class="btn btn-custom-primary-rounded">Archive Goal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast for notifications -->
<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 11">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date for target_date to today
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('target_date').setAttribute('min', today);

    // Initialize Bootstrap tooltips for category descriptions
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Function to show a toast message
    function showToast(message, type) {
    const toastLiveExample = document.getElementById('liveToast');
    if (toastLiveExample) {
        const toast = new bootstrap.Toast(toastLiveExample, {
            animation: true,
            autohide: true,
            delay: 5000
        });

        const toastBody = document.querySelector('.toast-body');
        const toastElement = document.querySelector('.toast');
        
        toastBody.textContent = message;
        toastElement.classList.remove('border-primary', 'border-warning', 'border-danger');
        
        switch (type) {
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
        }
    }

    // Function to get URL parameters
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    // Show success or error message if present in URL parameters
    var successMessage = getUrlParameter('success');
    var errorMessage = getUrlParameter('error');

    if (successMessage) {
    showToast(getSuccessMessage(successMessage), 'primary');
    }

    if (errorMessage) {
    showToast(errorMessage, 'danger');
    }

    // Function to get success message based on the success type
    function getSuccessMessage(successType) {
        switch (successType) {
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

    // Remove success and error parameters from URL
    if (successMessage || errorMessage) {
        var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + window.location.search.replace(/[?&]success=[^&]+/, '').replace(/[?&]error=[^&]+/, '');
        window.history.replaceState({path: newUrl}, '', newUrl);
    }

    // Function to open the delete goal modal
    window.openDeleteModal = function(goalId, goalName) {
        document.getElementById('deleteGoalId').value = goalId;
        document.getElementById('deleteGoalName').textContent = goalName;
        var modal = new bootstrap.Modal(document.getElementById('deleteGoalModal'));
        modal.show();
    }

    // Function to open the archive goal modal
    window.openArchiveModal = function(goalId, goalName) {
        document.getElementById('archiveGoalId').value = goalId;
        document.getElementById('archiveGoalName').textContent = goalName;
        var modal = new bootstrap.Modal(document.getElementById('archiveGoalModal'));
        modal.show();
    }

    // Function to open the update progress modal
    window.openUpdateModal = function(goalId, currentAmount) {
        document.getElementById('update_goal_id').value = goalId;
        document.getElementById('current_amount').value = currentAmount;
        var modal = new bootstrap.Modal(document.getElementById('updateProgressModal'));
        modal.show();
    }

    // Function to update URL parameters
    function updateUrlParams(params) {
        // Preserve existing parameters
        const currentParams = new URLSearchParams(window.location.search);
        for (let [key, value] of currentParams) {
            if (!params.has(key)) {
                params.set(key, value);
            }
        }
       
        // Update URL and reload page
        window.location.search = params.toString();
    }

    // Add event listeners for sorting and filtering
    document.querySelectorAll('#sortDropdown .dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const url = new URL(this.href);
            const params = new URLSearchParams(url.search);
            updateUrlParams(params);
        });
    });

    // Handle collapse icon rotation
    const collapseButton = document.querySelector('[data-bs-toggle="collapse"]');
    if (collapseButton) {
        // Set initial state
        const icon = collapseButton.querySelector('.bi');
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-up');

        collapseButton.addEventListener('click', function() {
            const icon = this.querySelector('.bi');
            if (icon.classList.contains('bi-chevron-down')) {
                icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
            } else {
                icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
            }
        });
    }

    // Handle filter dropdown item clicks
    document.querySelectorAll('#filterDropdown .dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const url = new URL(this.href);
            const params = new URLSearchParams(url.search);
            updateUrlParams(params);
        });
    });
});
</script>

<?php 
include('includes/footer.php');
ob_end_flush(); // End output buffering and flush the contents
?>