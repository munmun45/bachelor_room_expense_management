<?php
// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only set JSON header if this is an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
}

// Include config with proper path detection
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
} elseif (file_exists('config/config.php')) {
    require_once 'config/config.php';
} else {
    die('Configuration file not found');
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : '';
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : '';
$include_food = isset($_POST['include_food']) && $_POST['include_food'] === 'true';
$include_special = isset($_POST['include_special']) && $_POST['include_special'] === 'true';
$action = isset($_POST['action']) ? $_POST['action'] : 'create'; // 'preview' or 'create'

if ($group_id === 0 || empty($from_date) || empty($to_date)) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

// Check if user is admin of the group
$stmt = $conn->prepare("SELECT rm.is_admin FROM room_members rm 
                       JOIN room_groups rg ON rm.room_id = rg.room_id 
                       WHERE rg.id = ? AND rm.user_id = ? AND rm.is_admin = 1");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Only admins can create settlements']);
    exit();
}

try {
    // Get group members and room_id
    $stmt = $conn->prepare("SELECT gm.user_id, u.full_name, rg.room_id
                           FROM group_members gm 
                           JOIN users u ON gm.user_id = u.id 
                           JOIN room_groups rg ON gm.group_id = rg.id
                           WHERE gm.group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $members_result = $stmt->get_result();
    
    $members = [];
    $room_id = null;
    while ($member = $members_result->fetch_assoc()) {
        $members[$member['user_id']] = $member['full_name'];
        if ($room_id === null) {
            $room_id = $member['room_id'];
        }
    }
    
    if (empty($members)) {
        echo json_encode(['error' => 'No members found in group']);
        exit();
    }
    
    // Include calculator functions
    include_once '../includes/settlement_calculator.php';
    
    // Calculate settlement
    $settlement_data = calculateSettlement($conn, $group_id, $room_id, $from_date, $to_date, $include_food, $include_special, $members);
    
    if ($action === 'preview') {
        echo json_encode([
            'success' => true,
            'action' => 'preview',
            'settlement' => $settlement_data
        ]);
        exit();
    }
    
    // Create settlement in database and post to chat
    $conn->begin_transaction();
    
    // Insert settlement message to group chat
    $settlement_summary = generateSettlementSummary($settlement_data);
    $settlement_message = "🧮 **Settlement Created**\n\n" . $settlement_summary . "\n\n📅 Period: " . date('M d, Y', strtotime($from_date)) . " - " . date('M d, Y', strtotime($to_date));
    
    $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, amount, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt->bind_param("iis", $group_id, $user_id, $settlement_message);
    $stmt->execute();
    
    $message_id = $conn->insert_id;
    
    // Store settlement data in settlements table
    $settlement_json = json_encode($settlement_data);
    $stmt = $conn->prepare("INSERT INTO settlements (group_id, created_by, from_date, to_date, include_food, include_special, settlement_data, message_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iissiisi", $group_id, $user_id, $from_date, $to_date, $include_food, $include_special, $settlement_json, $message_id);
    $stmt->execute();
    
    $settlement_id = $conn->insert_id;
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'action' => 'create',
        'message' => 'Settlement created and posted to group chat',
        'settlement' => $settlement_data,
        'message_id' => $message_id,
        'settlement_id' => $settlement_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Settlement creation error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to create settlement: ' . $e->getMessage()]);
}

// Functions are now in includes/settlement_calculator.php
?>
