<?php
$page_title = "Dashboard";
include('_dbconnect.php');
include('authentication.php');
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

// Get current month and year
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = date('Y');

$userId = $_SESSION['auth_user']['user_id'];
$totalExpenses = getExpensesTotal($userId, $currentMonth, $currentYear);
$totalIncomes = getIncomesTotal($userId, $currentMonth, $currentYear);
$balance = $totalIncomes - $totalExpenses;

// Store or update the monthly balance
$balance = getOrUpdateMonthlyBalance($userId, $currentMonth, $currentYear, $balance);

?>

<!-- HTML -->
<div class="py-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <span>Hello, <?= $_SESSION['auth_user']['username']?>!</span>
            <a class="btn btn-dark btn-sm" href="notifications-page.php"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-bell-fill" viewBox="0 0 16 16">
            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2m.995-14.901a1 1 0 1 0-1.99 0A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901"/>
            </svg></a>
        </div>

        <div class="card my-3 text-center">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th scope="col">Expenses</th>
                                <th scope="col">Income</th>
                                <th scope="col">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= number_format($totalExpenses, 2) ?></td>
                                <td><?= number_format($totalIncomes, 2) ?></td>
                                <td><?= number_format($balance, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Month selection -->
        <div class="mb-3">
            <form action="" method="GET" class="d-flex align-items-center">
                <label for="month" class="me-2">Select Month:</label>
                <select name="month" id="month" class="form-select me-2" style="width: auto;">
                    <?php
                    for ($i = 1; $i <= 12; $i++) {
                        $monthName = date('F', mktime(0, 0, 0, $i, 1));
                        $selected = ($i == $currentMonth) ? 'selected' : '';
                        echo "<option value='{$i}' {$selected}>{$monthName}</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="btn btn-primary">View</button>
            </form>
        </div>
        
        <!-- Budget Cards -->
        <div class="row row-cols-2 g-4">
        <?php
        // Fetch budget data from the database
        $userId = $_SESSION['auth_user']['user_id'];
        $sql = "SELECT b.id, b.name, b.amount, b.date, SUM(e.amount) AS total_expenses 
                FROM budgets b 
                LEFT JOIN expenses e ON b.id = e.category_id AND MONTH(e.date) = '$currentMonth' AND YEAR(e.date) = '$currentYear'
                WHERE b.user_id = '$userId' AND MONTH(b.date) = '$currentMonth' AND YEAR(b.date) = '$currentYear'
                GROUP BY b.id, b.name, b.amount, b.date";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $budgetId = $row['id'];
                $budgetName = $row['name'];
                $budgetAmount = $row['amount'];
                $monthCreated = $row['date'];
                $totalExpenses = $row['total_expenses'] ?? 0;
                $remainingBalance = $budgetAmount - $totalExpenses;
                $percentageUsed = ($totalExpenses / $budgetAmount) * 100;
        ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="card-title mb-0" style="font-size: 1rem; font-weight: bold;"><?= $budgetName ?></h6>
                            <span style="font-size: 0.8rem;"><?= date('M', strtotime($monthCreated)) ?></span>
                        </div>
                            <div class="budget-info">
                                <p class="card-text mb-0" style="font-size: 0.85rem; line-height: 1.6;">Budget - ₱<?= number_format($budgetAmount, 2) ?></p>
                                <p class="card-text mb-0" style="font-size: 0.85rem; line-height: 1.6;">Spent - ₱<?= number_format($totalExpenses, 2) ?></p>
                                <p class="card-text mb-1" style="font-size: 0.85rem; line-height: 1.6;">Remaining - ₱<?= number_format($remainingBalance, 2) ?></p>
                            </div>
                            <div class="progress mt-auto">
                                <div class="progress-bar <?php if ($percentageUsed >= 90) { echo 'bg-danger'; } elseif ($percentageUsed >= 70) { echo 'bg-warning'; } else { echo 'bg-success'; } ?>" 
                                    role="progressbar" 
                                    style="width: <?= $percentageUsed ?>%;" 
                                    aria-valuenow="<?= $percentageUsed ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    <?= number_format($percentageUsed, 1) ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <?php
            }
        } else {
        ?>
            <div class="col">
                <div class="card mt-3">
                    <div class="card-body">
                        <p class="card-text">No budgets found for the selected month.</p>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
        </div>

    </div>
</div>

<?php include('includes/footer.php') ?>