<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user profile image
$sql = "SELECT profile_image FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Set profile image path
$profile_image = !empty($user['profile_image']) ? 'uploads/profile/' . $user['profile_image'] : 'uploads/profile/default_avatar.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Bachelor Room Expense Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/back-navigation.js"></script>

    <link rel="stylesheet" href="./css/style.css">

</head>

<body>


    <header class="app-header fixed top-0 left-0 right-0 z-10 py-2.5 px-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2.5">
                <button onclick="goBack()" class="mr-3 back-button" title="Go Back" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left text-white text-xl"></i>
                </button>

                <div>
                    <h1 class="text-base font-bold text-white tracking-tight">Find Room</h1>
                    <p class="text-[12px] text-white text-opacity-80 -mt-0.5">Find A Dream Room</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-7 h-7 rounded-full bg-white bg-opacity-20 flex items-center justify-center backdrop-blur-sm cursor-pointer hover:bg-opacity-30 transition-all duration-200" onclick="location.reload()" id="refreshButton">
                        <i class="fas fa-sync-alt text-white text-sm"></i>
                    </div>

                </div>
                <a href="profile.php" class="transform transition-transform hover:scale-105">
                    <div class="h-8 w-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center overflow-hidden border border-white border-opacity-30 backdrop-blur-sm" style="border: 2px solid white; height: 35px; width: 35px;">
                        <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-full w-full object-cover">
                    </div>
                </a>
            </div>
        </div>
    </header>



    <!-- Main Content -->
    <main class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white/90 backdrop-blur-lg rounded-3xl shadow-2xl p-8 max-w-md text-center">
            <!-- Icon / Illustration -->
            <div class="mb-6">
                <div class="w-24 h-24 mx-auto rounded-full bg-indigo-500 flex items-center justify-center text-white text-4xl
                ">
                    🔍
                </div>
            </div>

            <!-- Heading -->
            <h1 class="text-3xl font-extrabold text-gray-800 mb-3">Find Room</h1>

            <!-- Description -->
            <p class="text-gray-600 mb-6 text-sm">
                Oops! This page is coming soon. We are working hard to bring you a better experience for finding your perfect room.
            </p>

            <!-- Back Button -->
            <a href="#0" class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-indigo-700 transition-colors">
                Request to Access
            </a>
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

        <!-- Settings -->
        <a href="profile.php" class="flex flex-col items-center justify-center flex-1 text-blue-600 h-full">
            <i class="fas fa-cog text-lg mb-1"></i>
            <span class="text-xs font-medium">Settings</span>
        </a>

        <!-- Donation -->
        <a href="donate.php" class="flex flex-col items-center justify-center flex-1 text-purple-600 h-full">
            <i class="fas fa-donate text-lg mb-1"></i>
            <span class="text-xs font-medium">Donation</span>
        </a>

    </div>
</div>




    
</body>

</html>