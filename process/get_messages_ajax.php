<?php
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
$direction = isset($_GET['direction']) ? $_GET['direction'] : 'newer'; // 'newer' or 'older'
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 7; // Default to 7 for initial load

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

// Build query based on direction
if ($direction === 'newer' && $last_message_id > 0) {
    // Get newer messages (for real-time updates)
    $stmt = $conn->prepare("SELECT gm.*, u.full_name, u.profile_image,
                            DATE_FORMAT(gm.created_at, '%b %d, %h:%i %p') as formatted_time
                            FROM group_messages gm
                            JOIN users u ON gm.user_id = u.id
                            WHERE gm.group_id = ? AND gm.id > ?
                            ORDER BY gm.created_at ASC
                            LIMIT ?");
    $stmt->bind_param("iii", $group_id, $last_message_id, $limit);
} elseif ($direction === 'older' && $last_message_id > 0) {
    // Get older messages (for pagination when scrolling up)
    $stmt = $conn->prepare("SELECT gm.*, u.full_name, u.profile_image,
                            DATE_FORMAT(gm.created_at, '%b %d, %h:%i %p') as formatted_time
                            FROM group_messages gm
                            JOIN users u ON gm.user_id = u.id
                            WHERE gm.group_id = ? AND gm.id < ?
                            ORDER BY gm.created_at DESC
                            LIMIT ?");
    $stmt->bind_param("iii", $group_id, $last_message_id, $limit);
} else {
    // Get recent messages (initial load)
    $stmt = $conn->prepare("SELECT gm.*, u.full_name, u.profile_image,
                            DATE_FORMAT(gm.created_at, '%b %d, %h:%i %p') as formatted_time
                            FROM group_messages gm
                            JOIN users u ON gm.user_id = u.id
                            WHERE gm.group_id = ?
                            ORDER BY gm.created_at DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $group_id, $limit);
}

$stmt->execute();
$messages_result = $stmt->get_result();

$messages = [];
while ($message = $messages_result->fetch_assoc()) {
    $messages[] = [
        'id' => $message['id'],
        'user_id' => $message['user_id'],
        'full_name' => $message['full_name'],
        'profile_image' => $message['profile_image'],
        'message' => $message['message'],
        'amount' => $message['amount'],
        'attachment' => $message['attachment'],
        'formatted_time' => $message['formatted_time'],
        'created_at' => $message['created_at'],
        'is_my_message' => $message['user_id'] == $user_id
    ];
}

// If getting older messages, reverse the array to maintain chronological order
if ($direction === 'older') {
    $messages = array_reverse($messages);
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'direction' => $direction,
    'has_more' => count($messages) === $limit
]);
?>
