<?php
require_once 'config/config.php';

// Check if required tables exist
$tables = ['rooms', 'room_members', 'room_groups', 'group_members', 'users'];

echo "Checking database structure...\n\n";

foreach ($tables as $table) {
    echo "Checking table: $table\n";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "  ✓ Table exists\n";
        
        // Show table structure
        $desc_result = $conn->query("DESCRIBE $table");
        if ($desc_result) {
            echo "  Columns:\n";
            while ($row = $desc_result->fetch_assoc()) {
                echo "    - " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        }
    } else {
        echo "  ✗ Table does not exist\n";
    }
    echo "\n";
}

// Try to prepare the statement that's failing in room-details.php
echo "Testing the problematic query...\n";
$stmt = $conn->prepare("SELECT r.*, DATE_FORMAT(r.created_at, '%b %d, %Y') as formatted_date, 
                        (SELECT COUNT(*) FROM room_members WHERE room_id = r.id) as member_count,
                        rm.is_admin 
                        FROM rooms r 
                        JOIN room_members rm ON r.id = rm.room_id AND rm.user_id = ? 
                        WHERE r.id = ?");

if ($stmt === false) {
    echo "Prepare failed: " . $conn->error . "\n";
} else {
    echo "Query prepared successfully\n";
    $stmt->close();
}
?>