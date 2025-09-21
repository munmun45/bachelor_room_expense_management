<?php
require_once 'config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (!empty($email)) {
        // Check if admin exists with this email
        $stmt = $admin_conn->prepare("SELECT id, username, email, full_name FROM admin_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // In a real application, you would send a password reset email here
            // For this demo, we'll just show a success message
            $message = "Password reset instructions have been sent to your email address.";
            $message_type = "success";
        } else {
            $message = "No admin account found with that email address.";
            $message_type = "error";
        }
    } else {
        $message = "Please enter your email address.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-center">
                <h1 class="text-2xl font-bold text-white">Forgot Password</h1>
                <p class="text-blue-100 mt-1">Admin Panel</p>
            </div>
            
            <div class="p-6">
                <?php if ($message): ?>
                <div class="<?php echo $message_type === 'success' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'; ?> p-4 mb-4">
                    <p class="<?php echo $message_type === 'success' ? 'text-green-700' : 'text-red-700'; ?>"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <?php endif; ?>
                
                <p class="text-gray-600 mb-6">
                    Enter your email address and we'll send you instructions to reset your password.
                </p>
                
                <form method="POST" action="">
                    <div class="mb-6">
                        <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                placeholder="Enter your email"
                                required
                            >
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-2 px-4 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                    >
                        Send Reset Instructions
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Login
                    </a>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600 text-sm">
                        &copy; 2025 Bachelor Room Expense Management. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>