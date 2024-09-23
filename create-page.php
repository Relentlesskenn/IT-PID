<?php
include('_dbconnect.php');
include('authentication.php');
$page_title = "Create";
include('includes/header.php'); 

$selected_page = isset($_POST['page']) ? $_POST['page'] : 'page1';

function showPage1() {
    echo "<div class='card'>
            <div class='card-header'>
                Create a Budget Category
            </div>
            <div class='card-body'>
                <form method='post'>
                    <div class='mb-3'>
                        <label for='budget_category' class='form-label'>Budget Category 
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">+</button>
                        </label>
                        <select class='form-select' name='budget_category'>
                            <option value='Food'>Food</option>
                            <option value='Housing'>Housing</option>
                            <option value='Transportation'>Transportation</option>
                            <option value='Utilities'>Utilities</option>
                            <option value='Entertainment'>Entertainment</option>
                            <option value='Other'>Other</option>
                        </select>
                    </div>
                    <div class='mb-3'>
                        <label for='budget_amount' class='form-label'>Amount</label>
                        <input type='number' class='form-control' name='budget_amount'>
                    </div>
                    <button type='submit' class='btn btn-primary' name='budget_btn'>Add Budget</button>
                </form>
            </div>
        </div>";
}

function showPage2() {
    echo "<div class='card'>
            <div class='card-header'>
                Add New Expense
            </div>
            <div class='card-body'>
                <form method='post'>
                    <div class='mb-3'>
                        <label for='expense_name' class='form-label'>Expense Name</label>
                        <input type='text' class='form-control' id='expense_name'>
                    </div>
                    <div class='mb-3'>
                        <label for='expense_amount' class='form-label'>Amount</label>
                        <input type='number' class='form-control' id='expense_amount'>
                    </div>
                    <div class='mb-3'>
                        <label class='mb-2'>Budget Category</label>
                        <select class='form-select'>
                            <option value='1'>One</option>
                            <option value='2'>Two</option>
                            <option value='3'>Three</option>
                        </select>
                    </div>
                    <button type='submit' class='btn btn-primary' name='expense_btn'>Add Expense</button>
                </form>
            </div>
        </div>";
}
?>
<div class="py-3">
    <div class="container">
        <a class="btn btn-danger btn-sm mb-3" href="dashboard-page.php"><</a>
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <form method="post" action="" id="pageForm">
                    <input type="hidden" name="submitted_page" value="<?php echo $selected_page; ?>">
                    <div class="btn-group" role="group" aria-label="Page Selection">
                        <input type="radio" class="btn-check" name="page" id="page1" value="page1" autocomplete="off" <?php echo ($selected_page == 'page1') ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="page1">Budget</label>

                        <input type="radio" class="btn-check" name="page" id="page2" value="page2" autocomplete="off" <?php echo ($selected_page == 'page2') ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="page2">Expense</label>
                    </div>
                    <div class="content mt-4">
                        <?php
                            if ($selected_page == 'page1') {
                                showPage1();
                            } elseif ($selected_page == 'page2') {
                                showPage2();
                            }
                        ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_POST['budget_btn'])) {
    $budgetName = $_POST['budget_name'];
    $budgetCategory = $_POST['budget_category'];
    $budgetAmount = $_POST['budget_amount'];

    $sql = "INSERT INTO budgets (name, amount) VALUES ('$budgetName', '$budgetAmount')";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo "<script>alert('Budget added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding budget!');</script>";
    }
}
?>

<script>
document.querySelectorAll('input[name="page"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('pageForm').submit();
    });
});
</script>

<!-- Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add form for creating new category here -->
                <form method="post">
                    <div class="mb-3">
                        <label for="new_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="new_category_name" name="new_category_name">
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_category_btn">Add Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_POST['add_category_btn'])) {
    $newCategoryName = $_POST['new_category_name'];

    // Perform database insertion or any other necessary action
    // Example:
    // $sql = "INSERT INTO categories (name) VALUES ('$newCategoryName')";
    // $result = mysqli_query($conn, $sql);

    // Handle success or failure of the operation
    if ($result) {
        echo "<script>alert('Category added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding category!');</script>";
    }
}
?>

<?php include('includes/footer.php') ?>
