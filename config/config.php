<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'bachelor_room_expense_management');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Kolkata');
