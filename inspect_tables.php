<?php
require_once 'config/config.php';

// Check if rooms table exists and show its structure
$tables = ['rooms', 'room_members', 'room_groups', 'group_members'];

foreach ($tables as $table) {
    echo "Checking table: $table\n";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        echo "Table $table does not exist\n\n";
        continue;
    }
    
    // Show table structure
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "Columns in $table:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
    echo "\n";
}

// Try to prepare the statement that's failing
$stmt = $conn->prepare("INSERT INTO rooms (name, description, address, latitude, longitude, rental_date, monthly_rent, advance_payment, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

if ($stmt === false) {
    echo "Prepare failed: " . $conn->error . "\n";
} else {
    echo "Statement prepared successfully\n";
    $stmt->close();
}
?>