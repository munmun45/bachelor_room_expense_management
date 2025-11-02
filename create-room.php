<?php
// Start session
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Log user activity - create room page access
logUserActivity($conn, $_SESSION['user_id'], 'page_access', 'create_room', 'User accessed the create room page');

// Get user profile image
$user_id = $_SESSION['user_id'];
$profile_image = 'uploads/profile/default_avatar.png'; // Default image

$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $profile_image = 'uploads/profile/' . $user['profile_image'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Room - Bachelor Room Expense Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/back-navigation.js"></script>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="min-h-screen pb-20">
    <!-- Top Header with Logo and Profile -->
    <header class="app-header fixed top-0 left-0 right-0 z-10 py-2.5 px-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2.5">
                <button onclick="goBack()" class="mr-3 back-button" title="Go Back" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left text-white text-xl"></i>
                </button>

                <div>
                    <h1 class="text-base font-bold text-white tracking-tight">Create New Room</h1>
                    <p class="text-[12px] text-white text-opacity-80 -mt-0.5">For Expense Management</p>
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
    <main class="pt-16 px-3 max-w-md mx-auto">
        <!-- Create Room Form -->
        <div class="mt-4 relative">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h2 class="ml-3 text-lg font-bold text-gray-800">Create New Room</h2>
                </div>
            </div>

            <form action="process/create_room_process.php" method="post" class="space-y-5">
                <!-- Room Name -->
                <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100">
                    <label class="block text-gray-700 text-xs font-bold mb-2" for="room_name">
                        <i class="fas fa-home text-indigo-500 mr-1"></i> Room Name*
                    </label>
                    <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        id="room_name" name="room_name" type="text" placeholder="Enter room name" required>
                </div>

                <!-- Description -->
                <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100">
                    <label class="block text-gray-700 text-xs font-bold mb-2" for="room_description">
                        <i class="fas fa-align-left text-indigo-500 mr-1"></i> Description (Optional)
                    </label>
                    <textarea class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        id="room_description" name="room_description" placeholder="Enter room description" rows="2"></textarea>
                </div>

                <!-- Address -->
                <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100">
                    <label class="block text-gray-700 text-xs font-bold mb-2" for="room_address">
                        <i class="fas fa-map-marked-alt text-indigo-500 mr-1"></i> Address*
                    </label>
                    <textarea class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        id="room_address" name="room_address" placeholder="Enter room address" rows="2" required></textarea>
                </div>

                <!-- Latitude / Longitude -->
                <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-3">
                        <label class="block text-gray-700 text-xs font-bold" for="latitude">
                            <i class="fas fa-location-arrow text-indigo-500 mr-1"></i> Location Coordinates
                        </label>
                        <button type="button" onclick="getCurrentLocation()" class="text-white text-xs font-medium flex items-center bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 px-3 py-1.5 rounded-lg shadow-sm transition-all">
                            <i class="fas fa-map-marker-alt mr-1.5"></i> Get Current Location
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            id="latitude" name="latitude" type="text" placeholder="Latitude">
                        <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            id="longitude" name="longitude" type="text" placeholder="Longitude">
                    </div>
                </div>

                <!-- Rental Information -->
                <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="font-bold mb-4 text-gray-800 text-sm flex items-center">
                        <i class="fas fa-file-invoice-dollar mr-2 text-indigo-500"></i> Rental Information
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-gray-700 text-xs font-bold mb-2" for="rental_date">
                                <i class="fas fa-calendar-alt text-indigo-500 mr-1"></i> Rental Date
                            </label>
                            <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                id="rental_date" name="rental_date" type="date">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-xs font-bold mb-2" for="monthly_rent">
                                <i class="fas fa-rupee-sign text-indigo-500 mr-1"></i> Monthly Rent (₹)
                            </label>
                            <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                id="monthly_rent" name="monthly_rent" type="number" step="0.01" min="0" placeholder="Enter monthly rent">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-2" for="advance_payment">
                            <i class="fas fa-money-bill-wave text-indigo-500 mr-1"></i> Advance Payment (₹)
                        </label>
                        <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            id="advance_payment" name="advance_payment" type="number" step="0.01" min="0" placeholder="Enter advance payment">
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-center mt-6">
                    <button type="submit" name="create_room" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-md w-full">
                        <i class="fas fa-check-circle mr-2"></i> Create Room
                    </button>
                </div>
            </form>
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










    <script>
        // Function to get current location
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById('latitude').value = position.coords.latitude;
                        document.getElementById('longitude').value = position.coords.longitude;
                    },
                    function(error) {
                        alert("Error getting location: " + error.message);
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        // Tooltip functionality
        document.addEventListener('DOMContentLoaded', function() {
            const refreshButton = document.getElementById('refreshButton');
            const refreshTooltip = document.getElementById('refreshTooltip');

            if (refreshButton && refreshTooltip) {
                refreshButton.addEventListener('mouseenter', function() {
                    refreshTooltip.style.opacity = '1';
                });

                refreshButton.addEventListener('mouseleave', function() {
                    refreshTooltip.style.opacity = '0';
                });
            }
        });
    </script>
</body>

</html>