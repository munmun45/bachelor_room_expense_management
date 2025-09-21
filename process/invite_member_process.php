<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'invite_member_process', 'User attempted to access invite_member_process without login');
    }
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Log user activity - member invitation attempt
logUserActivity($conn, $user_id, 'member_invite_attempt', 'invite_member_process', 'User attempting to invite member to room');

if (isset($_POST['invite_member'])) {
    $room_id = intval($_POST['room_id']);
    $phone = trim($_POST['phone']);
    
    // Log user activity - processing invitation
    logUserActivity($conn, $user_id, 'member_invite_processing', 'invite_member_process', 'Processing invitation for phone: ' . $phone . ' to room ID: ' . $room_id);
    
    // Validate inputs
    if (empty($phone)) {
        // Log user activity - missing phone
        logUserActivity($conn, $user_id, 'member_invite_failed', 'invite_member_process', 'Member invitation failed: Phone number is required');
        header("Location: ../room-details.php?id=$room_id&error=Phone number is required");
        exit();
    }
    
    // Check if user is admin of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ? AND is_admin = 1");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - not admin
        logUserActivity($conn, $user_id, 'member_invite_failed', 'invite_member_process', 'Member invitation failed: User not admin of room ID: ' . $room_id);
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
        
        // Log user activity - invitation sent to non-registered user
        logUserActivity($conn, $user_id, 'member_invited_non_registered', 'invite_member_process', 'Invitation sent to non-registered user with phone: ' . $phone . ' for room ID: ' . $room_id);
        
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
            // Log user activity - user already member
            logUserActivity($conn, $user_id, 'member_invite_failed', 'invite_member_process', 'Member invitation failed: User already member of room ID: ' . $room_id);
            header("Location: ../room-details.php?id=$room_id&error=User is already a member of this room");
            exit();
        }
        
        // Add user as member with pending status
        $status = 'pending';
        $stmt = $conn->prepare("INSERT INTO room_members (room_id, user_id, is_admin, invitation_status, joined_at) VALUES (?, ?, 0, ?, NULL)");
        $stmt->bind_param("iis", $room_id, $invited_user_id, $status);
        $stmt->execute();
        
        // Log user activity - member invited successfully
        logUserActivity($conn, $user_id, 'member_invited', 'invite_member_process', 'Member invited successfully: User ID ' . $invited_user_id . ' to room ID: ' . $room_id);
        
        header("Location: ../room-details.php?id=$room_id&success=Member invited successfully");
        exit();
    }
} else {
    // If accessed directly without form submission
    // Log user activity - unauthorized access
    logUserActivity($conn, $user_id, 'unauthorized_access', 'invite_member_process', 'User attempted to access invite_member_process without form submission');
    header("Location: ../index.php");
    exit();
}
?>