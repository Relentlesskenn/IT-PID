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
            $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING));
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

// Get article counts for each category
$basicsCount = getArticleCountByCategory($conn, 'basics');
$savingsCount = getArticleCountByCategory($conn, 'savings');
$goalsCount = getArticleCountByCategory($conn, 'goals');

?>

<link rel="stylesheet" href="./assets/css/learn.css">

<!-- HTML content -->
<div class="learn-page py-4">
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
                <div class="category-card card" data-category="basics">
                    <div class="card-body text-center py-4">
                        <i class="bi bi-book resource-icon"></i>
                        <h5 class="card-title">Budgeting Basics</h5>
                        <p class="card-text">Master the fundamentals of personal finance</p>
                        <span class="badge"><?= $basicsCount ?> articles</span>
                    </div>
                </div>
            </div>

            <!-- Saving Tips Category -->
            <div class="col-md-4">
                <div class="category-card card" data-category="savings">
                    <div class="card-body text-center py-4">
                        <i class="bi bi-piggy-bank resource-icon"></i>
                        <h5 class="card-title">Saving Tips</h5>
                        <p class="card-text">Smart strategies to grow your savings</p>
                        <span class="badge"><?= $savingsCount ?> articles</span>
                    </div>
                </div>
            </div>

            <!-- Financial Goals Category -->
            <div class="col-md-4">
                <div class="category-card card" data-category="goals">
                    <div class="card-body text-center py-4">
                        <i class="bi bi-trophy resource-icon"></i>
                        <h5 class="card-title">Financial Goals</h5>
                        <p class="card-text">Turn your financial dreams into reality</p>
                        <span class="badge"><?= $goalsCount ?> articles</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Articles Section -->
        <div class="container">
            <div class="section-header mb-4">
                <h2>Latest Articles</h2>
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
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const articlesContainer = document.getElementById('articlesContainer');
    const categoryIndicator = document.getElementById('categoryIndicator');
    let currentCategory = null;

    // Function to safely escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Function to format date
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    // Show article in modal
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

    // Function to update articles display
    function updateArticles(category = null) {
        // Show loading state
        articlesContainer.innerHTML = `
            <div class="col-12 text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        // Update category indicator
        currentCategory = category;
        categoryIndicator.textContent = category ? 
            `Showing ${category.charAt(0).toUpperCase() + category.slice(1)} articles` : 
            'Showing all articles';

        // Update active category visual
        document.querySelectorAll('.category-card').forEach(card => {
            if (category && card.dataset.category === category) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });

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
            articlesContainer.innerHTML = '';
            
            if (!articles || articles.length === 0) {
                articlesContainer.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-custom-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            No articles found in this category.
                        </div>
                    </div>
                `;
                return;
            }

            articles.forEach(article => {
                const articleCard = `
                    <div class="col-md-6 col-lg-4 fade-in">
                        <div class="card learn-card article-card">
                            <div class="card-body">
                                <h6 class="card-title mb-3">${escapeHtml(article.title)}</h6>
                                <p class="card-text article-preview">${escapeHtml(article.preview)}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">${formatDate(article.date_published)}</small>
                                    <button class="btn btn-custom-primary-rounded btn-sm" 
                                            onclick="showArticle(${article.id})">
                                        Read More
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                articlesContainer.insertAdjacentHTML('beforeend', articleCard);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            articlesContainer.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        ${error.message || 'Error loading articles. Please try again later.'}
                    </div>
                </div>
            `;
        });
    }

    // Add event listeners for category cards
    document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', function() {
            const category = this.dataset.category;
            const isCurrentlyActive = this.classList.contains('active');
            
            // If clicking the active category, show all articles
            if (isCurrentlyActive) {
                updateArticles(null);
            } else {
                updateArticles(category);
            }
        });
    });

    // Show all articles button
    document.getElementById('showAllButton').addEventListener('click', function() {
        document.querySelectorAll('.category-card').forEach(card => {
            card.classList.remove('active');
        });
        updateArticles(null);
    });

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const articles = document.querySelectorAll('.article-card');
        let visibleCount = 0;
        
        articles.forEach(article => {
            const title = article.querySelector('.card-title').textContent.toLowerCase();
            const preview = article.querySelector('.article-preview').textContent.toLowerCase();
            const parent = article.closest('.col-md-6');
            
            if (title.includes(searchTerm) || preview.includes(searchTerm)) {
                parent.style.display = '';
                visibleCount++;
            } else {
                parent.style.display = 'none';
            }
        });
        
        // Update search clear button visibility
        clearSearch.style.display = searchTerm ? 'block' : 'none';
        
        // Update category indicator during search
        if (searchTerm) {
            categoryIndicator.textContent = `Found ${visibleCount} article${visibleCount !== 1 ? 's' : ''}`;
        } else {
            categoryIndicator.textContent = currentCategory ? 
                `Showing ${currentCategory.charAt(0).toUpperCase() + currentCategory.slice(1)} articles` : 
                'Showing all articles';
        }
        
        // Show/hide no results message
        const noResultsMessage = document.querySelector('.no-results-message');
        if (visibleCount === 0) {
            if (!noResultsMessage) {
                const message = `
                    <div class="col-12 no-results-message fade-in">
                        <div class="alert alert-custom-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            No articles found matching "${escapeHtml(searchTerm)}"
                        </div>
                    </div>
                `;
                articlesContainer.insertAdjacentHTML('beforeend', message);
            }
        } else if (noResultsMessage) {
            noResultsMessage.remove();
        }
    });

    // Clear search
    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        this.style.display = 'none';
        
        // Reset category indicator
        categoryIndicator.textContent = currentCategory ? 
            `Showing ${currentCategory.charAt(0).toUpperCase() + currentCategory.slice(1)} articles` : 
            'Showing all articles';
    });

    // Initialize tooltips if any exist
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initial setup
    clearSearch.style.display = 'none';
    categoryIndicator.textContent = 'Showing all articles';
});
</script>

<?php include('includes/footer.php'); ?>