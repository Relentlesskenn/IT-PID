<?php
$page_title = "Reports";
include('_dbconnect.php');
include('authentication.php');
include('includes/header.php');
include('includes/navbar.php');

// Fetch today's expenses from the database
$userId = $_SESSION['auth_user']['user_id'];
$today = date('Y-m-d');
$sql = "SELECT e.category_id, e.amount, e.date, e.comment FROM expenses e WHERE e.user_id = '$userId' AND DATE(e.date) = '$today' ORDER BY e.date DESC LIMIT 50";
$result = mysqli_query($conn, $sql);
?>
<div class="py-3">
    <div class="container">
        <a class="btn btn-secondary btn-sm mb-3" href="dashboard-page.php">X</a>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Today's Expenses (<?= date('F d, Y') ?>)</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Date</th>
                            <th scope="col">Comment</th>
                        </tr>
                    </thead>
                    <tbody>
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
