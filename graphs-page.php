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

<link rel="stylesheet" href="./assets/css/graphs.css">

<!-- Main container with bento grid -->
<body class="graphs-page">
<div class="graphs-container pt-4 pb-5">
    <!-- Header section -->
    <div class="d-flex justify-content-between align-items-center">
        <a href="reports-page.php" class="btn btn-custom-primary-rounded btn-sm">
            <i class="bi bi-arrow-left"></i>
            <span>Reports</span>
        </a>
        <h1 class="h4 mb-0">Financial Graphs</h1>
        <!-- Add print button -->
        <button class="btn btn-custom-primary-rounded btn-sm" onclick="printGraphs()" id="printButton">
            <i class="bi bi-printer"></i>
            <span>Print Graphs</span>
        </button>
    </div>

    <?php if ($hasData): ?>
    <!-- Bento grid layout for charts -->
    <div class="bento-grid">
        <!-- Spending Breakdown Chart -->
        <div class="bento-card">
            <div class="bento-card-header">
                <h2 class="bento-card-title">
                    Spending Breakdown
                    <small class="d-block text-white-50 mt-1">
                        <?= htmlspecialchars($currentDate) ?>
                    </small>
                </h2>
            </div>
            <div class="bento-card-body">
                <?php if (!empty($spendingBreakdown)): ?>
                    <canvas id="spendingBreakdownChart"></canvas>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <p class="text-muted">No spending data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Categories List -->
        <div class="bento-card">
            <div class="bento-card-header">
                <h2 class="bento-card-title">
                    Spending Categories
                    <small class="d-block text-white-50 mt-1">
                        <?= htmlspecialchars($currentDate) ?>
                    </small>
                </h2>
            </div>
            <div class="bento-card-body">
                <div class="category-list">
                    <?php if (!empty($spendingBreakdown)): ?>
                        <?php foreach ($spendingBreakdown as $category): ?>
                            <div class="category-item" 
                                 style="background-color: <?= htmlspecialchars($category['color']) ?>;">
                                <strong><?= htmlspecialchars($category['category']) ?></strong>
                                <strong>₱<?= number_format($category['total_amount'], 2) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <p class="text-muted">No categories available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Income vs Expenses Chart -->
        <div class="bento-card">
            <div class="bento-card-header">
                <h2 class="bento-card-title">
                    Income vs Expenses
                    <small class="d-block text-white-50 mt-1">
                        <?= htmlspecialchars($currentYear) ?>
                    </small>
                </h2>
            </div>
            <div class="bento-card-body">
                <?php if ($hasIncomeExpenseData): ?>
                    <canvas id="incomeExpensesChart"></canvas>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <p class="text-muted">No income/expense data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expense Trend Chart -->
        <div class="bento-card">
            <div class="bento-card-header">
                <h2 class="bento-card-title">
                    Expense Trend
                    <small class="d-block text-white-50 mt-1">
                        <?= htmlspecialchars($currentYear) ?>
                    </small>
                </h2>
            </div>
            <div class="bento-card-body">
                <?php if (!empty($expenseTrend)): ?>
                    <canvas id="expenseTrendChart"></canvas>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <p class="text-muted">No trend data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- No Data State -->
    <div class="no-data-container">
        <i class="bi bi-graph-up no-data-icon"></i>
        <h4 class="no-data-text">No Financial Data Available</h4>
        <p class="mb-4">Start tracking your finances to see detailed graphs and insights.</p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="create-page.php" class="back-button">
                <i class="bi bi-plus-lg"></i>
                Add Financial Data
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chart.js Date Formatting Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script>
// Add event listener to DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($hasData): ?>
    // Set global Chart.js defaults
    Chart.defaults.font.family = "'Lexend', 'sans-serif'";
    Chart.defaults.color = '#272727';

    // Common chart options
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

    // Initialize chart objects
    let spendingBreakdownChart, incomeExpensesChart, expenseTrendChart;

    // Spending Breakdown Chart
    <?php if (!empty($spendingBreakdown)): ?>
    spendingBreakdownChart = new Chart(document.getElementById('spendingBreakdownChart').getContext('2d'), {
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
            cutout: '60%',
            animation: {
                animateRotate: true,
                animateScale: true
            }
        }
    });
    <?php endif; ?>

    // Income vs Expenses Chart
    <?php if ($hasIncomeExpenseData): ?>
    incomeExpensesChart = new Chart(document.getElementById('incomeExpensesChart').getContext('2d'), {
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
    expenseTrendChart = new Chart(document.getElementById('expenseTrendChart').getContext('2d'), {
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

    // Print functionality
    window.printGraphs = async function() {
        try {
            // Show loading state
            const printButton = document.getElementById('printButton');
            const originalContent = printButton.innerHTML;
            printButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Preparing...';
            printButton.disabled = true;

            // Create print window content
            const printWindow = window.open('', '_blank');
            const currentDate = new Date().toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Generate print content
            let printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Financial Graphs Report - ${currentDate}</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@400;600&display=swap');
                        
                        body {
                            font-family: 'Lexend', Arial, sans-serif;
                            padding: 40px;
                            margin: 0;
                            color: #272727;
                        }
                        
                        .print-header {
                            text-align: center;
                            margin-bottom: 40px;
                            padding-bottom: 20px;
                            border-bottom: 2px solid #433878;
                        }
                        
                        .print-title {
                            color: #433878;
                            font-size: 24px;
                            margin: 0 0 10px 0;
                        }
                        
                        .print-date {
                            color: #666;
                            font-size: 14px;
                        }
                        
                        .chart-container {
                            page-break-inside: avoid;
                            margin-bottom: 50px;
                            padding: 20px;
                            background: #fff;
                            border-radius: 12px;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                        }
                        
                        .chart-title {
                            font-size: 18px;
                            color: #433878;
                            margin-bottom: 20px;
                            padding-bottom: 10px;
                            border-bottom: 1px solid #eee;
                        }
                        
                        .chart-image {
                            max-width: 100%;
                            height: auto;
                            margin: 0 auto;
                            display: block;
                        }
                        
                        .categories-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                            gap: 15px;
                            margin-top: 20px;
                        }
                        
                        .category-item {
                            padding: 12px 15px;
                            border-radius: 8px;
                            color: white;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            font-size: 14px;
                        }
                        
                        @media print {
                            body { padding: 20px; }
                            .chart-container { 
                                break-inside: avoid;
                                box-shadow: none;
                                border: 1px solid #eee;
                            }
                            .chart-image {
                                max-width: 90%;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h1 class="print-title">Financial Graphs Report</h1>
                        <div class="print-date">Generated on ${currentDate}</div>
                    </div>
            `;

            // Add charts
            const chartContainers = document.querySelectorAll('.bento-card');
            for (const container of chartContainers) {
                const title = container.querySelector('.bento-card-title').textContent.trim();
                const canvas = container.querySelector('canvas');
                
                if (canvas) {
                    const chart = Chart.getChart(canvas);
                    if (chart) {
                        // Ensure chart is fully rendered
                        await new Promise(resolve => setTimeout(resolve, 100));
                        const imageUrl = canvas.toDataURL('image/png', 1.0);
                        
                        printContent += `
                            <div class="chart-container">
                                <div class="chart-title">${title}</div>
                                <img src="${imageUrl}" class="chart-image" alt="${title}">
                            </div>
                        `;
                    }
                }
            }

            // Add categories if available
            const categoryList = document.querySelector('.category-list');
            if (categoryList) {
                printContent += `
                    <div class="chart-container">
                        <div class="chart-title">Spending Categories Details</div>
                        <div class="categories-grid">
                `;

                categoryList.querySelectorAll('.category-item').forEach(item => {
                    const backgroundColor = item.style.backgroundColor;
                    const categoryName = item.querySelector('strong:first-child').textContent;
                    const amount = item.querySelector('strong:last-child').textContent;
                    
                    printContent += `
                        <div class="category-item" style="background-color: ${backgroundColor}">
                            <strong>${categoryName}</strong>
                            <strong>${amount}</strong>
                        </div>
                    `;
                });

                printContent += `
                        </div>
                    </div>
                `;
            }

            // Close HTML structure
            printContent += `
                    </body>
                </html>
            `;

            // Write to print window and print
            printWindow.document.write(printContent);
            printWindow.document.close();

            // Wait for images to load before printing
            printWindow.onload = function() {
                printWindow.print();
                // Reset button state after printing
                printButton.innerHTML = originalContent;
                printButton.disabled = false;
            };

        } catch (error) {
            console.error('Print error:', error);
            // Reset button state on error
            const printButton = document.getElementById('printButton');
            printButton.innerHTML = '<i class="bi bi-printer"></i> Print Graphs';
            printButton.disabled = false;
            
            // Show error message to user
            alert('An error occurred while preparing the print. Please try again.');
        }
    };

    // Chart resize handling
    function updateChartSizes() {
        document.querySelectorAll('.bento-card-body').forEach(container => {
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

    // Initialize chart sizes
    updateChartSizes();

    // Window resize handler
    window.addEventListener('resize', updateChartSizes);

    // Category item hover effects
    <?php if (!empty($spendingBreakdown)): ?>
    const categoryItems = document.querySelectorAll('.category-item');
    categoryItems.forEach((item, index) => {
        item.addEventListener('mouseenter', () => {
            if (spendingBreakdownChart) {
                spendingBreakdownChart.setActiveElements([{datasetIndex: 0, index: index}]);
                spendingBreakdownChart.setActiveElements([{datasetIndex: 0, index: index}]);
                spendingBreakdownChart.update();
            }
        });
        
        item.addEventListener('mouseleave', () => {
            if (spendingBreakdownChart) {
                spendingBreakdownChart.setActiveElements([]);
                spendingBreakdownChart.update();
            }
        });
    });
    <?php endif; ?>

    // ResizeObserver for dynamic chart resizing
    const resizeObserver = new ResizeObserver(entries => {
        entries.forEach(entry => {
            const canvas = entry.target.querySelector('canvas');
            if (canvas) {
                const chart = Chart.getChart(canvas);
                if (chart) {
                    chart.resize();
                }
            }
        });
    });

    // Observe all chart containers
    document.querySelectorAll('.bento-card-body').forEach(cardBody => {
        resizeObserver.observe(cardBody);
    });

    // Currency formatting helper
    function formatCurrency(value) {
        return '₱' + value.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Update category totals
    function updateCategoryTotals() {
        const categoryItems = document.querySelectorAll('.category-item:not(.total)');
        let total = 0;

        categoryItems.forEach(item => {
            const amountText = item.querySelector('strong:last-child').textContent;
            const amount = parseFloat(amountText.replace('₱', '').replace(/,/g, ''));
            if (!isNaN(amount)) {
                total += amount;
            }
        });

        // Create or update total element
        const categoryList = document.querySelector('.category-list');
        let totalElement = categoryList.querySelector('.category-item.total');
        
        if (!totalElement) {
            totalElement = document.createElement('div');
            totalElement.className = 'category-item total';
            categoryList.appendChild(totalElement);
        }

        totalElement.innerHTML = `
            <strong>Total</strong>
            <strong>${formatCurrency(total)}</strong>
        `;
    }

    // Initialize category totals
    updateCategoryTotals();

    // Add error handling for chart rendering
    window.addEventListener('error', function(e) {
        if (e.target.tagName === 'CANVAS') {
            console.error('Canvas error:', e);
            const container = e.target.closest('.bento-card-body');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Error loading chart. Please refresh the page.
                    </div>
                `;
            }
        }
    }, true);

    <?php endif; ?> // End of if ($hasData)

    // Global error handler
    window.onerror = function(msg, url, line, col, error) {
        console.error('Global error:', {msg, url, line, col, error});
        return false;
    };

    // Handle print button keyboard navigation
    const printButton = document.getElementById('printButton');
    if (printButton) {
        printButton.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }
});
</script>

<?php include('includes/footer.php'); ?>