<?php
session_start();

// Database connection
$admin_conn = new mysqli('localhost', 'root', '', 'bachelor_room_expense_management');

// Check connection
if ($admin_conn->connect_error) {
    die("Connection failed: " . $admin_conn->connect_error);
}

// Include activity tracker with absolute path
require_once __DIR__ . '/../includes/activity_tracker.php';

// Function to check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Function to redirect to login if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to get admin info
function getAdminInfo($admin_conn, $admin_id) {
    $stmt = $admin_conn->prepare("SELECT id, username, email, full_name, profile_image FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Function to update last login time
function updateLastLogin($admin_conn, $admin_id) {
    $stmt = $admin_conn->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
}
?>