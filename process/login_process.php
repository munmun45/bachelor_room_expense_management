<?php
// Login process
session_start();
require_once '../config/config.php';

if (isset($_POST['login'])) {
    // Get form data
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($phone) || empty($password)) {
        header("Location: ../login.php?error=All fields are required");
        exit();
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, full_name, phone, password FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['logged_in'] = true;
            
            // Log user activity - login
            logUserActivity($conn, $user['id'], 'login', 'login_process', 'User successfully logged in');
            
            // Redirect to dashboard
            header("Location: ../index.php");
            exit();
        } else {
            // Log user activity - failed login
            logUserActivity($conn, $user['id'], 'login_failed', 'login_process', 'User failed to login - invalid password');
            header("Location: ../login.php?error=Invalid password");
            exit();
        }
    } else {
        // Log user activity - failed login
        logUserActivity($conn, 0, 'login_failed', 'login_process', 'User failed to login - user not found for phone: ' . $phone);
        header("Location: ../login.php?error=User not found");
        exit();
    }
} else {
    // If accessed directly without form submission
    header("Location: ../login.php");
    exit();
}
?>