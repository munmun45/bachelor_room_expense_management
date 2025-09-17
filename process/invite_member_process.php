<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['invite_member'])) {
    $room_id = intval($_POST['room_id']);
    $phone = trim($_POST['phone']);
    
    // Validate inputs
    if (empty($phone)) {
        header("Location: ../room-details.php?id=$room_id&error=Phone number is required");
        exit();
    }
    
    // Check if user is admin of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ? AND is_admin = 1");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../room-details.php?id=$room_id&error=Only room admin can invite members");
        exit();
    }
    
    // Check if user with this phone exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User doesn't exist, create invitation for non-registered user
        $invitation_code = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $conn->prepare("INSERT INTO member_invitations (room_id, phone, invited_by, invitation_code, created_at, expires_at) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("iisss", $room_id, $phone, $user_id, $invitation_code, $expires_at);
        $stmt->execute();
        
        // In a real app, send SMS with invitation link
        header("Location: ../room-details.php?id=$room_id&success=Invitation sent to non-registered user");
        exit();
    } else {
        $invited_user_id = $result->fetch_assoc()['id'];
        
        // Check if user is already a member
        $stmt = $conn->prepare("SELECT id FROM room_members WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $room_id, $invited_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            header("Location: ../room-details.php?id=$room_id&error=User is already a member of this room");
            exit();
        }
        
        // Add user as member with pending status
        $status = 'pending';
        $stmt = $conn->prepare("INSERT INTO room_members (room_id, user_id, is_admin, invitation_status, joined_at) VALUES (?, ?, 0, ?, NULL)");
        $stmt->bind_param("iis", $room_id, $invited_user_id, $status);
        $stmt->execute();
        
        header("Location: ../room-details.php?id=$room_id&success=Member invited successfully");
        exit();
    }
} else {
    // If accessed directly without form submission
    header("Location: ../index.php");
    exit();
}
?>