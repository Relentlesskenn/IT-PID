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
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm <?= empty($comment) ? 'btn-dark disabled' : '' ?>" data-bs-toggle="modal" data-bs-target="#commentModal" data-comment="<?= $comment ?>">
                                            View
                                        </button>
                                    </td>
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
            </div>
        </div>
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
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Get the modal and the comment content element
  const commentModal = document.getElementById('commentModal');
  const commentContent = document.getElementById('commentContent');

  // Add an event listener to the "View Comment" buttons
  const commentButtons = document.querySelectorAll('.btn-primary[data-bs-toggle="modal"]');
  commentButtons.forEach(button => {
    button.addEventListener('click', () => {
      // Get the comment from the button's data attribute
      const comment = button.getAttribute('data-comment');

      // Set the comment content in the modal
      commentContent.textContent = comment;
    });
  });
</script>

<?php include('includes/footer.php') ?>
