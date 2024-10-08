<?php
include('_dbconnect.php');
include('authentication.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'];
    $budgetId = $_POST['budgetId'];
    $alertType = $_POST['alertType'];

    // Sanitize inputs
    $userId = mysqli_real_escape_string($conn, $userId);
    $budgetId = mysqli_real_escape_string($conn, $budgetId);
    $alertType = mysqli_real_escape_string($conn, $alertType);

    $sql = "INSERT INTO budget_alerts (user_id, budget_id, alert_type) VALUES ('$userId', '$budgetId', '$alertType')";
    mysqli_query($conn, $sql);
}
?>