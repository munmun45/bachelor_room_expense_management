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

$current_user_id = $_SESSION['user_id'];
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

try {
    // Log user activity for viewing profile
    logUserActivity($conn, $current_user_id, 'profile_view', 'get_user_profile_ajax.php', 'Viewing profile of user ID: ' . $user_id);

    // Get user profile information
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.phone, u.email, u.profile_image, 
                           u.room_number, u.joined_date, u.occupation, u.emergency_contact, u.kyc_status
                           FROM users u 
                           WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Get shared groups with this user
    $shared_groups = [];
    $stmt = $conn->prepare("SELECT DISTINCT rg.id, rg.name, r.name as room_name
                           FROM room_groups rg
                           JOIN rooms r ON rg.room_id = r.id
                           WHERE rg.id IN (
                               SELECT gm1.group_id FROM group_members gm1 WHERE gm1.user_id = ?
                           ) AND rg.id IN (
                               SELECT gm2.group_id FROM group_members gm2 WHERE gm2.user_id = ?
                           )");
    $stmt->bind_param("ii", $current_user_id, $user_id);
    $stmt->execute();
    $groups_result = $stmt->get_result();
    
    while ($group = $groups_result->fetch_assoc()) {
        $shared_groups[] = $group;
    }
    
    // Format the response
    $profile_data = [
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'phone' => $user['phone'],
        'email' => $user['email'],
        'profile_image' => $user['profile_image'],
        'room_number' => $user['room_number'],
        'joined_date' => $user['joined_date'],
        'occupation' => $user['occupation'],
        'emergency_contact' => $user['emergency_contact'],
        'kyc_status' => $user['kyc_status'],
        'shared_groups' => $shared_groups,
        'is_own_profile' => $user_id == $current_user_id
    ];
    
    echo json_encode([
        'success' => true,
        'user' => $profile_data
    ]);
    
} catch (Exception $e) {
    error_log("Get user profile error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch user profile']);
}
?>