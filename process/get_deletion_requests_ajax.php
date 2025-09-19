<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

if (empty($group_id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid group ID']);
    exit();
}

try {
    // Check if user is group leader
    $stmt = $conn->prepare("SELECT gm.is_leader FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('You are not a member of this group');
    }
    
    $member_info = $result->fetch_assoc();
    if ($member_info['is_leader'] != 1) {
        throw new Exception('Only group leaders can view deletion requests');
    }
    
    // Get only pending deletion request messages (not rejected ones)
    $stmt = $conn->prepare("SELECT gm.*, u.full_name as requested_by,
                           DATE_FORMAT(gm.created_at, '%b %d, %Y at %h:%i %p') as requested_at
                           FROM group_messages gm
                           JOIN users u ON gm.user_id = u.id
                           WHERE gm.group_id = ? 
                           AND gm.message LIKE '%Group Deletion Request%'
                           AND gm.message NOT LIKE '%Group Deletion Request Rejected%'
                           AND gm.message NOT LIKE '%Group Deletion Request Approved%'
                           ORDER BY gm.created_at DESC");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        // Extract group name from message - try multiple patterns
        $group_name = 'Unknown Group';
        
        // Pattern 1: Look for "delete this group" followed by group name
        if (preg_match('/delete this group\.\*\*\n\n\*\*(.*?)\*\*/', $row['message'], $matches)) {
            $group_name = trim($matches[1]);
        }
        // Pattern 2: Look for "Group:" followed by group name
        elseif (preg_match('/\*\*Group:\*\* (.+?)\n/', $row['message'], $matches)) {
            $group_name = trim($matches[1]);
        }
        // Pattern 3: Look for any text between ** markers after "delete this group"
        elseif (preg_match('/delete this group.*?\*\*(.*?)\*\*/s', $row['message'], $matches)) {
            $group_name = trim($matches[1]);
        }
        
        // If still unknown, get the actual group name from database
        if ($group_name === 'Unknown Group') {
            $stmt_group = $conn->prepare("SELECT name FROM room_groups WHERE id = ?");
            $stmt_group->bind_param("i", $group_id);
            $stmt_group->execute();
            $group_result = $stmt_group->get_result();
            if ($group_result->num_rows > 0) {
                $group_info = $group_result->fetch_assoc();
                $group_name = $group_info['name'];
            }
        }
        
        $requests[] = [
            'group_id' => $group_id,
            'group_name' => $group_name,
            'requested_by' => $row['requested_by'],
            'requested_at' => $row['requested_at'],
            'message' => $row['message']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
