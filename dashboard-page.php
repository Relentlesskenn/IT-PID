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

// Function to update or insert the monthly balance
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

// Get current month, year, and set default year
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$defaultYear = date('Y');

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
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <span style="font-size: 1.1rem;">Hello, <?= $_SESSION['auth_user']['username']?>!</span>
            <a class="btn btn-dark btn-sm" href="notifications-page.php"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-bell-fill" viewBox="0 0 16 16">
            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2m.995-14.901a1 1 0 1 0-1.99 0A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901"/>
            </svg></a>
        </div>
        
        <!--Expenses, Incomes, and Balance-->
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
                                <td class="fs-5"><?= number_format($totalExpenses, 2) ?></td>
                                <td class="fs-5"><?= number_format($totalIncomes, 2) ?></td>
                                <td class="fs-5"><?= number_format($balance, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Month and Year selection -->
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
                            echo "<option value='{$i}' {$selected}>{$monthName}</option>";
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
                        $endYear = date('Y') + 1; 
                        for ($i = $startYear; $i <= $endYear; $i++) {
                            $selected = ($i == $currentYear) ? 'selected' : '';
                            echo "<option value='{$i}' {$selected}>{$i}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary" style="font-size: 0.95rem;">View</button>
                </div>
            </form>
        </div>
        
        <!-- Budget Cards -->
        <div class="row row-cols-2 g-4">

        <?php
        // Fetch budget data from the database and calculate the remaining balance
        $userId = $_SESSION['auth_user']['user_id'];
        $sql = "SELECT b.id, b.name, b.amount, b.month, SUM(e.amount) AS total_expenses 
                FROM budgets b 
                LEFT JOIN expenses e ON b.id = e.category_id AND MONTH(e.date) = '$currentMonth' AND YEAR(e.date) = '$currentYear'
                WHERE b.user_id = '$userId' AND b.month = '$currentYear-$currentMonth'
                GROUP BY b.id, b.name, b.amount, b.month";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
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
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="card-title mb-0" style="font-size: 1rem; font-weight: bold;"><?= $budgetName ?></h6>
                            <span style="font-size: 0.8rem;"><?= date('M Y', strtotime($monthCreated)) ?></span>
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
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="card-text text-center">No budgets found for the selected date.</p>
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