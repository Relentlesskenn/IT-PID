<?php
ob_start(); // Start output buffering
require_once('vendor/tecnickcom/tcpdf/tcpdf.php');
$page_title = "Reports · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');
include('includes/navbar.php');

$userId = $_SESSION['auth_user']['user_id'];

// Get the current date, month, and year
$currentDate = date('Y-m-d');
$currentMonth = date('Y-m');
$currentYear = date('Y');

// Get user's creation year
$userCreationYear = getUserCreationYear($conn, $userId);

// Check if date, month, and year are set in GET parameters
$selectedDate = isset($_GET['date']) ? filter_input(INPUT_GET, 'date') : $currentDate;
$selectedMonth = isset($_GET['month']) ? filter_input(INPUT_GET, 'month') : $currentMonth;
$selectedYear = isset($_GET['year']) ? filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) : $currentYear;

// Check if the view type is set (daily, monthly, or yearly)
$viewType = isset($_GET['view']) ? filter_input(INPUT_GET, 'view') : 'daily';

// Pagination variables
$perPage = 10; // Number of expenses per page
$page = isset($_GET['page']) ? filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]) : 1;
$offset = ($page - 1) * $perPage; // Calculate the offset for the SQL query

// Function to get user's creation year
function getUserCreationYear($conn, $userId) {
    $sql = "SELECT YEAR(created_at) as creation_year FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        error_log("Database error: " . $stmt->error);
        return date('Y'); // Return current year as fallback
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['creation_year'] ?? date('Y');
}

// Function to get expenses based on view type
function getExpenses($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear, $offset = null, $perPage = null) {
    switch ($viewType) {
        case 'daily':
            $sql = "SELECT e.category_id, e.amount, e.date, e.comment 
                    FROM expenses e 
                    WHERE e.user_id = ? AND DATE(e.date) = ?
                    ORDER BY e.date DESC";
            if ($offset !== null && $perPage !== null) {
                $sql .= " LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isii", $userId, $selectedDate, $perPage, $offset);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $userId, $selectedDate);
            }
            break;
        case 'monthly':
            $monthStart = date('Y-m-01', strtotime($selectedMonth));
            $monthEnd = date('Y-m-t', strtotime($selectedMonth));
            $sql = "SELECT e.category_id, e.amount, e.date, e.comment 
                    FROM expenses e 
                    WHERE e.user_id = ? AND e.date BETWEEN ? AND ?
                    ORDER BY e.date DESC";
            if ($offset !== null && $perPage !== null) {
                $sql .= " LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issii", $userId, $monthStart, $monthEnd, $perPage, $offset);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $userId, $monthStart, $monthEnd);
            }
            break;
        case 'yearly':
            $sql = "SELECT e.category_id, e.amount, e.date, e.comment 
                    FROM expenses e 
                    WHERE e.user_id = ? AND YEAR(e.date) = ?
                    ORDER BY e.date DESC";
            if ($offset !== null && $perPage !== null) {
                $sql .= " LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiii", $userId, $selectedYear, $perPage, $offset);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $userId, $selectedYear);
            }
            break;
    }
    if (!$stmt->execute()) {
        error_log("Database error: " . $stmt->error);
        return false;
    }
    return $stmt->get_result();
}

// Function to get total number of expenses
function getTotalExpenses($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear) {
    switch ($viewType) {
        case 'daily':
            $sql = "SELECT COUNT(*) AS total 
                    FROM expenses e 
                    WHERE e.user_id = ? AND DATE(e.date) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $userId, $selectedDate);
            break;
        case 'monthly':
            $monthStart = date('Y-m-01', strtotime($selectedMonth));
            $monthEnd = date('Y-m-t', strtotime($selectedMonth));
            $sql = "SELECT COUNT(*) AS total 
                    FROM expenses e 
                    WHERE e.user_id = ? AND e.date BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $userId, $monthStart, $monthEnd);
            break;
        case 'yearly':
            $sql = "SELECT COUNT(*) AS total 
                    FROM expenses e 
                    WHERE e.user_id = ? AND YEAR(e.date) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $selectedYear);
            break;
    }
    if (!$stmt->execute()) {
        error_log("Database error: " . $stmt->error);
        return 0;
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Get expenses and calculate total pages
$result = getExpenses($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear, $offset, $perPage);
$totalExpenses = getTotalExpenses($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear);
$totalPages = ceil($totalExpenses / $perPage);

// Function to generate PDF
function generatePDF($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear) {
    ob_clean(); // Clear any previous output
    try {
        // Extend TCPDF with custom Header and Footer
        class MYPDF extends TCPDF {
            protected $headerTitle = '';
            protected $reportPeriod = '';

            // Use the proper method signature to set header information
            public function setHeaderTitle($title, $period) {
                $this->headerTitle = $title;
                $this->reportPeriod = $period;
            }

            public function Header() {
                // Set purple theme color
                $purple = array(100, 13, 107);
                $lightPurple = array(232, 220, 234);
                
                // Add purple rectangle at the top
                $this->Rect(0, 0, $this->getPageWidth(), 50, 'F', array(), $purple); // Increased height for IT-PID text
                
                // Add IT-PID text at the top
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('helvetica', 'B', 24);
                $this->SetY(11); // Position for IT-PID text
                $this->Cell(0, 0, 'IT-PID', 0, 1, 'C');
            
                // Add a subtle line under IT-PID text
                $this->SetLineStyle(array('width' => 0.5, 'color' => array(255, 255, 255)));

                // Main header title
                $this->SetFont('helvetica', 'B', 24);
                $this->SetY(20); // Adjusted position for main title
                $this->Cell(0, 0, $this->headerTitle, 0, 1, 'C');
                
                // Period text
                $this->SetFont('helvetica', '', 12);
                $this->SetY(32); // Adjusted position for period text
                $this->Cell(0, 0, $this->reportPeriod, 0, 1, 'C');
            
                // Reset text color
                $this->SetTextColor(0, 0, 0);
            }
        }

        // Create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('IT-PID Team');
        $pdf->SetAuthor('IT-PID');
        $pdf->SetTitle('Expense Report');

        // Get period text based on view type
        $periodText = '';
        switch ($viewType) {
            case 'daily':
                $periodText = 'Daily Report for ' . date('F j, Y', strtotime($selectedDate));
                break;
            case 'monthly':
                $periodText = 'Monthly Report for ' . date('F Y', strtotime($selectedMonth));
                break;
            case 'yearly':
                $periodText = 'Yearly Report for ' . $selectedYear;
                break;
        }

        // Set custom header title and period
        $pdf->setHeaderTitle('Expense Report', $periodText);

        // Set margins
        $pdf->SetMargins(15, 55, 15);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 20);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Fetch expenses
        $expenses = getExpenses($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear);

        // Calculate totals
        $totalAmount = 0;
        $expenseData = array();
        while ($row = $expenses->fetch_assoc()) {
            $totalAmount += $row['amount'];
            $expenseData[] = $row;
        }

        // Add summary section
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(100, 13, 107);
        $pdf->Cell(0, 10, 'Summary', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'Total Expenses: P' . number_format($totalAmount, 2), 0, 1, 'L');
        $pdf->Cell(0, 10, 'Number of Transactions: ' . count($expenseData), 0, 1, 'L');
        $pdf->Ln(5);

        // Create the table header with purple background
        $pdf->SetFillColor(100, 13, 107);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        
        // Table header
        $pdf->Cell(50, 10, 'Category', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Amount', 1, 0, 'C', true);
        $pdf->Cell(40, 10, 'Date', 1, 0, 'C', true);
        $pdf->Cell(0, 10, 'Comment', 1, 1, 'C', true);

        // Reset text color for table content
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);

        // Add table rows with alternating background
        $fill = false;
        foreach ($expenseData as $row) {
            // Fetch category name
            $categoryId = $row['category_id'];
            $sqlCategory = "SELECT name FROM budgets WHERE id = ?";
            $stmtCategory = $conn->prepare($sqlCategory);
            $stmtCategory->bind_param("i", $categoryId);
            $stmtCategory->execute();
            $resultCategory = $stmtCategory->get_result();
            $rowCategory = $resultCategory->fetch_assoc();
            $categoryName = $rowCategory['name'] ?? 'Unknown Category';

            // Set light purple background for alternate rows
            $pdf->SetFillColor(247, 241, 250);
            
            $pdf->Cell(50, 8, $categoryName, 1, 0, 'L', $fill);
            $pdf->Cell(40, 8, 'P' . number_format($row['amount'], 2), 1, 0, 'R', $fill);
            $pdf->Cell(40, 8, date('Y-m-d', strtotime($row['date'])), 1, 0, 'C', $fill);
            
            // Handle long comments
            $comment = $row['comment'];
            if (strlen($comment) > 40) {
                $comment = substr($comment, 0, 37) . '...';
            }
            $pdf->Cell(0, 8, $comment, 1, 1, 'L', $fill);
            
            $fill = !$fill; // Toggle fill for next row
        }

        // Add total row
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(100, 13, 107);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(50, 10, 'Total', 1, 0, 'L', true);
        $pdf->Cell(40, 10, 'P' . number_format($totalAmount, 2), 1, 0, 'R', true);
        $pdf->Cell(0, 10, '', 1, 1, 'L', true);

        // Add generation info at the bottom
        $pdf->SetY(-30);
        $pdf->SetTextColor(100, 13, 107);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Generated on: ' . date('F j, Y g:i A'), 0, 1, 'L');

        // Close and output PDF document
        $pdf->Output('IT-PID_Expense_Report_' . date('Y-m-d') . '.pdf', 'D');

    } catch (Exception $e) {
        error_log('PDF Generation Error: ' . $e->getMessage());
        echo '<div class="alert alert-danger">An error occurred while generating the PDF. Please try again later.</div>';
    }
}

// Check if PDF generation is requested
if (isset($_POST['generate_pdf'])) {
    $userId = $_SESSION['auth_user']['user_id'];
    $viewType = filter_input(INPUT_POST, 'view');
    $selectedDate = filter_input(INPUT_POST, 'date');
    $selectedMonth = filter_input(INPUT_POST, 'month');
    $selectedYear = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    
    generatePDF($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear);
}

// After fetching expenses and before HTML output
$hasExpenses = ($result && $result->num_rows > 0);

?>

<link rel="stylesheet" href="./assets/css/reports.css">

<!-- HTML content -->
<div class="pt-4 pb-5">
    <div class="container">
        <!-- Reports and Graph Button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4">Reports</h1>
            <a href="graphs-page.php" class="btn btn-custom-primary-rounded">
                <i class="bi bi-graph-up"></i> Graphs
            </a>
        </div>

        <!-- Expense History Controls Card -->
        <div class="card shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h1>Expense History</h1>
                </div>
                <!-- View Type and Date Selection - Centered -->
                <form class="mb-4" method="get" id="viewForm">
                    <div class="row justify-content-center">
                        <div class="col-auto d-flex align-items-center">
                            <label for="view" class="col-form-label me-2">View:</label>
                            <select name="view" id="view" class="form-select" style="width: auto;" onchange="document.getElementById('viewForm').submit();">
                                <option value="daily" <?php echo $viewType === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="monthly" <?php echo $viewType === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="yearly" <?php echo $viewType === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                            </select>
                        </div>

                        <?php if ($viewType === 'daily'): ?>
                        <div class="col-auto">
                            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>" 
                                   min="<?php echo htmlspecialchars($userCreationYear); ?>-01-01" 
                                   max="<?php echo htmlspecialchars($currentDate); ?>" 
                                   onchange="document.getElementById('viewForm').submit();">
                        </div>
                        <?php elseif ($viewType === 'monthly'): ?>
                        <div class="col-auto">
                            <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($selectedMonth); ?>" 
                                   min="<?php echo htmlspecialchars($userCreationYear); ?>-01" 
                                   max="<?php echo htmlspecialchars($currentMonth); ?>" 
                                   onchange="document.getElementById('viewForm').submit();">
                        </div>
                        <?php elseif ($viewType === 'yearly'): ?>
                        <div class="col-auto">
                            <select name="year" class="form-select" onchange="document.getElementById('viewForm').submit();">
                                <?php
                                for ($y = $currentYear; $y >= $userCreationYear; $y--) {
                                    $selected = $y == $selectedYear ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($y) . "' $selected>" . htmlspecialchars($y) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- PDF Generation Form -->
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <form method="post">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($viewType); ?>">
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                            <input type="hidden" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>">
                            <input type="hidden" name="year" value="<?php echo htmlspecialchars($selectedYear); ?>">
                            <button type="submit" name="generate_pdf" class="btn btn-custom-primary-rounded w-100" <?php echo $hasExpenses ? '' : 'disabled'; ?>>
                                Generate PDF
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table Card -->
        <div class="card shadow-sm rounded-4">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($hasExpenses) {
                                while ($row = $result->fetch_assoc()) {
                                    $categoryId = $row['category_id'];
                                    $amount = $row['amount'];
                                    $date = $row['date'];
                                    $comment = trim($row['comment']);

                                    // Fetch category name from the database
                                    $sqlCategory = "SELECT name FROM budgets WHERE id = ?";
                                    $stmtCategory = $conn->prepare($sqlCategory);
                                    $stmtCategory->bind_param("i", $categoryId);
                                    $stmtCategory->execute();
                                    $resultCategory = $stmtCategory->get_result();
                                    $rowCategory = $resultCategory->fetch_assoc();
                                    $categoryName = $rowCategory['name'] ?? 'Unknown Category';

                                    // Check if comment is empty
                                    $hasComment = !empty($comment);
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($categoryName); ?></td>
                                        <td>₱<?php echo number_format($amount, 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($date)); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm <?php echo $hasComment ? 'btn-view' : 'btn-view'; ?> w-100" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#commentModal" 
                                                    data-comment="<?php echo htmlspecialchars($comment); ?>"
                                                    <?php echo $hasComment ? '' : 'disabled'; ?>>
                                                <?php echo $hasComment ? 'View' : 'View'; ?>
                                            </button>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>No expenses found for the selected period.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($hasExpenses): ?>
                <div class="mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-start mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?view=<?php echo htmlspecialchars($viewType); ?>&date=<?php echo htmlspecialchars($selectedDate); ?>&month=<?php echo htmlspecialchars($selectedMonth); ?>&year=<?php echo htmlspecialchars($selectedYear); ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?view=<?php echo htmlspecialchars($viewType); ?>&date=<?php echo htmlspecialchars($selectedDate); ?>&month=<?php echo htmlspecialchars($selectedMonth); ?>&year=<?php echo htmlspecialchars($selectedYear); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?view=<?php echo htmlspecialchars($viewType); ?>&date=<?php echo htmlspecialchars($selectedDate); ?>&month=<?php echo htmlspecialchars($selectedMonth); ?>&year=<?php echo htmlspecialchars($selectedYear); ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal for displaying the comment -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentModalLabel">Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="commentContent"></p>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript to handle the comment modal
    const commentModal = document.getElementById('commentModal');
    commentModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const comment = button.getAttribute('data-comment');
        const modalBody = commentModal.querySelector('.modal-body p');
        modalBody.textContent = comment;
    });
</script>

<?php 
include('includes/footer.php');
ob_end_flush(); // End output buffering and send output
?>