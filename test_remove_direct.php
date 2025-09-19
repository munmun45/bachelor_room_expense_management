<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Not logged in');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = intval($_POST['group_id']);
    $target_user_id = intval($_POST['target_user_id']);
    $member_name = $_POST['member_name'] ?? '';
    
    echo "<h3>Testing Remove Member Directly:</h3>";
    echo "<p>Group ID: $group_id</p>";
    echo "<p>Target User ID: $target_user_id</p>";
    echo "<p>Member Name: $member_name</p>";
    
    // Simulate the exact same request that the JavaScript makes
    $_POST['group_id'] = $group_id;
    $_POST['user_id'] = $target_user_id;
    $_POST['member_name'] = $member_name;
    
    // Capture the output
    ob_start();
    include 'process/remove_group_member_process.php';
    $output = ob_get_clean();
    
    echo "<h4>Response from remove_group_member_process.php:</h4>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Try to decode the JSON response
    $response = json_decode($output, true);
    if ($response) {
        echo "<h4>Decoded Response:</h4>";
        echo "<pre>" . print_r($response, true) . "</pre>";
        
        if (isset($response['success']) && $response['success']) {
            echo "<p style='color: green;'>✅ Member removed successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . ($response['error'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON response</p>";
    }
}

echo "<h2>Direct Remove Member Test</h2>";
echo "<p>Current User ID: $user_id</p>";

// Show current groups and members
$result = $conn->query("SELECT rg.id, rg.name as group_name, r.name as room_name,
                       (SELECT u.full_name FROM group_members gm 
                        JOIN users u ON gm.user_id = u.id 
                        WHERE gm.group_id = rg.id AND gm.is_leader = 1) as leader_name
                       FROM room_groups rg
                       JOIN rooms r ON rg.room_id = r.id
                       ORDER BY rg.id");

echo "<h3>Available Groups:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Group ID</th><th>Group Name</th><th>Room</th><th>Leader</th><th>Actions</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['group_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['room_name']) . "</td>";
    echo "<td>" . ($row['leader_name'] ? htmlspecialchars($row['leader_name']) : 'No Leader') . "</td>";
    echo "<td><a href='group.php?id=" . $row['id'] . "'>View Group</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Test Remove Member:</h3>";
echo "<form method='post'>";
echo "<p>Group ID: <input type='number' name='group_id' value='3' required></p>";
echo "<p>Target User ID: <input type='number' name='target_user_id' required></p>";
echo "<p>Member Name: <input type='text' name='member_name' required></p>";
echo "<button type='submit'>Test Remove Member</button>";
echo "</form>";

echo "<p><a href='index.php'>Back to Home</a></p>";
?>
