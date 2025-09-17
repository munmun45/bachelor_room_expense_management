<?php
require_once 'config/config.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully\n";

// Check if tables exist
$tables = ['users', 'rooms', 'room_members', 'room_groups', 'group_members'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "Table $table exists\n";
    } else {
        echo "Table $table does NOT exist\n";
    }
}

// Check users table structure
echo "\nUsers table structure:\n";
$result = $conn->query("DESCRIBE users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}

// Check room_members table structure
echo "\nRoom members table structure:\n";
$result = $conn->query("DESCRIBE room_members");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}
?>