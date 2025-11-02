<?php
session_start();
require_once 'config/config.php';

// Log user activity
if (isset($_SESSION['user_id'])) {
    logUserActivity($conn, $_SESSION['user_id'], 'terms_conditions_access', 'terms-conditions.php', 'User accessed terms and conditions page');
}

// Get profile image for header
$profile_image = 'uploads/profile/default_avatar.png';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (!empty($user['profile_image'])) {
            $profile_image = 'uploads/profile/' . $user['profile_image'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - Bachelor Room Expense Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/back-navigation.js"></script>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/transitions.css">
    <script src="js/page-transitions.js"></script>
</head>

<body>
<div class="content-wrapper page-transition slide-up">
    <header class="app-header fixed top-0 left-0 right-0 z-10 py-2.5 px-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2.5">
                <button onclick="goBack()" class="mr-3 back-button" title="Go Back" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left text-white text-xl"></i>
                </button>

                <div>
                    <h1 class="text-base font-bold text-white tracking-tight">Terms & Conditions</h1>
                    <p class="text-[12px] text-white text-opacity-80 -mt-0.5">Bachelor Room Expense Management</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="transform transition-transform hover:scale-105">
                    <div class="h-8 w-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center overflow-hidden border border-white border-opacity-30 backdrop-blur-sm" style="border: 2px solid white; height: 35px; width: 35px;">
                        <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-full w-full object-cover">
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 pt-20 pb-20 max-w-3xl">
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Terms and Conditions</h2>
            <p class="text-gray-600 mb-4">Last Updated: <?php echo date("F d, Y"); ?></p>
            
            <div class="space-y-6 text-gray-700">
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">1. Introduction</h3>
                    <p>Welcome to Bachelor Room Expense Management. These terms and conditions outline the rules and regulations for the use of our website and services.</p>
                    <p class="mt-2">By accessing this website, we assume you accept these terms and conditions in full. Do not continue to use Bachelor Room Expense Management if you do not accept all of the terms and conditions stated on this page.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">2. License to Use</h3>
                    <p>Unless otherwise stated, Bachelor Room Expense Management and/or its licensors own the intellectual property rights for all material on this website. All intellectual property rights are reserved.</p>
                    <p class="mt-2">You may view and/or print pages from the website for your own personal use subject to restrictions set in these terms and conditions.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">3. User Account</h3>
                    <p>To use certain features of the website, you must register for an account. You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete.</p>
                    <p class="mt-2">You are responsible for safeguarding your password and for all activities that occur under your account. You agree not to disclose your password to any third party.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">4. Room Management</h3>
                    <p>Our service allows users to create and manage rooms, track expenses, and settle payments between roommates. By using these features, you agree to:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Provide accurate expense information</li>
                        <li>Respect the privacy and financial information of other room members</li>
                        <li>Settle payments as agreed upon within the platform</li>
                        <li>Not use the platform for any fraudulent or illegal activities</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">5. KYC Verification</h3>
                    <p>To ensure security and trust among users, we may require Know Your Customer (KYC) verification. By submitting your documents for verification, you:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Confirm that all information provided is accurate and belongs to you</li>
                        <li>Authorize us to verify your identity using the provided documents</li>
                        <li>Understand that false information may result in account termination</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">6. Limitation of Liability</h3>
                    <p>In no event shall Bachelor Room Expense Management, nor any of its officers, directors, and employees, be liable for anything arising out of or in any way connected with your use of this website, whether such liability is under contract, tort, or otherwise.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">7. Governing Law</h3>
                    <p>These terms and conditions are governed by and construed in accordance with the laws of your country, and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">8. Changes to Terms</h3>
                    <p>We reserve the right to modify these terms at any time. By continuing to access or use our service after any revisions become effective, you agree to be bound by the revised terms.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">9. Contact Us</h3>
                    <p>If you have any questions about these Terms and Conditions, please contact us at:</p>
                    <p class="mt-2">Email: support@bachelorroom.com</p>
                    <p>Phone: +1-234-567-8900</p>
                </section>
            </div>
        </div>
    </main>

    <div class="fixed bottom-0 left-0 right-0 bg-white shadow-lg border-t border-gray-200 z-10 h-[70px]">
        <div class="flex justify-between items-center max-w-md mx-auto h-full relative">
            <!-- Home -->
            <a href="index.php" class="flex flex-col items-center justify-center flex-1 text-indigo-600 h-full">
                <i class="fas fa-home text-lg mb-1"></i>
                <span class="text-xs font-medium">Home</span>
            </a>

            <!-- Find a Room -->
            <a href="find-room.php" class="flex flex-col items-center justify-center flex-1 text-green-600 h-full">
                <i class="fas fa-search text-lg mb-1"></i>
                <span class="text-xs font-medium">Find Room</span>
            </a>

            <!-- Create Room -->
            <a href="create-room.php" class="flex flex-col items-center justify-center flex-1 text-blue-600 h-full">
                <i class="fas fa-door-open text-lg mb-1"></i>
                <span class="text-xs font-medium">Add Room</span>
            </a>

            <!-- Donation -->
            <a href="donate.php" class="flex flex-col items-center justify-center flex-1 text-purple-600 h-full">
                <i class="fas fa-donate text-lg mb-1"></i>
                <span class="text-xs font-medium">Donation</span>
            </a>
        </div>
    </div>
</div>
</body>
</html>