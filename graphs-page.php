<?php
$page_title = "Graphs";
include('_dbconnect.php');
include('authentication.php');
include('includes/header.php');

$userId = $_SESSION['auth_user']['user_id'];

// Get the current month and year
$currentMonth = date('Y-m');

// Function to fetch spending breakdown data
function getSpendingBreakdown($conn, $userId, $month) {
    $sql = "SELECT b.name AS category, b.color, SUM(e.amount) AS total_amount
            FROM expenses e
            JOIN budgets b ON e.category_id = b.id
            WHERE e.user_id = ? AND DATE_FORMAT(e.date, '%Y-%m') = ?
            GROUP BY b.name, b.color
            ORDER BY total_amount DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Fetch spending breakdown data
$spendingBreakdown = getSpendingBreakdown($conn, $userId, $currentMonth);

// Prepare data for Chart.js
$labels = array();
$amounts = array();
$backgroundColor = array();

foreach ($spendingBreakdown as $category) {
    $labels[] = $category['category'];
    $amounts[] = $category['total_amount'];
    $backgroundColor[] = $category['color'];
}

// Fetch spending breakdown data
$spendingBreakdown = getSpendingBreakdown($conn, $userId, $currentMonth);

// Check if there's any data
$hasData = !empty($spendingBreakdown);

// Prepare data for Chart.js
$labels = array();
$amounts = array();
$backgroundColor = array();

if ($hasData) {
    foreach ($spendingBreakdown as $category) {
        $labels[] = $category['category'];
        $amounts[] = $category['total_amount'];
        $backgroundColor[] = $category['color'];
    }
}
?>

<!-- HTML content -->
<link rel="stylesheet" href=".\assets\css\graphs.css">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="reports-page.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left"></i> Reports
        </a>
        <h1 class="h4 mb-0">Graphs</h1>
    </div>
    
    <?php if ($hasData): ?>
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Spending Breakdown</h2>
                </div>
                <div class="card-body">
                    <canvas id="spendingBreakdownChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Categories</h2>
                </div>
                <div class="card-body category-list">
                    <?php foreach ($spendingBreakdown as $category): ?>
                        <div class="category-item" style="background-color: <?php echo htmlspecialchars($category['color']); ?>; color:#272727;">
                            <strong><?php echo htmlspecialchars($category['category']); ?></strong>
                            <span class="float-end">₱<?php echo number_format($category['total_amount'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info" role="alert">
        No spending data available for <?php echo date("F Y", strtotime($currentMonth)); ?>. Add some expenses to see your spending breakdown.
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($hasData): ?>
    var ctx = document.getElementById('spendingBreakdownChart').getContext('2d');
    var spendingBreakdownChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                data: <?php echo json_encode($amounts); ?>,
                backgroundColor: <?php echo json_encode($backgroundColor); ?>,
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: 'Spending Breakdown for <?php echo date("F Y", strtotime($currentMonth)); ?>',
                    font: {
                        size: 18
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            var value = context.parsed || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = ((value / total) * 100).toFixed(2);
                            return label + ': ₱' + value.toFixed(2) + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '60%',
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });

    // Highlight corresponding chart segment when hovering over category item
    document.querySelectorAll('.category-item').forEach((item, index) => {
        item.addEventListener('mouseenter', () => {
            spendingBreakdownChart.setActiveElements([{datasetIndex: 0, index: index}]);
            spendingBreakdownChart.update();
        });
        item.addEventListener('mouseleave', () => {
            spendingBreakdownChart.setActiveElements([]);
            spendingBreakdownChart.update();
        });
    });
    <?php endif; ?>
});
</script>

<?php include('includes/footer.php'); ?>