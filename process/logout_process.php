<?php
// Start the session
session_start();

// Log user activity for logout
if (isset($_SESSION['user_id'])) {
    require_once '../config/config.php';
    if (function_exists('logUserActivity')) {
        logUserActivity($conn, $_SESSION['user_id'], 'user_logout', 'logout_process.php', 'User logged out');
    }
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../login.php");
exit();
?>