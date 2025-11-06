<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'bachelor_room_expense_management');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include activity tracker with absolute path
require_once __DIR__ . '/../includes/activity_tracker.php';
// require_once $_SERVER['DOCUMENT_ROOT'] . '/brem/includes/activity_tracker.php';


date_default_timezone_set('Asia/Kolkata');