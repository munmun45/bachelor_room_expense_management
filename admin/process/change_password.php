<?php
session_start();
require("../config/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $old_password = trim($_POST['old_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $username = $_SESSION['username']; // Assuming user is logged in

    // Validate inputs
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        echo "<script>alert('All fields are required.'); window.location.href = './logout';</script>";
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo "<script>alert('New password and confirmation do not match.'); window.location.href = './logout';</script>";
        exit();
    }

    // Fetch user data from the database
    $sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username); // Bind the username parameter
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify the old password
    if ($user && password_verify($old_password, $user['password'])) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in the database
        $sql = "UPDATE admin SET password = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_password, $username);

        if ($stmt->execute()) {
            echo "<script>alert('Password changed successfully.'); window.location.href = './logout';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating password. Please try again.'); window.location.href = './logout';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Old password is incorrect.'); window.location.href = './logout';</script>";
        exit();
    }
}
