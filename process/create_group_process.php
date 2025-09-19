<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['create_group'])) {
    $room_id = intval($_POST['room_id']);
    $group_name = trim($_POST['group_name']);
    $group_description = trim($_POST['group_description'] ?? '');
    
    // Validate inputs
    if (empty($group_name)) {
        header("Location: ../index.php?id=$room_id&error=Group name is required");
        exit();
    }
    
    // Check if user is admin of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ? AND is_admin = 1");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
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
        
        header("Location: ../index.php?id=$room_id&success=Group created successfully");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../index.php?id=$room_id&error=Failed to create group: " . $e->getMessage());
        exit();
    }
} else {
    // If accessed directly without form submission
    header("Location: ../index.php");
    exit();
}
?>