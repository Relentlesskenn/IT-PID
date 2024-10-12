<?php
$page_title = "Graphs · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');

$userId = $_SESSION['auth_user']['user_id'];

// Get the current month and year
$currentMonth = date('Y-m');
$currentYear = date('Y');
$currentDate = date('M Y');

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

// Function to fetch monthly income and expenses
function getMonthlyIncomeExpenses($conn, $userId, $year) {
    $sql = "SELECT 
                MONTH(date) as month,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
            FROM (
                SELECT date, amount, 'income' as type FROM incomes WHERE user_id = ? AND YEAR(date) = ?
                UNION ALL
                SELECT date, amount, 'expense' as type FROM expenses WHERE user_id = ? AND YEAR(date) = ?
            ) combined
            GROUP BY MONTH(date)
            ORDER BY MONTH(date)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $userId, $year, $userId, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array_fill(1, 12, ['income' => 0, 'expense' => 0]); // Initialize all months
    while ($row = $result->fetch_assoc()) {
        $data[$row['month']] = [
            'income' => $row['income'],
            'expense' => $row['expense']
        ];
    }
    
    return $data;
}

// Function to fetch expense trend data
function getExpenseTrend($conn, $userId, $year) {
    $sql = "SELECT DATE_FORMAT(date, '%Y-%m-%d') as date, SUM(amount) as total_amount
            FROM expenses
            WHERE user_id = ? AND YEAR(date) = ?
            GROUP BY DATE(date)
            ORDER BY date";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Fetch data for all graphs
$spendingBreakdown = getSpendingBreakdown($conn, $userId, $currentMonth);
$monthlyIncomeExpenses = getMonthlyIncomeExpenses($conn, $userId, $currentYear);
$expenseTrend = getExpenseTrend($conn, $userId, $currentYear);

// Check if there's any income or expense data
$hasIncomeExpenseData = !empty(array_filter($monthlyIncomeExpenses, function($month) {
    return $month['income'] > 0 || $month['expense'] > 0;
}));

// Check if there's any data
$hasData = !empty($spendingBreakdown) || !empty(array_filter($monthlyIncomeExpenses)) || !empty($expenseTrend);

// Prepare data for Chart.js
$labels = $amounts = $backgroundColor = array();
$incomeData = $expenseData = array_fill(1, 12, 0);
$trendDates = $trendAmounts = array();

if ($hasData) {
    // Spending Breakdown data
    foreach ($spendingBreakdown as $category) {
        $labels[] = $category['category'];
        $amounts[] = $category['total_amount'];
        $backgroundColor[] = $category['color'];
    }

    // Income vs Expenses data
    foreach ($monthlyIncomeExpenses as $month => $data) {
        $incomeData[$month] = $data['income'];
        $expenseData[$month] = $data['expense'];
    }

    // Expense Trend data
    foreach ($expenseTrend as $item) {
        $trendDates[] = $item['date'];
        $trendAmounts[] = $item['total_amount'];
    }
}
?>

<!-- HTML content -->
<link rel="stylesheet" href=".\assets\css\graphs.css">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="reports-page.php" class="btn btn-custom-primary">
            <i class="bi bi-arrow-left"></i> Reports
        </a>
        <h1 class="h4 mb-0">Graphs</h1>
    </div>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Spending Breakdown for <?= $currentDate?></h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($spendingBreakdown)): ?>
                        <canvas id="spendingBreakdownChart"></canvas>
                    <?php else: ?>
                        <p class="text-center">No spending data available for <?php echo date('F', strtotime($currentMonth)); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Categories for <?= $currentDate?></h2>
                </div>
                <div class="card-body category-list">
                    <?php if (!empty($spendingBreakdown)): ?>
                        <?php foreach ($spendingBreakdown as $category): ?>
                            <div class="category-item" style="background-color: <?php echo htmlspecialchars($category['color']); ?>; color:white;">
                                <strong><?php echo htmlspecialchars($category['category']); ?></strong>
                                <strong><span class="float-end">₱<?php echo number_format($category['total_amount'], 2); ?></span></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">No categories with spending for <?php echo date('F', strtotime($currentMonth)); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Income vs. Expenses for <?= $currentYear?></h2>
                </div>
                <div class="card-body">
                    <?php if ($hasIncomeExpenseData): ?>
                        <canvas id="incomeExpensesChart"></canvas>
                    <?php else: ?>
                        <p class="text-center">No income or expense data available for <?php echo date('Y'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Expense Trend Over Time for <?= $currentYear?></h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($expenseTrend)): ?>
                        <canvas id="expenseTrendChart"></canvas>
                    <?php else: ?>
                        <p class="text-center">No expense trend data available for <?php echo date('Y'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$hasData): ?>
    <div class="alert alert-info mt-4" role="alert">
        <h4 class="alert-heading">No Financial Data Available</h4>
        <p>There is currently no financial data to display graphs for <?php echo date("Y", strtotime($currentYear)); ?>.</p>
        <hr>
        <p class="mb-0">To start seeing your financial graphs:</p>
        <ul>
            <li>Add some income entries</li>
            <li>Record your expenses</li>
            <li>Create budget categories</li>
        </ul>
        <a href="create-page.php" class="btn btn-primary mt-3">Add Financial Data</a>
    </div>
    <?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($hasData): ?>
    Chart.defaults.font.family = "'Lexend', 'sans-serif'";
    Chart.defaults.color = '#272727';

    // Spending Breakdown Chart
    var ctxSpending = document.getElementById('spendingBreakdownChart').getContext('2d');
    var spendingBreakdownChart = new Chart(ctxSpending, {
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
                    position: 'right',
                    labels: {
                        font: {
                            size: 16,
                            weight: 'bold',
                        },
                        padding: 10
                    }
                },
                title: {
                    disabled: true,
                },
                tooltip: {
                    titleFont: {
                        size: 16,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 14,
                        weight: 'normal'
                    },
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₱' + context.parsed.toFixed(2);
                            return label;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });

    // Income vs Expenses Chart
    var ctxIncome = document.getElementById('incomeExpensesChart').getContext('2d');
    var incomeExpensesChart = new Chart(ctxIncome, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Income',
                data: <?php echo json_encode(array_values($incomeData)); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            },
            {
                label: 'Expenses',
                data: <?php echo json_encode(array_values($expenseData)); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return '₱' + value.toLocaleString();
                        },
                        font: {
                            size: 14,
                            weight: 'normal',
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 16,
                            weight: 'bold',
                        },
                        padding: 10
                    }
                },
                title: {
                    disabled: true,
                },
                tooltip: {
                    titleFont: {
                        size: 16,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 14,
                        weight: 'normal'
                    },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₱' + context.parsed.y.toFixed(2);
                            return label;
                        }
                    }
                }
            }
        }
    });

    // Expense Trend Chart
    var ctxTrend = document.getElementById('expenseTrendChart').getContext('2d');
    var expenseTrendChart = new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trendDates); ?>,
            datasets: [{
                label: 'Daily Expenses',
                data: <?php echo json_encode($trendAmounts); ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        tooltipFormat: 'MMM D, YYYY'
                    },
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return '₱' + value.toLocaleString();
                        },
                        font: {
                            size: 14,
                            weight: 'normal',
                        },
                    },
                    title: {
                        display: false,
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    disabled: true,
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₱' + context.parsed.y.toFixed(2);
                            return label;
                        }
                    }
                }
            }
        }
    });

    // Function to update chart sizes based on screen width
    function updateChartSizes() {
        var chartContainers = document.querySelectorAll('.card-body');
        chartContainers.forEach(function(container) {
            var containerWidth = container.offsetWidth;
            var aspectRatio = window.innerWidth < 768 ? 1 : 2;  // 1:1 aspect ratio on mobile, 2:1 on larger screens
            
            var chart = Chart.getChart(container.querySelector('canvas'));
            if (chart) {
                chart.options.aspectRatio = aspectRatio;
                chart.resize();
            }
        });
    }

    // Initial call to set chart sizes
    updateChartSizes();

    // Update chart sizes on window resize
    window.addEventListener('resize', updateChartSizes);

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
    
    // Add resize observer to adjust chart sizes when card size changes
    const resizeObserver = new ResizeObserver(entries => {
        for (let entry of entries) {
            const chart = Chart.getChart(entry.target.querySelector('canvas'));
            if (chart) {
                chart.resize();
            }
        }
    });

    document.querySelectorAll('.card-body').forEach(cardBody => {
        resizeObserver.observe(cardBody);
    });

    // Function to format currency
    function formatCurrency(value) {
        return '₱' + value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Update total amounts in the category list
    function updateCategoryTotals() {
        let total = 0;
        document.querySelectorAll('.category-item span').forEach(span => {
            const amount = parseFloat(span.textContent.replace('₱', '').replace(',', ''));
            total += amount;
        });
        
        const totalElement = document.createElement('div');
        totalElement.className = 'category-item total';
        totalElement.innerHTML = `<strong>Total</strong><strong><span class="float-end">${formatCurrency(total)}</span></strong>`;
        
        const categoryList = document.querySelector('.category-list');
        const existingTotal = categoryList.querySelector('.total');
        if (existingTotal) {
            categoryList.removeChild(existingTotal);
        }
        categoryList.appendChild(totalElement);
    }

    // Call the function to update totals
    updateCategoryTotals();

    <?php endif; ?>
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<?php include('includes/footer.php'); ?>