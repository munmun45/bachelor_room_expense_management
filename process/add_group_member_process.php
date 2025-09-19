<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['add_members'])) {
    $group_id = intval($_POST['group_id']);
    $room_id = intval($_POST['room_id']);
    $member_ids = isset($_POST['member_ids']) ? $_POST['member_ids'] : [];
    
    // Validate inputs
    if (empty($member_ids)) {
        header("Location: ../group.php?id=$group_id&error=Please select at least one member to add");
        exit();
    }
    
    // Check if user is admin of the room OR is a group leader
    $stmt = $conn->prepare("SELECT rm.is_admin, gm.is_leader FROM room_members rm 
                           JOIN room_groups rg ON rm.room_id = rg.room_id 
                           JOIN group_members gm ON rg.id = gm.group_id
                           WHERE rg.id = ? AND rm.user_id = ? AND gm.user_id = ?");
    $stmt->bind_param("iii", $group_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../group.php?id=$group_id&error=Only room admin or group leader can add members to groups");
        exit();
    }
    
    $user_info = $result->fetch_assoc();
    $is_admin = $user_info['is_admin'] == 1;
    $is_leader = $user_info['is_leader'] == 1;
    
    // Check if user has permission (admin OR group leader)
    if (!$is_admin && !$is_leader) {
        header("Location: ../group.php?id=$group_id&error=Only room admin or group leader can add members to groups");
        exit();
    }
    
    // Verify that the group belongs to the room
    $stmt = $conn->prepare("SELECT room_id FROM room_groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../index.php?error=Group not found");
        exit();
    }
    
    $group_data = $result->fetch_assoc();
    if ($group_data['room_id'] != $room_id) {
        header("Location: ../group.php?id=$group_id&error=Invalid room or group");
        exit();
    }
    
    $conn->begin_transaction();
    
    try {
        $added_count = 0;
        $already_member_count = 0;
        $invalid_member_count = 0;
        $added_members = []; // Store added member names for notification
        
        // Get admin's name for notification
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin_info = $result->fetch_assoc();
        $admin_name = $admin_info['full_name'];
        
        foreach ($member_ids as $member_id) {
            $member_id = intval($member_id);
            
            // Verify that the member belongs to the room
            $stmt = $conn->prepare("SELECT id FROM room_members WHERE room_id = ? AND user_id = ? AND invitation_status = 'accepted'");
            $stmt->bind_param("ii", $room_id, $member_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $invalid_member_count++;
                continue;
            }
            
            // Check if user is already a member of the group
            $stmt = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $group_id, $member_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $already_member_count++;
                continue;
            }
            
            // Get member's name before adding
            $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $member_info = $result->fetch_assoc();
            $member_name = $member_info['full_name'];
            
            // Check if the member being added is a room admin
            $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $room_id, $member_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $is_admin_member = $result->num_rows > 0 && $result->fetch_assoc()['is_admin'] == 1;
            
            // Add member to the group (with leadership if they are admin)
            $is_leader = $is_admin_member ? 1 : 0;
            $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, is_leader, added_by, added_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiii", $group_id, $member_id, $is_leader, $user_id);
            
            if ($stmt->execute()) {
                $added_count++;
                $added_members[] = $member_name;
                
                // If admin was added as leader, remove leadership from current leader
                if ($is_admin_member) {
                    $stmt = $conn->prepare("UPDATE group_members SET is_leader = 0 WHERE group_id = ? AND user_id != ? AND is_leader = 1");
                    $stmt->bind_param("ii", $group_id, $member_id);
                    $stmt->execute();
                }
            }
        }
        
        $conn->commit();
        
        // Create chat notification if members were added
        if ($added_count > 0 && !empty($added_members)) {
            $notification_message = "👥 **Members Added to Group**\n\n";
            $notification_message .= "📋 **Action:** New members added to group\n";
            $notification_message .= "👨‍💼 **Added by:** {$admin_name}\n";
            $notification_message .= "👥 **New Members:** " . implode(', ', $added_members) . "\n";
            $notification_message .= "📅 **Date:** " . date('M d, Y \a\t g:i A') . "\n\n";
            
            // Check if any admin was added (they would become leader)
            $admin_added = false;
            foreach ($member_ids as $member_id) {
                $member_id_int = intval($member_id);
                $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $room_id, $member_id_int);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0 && $result->fetch_assoc()['is_admin'] == 1) {
                    $admin_added = true;
                    break;
                }
            }
            
            if ($admin_added) {
                $notification_message .= "👑 **Note:** Room admin automatically becomes group leader!\n";
                $notification_message .= "⚠️ Only one member can be group leader at a time.\n\n";
            }
            
            $notification_message .= "🎉 Welcome the new members to the group!";
            
            // Insert the notification message
            $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $group_id, $user_id, $notification_message);
            $stmt->execute();
            
            // Log the action for admin records
            error_log("Group members added - Admin: {$admin_name} (ID: {$user_id}), Added: " . implode(', ', $added_members) . ", Group ID: {$group_id}");
        }
        
        // Create success message based on results
        $success_msg = "";
        if ($added_count > 0) {
            $success_msg .= "$added_count member(s) added successfully";
        }
        if ($already_member_count > 0) {
            if ($success_msg) $success_msg .= ". ";
            $success_msg .= "$already_member_count member(s) were already in the group";
        }
        if ($invalid_member_count > 0) {
            if ($success_msg) $success_msg .= ". ";
            $success_msg .= "$invalid_member_count invalid member(s) skipped";
        }
        
        if ($added_count > 0) {
            header("Location: ../group.php?id=$group_id&success=" . urlencode($success_msg));
        } else {
            header("Location: ../group.php?id=$group_id&error=" . urlencode($success_msg ?: "No members were added"));
        }
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Add group member error: " . $e->getMessage());
        header("Location: ../group.php?id=$group_id&error=Failed to add members: " . urlencode($e->getMessage()));
        exit();
    }
    
} else {
    // If accessed directly without form submission
    header("Location: ../index.php");
    exit();
}
?>
