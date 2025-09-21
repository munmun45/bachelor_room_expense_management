<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'join_group_process', 'User attempted to access join_group_process without login');
    }
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

// Log user activity - group join attempt
logUserActivity($conn, $user_id, 'group_join_attempt', 'join_group_process', 'User attempting to join group ID: ' . $group_id . ' in room ID: ' . $room_id);

if ($group_id && $room_id) {
    // Check if group exists and belongs to the room
    $stmt = $conn->prepare("SELECT id FROM room_groups WHERE id = ? AND room_id = ?");
    $stmt->bind_param("ii", $group_id, $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - group not found
        logUserActivity($conn, $user_id, 'group_join_failed', 'join_group_process', 'Group join failed: Group ID ' . $group_id . ' not found in room ID: ' . $room_id);
        header("Location: ../room-details.php?id=$room_id&error=Group not found");
        exit();
    }
    
    // Check if user is already a member of the group
    $stmt = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Log user activity - already member
        logUserActivity($conn, $user_id, 'group_join_failed', 'join_group_process', 'Group join failed: User already member of group ID: ' . $group_id);
        header("Location: ../room-details.php?id=$room_id&error=You are already a member of this group");
        exit();
    }
    
    // Add user to the group
    $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, added_by, added_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iii", $group_id, $user_id, $user_id);
    $stmt->execute();
    
    // Log user activity - joined group successfully
    logUserActivity($conn, $user_id, 'group_joined', 'join_group_process', 'User joined group ID: ' . $group_id . ' successfully');
    
    header("Location: ../room-details.php?id=$room_id&success=You have joined the group");
    exit();
} else {
    // Log user activity - invalid parameters
    logUserActivity($conn, $user_id, 'group_join_failed', 'join_group_process', 'Group join failed: Invalid group ID or room ID');
    header("Location: ../index.php");
    exit();
}
?>