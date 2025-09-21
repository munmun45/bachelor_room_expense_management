<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'create_group_process', 'User attempted to access create_group_process without login');
    }
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Log user activity - group creation attempt
logUserActivity($conn, $user_id, 'group_create_attempt', 'create_group_process', 'User attempting to create group');

if (isset($_POST['create_group'])) {
    $room_id = intval($_POST['room_id']);
    $group_name = trim($_POST['group_name']);
    $group_description = trim($_POST['group_description'] ?? '');
    
    // Log user activity - processing group creation
    logUserActivity($conn, $user_id, 'group_create_processing', 'create_group_process', 'Processing group creation: ' . $group_name . ' in room ID: ' . $room_id);
    
    // Validate inputs
    if (empty($group_name)) {
        // Log user activity - missing group name
        logUserActivity($conn, $user_id, 'group_create_failed', 'create_group_process', 'Group creation failed: Group name is required');
        header("Location: ../index.php?id=$room_id&error=Group name is required");
        exit();
    }
    
    // Check if user is admin of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ? AND is_admin = 1");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - not admin
        logUserActivity($conn, $user_id, 'group_create_failed', 'create_group_process', 'Group creation failed: User not admin of room ID: ' . $room_id);
        header("Location: ../index.php?id=$room_id&error=Only room admin can create groups");
        exit();
    }
    
    // Create group
    $conn->begin_transaction();
    
    try {
        // Insert group
        $stmt = $conn->prepare("INSERT INTO room_groups (room_id, name, description, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("issi", $room_id, $group_name, $group_description, $user_id);
        $stmt->execute();
        
        $group_id = $conn->insert_id;
        
        // Add creator to the group as leader (since they are admin)
        $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, is_leader, added_by, added_at) VALUES (?, ?, 1, ?, NOW())");
        $stmt->bind_param("iii", $group_id, $user_id, $user_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Log user activity - group created successfully
        logUserActivity($conn, $user_id, 'group_created', 'create_group_process', 'Group created successfully: ' . $group_name . ' with ID: ' . $group_id . ' in room ID: ' . $room_id);
        
        header("Location: ../index.php?id=$room_id&success=Group created successfully");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        
        // Log user activity - group creation failed
        logUserActivity($conn, $user_id, 'group_create_failed', 'create_group_process', 'Group creation failed: ' . $e->getMessage());
        
        header("Location: ../index.php?id=$room_id&error=Failed to create group: " . $e->getMessage());
        exit();
    }
} else {
    // If accessed directly without form submission
    // Log user activity - unauthorized access
    logUserActivity($conn, $user_id, 'unauthorized_access', 'create_group_process', 'User attempted to access create_group_process without form submission');
    header("Location: ../index.php");
    exit();
}
?>