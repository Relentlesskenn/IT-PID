<?php
/**
 * Main header file for IT-PID Budget Tracking App
 * Contains essential meta tags, security headers, and required resources
 */

// Strict type declarations
declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers with explanatory comments
$securityHeaders = [
    'X-Frame-Options' => 'DENY',                    // Prevent clickjacking
    'X-XSS-Protection' => '1; mode=block',         // Enable XSS filtering
    'X-Content-Type-Options' => 'nosniff',         // Prevent MIME-type sniffing
    'Referrer-Policy' => 'strict-origin-when-cross-origin', // Control referrer information
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()', // Restrict browser features
];

foreach ($securityHeaders as $header => $value) {
    header("$header: $value");
}

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Cache control for dynamic content
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Default meta values
$defaultMeta = [
    'description' => 'IT-PID - Your personal budgeting app',
    'keywords' => 'budget, finance, money management, expense tracking, savings goals',
    'author' => 'IT-PID Team',
];

// Merge custom meta with defaults
$meta_description = $meta_description ?? $defaultMeta['description'];
$meta_keywords = $meta_keywords ?? $defaultMeta['keywords'];

// Determine environment for resource loading
$isProduction = ($_SERVER['SERVER_NAME'] ?? 'localhost') !== 'localhost';
$resourceVersion = $isProduction ? '1.0.0' : time();
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($defaultMeta['author']); ?>">
    <meta name="theme-color" content="#433878">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title ?? 'IT-PID Budget Tracker'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/imgs/logo/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="assets/imgs/logo/apple-touch-icon.png">
    
    <!-- Stylesheets with versioning -->
    <link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css?v=<?php echo $resourceVersion; ?>">
    <link rel="stylesheet" href="vendor/twbs/bootstrap-icons/font/bootstrap-icons.css?v=<?php echo $resourceVersion; ?>">
    <link rel="stylesheet" href="assets/css/fonts.css?v=<?php echo $resourceVersion; ?>">
    <link rel="stylesheet" href="assets/css/global.css?v=<?php echo $resourceVersion; ?>">
    <link rel="stylesheet" href="assets/css/custom-alerts.css?v=<?php echo $resourceVersion; ?>">
    
    <!-- Page-specific CSS -->
    <?php if (isset($pageSpecificCSS)): ?>
        <?php foreach ($pageSpecificCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>?v=<?php echo $resourceVersion; ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Title -->
    <title><?php echo htmlspecialchars($page_title ?? 'IT-PID Budget Tracker'); ?></title>

    <!-- Inline critical CSS for faster page load -->
    <style>
        .content-loading {
            opacity: 0;
            transition: opacity 0.3s ease-in;
        }
        .content-loaded {
            opacity: 1;
        }
        #pageLoadingIndicator {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }
        #pageLoadingIndicator.hidden {
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- Page loading indicator -->
    <div id="pageLoadingIndicator">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Main content wrapper -->
    <main id="mainContent" class="flex-shrink-0">