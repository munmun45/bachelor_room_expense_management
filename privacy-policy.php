<?php
session_start();
require_once 'config/config.php';

// Log user activity
if (isset($_SESSION['user_id'])) {
    logUserActivity($conn, $_SESSION['user_id'], 'privacy_policy_access', 'privacy-policy.php', 'User accessed privacy policy page');
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
    <title>Privacy Policy - Bachelor Room Expense Management</title>
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
                    <h1 class="text-base font-bold text-white tracking-tight">Privacy Policy</h1>
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
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Privacy Policy</h2>
            <p class="text-gray-600 mb-4">Last Updated: <?php echo date("F d, Y"); ?></p>
            
            <div class="space-y-6 text-gray-700">
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">1. Introduction</h3>
                    <p>Welcome to Bachelor Room Expense Management. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you about how we look after your personal data when you visit our website and tell you about your privacy rights and how the law protects you.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">2. Data We Collect</h3>
                    <p>We may collect, use, store and transfer different kinds of personal data about you which we have grouped together as follows:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Identity Data: includes first name, last name, username or similar identifier.</li>
                        <li>Contact Data: includes email address, telephone numbers, and emergency contact information.</li>
                        <li>Profile Data: includes your username and password, your interests, preferences, feedback, and survey responses.</li>
                        <li>Financial Data: includes expense records and payment information related to room expenses.</li>
                        <li>Technical Data: includes internet protocol (IP) address, browser type and version, time zone setting and location, browser plug-in types and versions, operating system and platform, and other technology on the devices you use to access this website.</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">3. How We Use Your Data</h3>
                    <p>We will only use your personal data when the law allows us to. Most commonly, we will use your personal data in the following circumstances:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>To register you as a new user.</li>
                        <li>To manage your account and provide you with our services.</li>
                        <li>To manage room expenses and settlements between roommates.</li>
                        <li>To verify your identity through KYC processes.</li>
                        <li>To improve our website, products/services, marketing, or customer relationships.</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">4. Data Security</h3>
                    <p>We have put in place appropriate security measures to prevent your personal data from being accidentally lost, used, or accessed in an unauthorized way, altered, or disclosed. We limit access to your personal data to those employees, agents, contractors, and other third parties who have a business need to know.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">5. Data Retention</h3>
                    <p>We will only retain your personal data for as long as necessary to fulfill the purposes we collected it for, including for the purposes of satisfying any legal, accounting, or reporting requirements.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">6. Your Legal Rights</h3>
                    <p>Under certain circumstances, you have rights under data protection laws in relation to your personal data, including the right to:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li>Request access to your personal data.</li>
                        <li>Request correction of your personal data.</li>
                        <li>Request erasure of your personal data.</li>
                        <li>Object to processing of your personal data.</li>
                        <li>Request restriction of processing your personal data.</li>
                        <li>Request transfer of your personal data.</li>
                        <li>Right to withdraw consent.</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">7. Contact Us</h3>
                    <p>If you have any questions about this privacy policy or our privacy practices, please contact us at:</p>
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