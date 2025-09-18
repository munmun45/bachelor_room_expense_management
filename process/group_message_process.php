<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['submit_message'])) {
    $group_id = intval($_POST['group_id']);
    $message = trim($_POST['message'] ?? '');
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : [];
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
                    $upload_error = 'Failed to save uploaded file';
                    $attachment = null;
                }
            } else {
                $upload_error = 'File type not allowed. Please upload jpg, png, gif, pdf, doc, docx, or txt files';
            }
        } else {
            // Handle different upload errors
            switch ($_FILES['attachment']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $upload_error = 'File is too large';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $upload_error = 'File upload was interrupted';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $upload_error = 'No file was uploaded';
                    break;
                default:
                    $upload_error = 'File upload failed';
                    break;
            }
        }
    }
    
    // If there was an upload error, redirect with error
    if (!empty($upload_error)) {
        header("Location: ../group.php?id=$group_id&error=" . urlencode($upload_error));
        exit();
    }
    
    // Validate inputs
    if (empty($message) && $amount <= 0 && !$attachment) {
        header("Location: ../group.php?id=$group_id&error=Please provide a message, amount, or attachment");
        exit();
    }
    
    // Check if user is a member of the group
    $stmt = $conn->prepare("SELECT gm.id FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../index.php?error=You are not a member of this group");
        exit();
    }
    
    
    $conn->begin_transaction();
    
    try {
        // Insert the message
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, amount, attachment, is_special_item, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisdsi", $group_id, $user_id, $message, $amount, $attachment, $is_special_item);
        $stmt->execute();
        
        $message_id = $conn->insert_id;
        
        $conn->commit();
        
        header("Location: ../group.php?id=$group_id&success=Message sent successfully");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        
        // Clean up uploaded file if transaction failed
        if ($attachment && file_exists($upload_dir . $attachment)) {
            unlink($upload_dir . $attachment);
        }
        
        error_log("Group message error: " . $e->getMessage());
        header("Location: ../group.php?id=$group_id&error=Failed to send message: " . urlencode($e->getMessage()));
        exit();
    }
    
} else {
    // If accessed directly without form submission
    error_log("Group message process accessed without submit_message. POST data: " . print_r($_POST, true));
    error_log("Files data: " . print_r($_FILES, true));
    header("Location: ../index.php?error=Form not submitted properly");
    exit();
}
?>
