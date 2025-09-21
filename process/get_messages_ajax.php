<?php
// FIXED VERSION OF get_messages_ajax.php
header('Content-Type: application/json');
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;
$direction = isset($_GET['direction']) ? $_GET['direction'] : 'newer';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 7;

if ($group_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid group ID']);
    exit();
}

// Check if user is a member of the group
$stmt = $conn->prepare("SELECT gm.id FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'You are not a member of this group']);
    exit();
}

// Log user activity for accessing group messages
logUserActivity($conn, $user_id, 'message_access', 'get_messages_ajax.php', 'Accessing messages for group ID: ' . $group_id);

// Build query based on direction - FIXED LOGIC
if ($direction === 'newer' && $last_message_id > 0) {
    // Get newer messages (for real-time updates)
    // Order ASC to get chronological order (oldest to newest)
    $stmt = $conn->prepare("SELECT gm.*, u.full_name, u.profile_image,
                            DATE_FORMAT(gm.created_at, '%b %d, %h:%i %p') as formatted_time,
                            s.id as settlement_id
                            FROM group_messages gm
                            JOIN users u ON gm.user_id = u.id
                            LEFT JOIN settlements s ON s.message_id = gm.id
                            WHERE gm.group_id = ? AND gm.id > ?
                            ORDER BY gm.created_at ASC, gm.id ASC
                            LIMIT ?");
    $stmt->bind_param("iii", $group_id, $last_message_id, $limit);
} elseif ($direction === 'older' && $last_message_id > 0) {
    // Get older messages (for pagination when scrolling up)
    // Order DESC to get most recent first, then we'll reverse in PHP
    $stmt = $conn->prepare("SELECT gm.*, u.full_name, u.profile_image,
                            DATE_FORMAT(gm.created_at, '%b %d, %h:%i %p') as formatted_time,
                            s.id as settlement_id
                            FROM group_messages gm
                            JOIN users u ON gm.user_id = u.id
                            LEFT JOIN settlements s ON s.message_id = gm.id
                            WHERE gm.group_id = ? AND gm.id < ?
                            ORDER BY gm.created_at DESC, gm.id DESC
                            LIMIT ?");
    $stmt->bind_param("iii", $group_id, $last_message_id, $limit);
} else {
    // Get recent messages (initial load)
    // Order DESC to get most recent first, then we'll reverse in PHP
    $stmt = $conn->prepare("SELECT gm.*, u.full_name, u.profile_image,
                            DATE_FORMAT(gm.created_at, '%b %d, %h:%i %p') as formatted_time,
                            s.id as settlement_id
                            FROM group_messages gm
                            JOIN users u ON gm.user_id = u.id
                            LEFT JOIN settlements s ON s.message_id = gm.id
                            WHERE gm.group_id = ?
                            ORDER BY gm.created_at DESC, gm.id DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $group_id, $limit);
}

$stmt->execute();
$messages_result = $stmt->get_result();

$messages = [];
while ($message = $messages_result->fetch_assoc()) {
    $messages[] = [
        'id' => intval($message['id']),
        'user_id' => intval($message['user_id']),
        'full_name' => $message['full_name'],
        'profile_image' => $message['profile_image'],
        'message' => $message['message'],
        'amount' => floatval($message['amount']),
        'attachment' => $message['attachment'],
        'formatted_time' => $message['formatted_time'],
        'created_at' => $message['created_at'],
        'is_special_item' => isset($message['is_special_item']) ? intval($message['is_special_item']) : 0,
        'is_my_message' => $message['user_id'] == $user_id
    ];
}

// FIXED: Proper message ordering logic
if ($direction === 'older' || $last_message_id == 0) {
    // For older messages and initial load: reverse to show chronological order (oldest first)
    $messages = array_reverse($messages);
}
// For newer messages: already in correct order (ASC from query)

// Check if there are more messages available
$has_more = false;
if ($direction === 'older' && count($messages) === $limit) {
    // Check if there are older messages
    $oldest_id = count($messages) > 0 ? $messages[0]['id'] : $last_message_id;
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM group_messages WHERE group_id = ? AND id < ?");
    $check_stmt->bind_param("ii", $group_id, $oldest_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $has_more = $check_result->fetch_assoc()['count'] > 0;
} elseif ($direction === 'newer') {
    // For newer messages, there might be more if we got exactly the limit
    $has_more = count($messages) === $limit;
} else {
    // For initial load, check if there are older messages
    if (count($messages) > 0) {
        $oldest_id = $messages[0]['id'];
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM group_messages WHERE group_id = ? AND id < ?");
        $check_stmt->bind_param("ii", $group_id, $oldest_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $has_more = $check_result->fetch_assoc()['count'] > 0;
    }
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'direction' => $direction,
    'has_more' => $has_more,
    'debug_info' => [
        'query_direction' => $direction,
        'last_message_id' => $last_message_id,
        'limit' => $limit,
        'message_count' => count($messages),
        'first_message_id' => count($messages) > 0 ? $messages[0]['id'] : null,
        'last_message_id_result' => count($messages) > 0 ? $messages[count($messages)-1]['id'] : null
    ]
]);
?>