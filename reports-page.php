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
$selectedDate = isset($_GET['date']) ? filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING) : $currentDate;
$selectedMonth = isset($_GET['month']) ? filter_input(INPUT_GET, 'month', FILTER_SANITIZE_STRING) : $currentMonth;
$selectedYear = isset($_GET['year']) ? filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) : $currentYear;

// Check if the view type is set (daily, monthly, or yearly)
$viewType = isset($_GET['view']) ? filter_input(INPUT_GET, 'view', FILTER_SANITIZE_STRING) : 'daily';

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
            public function Header() {
                $this->SetY(15);
                $this->SetFont('helvetica', 'B', 20);
                $this->Cell(0, 15, 'IT-PID Expense Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            }
            public function Footer() {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        }

        // Create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('IT-PID');
        $pdf->SetTitle('Expense Report');
        $pdf->SetSubject('Expense Report');

        // Set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Fetch expenses
        $expenses = getExpenses($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear);

        // Create the table header
        $html = '<table border="1" cellpadding="4">
                    <tr>
                        <th><b>Category</b></th>
                        <th><b>Amount</b></th>
                        <th><b>Date</b></th>
                        <th><b>Comment</b></th>
                    </tr>';

        // Add expenses to the table
        while ($row = $expenses->fetch_assoc()) {
            $categoryId = $row['category_id'];
            $sqlCategory = "SELECT name FROM budgets WHERE id = ?";
            $stmtCategory = $conn->prepare($sqlCategory);
            $stmtCategory->bind_param("i", $categoryId);
            $stmtCategory->execute();
            $resultCategory = $stmtCategory->get_result();
            $rowCategory = $resultCategory->fetch_assoc();
            $categoryName = $rowCategory['name'] ?? 'Unknown Category';

            $html .= '<tr>
                        <td>' . htmlspecialchars($categoryName) . '</td>
                        <td>P' . number_format($row['amount'], 2) . '</td>
                        <td>' . date('Y-m-d', strtotime($row['date'])) . '</td>
                        <td>' . htmlspecialchars($row['comment']) . '</td>
                      </tr>';
        }

        $html .= '</table>';

        // Print the table
        $pdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document
        $pdf->Output('expense_report.pdf', 'D');
    } catch (Exception $e) {
        // Log the error
        error_log('PDF Generation Error: ' . $e->getMessage());
        // Display an error message to the user
        echo '<div class="alert alert-danger">An error occurred while generating the PDF. Please try again later.</div>';
    }
}

// Check if PDF generation is requested
if (isset($_POST['generate_pdf'])) {
    $userId = $_SESSION['auth_user']['user_id'];
    $viewType = filter_input(INPUT_POST, 'view', FILTER_SANITIZE_STRING);
    $selectedDate = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $selectedMonth = filter_input(INPUT_POST, 'month', FILTER_SANITIZE_STRING);
    $selectedYear = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    
    generatePDF($conn, $userId, $viewType, $selectedDate, $selectedMonth, $selectedYear);
}

// After fetching expenses and before HTML output
$hasExpenses = ($result && $result->num_rows > 0);

?>

<!-- HTML content -->
<div class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4">Reports</h1>
        <a href="graphs-page.php" class="btn btn-custom-primary">
            <i class="bi bi-graph-up"></i> Graphs
        </a>
        </div>
        <h1 class="mb-4">Expense History</h1>

        <!-- View Type and Date Selection -->
        <form class="mb-4" method="get" id="viewForm">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="view" class="col-form-label">View:</label>
                </div>
                <div class="col-auto">
                    <select name="view" id="view" class="form-select" onchange="document.getElementById('viewForm').submit();">
                        <option value="daily" <?php echo $viewType === 'daily' ? 'selected' : ''; ?>>Daily</option>
                        <option value="monthly" <?php echo $viewType === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="yearly" <?php echo $viewType === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                </div>
                <?php if ($viewType === 'daily'): ?>
                <div class="col-auto">
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>" min="<?php echo htmlspecialchars($userCreationYear); ?>-01-01" max="<?php echo htmlspecialchars($currentDate); ?>" onchange="document.getElementById('viewForm').submit();">
                </div>
                <?php elseif ($viewType === 'monthly'): ?>
                <div class="col-auto">
                    <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($selectedMonth); ?>" min="<?php echo htmlspecialchars($userCreationYear); ?>-01" max="<?php echo htmlspecialchars($currentMonth); ?>" onchange="document.getElementById('viewForm').submit();">
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
        <form method="post" class="mb-3">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($viewType); ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
            <input type="hidden" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($selectedYear); ?>">
            <button type="submit" name="generate_pdf" class="btn btn-custom-primary w-100" <?php echo $hasExpenses ? '' : 'disabled'; ?>>
                Generate PDF
            </button>
        </form>

        <!-- Expenses Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
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
                            $comment = $row['comment'];

                            // Fetch category name from the database
                            $sqlCategory = "SELECT name FROM budgets WHERE id = ?";
                            $stmtCategory = $conn->prepare($sqlCategory);
                            $stmtCategory->bind_param("i", $categoryId);
                            $stmtCategory->execute();
                            $resultCategory = $stmtCategory->get_result();
                            $rowCategory = $resultCategory->fetch_assoc();
                            $categoryName = $rowCategory['name'] ?? 'Unknown Category';
                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($categoryName); ?></td>
                                <td>₱<?php echo number_format($amount, 2); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($date)); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-custom-primary w-100" data-bs-toggle="modal" data-bs-target="#commentModal" data-comment="<?php echo htmlspecialchars($comment); ?>">
                                        View
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
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-start">
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
        <?php endif; ?>
    </div>
</div>

<!-- Modal for displaying the comment -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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