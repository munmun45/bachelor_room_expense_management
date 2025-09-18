<?php
require_once 'config/config.php';

echo "<h2>Debug: Group Members Analysis</h2>";

// Check all rooms and their members
echo "<h3>Room Members:</h3>";
$stmt = $conn->prepare("SELECT r.id as room_id, r.name as room_name, rm.user_id, u.full_name, rm.invitation_status 
                        FROM rooms r 
                        LEFT JOIN room_members rm ON r.id = rm.room_id 
                        LEFT JOIN users u ON rm.user_id = u.id 
                        ORDER BY r.id, u.full_name");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "Room {$row['room_id']} ({$row['room_name']}): User {$row['user_id']} - {$row['full_name']} - Status: {$row['invitation_status']}<br>";
}

echo "<br><h3>Group Members:</h3>";
$stmt = $conn->prepare("SELECT rg.id as group_id, rg.name as group_name, rg.room_id, gm.user_id, u.full_name 
                        FROM room_groups rg 
                        LEFT JOIN group_members gm ON rg.id = gm.group_id 
                        LEFT JOIN users u ON gm.user_id = u.id 
                        ORDER BY rg.id, u.full_name");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "Group {$row['group_id']} ({$row['group_name']}) in Room {$row['room_id']}: User {$row['user_id']} - {$row['full_name']}<br>";
}

echo "<br><h3>Available Members for Each Group (Accepted Room Members NOT in Group):</h3>";
$stmt = $conn->prepare("SELECT rg.id as group_id, rg.name as group_name, 
                        COUNT(rm.user_id) as available_members
                        FROM room_groups rg 
                        LEFT JOIN room_members rm ON rg.room_id = rm.room_id 
                        AND rm.invitation_status = 'accepted' 
                        AND rm.user_id NOT IN (SELECT user_id FROM group_members WHERE group_id = rg.id)
                        GROUP BY rg.id, rg.name");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "Group {$row['group_id']} ({$row['group_name']}): {$row['available_members']} available members to add<br>";
}
?>
