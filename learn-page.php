<?php
// Handle AJAX requests first, before any output
if (isset($_POST['action'])) {
    // Don't include header files for AJAX requests
    require_once('_dbconnect.php');
    
    header('Content-Type: application/json');
    ob_clean(); // Clear any previous output
    
    try {
        if ($_POST['action'] === 'getArticle') {
            $articleId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$articleId) {
                throw new Exception('Invalid article ID');
            }
            
            $article = getArticle($conn, $articleId);
            if (!$article) {
                throw new Exception('Article not found');
            }
            
            $response = [
                'success' => true,
                'data' => $article
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($_POST['action'] === 'filterArticles') {
            $category = trim(filter_input(INPUT_POST, 'category'));
            $articles = getArticles($conn, $category);
            
            if ($articles === false) {
                throw new Exception('Database error while fetching articles');
            }
            
            $articlesArray = [];
            while ($row = $articles->fetch_assoc()) {
                // Explicitly define the structure of each article
                $articlesArray[] = [
                    'id' => (int)$row['id'],
                    'title' => htmlspecialchars_decode($row['title']),
                    'category' => htmlspecialchars_decode($row['category']),
                    'preview' => htmlspecialchars_decode($row['preview']),
                    'content' => htmlspecialchars_decode($row['content']),
                    'date_published' => $row['date_published']
                ];
            }
            
            $response = [
                'success' => true,
                'data' => $articlesArray
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($_POST['action'] === 'getQuotes') {
            $category = trim(filter_input(INPUT_POST, 'category'));
            $quotes = getQuotes($conn, $category ?: null, 3);
            
            $response = [
                'success' => true,
                'data' => $quotes
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($_POST['action'] === 'filterVideos') {
            $category = trim(filter_input(INPUT_POST, 'category'));
            $level = trim(filter_input(INPUT_POST, 'level'));
            $videos = getVideoTutorials($conn, $category, $level);
            
            if ($videos === false) {
                throw new Exception('Database error while fetching videos');
            }
            
            $videosArray = [];
            while ($row = $videos->fetch_assoc()) {
                $videosArray[] = [
                    'id' => (int)$row['id'],
                    'title' => htmlspecialchars_decode($row['title']),
                    'description' => htmlspecialchars_decode($row['description']),
                    'thumbnail_url' => $row['thumbnail_url'],
                    'video_url' => $row['video_url'],
                    'category' => htmlspecialchars_decode($row['category']),
                    'duration' => $row['duration'],
                    'level' => $row['level'],
                    'views' => (int)$row['views']
                ];
            }
            
            $response = [
                'success' => true,
                'data' => $videosArray
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        throw new Exception('Invalid action');
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

$page_title = "Learn · IT-PID";
include('_dbconnect.php');
include('includes/authentication.php');
include('includes/header.php');
include('includes/navbar.php');

// Function to get all articles with pagination
function getArticles($conn, $category = null) {
    try {
        $sql = "SELECT * FROM articles WHERE 1=1";
        if ($category && $category != '') {
            $sql .= " AND category = ?";
        }
        $sql .= " ORDER BY date_published DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if ($category && $category != '') {
            $stmt->bind_param("s", $category);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt->get_result();
        
    } catch (Exception $e) {
        error_log("Error in getArticles: " . $e->getMessage());
        return false;
    }
}

// Function to get a single article
function getArticle($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
        
    } catch (Exception $e) {
        error_log("Error in getArticle: " . $e->getMessage());
        return null;
    }
}

// Function to get article count by category
function getArticleCountByCategory($conn, $category) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles WHERE category = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $category);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
        
    } catch (Exception $e) {
        error_log("Error in getArticleCountByCategory: " . $e->getMessage());
        return 0;
    }
}

// Function to get random quotes with optional category filter
function getQuotes($conn, $category = null, $limit = 3) {
    try {
        $sql = "SELECT id, content, author, category FROM quotes";
        $params = [];
        $types = "";
        
        if ($category) {
            $sql .= " WHERE category = ?";
            $params[] = $category;
            $types .= "s";
        }
        
        $sql .= " ORDER BY RAND() LIMIT ?";
        $params[] = $limit;
        $types .= "i";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error in getQuotes: " . $e->getMessage());
        return [];
    }
}

// Function to get quote categories
function getQuoteCategories($conn) {
    try {
        $stmt = $conn->prepare("SELECT DISTINCT category FROM quotes ORDER BY category");
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error in getQuoteCategories: " . $e->getMessage());
        return [];
    }
}

// Function to get video tutorials with optional filters
function getVideoTutorials($conn, $category = null, $level = null) {
    try {
        // Base SQL query
        $sql = "SELECT 
                id,
                title,
                description,
                CONCAT('assets/imgs/thumbnails/', id, '.jpg') as thumbnail_url,
                video_url,
                category,
                duration,
                level,
                views 
                FROM videos 
                WHERE status = 'active'";
        
        $params = [];
        $types = "";
        
        // Add category filter if provided
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
            $types .= "s";
        }
        
        // Add level filter if provided
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
            $types .= "s";
        }
        
        // Order by most recent
        $sql .= " ORDER BY date_added DESC";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters if any
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt->get_result();
        
    } catch (Exception $e) {
        error_log("Error in getVideoTutorials: " . $e->getMessage());
        return false;
    }
}

// Function to get video count by category
function getVideoCountByCategory($conn, $category) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM videos WHERE category = ? AND status = 'active'");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $category);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
        
    } catch (Exception $e) {
        error_log("Error in getVideoCountByCategory: " . $e->getMessage());
        return 0;
    }
}

function getThumbnailUrl($videoId) {
    $thumbnailPath = "assets/imgs/thumbnails/{$videoId}.jpg";
    
    // Check if thumbnail exists
    if (file_exists($thumbnailPath)) {
        return $thumbnailPath;
    }
    
    // Return default thumbnail if original doesn't exist
    return "assets/imgs/thumbnails/default.jpg";
}

// Get article counts for each category
$basicsCount = getArticleCountByCategory($conn, 'basics');
$savingsCount = getArticleCountByCategory($conn, 'savings');
$goalsCount = getArticleCountByCategory($conn, 'goals');

?>

<link rel="stylesheet" href="./assets/css/learn.css">

<!-- HTML content -->
<div class="learn-page pt-4 pb-5">
    <div class="container">
        <!-- Header Section -->
        <div class="row align-items-center mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <h1 class="h4 mb-0">Learn</h1>
            </div>
            <div class="col-md-6">
                <div class="search-container ms-md-auto">
                    <input type="text" class="form-control form-control-lg" id="searchInput" placeholder="Search articles...">
                    <button class="btn" type="button" id="clearSearch">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Quotes Section -->
        <div class="quotes-section">
            <div class="section-header">
                <h2 class="mb-0">Financial Wisdom</h2>
                <div class="quote-controls">
                    <select class="quote-category-select" id="quoteCategory">
                        <option value="">All Categories</option>
                        <?php 
                        $categories = getQuoteCategories($conn);
                        foreach ($categories as $category): 
                            $categoryName = ucfirst($category['category']);
                        ?>
                            <option value="<?= htmlspecialchars($category['category']) ?>">
                                <?= htmlspecialchars($categoryName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="quote-refresh-btn" id="refreshQuotes">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Refresh Quotes</span>
                    </button>
                </div>
            </div>
            
            <div class="quotes-grid" id="quotesContainer">
                <?php
                $quotes = getQuotes($conn, null, 3);
                foreach ($quotes as $quote):
                ?>
                    <div class="quote-card fade-in">
                        <i class="bi bi-quote quote-icon"></i>
                        <p class="quote-content">
                            "<?= htmlspecialchars($quote['content']) ?>"
                        </p>
                        <p class="quote-author">
                            <span>- <?= htmlspecialchars($quote['author']) ?></span>
                            <small class="text-white-50">
                                #<?= htmlspecialchars(ucfirst($quote['category'])) ?>
                            </small>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Featured Categories Section -->
        <div class="row g-2 mb-4">
            <div class="col-12">
                <div class="section-header">
                    <h2 class="mb-0">Featured Categories</h2>
                    <button class="btn category-reset" id="showAllButton">
                        <i class="bi bi-grid me-2"></i>Show All
                    </button>
                </div>
            </div>
            
            <!-- Budgeting Basics Category -->
            <div class="col-md-4">
                <div class="category-card card" role="button" data-category="basics">
                    <div class="card-body text-center py-4">
                        <i class="bi bi-book resource-icon"></i>
                        <h5 class="card-title">Budgeting Basics</h5>
                        <p class="card-text">Master the fundamentals of personal finance</p>
                        <span class="badge bg-primary"><?= $basicsCount ?> articles</span>
                    </div>
                </div>
            </div>

            <!-- Saving Tips Category -->
            <div class="col-md-4">
                <div class="category-card card" role="button" data-category="savings">
                    <div class="card-body text-center py-4">
                        <i class="bi bi-piggy-bank resource-icon"></i>
                        <h5 class="card-title">Saving Tips</h5>
                        <p class="card-text">Smart strategies to grow your savings</p>
                        <span class="badge bg-primary"><?= $savingsCount ?> articles</span>
                    </div>
                </div>
            </div>

            <!-- Financial Goals Category -->
            <div class="col-md-4">
                <div class="category-card card" role="button" data-category="goals">
                    <div class="card-body text-center py-4">
                        <i class="bi bi-trophy resource-icon"></i>
                        <h5 class="card-title">Financial Goals</h5>
                        <p class="card-text">Turn your financial dreams into reality</p>
                        <span class="badge bg-primary"><?= $goalsCount ?> articles</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Articles Section -->
        <div class="row g-2 mb-4"> <!-- Use same row structure as other sections -->
            <div class="col-12">
                <div class="section-header">
                    <h2 class="mb-0">Latest Articles</h2>
                    <span class="articles-status">Showing all articles</span>
                </div>
                
                <div class="articles-grid">
                    <?php
                    $articles = getArticles($conn);
                    
                    if ($articles === false) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Error loading articles. Please try again later.
                        </div>
                        <?php
                    } else if ($articles->num_rows === 0) {
                        ?>
                        <div class="alert alert-custom-info" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            No articles available at the moment.
                        </div>
                        <?php
                    } else {
                        while ($article = $articles->fetch_assoc()):
                        ?>
                        <article class="article-card">
                            <h3 class="article-title">
                                <?= htmlspecialchars($article['title']) ?>
                            </h3>
                            <p class="article-description">
                                <?= htmlspecialchars($article['preview']) ?>
                            </p>
                            <div class="article-footer">
                                <span class="article-date">
                                    <?= date('M d, Y', strtotime($article['date_published'])) ?>
                                </span>
                                <button class="read-more-btn" onclick="showArticle(<?= $article['id'] ?>)">
                                    Read More
                                </button>
                            </div>
                        </article>
                        <?php 
                        endwhile;
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Video Tutorials Section -->
        <div class="video-tutorials-section mb-4">
            <div class="section-header">
                <h2 class="mb-0">Video Tutorials</h2>
                <div class="video-controls">
                    <select class="video-filter-select me-2" id="videoLevel">
                        <option value="">All Levels</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <button class="quote-refresh-btn" id="resetVideoFilters">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Filters
                    </button>
                </div>
            </div>

            <!-- Video Category Pills -->
            <div class="video-categories mb-4">
                <button class="video-category-pill active" data-category="">All Videos</button>
                <button class="video-category-pill" data-category="basics">Basics</button>
                <button class="video-category-pill" data-category="savings">Savings</button>
                <button class="video-category-pill" data-category="goals">Goals</button>
            </div>

            <!-- Videos Grid -->
            <div class="videos-grid" id="videosContainer">
                <?php
                $videos = getVideoTutorials($conn);
                if ($videos === false) {
                    echo '<div class="alert alert-danger">Error loading videos. Please try again later.</div>';
                } else if ($videos->num_rows === 0) {
                    echo '<div class="alert alert-custom-info">No video tutorials available at the moment.</div>';
                } else {
                    while ($video = $videos->fetch_assoc()):
                        $thumbnailUrl = getThumbnailUrl($video['id']);
                    ?>
                    <div class="video-card">
                        <div class="video-thumbnail">
                            <img src="<?= htmlspecialchars($thumbnailUrl) ?>" 
                                alt="<?= htmlspecialchars($video['title']) ?>" 
                                loading="lazy"
                                onerror="this.src='assets/imgs/thumbnails/default.jpg'">
                            <span class="video-duration"><?= htmlspecialchars($video['duration']) ?></span>
                            <div class="video-play-overlay">
                                <i class="bi bi-play-circle"></i>
                            </div>
                        </div>
                        <div class="video-info">
                            <h3 class="video-title"><?= htmlspecialchars($video['title']) ?></h3>
                            <div class="video-meta">
                                <span class="video-level <?= htmlspecialchars($video['level']) ?>">
                                    <?= ucfirst(htmlspecialchars($video['level'])) ?>
                                </span>
                                <span class="video-category">
                                    <?= ucfirst(htmlspecialchars($video['category'])) ?>
                                </span>
                            </div>
                            <p class="video-description"><?= htmlspecialchars($video['description']) ?></p>
                        </div>
                    </div>
                    <?php
                    endwhile;
                }
                ?>
            </div>
        </div>

        <!-- Video Modal -->
        <div class="modal fade" id="videoModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="video-wrapper ratio ratio-16x9">
                            <iframe src="" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Tips Section -->
        <div class="row g-2">
            <div class="col-12">
                <div class="section-header">
                    <h2 class="mb-0">Quick Tips</h2>
                </div>
            </div>
            
            <!-- 50/30/20 Rule Tip -->
            <div class="col-md-6 col-lg-3">
                <div class="quick-tip-card card h-100">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="bi bi-lightning-charge-fill me-2"></i>50/30/20 Rule
                        </h6>
                        <p class="card-text small">Allocate 50% for needs, 30% for wants, and 20% for savings and debt repayment.</p>
                    </div>
                </div>
            </div>

            <!-- Pay Yourself First Tip -->
            <div class="col-md-6 col-lg-3">
                <div class="quick-tip-card card h-100">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="bi bi-graph-up-arrow me-2"></i>Pay Yourself First
                        </h6>
                        <p class="card-text small">Save a portion of your income before spending on other expenses.</p>
                    </div>
                </div>
            </div>

            <!-- Emergency Fund Tip -->
            <div class="col-md-6 col-lg-3">
                <div class="quick-tip-card card h-100">
                    <div class="card-body">
                        <h6 class="card-title text-warning">
                            <i class="bi bi-shield-check me-2"></i>Emergency Fund
                        </h6>
                        <p class="card-text small">Build a fund covering 3-6 months of expenses for unexpected situations.</p>
                    </div>
                </div>
            </div>

            <!-- Review Regularly Tip -->
            <div class="col-md-6 col-lg-3">
                <div class="quick-tip-card card h-100">
                    <div class="card-body">
                        <h6 class="card-title text-danger">
                            <i class="bi bi-clock-history me-2"></i>Review Regularly
                        </h6>
                        <p class="card-text small">Monitor your budget weekly and adjust as needed to stay on track.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Article Modal -->
<div class="modal fade" id="articleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cache DOM elements
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const categoryIndicator = document.getElementById('categoryIndicator');
    const videoContainer = document.getElementById('videosContainer');
    const videoLevelSelect = document.getElementById('videoLevel');
    const videoCategoryPills = document.querySelectorAll('.video-category-pill');
    const resetVideoFilters = document.getElementById('resetVideoFilters');
    const videoModal = document.getElementById('videoModal');
    const quoteCategorySelect = document.getElementById('quoteCategory');
    const refreshQuotesBtn = document.getElementById('refreshQuotes');

    // State variables
    let currentArticleCategory = null;
    let currentVideoCategory = '';
    let currentVideoLevel = '';

    // Utility Functions
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    // Article Functions
    window.showArticle = function(articleId) {
        const modal = new bootstrap.Modal(document.getElementById('articleModal'));
        const modalTitle = document.querySelector('#articleModal .modal-title');
        const modalBody = document.querySelector('#articleModal .modal-body');

        // Show loading state
        modalTitle.textContent = 'Loading...';
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        modal.show();

        // Fetch article data
        fetch('learn-page.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=getArticle&id=${articleId}`
        })
        .then(response => response.json())
        .then(response => {
            if (!response.success) {
                throw new Error(response.error || 'Failed to load article');
            }
            const article = response.data;
            modalTitle.textContent = article.title;
            modalBody.innerHTML = article.content;
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${error.message || 'Error loading article. Please try again later.'}
                </div>
            `;
        });
    };

    function updateArticles(category = null) {
        currentArticleCategory = category;
        const articlesGrid = document.querySelector('.articles-grid');
        
        // Show loading state
        articlesGrid.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        // Reset category cards
        document.querySelectorAll('.category-card').forEach(card => {
            card.classList.remove('active');
        });

        // Update active category if selected
        if (category) {
            document.querySelector(`[data-category="${category}"]`).classList.add('active');
        }

        // Update articles status
        const articlesStatus = document.querySelector('.articles-status');
        articlesStatus.textContent = category ? 
            `Showing ${category.charAt(0).toUpperCase() + category.slice(1)} articles` : 
            'Showing all articles';

        // Fetch filtered articles
        fetch('learn-page.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=filterArticles&category=${category || ''}`
        })
        .then(response => response.json())
        .then(response => {
            if (!response.success) {
                throw new Error(response.error || 'Failed to load articles');
            }

            const articles = response.data;

            if (!articles || articles.length === 0) {
                articlesGrid.innerHTML = `
                    <div class="alert alert-custom-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        No articles found ${category ? `in ${category} category` : ''}.
                    </div>
                `;
                return;
            }

            // Build articles grid
            articlesGrid.innerHTML = articles.map(article => `
                <article class="article-card fade-in">
                    <h3 class="article-title">
                        ${escapeHtml(article.title)}
                    </h3>
                    <p class="article-description">
                        ${escapeHtml(article.preview)}
                    </p>
                    <div class="article-footer">
                        <span class="article-date">
                            ${formatDate(article.date_published)}
                        </span>
                        <button class="read-more-btn" onclick="showArticle(${article.id})">
                            Read More
                        </button>
                    </div>
                </article>
            `).join('');

            // Reset search if category changes
            if (searchInput.value) {
                searchInput.value = '';
                clearSearch.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            articlesGrid.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${error.message || 'Error loading articles. Please try again later.'}
                </div>
            `;
        });
    }

    // Video Functions
    function updateVideos(category = '', level = '') {
        videoContainer.innerHTML = `
            <div class="video-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading videos...</span>
                </div>
            </div>
        `;

        // Update active category pill
        videoCategoryPills.forEach(pill => {
            pill.classList.toggle('active', pill.dataset.category === category);
        });

        // Fetch filtered videos
        fetch('learn-page.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=filterVideos&category=${category}&level=${level}`
        })
        .then(response => response.json())
        .then(response => {
            if (!response.success) {
                throw new Error(response.error || 'Failed to load videos');
            }

            const videos = response.data;

            if (!videos || videos.length === 0) {
                videoContainer.innerHTML = `
                    <div class="no-videos-found">
                        <i class="bi bi-camera-video"></i>
                        <h4>No Videos Found</h4>
                        <p class="mb-0">Try adjusting your filters or check back later for new content.</p>
                    </div>
                `;
                return;
            }

            videoContainer.innerHTML = videos.map(video => `
                <div class="video-card" onclick="playVideo('${video.video_url}', '${escapeHtml(video.title)}')">
                    <div class="video-thumbnail">
                        <img src="${video.thumbnail_url}" 
                            alt="${escapeHtml(video.title)}" 
                            loading="lazy"
                            onerror="this.src='assets/imgs/thumbnails/default.jpg'">
                        <span class="video-duration">${video.duration}</span>
                        <div class="video-play-overlay">
                            <i class="bi bi-play-circle"></i>
                        </div>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title">${escapeHtml(video.title)}</h3>
                        <div class="video-meta">
                            <span class="video-level ${video.level}">
                                ${video.level.charAt(0).toUpperCase() + video.level.slice(1)}
                            </span>
                            <span class="video-category">
                                ${video.category.charAt(0).toUpperCase() + video.category.slice(1)}
                            </span>
                        </div>
                        <p class="video-description">${escapeHtml(video.description)}</p>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Error:', error);
            videoContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error loading videos. Please try again later.
                </div>
            `;
        });
    }

    // Video player function
    window.playVideo = function(videoUrl, videoTitle) {
        const modalTitle = videoModal.querySelector('.modal-title');
        const videoFrame = videoModal.querySelector('iframe');
        
        modalTitle.textContent = videoTitle;
        videoFrame.src = videoUrl;
        
        const modal = new bootstrap.Modal(videoModal);
        modal.show();
    };

    // Quotes Functions
    function updateQuotes(category = '') {
        const quotesContainer = document.getElementById('quotesContainer');
        const refreshButton = document.getElementById('refreshQuotes');
        const categorySelect = document.getElementById('quoteCategory');
        
        refreshButton.disabled = true;
        categorySelect.disabled = true;
        
        const refreshIcon = refreshButton.querySelector('i');
        refreshIcon.classList.add('spin');
        
        quotesContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading quotes...</span>
                </div>
                <p class="text-muted mt-2">Refreshing quotes...</p>
            </div>
        `;
        
        fetch('learn-page.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=getQuotes&category=${category}`
        })
        .then(response => response.json())
        .then(response => {
            if (!response.success) {
                throw new Error(response.error || 'Failed to load quotes');
            }
            
            quotesContainer.innerHTML = response.data.map(quote => `
                <div class="quote-card fade-in">
                    <i class="bi bi-quote quote-icon"></i>
                    <p class="quote-content">
                        "${escapeHtml(quote.content)}"
                    </p>
                    <p class="quote-author">
                        <span>- ${escapeHtml(quote.author)}</span>
                        <small class="text-white-50">
                            #${escapeHtml(quote.category.charAt(0).toUpperCase() + quote.category.slice(1))}
                        </small>
                    </p>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Error:', error);
            quotesContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error loading quotes. Please try again.
                </div>
            `;
        })
        .finally(() => {
            refreshButton.disabled = false;
            categorySelect.disabled = false;
            refreshIcon.classList.remove('spin');
        });
    }

    // Event Listeners
    // Category cards
    document.querySelectorAll('.category-card').forEach(card => {
        card.setAttribute('tabindex', '0');
        
        card.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });

        card.addEventListener('click', function() {
            const category = this.dataset.category;
            const isCurrentlyActive = this.classList.contains('active');
            updateArticles(isCurrentlyActive ? null : category);
        });
    });

    // Show all button
    document.getElementById('showAllButton').addEventListener('click', () => {
        updateArticles(null);
    });

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const articles = document.querySelectorAll('.article-card');
        let visibleCount = 0;
        
        articles.forEach(article => {
            const title = article.querySelector('.article-title').textContent.toLowerCase();
            const preview = article.querySelector('.article-description').textContent.toLowerCase();
            const isVisible = title.includes(searchTerm) || preview.includes(searchTerm);
            
            article.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        clearSearch.style.display = searchTerm ? 'block' : 'none';
        
        const articlesStatus = document.querySelector('.articles-status');
        articlesStatus.textContent = searchTerm ? 
            `Found ${visibleCount} article${visibleCount !== 1 ? 's' : ''}` :
            (currentArticleCategory ? 
                `Showing ${currentArticleCategory.charAt(0).toUpperCase() + currentArticleCategory.slice(1)} articles` : 
                'Showing all articles');

        // No results message
        const existingAlert = document.querySelector('.alert-custom-info');
        if (existingAlert) {
            existingAlert.remove();
        }

        if (visibleCount === 0) {
            document.querySelector('.articles-grid').insertAdjacentHTML('beforeend', `
                <div class="alert alert-custom-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    No articles found matching "${escapeHtml(searchTerm)}"
                </div>
            `);
        }
    });

    // Clear search
    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        this.style.display = 'none';
    });

    // Video section event listeners
    videoCategoryPills.forEach(pill => {
        pill.addEventListener('click', function() {
            currentVideoCategory = this.dataset.category;
            updateVideos(currentVideoCategory, currentVideoLevel);
        });
    });

    videoLevelSelect.addEventListener('change', function() {
        currentVideoLevel = this.value;
        updateVideos(currentVideoCategory, currentVideoLevel);
    });

    resetVideoFilters.addEventListener('click', function() {
        currentVideoCategory = '';
        currentVideoLevel = '';
        videoLevelSelect.value = '';
        updateVideos();
    });

    // Quotes section event listeners
    quoteCategorySelect.addEventListener('change', function() {
        updateQuotes(this.value);
    });

    refreshQuotesBtn.addEventListener('click', function() {
        const category = quoteCategorySelect.value;
        updateQuotes(category);
    });

    // Modal event listeners
    videoModal.addEventListener('hidden.bs.modal', function () {
        const iframe = this.querySelector('iframe');
        iframe.src = iframe.src; // Reload iframe to stop video
    });

    // Spin animation for refresh button
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin {
            animation: spin 1s linear infinite;
        }
    `;
    document.head.appendChild(style);

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Lazy loading for images
    function lazyLoadImages() {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Handle scroll performance
    let scrollTimeout;
    function throttleScroll() {
        if (scrollTimeout) {
            return;
        }

        scrollTimeout = setTimeout(() => {
            scrollTimeout = null;
            lazyLoadImages();
        }, 250);
    }

    // Scroll event listener with throttling
    window.addEventListener('scroll', throttleScroll, { passive: true });

    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        if (resizeTimeout) {
            clearTimeout(resizeTimeout);
        }
        resizeTimeout = setTimeout(function() {
            // Reinitialize any responsive components
            adjustLayoutForScreenSize();
        }, 250);
    });

    // Adjust layout based on screen size
    function adjustLayoutForScreenSize() {
        const isMobile = window.innerWidth < 768;
        const videosGrid = document.querySelector('.videos-grid');
        const articlesGrid = document.querySelector('.articles-grid');

        if (isMobile) {
            videosGrid?.classList.add('mobile-layout');
            articlesGrid?.classList.add('mobile-layout');
        } else {
            videosGrid?.classList.remove('mobile-layout');
            articlesGrid?.classList.remove('mobile-layout');
        }
    }

    // Handle keyboard navigation
    function handleKeyboardNavigation() {
        const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
        
        document.addEventListener('keydown', function(e) {
            // Handle escape key
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    const modalInstance = bootstrap.Modal.getInstance(activeModal);
                    modalInstance.hide();
                }
            }

            // Handle tab navigation in modals
            if (e.key === 'Tab') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    const focusable = activeModal.querySelectorAll(focusableElements);
                    const firstFocusable = focusable[0];
                    const lastFocusable = focusable[focusable.length - 1];

                    if (e.shiftKey) {
                        if (document.activeElement === firstFocusable) {
                            lastFocusable.focus();
                            e.preventDefault();
                        }
                    } else {
                        if (document.activeElement === lastFocusable) {
                            firstFocusable.focus();
                            e.preventDefault();
                        }
                    }
                }
            }
        });
    }

    // Error handling function
    function handleError(error, container, message = 'An error occurred. Please try again later.') {
        console.error('Error:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${message}
            </div>
        `;
    }

    // Initialize video progress tracking
    function initVideoProgress() {
        const videoCards = document.querySelectorAll('.video-card');
        videoCards.forEach(card => {
            card.addEventListener('click', function() {
                const videoId = this.dataset.videoId;
                if (videoId) {
                    // Store video progress in localStorage
                    const progress = localStorage.getItem(`video-progress-${videoId}`) || '0';
                    const videoFrame = videoModal.querySelector('iframe');
                    if (videoFrame && videoFrame.contentWindow.postMessage) {
                        // Send progress to video player (if supported)
                        videoFrame.contentWindow.postMessage({
                            event: 'seekTo',
                            time: parseFloat(progress)
                        }, '*');
                    }
                }
            });
        });
    }

    // Save video progress
    window.addEventListener('message', function(event) {
        if (event.data.event === 'videoProgress') {
            const videoId = document.querySelector('.video-card.active')?.dataset.videoId;
            if (videoId) {
                localStorage.setItem(`video-progress-${videoId}`, event.data.time);
            }
        }
    });

    // Initialize everything
    function init() {
        // Initial setup
        clearSearch.style.display = 'none';
        adjustLayoutForScreenSize();
        handleKeyboardNavigation();
        initVideoProgress();

        // Load initial content
        updateVideos();
        lazyLoadImages();

        // Add visibility change handler for video modal
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                const activeModal = bootstrap.Modal.getInstance(videoModal);
                if (activeModal) {
                    activeModal.hide();
                }
            }
        });
    }

    // Call init function
    init();
});
</script>

<?php include('includes/footer.php'); ?>