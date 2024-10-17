<?php
$page_title = "Graphs · IT-PID";
$is_graphs_page = true;
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');

// Get the current user ID
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
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    
    $data = array_fill(1, 12, ['income' => 0, 'expense' => 0]);
    while ($row = $result->fetch_assoc()) {
        $data[$row['month']] = $row;
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
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
$hasData = !empty($spendingBreakdown) || $hasIncomeExpenseData || !empty($expenseTrend);

// Prepare data for Chart.js
$labels = $amounts = $backgroundColor = [];
$incomeData = $expenseData = array_fill(0, 12, 0);
$trendDates = $trendAmounts = [];

if ($hasData) {
    // Spending Breakdown data
    foreach ($spendingBreakdown as $category) {
        $labels[] = $category['category'];
        $amounts[] = $category['total_amount'];
        $backgroundColor[] = $category['color'];
    }

    // Income vs Expenses data
    foreach ($monthlyIncomeExpenses as $month => $data) {
        $incomeData[$month - 1] = $data['income'] ?? 0;
        $expenseData[$month - 1] = $data['expense'] ?? 0;
    }

    // Convert to indexed arrays for Chart.js
    $incomeData = array_values($incomeData);
    $expenseData = array_values($expenseData);

    // Expense Trend data
    $trendDates = $trendAmounts = [];
    foreach ($expenseTrend as $item) {
        $trendDates[] = $item['date'];
        $trendAmounts[] = floatval($item['total_amount']);
    }
}
?>

<!-- HTML content -->
<body class="graphs-page">
<link rel="stylesheet" href="./assets/css/graphs.css">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="reports-page.php" class="btn btn-custom-primary btn-sm">
            <i class="bi bi-arrow-left"></i> Reports
        </a>
        <h1 class="h4 mb-0">Graphs</h1>
    </div>
    
    <!-- Spending Breakdown Chart -->
    <div class="row">
        <?php if ($hasData): ?>
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title">Spending Breakdown for <?= htmlspecialchars($currentDate) ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($spendingBreakdown)): ?>
                            <canvas id="spendingBreakdownChart"></canvas>
                        <?php else: ?>
                            <p class="text-center">No spending data available for <?= date('F', strtotime($currentMonth)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Spending Breakdown Categories -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title">Categories for <?= htmlspecialchars($currentDate) ?></h2>
                    </div>
                    <div class="card-body category-list">
                        <?php if (!empty($spendingBreakdown)): ?>
                            <?php foreach ($spendingBreakdown as $category): ?>
                                <div class="category-item" style="background-color: <?= htmlspecialchars($category['color']) ?>; color:white;">
                                    <strong><?= htmlspecialchars($category['category']) ?></strong>
                                    <strong><span class="float-end">₱<?= number_format($category['total_amount'], 2) ?></span></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center">No categories with spending for <?= date('F', strtotime($currentMonth)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Income vs. Expenses Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title">Income vs. Expenses for <?= htmlspecialchars($currentYear) ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if ($hasIncomeExpenseData): ?>
                            <canvas id="incomeExpensesChart"></canvas>
                        <?php else: ?>
                            <p class="text-center">No income or expense data available for <?= date('Y') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Expense Trend Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title">Expense Trend Over Time for <?= htmlspecialchars($currentYear) ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($expenseTrend)): ?>
                            <canvas id="expenseTrendChart"></canvas>
                        <?php else: ?>
                            <p class="text-center">No expense trend data available for <?= date('Y') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- No Data Message -->
            <div class="col-12">
                <div class="alert alert-custom-info mt-1" role="alert">
                    <h4 class="alert-heading">No Financial Data Available</h4>
                    <p>There is currently no financial data to display graphs for <?= date("Y", strtotime($currentYear)) ?>.</p>
                    <hr>
                    <p class="mb-0">To start seeing your financial graphs:</p>
                    <ul>
                        <li>Add some income entries</li>
                        <li>Create budget categories</li>
                        <li>Record your expenses</li>
                    </ul>
                    <a href="create-page.php" class="btn btn-custom-primary mt-3">Add Financial Data</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chart.js Date Formatting Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script>
// Add event listener to DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($hasData): ?>
    Chart.defaults.font.family = "'Lexend', 'sans-serif'";
    Chart.defaults.color = '#272727';

    const chartOptions = {
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
                display: false,
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
        }
    };

    // Spending Breakdown Chart
    <?php if (!empty($spendingBreakdown)): ?>
    new Chart(document.getElementById('spendingBreakdownChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                data: <?= json_encode($amounts) ?>,
                backgroundColor: <?= json_encode($backgroundColor) ?>,
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            ...chartOptions,
            cutout: '60%'
        }
    });
    <?php endif; ?>

    // Income vs Expenses Chart
    <?php if ($hasIncomeExpenseData): ?>
    new Chart(document.getElementById('incomeExpensesChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Income',
                data: <?= json_encode(array_values($incomeData)) ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            },
            {
                label: 'Expenses',
                data: <?= json_encode(array_values($expenseData)) ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.8)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        },
                        font: {
                            size: 12,
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                        },
                        padding: 20
                    }
                },
                tooltip: {
                    titleFont: {
                        size: 16,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 14
                    },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₱' + context.parsed.y.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Expense Trend Chart
    <?php if (!empty($expenseTrend)): ?>
    new Chart(document.getElementById('expenseTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            datasets: [{
                label: 'Daily Expenses',
                data: <?= json_encode(array_map(function($item) {
                    return [
                        'x' => $item['date'],
                        'y' => floatval($item['total_amount'])
                    ];
                }, $expenseTrend)) ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderWidth: 3,
                fill: true,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: 'rgba(54, 162, 235, 1)',
                pointHoverBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointHoverBorderColor: 'white'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'month',
                        displayFormats: {
                            month: 'MMM yyyy'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Date'
                    },
                    ticks: {
                        maxTicksLimit: 12
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Expense Amount (₱)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    titleFont: {
                        size: 16,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 14
                    },
                    callbacks: {
                        title: function(tooltipItems) {
                            return new Date(tooltipItems[0].parsed.x).toLocaleDateString('en-PH', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                        },
                        label: function(context) {
                            return 'Expense: ₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
    <?php endif; ?>

    // Function to update chart sizes based on screen width
    function updateChartSizes() {
        document.querySelectorAll('.card-body').forEach(container => {
            const canvas = container.querySelector('canvas');
            if (canvas) {
                const chart = Chart.getChart(canvas);
                if (chart) {
                    chart.options.aspectRatio = window.innerWidth < 768 ? 1 : 2;
                    chart.resize();
                }
            }
        });
    }

    // Initial call to set chart sizes
    updateChartSizes();

    // Update chart sizes on window resize
    window.addEventListener('resize', updateChartSizes);

    // Highlight corresponding chart segment when hovering over category item
    <?php if (!empty($spendingBreakdown)): ?>
    const spendingBreakdownChart = Chart.getChart('spendingBreakdownChart');
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
    
    // Add resize observer to adjust chart sizes when card size changes
    const resizeObserver = new ResizeObserver(entries => {
        for (let entry of entries) {
            const canvas = entry.target.querySelector('canvas');
            if (canvas) {
                const chart = Chart.getChart(canvas);
                if (chart) {
                    chart.resize();
                }
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

    <?php endif;?> // End of if ($hasData)
});
</script>

<?php include('includes/footer.php'); ?>