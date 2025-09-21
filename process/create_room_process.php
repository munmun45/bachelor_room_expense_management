<?php
// Start session
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['create_room'])) {
    // Get form data
    $room_name = trim($_POST['room_name']);
    $room_description = trim($_POST['room_description'] ?? '');
    $room_address = trim($_POST['room_address']);
    $latitude = trim($_POST['latitude'] ?? null);
    $longitude = trim($_POST['longitude'] ?? null);
    $rental_date = !empty($_POST['rental_date']) ? $_POST['rental_date'] : null;
    $monthly_rent = !empty($_POST['monthly_rent']) ? $_POST['monthly_rent'] : null;
    $advance_payment = !empty($_POST['advance_payment']) ? $_POST['advance_payment'] : null;
    $user_id = $_SESSION['user_id'];
    
    // Log user activity - room creation attempt
    logUserActivity($conn, $user_id, 'room_create_attempt', 'create_room_process', 'User attempting to create room: ' . $room_name);
    
    // Validate inputs
    if (empty($room_name) || empty($room_address)) {
        // Log user activity - room creation failed
        logUserActivity($conn, $user_id, 'room_create_failed', 'create_room_process', 'Room creation failed: Name or address missing');
        header("Location: ../index.php?error=Room name and address are required");
        exit();
    }
    
    // Create room
    $conn->begin_transaction();
    
    try {
        // Insert room
        $stmt = $conn->prepare("INSERT INTO rooms (name, description, address, latitude, longitude, rental_date, monthly_rent, advance_payment, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        // Check if prepare was successful
        if ($stmt === false) {
            throw new Exception("Failed to prepare room insertion statement: " . $conn->error);
        }
        
        $stmt->bind_param("ssssssssi", $room_name, $room_description, $room_address, $latitude, $longitude, $rental_date, $monthly_rent, $advance_payment, $user_id);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to execute room insertion: " . $stmt->error);
        }
        
        $room_id = $conn->insert_id;
        
        // Add creator as admin member
        $is_admin = 1;
        $stmt = $conn->prepare("INSERT INTO room_members (room_id, user_id, is_admin, joined_at, last_activity) VALUES (?, ?, ?, NOW(), NOW())");
        
        // Check if prepare was successful
        if ($stmt === false) {
            throw new Exception("Failed to prepare room member insertion statement: " . $conn->error);
        }
        
        $stmt->bind_param("iii", $room_id, $user_id, $is_admin);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to execute room member insertion: " . $stmt->error);
        }
        
        // Create default group for common expenses
        $group_name = "Common Expenses";
        $group_description = "Shared expenses like rent, electricity, water, etc.";
        $stmt = $conn->prepare("INSERT INTO room_groups (room_id, name, description, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        // Check if prepare was successful
        if ($stmt === false) {
            throw new Exception("Failed to prepare room group insertion statement: " . $conn->error);
        }
        
        $stmt->bind_param("issi", $room_id, $group_name, $group_description, $user_id);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to execute room group insertion: " . $stmt->error);
        }
        
        $group_id = $conn->insert_id;
        
        // Add creator to the default group
        $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, added_by, added_at) VALUES (?, ?, ?, NOW())");
        
        // Check if prepare was successful
        if ($stmt === false) {
            throw new Exception("Failed to prepare group member insertion statement: " . $conn->error);
        }
        
        $stmt->bind_param("iii", $group_id, $user_id, $user_id);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Failed to execute group member insertion: " . $stmt->error);
        }
        
        // Set the creator as the leader of the group
        $stmt = $conn->prepare("UPDATE group_members SET is_leader = 1 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Log user activity - room created successfully
        logUserActivity($conn, $user_id, 'room_created', 'create_room_process', 'Room created successfully with ID: ' . $room_id);
        
        // Redirect to room details page
        header("Location: ../room-details.php?id=$room_id&success=Room created successfully");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Room creation error: " . $e->getMessage());
        // Log user activity - room creation failed
        logUserActivity($conn, $user_id, 'room_create_failed', 'create_room_process', 'Room creation failed: ' . $e->getMessage());
        header("Location: ../index.php?error=Failed to create room: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If accessed directly without form submission
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'create_room_process', 'User attempted to access create_room_process without form submission');
    }
    header("Location: ../index.php");
    exit();
}
?>