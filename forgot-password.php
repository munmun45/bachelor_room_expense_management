<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Bachelor Room Expense Management</title>
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
                <h1 class="text-2xl font-bold text-gray-800">Forgot Password?</h1>
                <p class="text-gray-500 mt-2">Enter your phone number to receive an OTP for password reset</p>
            </div>
            
            <?php
            // Display error or success messages if any
            if (isset($_GET['error'])) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">' . htmlspecialchars($_GET['error']) . '</span>
                </div>';
                
                // Log password reset error
                session_start();
                if (isset($_SESSION['user_id'])) {
                    require_once 'config/config.php';
                    if (function_exists('logUserActivity')) {
                        logUserActivity($conn, $_SESSION['user_id'], 'forgot_password_error', 'forgot-password.php', 'User encountered error on forgot password page: ' . $_GET['error']);
                    }
                }
            }
            if (isset($_GET['success'])) {
                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">' . htmlspecialchars($_GET['success']) . '</span>
                </div>';
                
                // Log password reset success
                session_start();
                if (isset($_SESSION['user_id'])) {
                    require_once 'config/config.php';
                    if (function_exists('logUserActivity')) {
                        logUserActivity($conn, $_SESSION['user_id'], 'forgot_password_success', 'forgot-password.php', 'User successfully accessed forgot password page: ' . $_GET['success']);
                    }
                }
            }
            
            // Log page access for analytics (only when there's a message or when first accessing the page)
            if (!isset($_GET['error']) && !isset($_GET['success'])) {
                session_start();
                if (isset($_SESSION['user_id'])) {
                    require_once 'config/config.php';
                    if (function_exists('logUserActivity')) {
                        logUserActivity($conn, $_SESSION['user_id'], 'forgot_password_page_access', 'forgot-password.php', 'User accessed forgot password page');
                    }
                }
            }
            ?>
            
            <!-- Step 1: Phone Number Form -->
            <form id="phone-form" action="process/forgot_password_process.php" method="POST" class="space-y-6" <?php echo isset($_GET['phone']) ? 'style="display:none;"' : ''; ?>>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-gray-400"></i>
                        </div>
                        <input type="tel" id="phone" name="phone" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your phone number" required>
                    </div>
                </div>
                
                <div>
                    <button type="submit" name="send_otp" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Send OTP
                    </button>
                </div>
            </form>
            
            <!-- Step 2: OTP Verification Form -->
            <form id="otp-verification-form" action="process/verify_otp_process.php" method="POST" class="space-y-6" <?php echo isset($_GET['phone']) ? '' : 'style="display:none;"'; ?>>
                <div class="text-center mb-4">
                    <p class="text-gray-600">We've sent a verification code to your phone number</p>
                    <?php if(isset($_GET['phone'])): ?>
                    <p class="font-medium"><?php echo htmlspecialchars($_GET['phone']); ?></p>
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_GET['phone']); ?>">
                    <input type="hidden" name="purpose" value="password_reset">
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">Enter OTP</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-400"></i>
                        </div>
                        <input type="text" id="otp" name="otp" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter 6-digit OTP" required maxlength="6" pattern="[0-9]{6}">
                    </div>
                </div>
                
                <div>
                    <button type="submit" name="verify_otp" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Verify OTP
                    </button>
                </div>
                
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Didn't receive the code? 
                        <a href="process/resend_otp.php?phone=<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>&purpose=password_reset" class="font-medium text-blue-600 hover:text-blue-500">Resend OTP</a>
                    </p>
                </div>
            </form>
            
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Or</span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            Remember your password? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>