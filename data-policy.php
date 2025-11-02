<?php
session_start();
require_once 'config/config.php';

// Log user activity
if (isset($_SESSION['user_id'])) {
    logUserActivity($conn, $_SESSION['user_id'], 'data_policy_access', 'data-policy.php', 'User accessed data policy page');
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
    <title>Data Policy - Bachelor Room Expense Management</title>
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
                    <h1 class="text-base font-bold text-white tracking-tight">Data Policy</h1>
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
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Data Policy</h2>
            <p class="text-gray-600 mb-4">Last Updated: <?php echo date("F d, Y"); ?></p>
            
            <div class="space-y-6 text-gray-700">
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">1. Data Collection and Usage</h3>
                    <p>Bachelor Room Expense Management collects and processes personal data to provide our services. This policy explains how we collect, use, and protect your data when you use our platform.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">2. Types of Data We Process</h3>
                    <p>We collect and process the following types of data:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li><strong>Account Data:</strong> Information you provide when creating an account, such as name, email, phone number, and profile image.</li>
                        <li><strong>KYC Data:</strong> Identity documents, address proofs, and selfie verification submitted for KYC verification.</li>
                        <li><strong>Room Data:</strong> Information about rooms you create or join, including room details, members, and expenses.</li>
                        <li><strong>Financial Data:</strong> Expense records, settlements, and payment information related to room expenses.</li>
                        <li><strong>Usage Data:</strong> Information about how you interact with our platform, including access logs and activity tracking.</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">3. Data Storage and Security</h3>
                    <p>We implement appropriate technical and organizational measures to protect your personal data against unauthorized or unlawful processing, accidental loss, destruction, or damage. Your data is stored securely on our servers with restricted access.</p>
                    <p class="mt-2">We retain your data only for as long as necessary to provide our services or as required by law. When data is no longer needed, we securely delete or anonymize it.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">4. Data Sharing</h3>
                    <p>We may share your data with:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li><strong>Room Members:</strong> Certain information is shared with other members of rooms you join, such as your name, profile image, and expense contributions.</li>
                        <li><strong>Service Providers:</strong> We may engage third-party companies to perform services on our behalf, such as hosting, data analysis, and customer service.</li>
                        <li><strong>Legal Requirements:</strong> We may disclose your data if required by law or in response to valid requests by public authorities.</li>
                    </ul>
                    <p class="mt-2">We do not sell your personal data to third parties.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">5. Your Data Rights</h3>
                    <p>You have the following rights regarding your personal data:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li><strong>Access:</strong> You can request a copy of your personal data.</li>
                        <li><strong>Correction:</strong> You can request correction of inaccurate data.</li>
                        <li><strong>Deletion:</strong> You can request deletion of your data under certain circumstances.</li>
                        <li><strong>Restriction:</strong> You can request restriction of processing of your data.</li>
                        <li><strong>Portability:</strong> You can request transfer of your data to another service.</li>
                        <li><strong>Objection:</strong> You can object to processing of your data.</li>
                    </ul>
                    <p class="mt-2">To exercise these rights, please contact us using the information provided below.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">6. Cookies and Tracking</h3>
                    <p>We use cookies and similar tracking technologies to enhance your experience on our platform. These technologies help us understand how you use our services, remember your preferences, and improve our platform.</p>
                    <p class="mt-2">You can control cookies through your browser settings. However, disabling cookies may limit your ability to use certain features of our platform.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">7. Changes to This Policy</h3>
                    <p>We may update this Data Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last Updated" date.</p>
                    <p class="mt-2">We encourage you to review this policy periodically to stay informed about how we protect your data.</p>
                </section>
                
                <section>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">8. Contact Us</h3>
                    <p>If you have any questions about this Data Policy or our data practices, please contact us at:</p>
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