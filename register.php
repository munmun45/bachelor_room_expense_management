<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bachelor Room Expense Management</title>
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
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>

            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Create Account</h1>
                <p class="text-gray-500">Join Bachelor Room Expense Management</p>
            </div>

            <?php
            // Display error or success messages if any
            if (isset($_GET['error'])) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">' . htmlspecialchars($_GET['error']) . '</span>
                </div>';
            }
            if (isset($_GET['success'])) {
                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">' . htmlspecialchars($_GET['success']) . '</span>
                </div>';
            }

            // Log page access for analytics
            session_start();
            if (isset($_SESSION['user_id'])) {
                require_once 'config/config.php';
                if (function_exists('logUserActivity')) {
                    logUserActivity($conn, $_SESSION['user_id'], 'register_page_access', 'register.php', 'User accessed registration page');
                }
            }
            ?>

            <!-- Step 1: Registration Form -->
            <form id="registration-form" action="process/register_process.php" method="POST" class="space-y-4" <?php echo isset($_GET['phone']) ? 'style="display:none;"' : ''; ?>>
                <div>
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your full name" required>
                    </div>
                </div>

                <div>
    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-phone text-gray-400"></i>
        </div>
        <input type="tel" id="phone" name="phone"
            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
            placeholder="Enter your phone number"
            maxlength="10"
            pattern="[0-9]{10}"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            required>
    </div>
</div>


                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your email" required>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Create a password" required>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="confirm_password" name="confirm_password" class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Confirm your password" required>
                    </div>
                </div>

                <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" required>
                    <label for="terms" class="ml-2 block text-sm text-gray-700">
                        I agree to the <a href="#" class="text-blue-600 hover:text-blue-500">Terms</a> and <a href="#" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>
                    </label>
                </div>

                <div>
                    <button type="submit" name="register" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Continue
                    </button>
                </div>
            </form>

            <!-- Step 2: OTP Verification Form -->
            <form id="otp-verification-form" action="process/verify_otp_process.php" method="POST" class="space-y-6" <?php echo isset($_GET['phone']) ? '' : 'style="display:none;"'; ?>>
                <div class="text-center mb-4">
                    <p class="text-gray-600">We've sent a verification code to your Email Id</p>
                    <?php if (isset($_GET['phone'])): ?>
                        <p class="font-medium"><?php echo htmlspecialchars($_GET['phone']); ?></p>
                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($_GET['phone']); ?>">
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
                        Verify & Create Account
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Didn't receive the code? <a href="process/resend_otp.php?phone=<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>" class="font-medium text-blue-600 hover:text-blue-500">Resend OTP</a>
                    </p>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>