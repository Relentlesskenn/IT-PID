<?php
$page_title = "IT-PID · Admin";
include('includes/header.php');
include('_dbconnect.php');

// Example function to fetch analytics data
function getAnalyticsData() {
    // Connect to your database and fetch data
    // This is a placeholder for your actual database query
    return [
        'labels' => ['January', 'February', 'March', 'April', 'May'],
        'userData' => [10, 20, 30, 40, 50], // Example user engagement data
        'subscriberData' => [5, 15, 25, 35, 45] // Example subscriber counts
    ];
}

$analytics = getAnalyticsData();
?>

<link rel="stylesheet" href="./assets/css/landing_page.css">
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<section class="hero">
    <div class="container my-5">
        <h1 class="text-center mb-4"></h1>
        <div class="row g-4">
            <!-- Large item for Analytics -->
            <div class="col-md-8">
                <div class="bento-item bento-tall">
                    <canvas id="dataAnalyticsChart" width="400" height="400"></canvas>
                </div>
            </div>
            <!-- Two small items -->
            <div class="col-md-4">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="bento-item" data-bs-toggle="modal" data-bs-target="#adsModal">
                            Manage Advertisements
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="bento-item" data-bs-toggle="modal" data-bs-target="#quotesModal">
                            Manage Quotes
                        </div>
                    </div>
                </div>
            </div>
            <!-- Medium item -->
            <div class="col-md-6">
                <div class="bento-item" data-bs-toggle="modal" data-bs-target="#tutorialsModal">
                    Manage Tutorials (Video)
                </div>
            </div>
            <!-- Medium item -->
            <div class="col-md-6">
                <div class="bento-item" data-bs-toggle="modal" data-bs-target="#budgetingTipsModal">
                    Manage Budgeting Tips
                </div>
            </div>
        </div>

        <!-- Modal for Manage Advertisements with Glassmorphism Effect -->
        <div class="modal fade" id="adsModal" tabindex="-1" aria-labelledby="adsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glassmorphism-modal"> <!-- Apply glassmorphism style here -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="adsModalLabel">Manage Advertisements</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="post_ad.php" method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Advertisement Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="link" class="form-label">Advertisement Link</label>
                                <input type="url" class="form-control" id="link" name="link" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Post Advertisement</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Manage Quotes -->
        <div class="modal fade" id="quotesModal" tabindex="-1" aria-labelledby="quotesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glassmorphism-modal"> <!-- Apply glassmorphism style here -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="quotesModalLabel">Manage Quotes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="post_quote.php" method="POST">
                            <div class="mb-3">
                                <label for="quote_text" class="form-label text-white">Quote Text</label>
                                <textarea class="form-control" id="quote_text" name="quote_text" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="author" class="form-label text-white">Author</label>
                                <input type="text" class="form-control" id="author" name="author" placeholder="Anonymous">
                            </div>
                            <button type="submit" class="btn btn-primary">Post Quote</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Manage Tutorials  -->
        <div class="modal fade" id="tutorialsModal" tabindex="-1" aria-labelledby="tutorialsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glassmorphism-modal"> <!-- Apply glassmorphism style here -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="tutorialsModalLabel">Manage Tutorials</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="post_tutorial.php" method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label text-white">Tutorial Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="youtube_link" class="form-label text-white">YouTube Link</label>
                                <input type="url" class="form-control" id="youtube_link" name="youtube_link" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Post Tutorial</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Manage Budgeting Tips -->
        <div class="modal fade" id="budgetingTipsModal" tabindex="-1" aria-labelledby="budgetingTipsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glassmorphism-modal"> <!-- Apply glassmorphism style here -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="budgetingTipsModalLabel">Manage Budgeting Tips</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="post_budgeting_tip.php" method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label text-white">Tip Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label text-white">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="Budgeting Basics">Budgeting Basics</option>
                                    <option value="Saving Tips">Saving Tips</option>
                                    <option value="Financial Goals">Financial Goals</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label text-white">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Post Tip</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = <?php echo json_encode($analytics['labels']); ?>;
        const data = {
            labels: labels,
            datasets: [
                {
                    label: 'Users',
                    backgroundColor: 'rgba(75, 192, 192, 0.5)', // Color for user engagement
                    borderColor: 'rgba(75, 192, 192, 1)', // Solid color for user engagement
                    borderWidth: 1,
                    data: <?php echo json_encode($analytics['userData']); ?>,
                },
                {
                    label: 'Subscribers',
                    backgroundColor: 'rgba(255, 255, 255, 0.5)', // White background for subscribers
                    borderColor: 'rgba(255, 255, 255, 1)', // Solid white border for subscribers
                    borderWidth: 1,
                    data: <?php echo json_encode($analytics['subscriberData']); ?>,
                }
            ]
        };

        const config = {
            type: 'line', // Change to 'bar', 'pie', etc. as needed
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: 'white' // Set legend text color to white
                        }
                    },
                    title: {
                        display: true,
                        text: 'User  Engagement and Subscriber Analytics',
                        color: 'white' // Set title text color to white
                    },
                    tooltip: {
                        backgroundColor: 'white', // Tooltip background color
                        titleColor: 'black', // Tooltip title color (can be adjusted as needed)
                        bodyColor: 'black' // Tooltip body color (can be adjusted as needed)
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: 'white' // Change Y-axis ticks color to white
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.2)' // Change Y-axis grid color to a light shade
                        }
                    },
                    x: {
                        ticks: {
                            color: 'white' // Change X-axis ticks color to white
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.2)' // Change X-axis grid color to a light shade
                        }
                    }
                }
            },
        };

        const dataAnalyticsChart = new Chart(
            document.getElementById('dataAnalyticsChart'),
            config
        );
    });
</script>
</body>
</html>

<style>
   .bento-item {
    height: 200px; /* Retain the original height */
    width: 100%; /* Ensure the item fills its column */
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    transition: transform 0.3s ease, background 0.3s ease; /* Add smooth transition for background */
    background: rgba(255, 255, 255, 0.1); /* Semi-transparent background */
    backdrop-filter: blur(10px); /* Apply blur effect */
    -webkit-backdrop-filter: blur(10px); /* Safari support */
    border: 1px solid rgba(255, 255, 255, 0.2); /* Light border */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4); /* Shadow for depth */
}

.bento-item:hover {
    transform: scale(1.05);
    background: rgba(255, 255, 255, 0.2); /* Change background on hover */
}

.bento-tall {
    height: 425px; /* Retain the height for tall items */
}

.bento-item .modal-content {
    background: rgba(255, 255, 255, 0.1); /* Same glassmorphic style for modal */
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Glassmorphism Style */
.glassmorphism-modal {
    background: rgba(255, 255, 255, 0.2); /* semi-transparent white */
    backdrop-filter: blur(10px); /* blur for frosted effect */
    border-radius: 15px; /* rounded corners */
    border: 1px solid rgba(255, 255, 255, 0.2); /* light border */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* subtle shadow */
}

h5 {
    color: white;
}

.glassmorphism-modal .form-label {
    color: white;
}
</style>

<?php include('includes/footer.php'); ?>