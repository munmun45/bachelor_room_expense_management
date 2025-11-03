<?php
session_start();

require("../config/config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
       
        echo "<script>alert('Please fill in all fields.'); window.location.href = '../login';</script>";
        exit();
    }

    // Query to fetch user details
    $sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username); // Bind username
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify password and handle login
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username']; // Start user session
        header("Location: ../index"); // Redirect to dashboard
        exit();
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href = '../login';</script>";
        exit();
    }
}
