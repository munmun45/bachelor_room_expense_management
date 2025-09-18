<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Bachelor Room Expense Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/back-navigation.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-start mb-6">
                <button onclick="goBack()" class="text-blue-600 hover:text-blue-800 back-button" title="Go Back">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </button>
            </div>
            
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Reset Password</h1>
                <p class="text-gray-500 mt-2">Create a new password for your account</p>
            </div>
            
            <?php
            // Check if token is valid
            if (!isset($_GET['phone']) || !isset($_GET['token'])) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">Invalid password reset request. Please try again.</span>
                </div>';
                echo '<div class="text-center mt-4">
                    <a href="forgot-password.php" class="font-medium text-blue-600 hover:text-blue-500">Go back to forgot password</a>
                </div>';
                exit();
            }
            
            // Display error or success messages if any
            if (isset($_GET['error'])) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">' . htmlspecialchars($_GET['error']) . '</span>
                </div>';
            }
            ?>
            
            <form action="process/reset_password_process.php" method="POST" class="space-y-6">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_GET['phone']); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter new password" required minlength="6">
                    </div>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="confirm_password" name="confirm_password" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Confirm new password" required minlength="6">
                    </div>
                </div>
                
                <div>
                    <button type="submit" name="reset_password" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>