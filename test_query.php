<?php
require_once 'config/config.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully\n";

// Test the specific query that's failing
$room_id = 1; // Assuming room ID 1 exists
$query = "SELECT rm.*, u.full_name, u.phone, u.email, u.profile_image 
          FROM room_members rm 
          JOIN users u ON rm.user_id = u.id 
          WHERE rm.room_id = ?
          ORDER BY rm.is_admin DESC, u.full_name ASC";

echo "Testing query: $query\n";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    echo "Prepare failed: " . $conn->error . "\n";
} else {
    echo "Prepare succeeded\n";
    $stmt->bind_param("i", $room_id);
    if ($stmt->execute()) {
        echo "Execute succeeded\n";
        $result = $stmt->get_result();
        echo "Found " . $result->num_rows . " rows\n";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Execute failed: " . $stmt->error . "\n";
    }
}
?>