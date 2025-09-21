<?php
/**
 * User Activity Tracking Functions
 * Provides functions to track and log user activities throughout the application
 */

/**
 * Log user activity
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param string $activity_type Type of activity (e.g., 'page_access', 'message_sent', 'room_join')
 * @param string $page_accessed Page or section accessed
 * @param string $action_details Additional details about the action
 * @return bool Success status
 */
function logUserActivity($conn, $user_id, $activity_type, $page_accessed = null, $action_details = null) {
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Prepare and execute the query
    $stmt = $conn->prepare("INSERT INTO user_activities (user_id, activity_type, page_accessed, action_details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    if ($stmt === false) {
        error_log("Failed to prepare activity tracking statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isssss", $user_id, $activity_type, $page_accessed, $action_details, $ip_address, $user_agent);
    
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Failed to log user activity: " . $stmt->error);
    }
    
    $stmt->close();
    return $result;
}

/**
 * Get user's recent activities
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $limit Number of activities to retrieve
 * @return array Activities data
 */
function getUserRecentActivities($conn, $user_id, $limit = 50) {
    $stmt = $conn->prepare("SELECT ua.*, u.full_name FROM user_activities ua JOIN users u ON ua.user_id = u.id WHERE ua.user_id = ? ORDER BY ua.created_at DESC LIMIT ?");
    
    if ($stmt === false) {
        error_log("Failed to prepare activity retrieval statement: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    $stmt->close();
    return $activities;
}

/**
 * Get all recent activities (for admin view)
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of activities to retrieve
 * @return array Activities data
 */
function getAllRecentActivities($conn, $limit = 100) {
    $stmt = $conn->prepare("SELECT ua.*, u.full_name FROM user_activities ua JOIN users u ON ua.user_id = u.id ORDER BY ua.created_at DESC LIMIT ?");
    
    if ($stmt === false) {
        error_log("Failed to prepare all activities retrieval statement: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    $stmt->close();
    return $activities;
}

/**
 * Get activities by type
 * 
 * @param mysqli $conn Database connection
 * @param string $activity_type Activity type to filter by
 * @param int $limit Number of activities to retrieve
 * @return array Activities data
 */
function getActivitiesByType($conn, $activity_type, $limit = 50) {
    $stmt = $conn->prepare("SELECT ua.*, u.full_name FROM user_activities ua JOIN users u ON ua.user_id = u.id WHERE ua.activity_type = ? ORDER BY ua.created_at DESC LIMIT ?");
    
    if ($stmt === false) {
        error_log("Failed to prepare activity type retrieval statement: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("si", $activity_type, $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    $stmt->close();
    return $activities;
}
?>