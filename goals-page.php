<?php
$page_title = "Goals · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');
include('includes/navbar.php');

// Get the current user ID
$userId = $_SESSION['auth_user']['user_id'];

// Function to get user's current balance
function getCurrentBalance($conn, $userId) {
    $sql = "SELECT SUM(amount) as total_income FROM incomes WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $income = $result->fetch_assoc()['total_income'] ?? 0;

    $sql = "SELECT SUM(amount) as total_expense FROM expenses WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $expense = $result->fetch_assoc()['total_expense'] ?? 0;

    return $income - $expense;
}

// Function to get all goals for a user
function getUserGoals($conn, $userId, $sortBy = 'target_date', $sortOrder = 'ASC') {
    $allowedSortFields = ['name', 'target_amount', 'current_amount', 'target_date', 'category'];
    $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'target_date';
    $sortOrder = $sortOrder === 'DESC' ? 'DESC' : 'ASC';

    $sql = "SELECT * FROM goals WHERE user_id = ? ORDER BY $sortBy $sortOrder";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
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
        $successMessage = "Goal added successfully!";
    } else {
        $errorMessage = "Error adding goal: " . $conn->error;
    }
}

// Handle goal deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_goal'])) {
    $goalId = $_POST['goal_id'];
    $sql = "DELETE FROM goals WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $goalId, $userId);
    
    if ($stmt->execute()) {
        $successMessage = "Goal deleted successfully!";
    } else {
        $errorMessage = "Error deleting goal: " . $conn->error;
    }
}

// Handle goal progress update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_progress'])) {
    $goalId = $_POST['goal_id'];
    $currentAmount = $_POST['current_amount'];
    $sql = "UPDATE goals SET current_amount = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dii", $currentAmount, $goalId, $userId);
    
    if ($stmt->execute()) {
        $successMessage = "Goal progress updated successfully!";
    } else {
        $errorMessage = "Error updating goal progress: " . $conn->error;
    }
}

// Get current balance and goals
$currentBalance = getCurrentBalance($conn, $userId);
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'target_date';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$goals = getUserGoals($conn, $userId, $sortBy, $sortOrder);

// Calculate goal statistics
$totalGoals = $goals->num_rows;
$completedGoals = 0;
$totalTargetAmount = 0;
$totalCurrentAmount = 0;

while ($goal = $goals->fetch_assoc()) {
    $totalTargetAmount += $goal['target_amount'];
    $totalCurrentAmount += $goal['current_amount'];
    if ($goal['current_amount'] >= $goal['target_amount']) {
        $completedGoals++;
    }
}
$goals->data_seek(0); // Reset the result pointer

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
            <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4">Goals</h1>
            </div>

            <!-- Goal Summary -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Goal Summary</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <strong>Total Goals:</strong> <?php echo $totalGoals; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Completed Goals:</strong> <?php echo $completedGoals; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Total Target:</strong> ₱<?php echo number_format($totalTargetAmount, 2); ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Total Saved:</strong> ₱<?php echo number_format($totalCurrentAmount, 2); ?>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $overallProgress; ?>%" aria-valuenow="<?php echo $overallProgress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <!-- Display current balance -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="card balance-card h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h5 class="card-title text-white-50">Current Balance</h5>
                            <h2 class="card-text display-4">₱<?php echo number_format($currentBalance, 2); ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Add new goal form -->
                <div class="col-md-6">
                    <div class="card h-100">
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
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <?php foreach ($goalCategories as $category => $description): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($description); ?>">
                                                <?php echo htmlspecialchars($category); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_goal" class="btn btn-custom-primary w-100">Add Goal</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display existing goals -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Your Goals</h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-custom dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Sort Goals
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item" href="?sort=target_date&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">By Date</a></li>
                            <li><a class="dropdown-item" href="?sort=target_amount&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">By Amount</a></li>
                            <li><a class="dropdown-item" href="?sort=category&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">By Category</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php while ($goal = $goals->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card goal-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($goal['name']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($goal['category']); ?></h6>
                                        <p class="card-text">
                                            <strong>Target:</strong> ₱<?php echo number_format($goal['target_amount'], 2); ?><br>
                                            <strong>Current:</strong> ₱<?php echo number_format($goal['current_amount'], 2); ?><br>
                                            <strong>Due:</strong> <?php echo date('M d, Y', strtotime($goal['target_date'])); ?>
                                        </p>
                                        <?php
                                        $progress = ($goal['current_amount'] / $goal['target_amount']) * 100;
                                        $progressBarClass = $progress >= 100 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <div class="progress mb-2">
                                            <div class="progress-bar <?php echo $progressBarClass; ?>" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="text-end mb-2"><strong><?php echo number_format($progress, 1); ?>%</strong> Complete</p>
                                        <div class="d-flex justify-content-between">
                                            <button class="btn btn-sm btn-outline-custom" onclick="openUpdateModal(<?php echo $goal['id']; ?>, <?php echo $goal['current_amount']; ?>)">Update Progress</button>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this goal?');">
                                                <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                                <button type="submit" name="delete_goal" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" aria-labelledby="updateProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
                    <button type="submit" name="update_progress" class="btn btn-custom-primary w-100">Update Progress</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast for notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
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

    // Initialize toast
    var toastElList = [].slice.call(document.querySelectorAll('.toast'))
    var toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl)
    });

    // Show toast message if exists
    <?php if (isset($successMessage)): ?>
        showToast("<?php echo $successMessage; ?>", 'success');
    <?php endif; ?>
    <?php if (isset($errorMessage)): ?>
        showToast("<?php echo $errorMessage; ?>", 'danger');
    <?php endif; ?>
});

function openUpdateModal(goalId, currentAmount) {
    document.getElementById('update_goal_id').value = goalId;
    document.getElementById('current_amount').value = currentAmount;
    var modal = new bootstrap.Modal(document.getElementById('updateProgressModal'));
    modal.show();
}

function showToast(message, type) {
    var toastEl = document.getElementById('liveToast');
    var toast = new bootstrap.Toast(toastEl);
    toastEl.querySelector('.toast-body').textContent = message;
    toastEl.classList.remove('bg-success', 'bg-danger');
    toastEl.classList.add('bg-' + type);
    toast.show();
}
</script>

<?php include('includes/footer.php') ?>