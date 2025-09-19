<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Not logged in');
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 3;

echo "<h2>Debug Leader Remove Member Issue</h2>";
echo "<p>Current User ID: $user_id</p>";
echo "<p>Group ID: $group_id</p>";

// Check user's status in this group
$stmt = $conn->prepare("SELECT rg.*, r.id as room_id, r.name as room_name,
                        (SELECT is_admin FROM room_members WHERE room_id = r.id AND user_id = ?) as is_admin,
                        (SELECT is_leader FROM group_members WHERE group_id = rg.id AND user_id = ?) as is_leader
                        FROM room_groups rg 
                        JOIN rooms r ON rg.room_id = r.id
                        JOIN group_members gm ON rg.id = gm.group_id AND gm.user_id = ?
                        WHERE rg.id = ?");
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color: red;'>❌ You are not a member of this group</p>";
    exit();
}

$group = $result->fetch_assoc();
$is_admin = $group['is_admin'] == 1;
$is_leader = $group['is_leader'] == 1;

echo "<h3>Your Status:</h3>";
echo "<p>Is Admin: " . ($is_admin ? 'YES' : 'NO') . "</p>";
echo "<p>Is Leader: " . ($is_leader ? 'YES' : 'NO') . "</p>";
echo "<p>Can Remove Members: " . (($is_admin || $is_leader) ? 'YES' : 'NO') . "</p>";

// Get all members in this group
$stmt = $conn->prepare("SELECT gm.*, u.id as user_id, u.full_name, u.phone, u.email, u.profile_image 
                        FROM group_members gm 
                        JOIN users u ON gm.user_id = u.id 
                        WHERE gm.group_id = ?
                        ORDER BY gm.is_leader DESC, u.full_name ASC");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();

echo "<h3>Group Members and Remove Buttons:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>User ID</th><th>Name</th><th>Is Leader</th><th>Is You</th><th>Should Show Remove</th><th>Button Type</th></tr>";

while ($member = $members_result->fetch_assoc()) {
    $is_you = $member['user_id'] == $user_id;
    $should_show_remove = ($is_admin || $is_leader) && !$is_you;
    
    // Check if this member is a room admin
    $stmt_check_admin = $conn->prepare("SELECT rm.is_admin FROM room_members rm 
                                      JOIN room_groups rg ON rm.room_id = rg.room_id 
                                      WHERE rg.id = ? AND rm.user_id = ?");
    $stmt_check_admin->bind_param("ii", $group_id, $member['user_id']);
    $stmt_check_admin->execute();
    $admin_result = $stmt_check_admin->get_result();
    $is_room_admin = $admin_result->num_rows > 0 && $admin_result->fetch_assoc()['is_admin'] == 1;
    
    $button_type = '';
    if ($should_show_remove) {
        if ($is_room_admin) {
            $button_type = 'Remove Admin (Shield)';
        } else {
            $button_type = 'Remove Member (Minus)';
        }
    } else {
        $button_type = 'No Button';
    }
    
    echo "<tr>";
    echo "<td>" . $member['user_id'] . "</td>";
    echo "<td>" . htmlspecialchars($member['full_name']) . "</td>";
    echo "<td>" . ($member['is_leader'] ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($is_you ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($should_show_remove ? 'YES' : 'NO') . "</td>";
    echo "<td>" . $button_type . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the actual remove member process
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = intval($_POST['target_user_id']);
    $member_name = $_POST['member_name'] ?? '';
    
    echo "<h3>Testing Remove Member Process:</h3>";
    
    try {
        // Check permissions again
        $stmt = $conn->prepare("SELECT rg.room_id, rm.is_admin, gm.is_leader, r.name as room_name, rg.name as group_name
                               FROM room_groups rg
                               JOIN room_members rm ON rg.room_id = rm.room_id
                               JOIN group_members gm ON rg.id = gm.group_id
                               JOIN rooms r ON rg.room_id = r.id
                               WHERE rg.id = ? AND rm.user_id = ? AND gm.user_id = ?");
        $stmt->bind_param("iii", $group_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('You are not authorized to remove members from this group');
        }
        
        $user_info = $result->fetch_assoc();
        $is_admin_check = $user_info['is_admin'] == 1;
        $is_leader_check = $user_info['is_leader'] == 1;
        
        echo "<p>Permission Check - Is Admin: " . ($is_admin_check ? 'YES' : 'NO') . "</p>";
        echo "<p>Permission Check - Is Leader: " . ($is_leader_check ? 'YES' : 'NO') . "</p>";
        
        if (!$is_admin_check && !$is_leader_check) {
            throw new Exception('Only room admins or group leaders can remove members');
        }
        
        echo "<p style='color: green;'>✅ Permission check passed!</p>";
        echo "<p>You can remove members from this group.</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Test Remove Member:</h3>";
echo "<form method='post'>";
echo "<p>Target User ID: <input type='number' name='target_user_id' required></p>";
echo "<p>Member Name: <input type='text' name='member_name' required></p>";
echo "<button type='submit'>Test Remove</button>";
echo "</form>";

echo "<p><a href='group.php?id=$group_id'>Go to Group Page</a></p>";
echo "<p><a href='index.php'>Back to Home</a></p>";
?>
