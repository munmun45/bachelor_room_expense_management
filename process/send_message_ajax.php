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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = intval($_POST['group_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $is_special_item = isset($_POST['is_special_item']) && $_POST['is_special_item'] === 'on' ? 1 : 0;
    
    // Handle file upload
    $attachment = null;
    $upload_error = '';
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['name'] != '') {
        if ($_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/attachments/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $attachment = time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $attachment;
                
                if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                    echo json_encode(['error' => 'Failed to save uploaded file']);
                    exit();
                }
            } else {
                echo json_encode(['error' => 'File type not allowed. Please upload jpg, png, gif, pdf, doc, docx, or txt files']);
                exit();
            }
        } else {
            // Handle different upload errors
            switch ($_FILES['attachment']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    echo json_encode(['error' => 'File is too large']);
                    exit();
                case UPLOAD_ERR_PARTIAL:
                    echo json_encode(['error' => 'File upload was interrupted']);
                    exit();
                default:
                    echo json_encode(['error' => 'File upload failed']);
                    exit();
            }
        }
    }
    
    // Validate inputs
    if (empty($message) && $amount <= 0 && !$attachment) {
        echo json_encode(['error' => 'Please provide a message, amount, or attachment']);
        exit();
    }
    
    // Check if user is a member of the group
    $stmt = $conn->prepare("SELECT gm.id FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'You are not a member of this group']);
        exit();
    }
    
    $conn->begin_transaction();
    
    try {
        // Insert the message
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, amount, attachment, is_special_item, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisdsi", $group_id, $user_id, $message, $amount, $attachment, $is_special_item);
        $stmt->execute();
        
        $message_id = $conn->insert_id;
        
        // Get the inserted message with user details
        $stmt = $conn->prepare("SELECT gm.*, u.full_name, u.profile_image,
                                DATE_FORMAT(gm.created_at, '%b %d, %h:%i %p') as formatted_time
                                FROM group_messages gm
                                JOIN users u ON gm.user_id = u.id
                                WHERE gm.id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $new_message = $result->fetch_assoc();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => [
                'id' => $new_message['id'],
                'user_id' => $new_message['user_id'],
                'full_name' => $new_message['full_name'],
                'profile_image' => $new_message['profile_image'],
                'message' => $new_message['message'],
                'amount' => $new_message['amount'],
                'attachment' => $new_message['attachment'],
                'formatted_time' => $new_message['formatted_time'],
                'created_at' => $new_message['created_at'],
                'is_my_message' => true
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        
        // Clean up uploaded file if transaction failed
        if ($attachment && file_exists($upload_dir . $attachment)) {
            unlink($upload_dir . $attachment);
        }
        
        error_log("Send message AJAX error: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
