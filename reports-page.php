<?php
$page_title = "Reports";
include('_dbconnect.php');
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');

// Fetch today's expenses from the database
$userId = $_SESSION['auth_user']['user_id'];
$today = date('Y-m-d');

// Pagination variables
$perPage = 10; // Number of expenses per page
$page = isset($_GET['page']) ? $_GET['page'] : 1; // Get the current page number
$offset = ($page - 1) * $perPage; // Calculate the offset for the SQL query

$sql = "SELECT e.category_id, e.amount, e.date, e.comment FROM expenses e WHERE e.user_id = '$userId' AND DATE(e.date) = '$today' ORDER BY e.date DESC LIMIT $perPage OFFSET $offset";
$result = mysqli_query($conn, $sql);

// Calculate total number of pages
$sqlCount = "SELECT COUNT(*) AS total FROM expenses e WHERE e.user_id = '$userId' AND DATE(e.date) = '$today'";
$resultCount = mysqli_query($conn, $sqlCount);
$rowCount = mysqli_fetch_assoc($resultCount);
$totalPages = ceil($rowCount['total'] / $perPage);
?>
<div class="py-3">
    <div class="container">
        <a class="btn btn-secondary btn-sm mb-3" href="dashboard-page.php">X</a>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Today's Expenses (<?= date('m-d-Y') ?>)</h5>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $page == $i ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <table class="table">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Date</th>
                            <th scope="col">Comment</th>
                        </tr>
                    </thead>
                    <tbody class="table table-bordered">
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $categoryId = $row['category_id'];
                                $amount = $row['amount'];
                                $date = $row['date'];
                                $comment = $row['comment'];

                                // Fetch category name from the database
                                $sqlCategory = "SELECT name FROM budgets WHERE id = '$categoryId'";
                                $resultCategory = mysqli_query($conn, $sqlCategory);
                                $rowCategory = mysqli_fetch_assoc($resultCategory);
                                $categoryName = $rowCategory['name'];
                        ?>
                                <tr>
                                    <td><?= $categoryName ?></td>
                                    <td>₱<?= number_format($amount, 2) ?></td>
                                    <td><?= date('Y-m-d', strtotime($date)) ?></td>
                                    <td><?= $comment ?></td>
                                </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="4">No expenses found for today.</td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php') ?>
