<?php
// Start session
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

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
    <title>Bachelor Room Expense Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }

        .bottom-nav {
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }

        .add-expense-btn {
            transform: translateY(-50%);
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.5);
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Top Header with Logo and Profile -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-10">
        <div class="flex justify-between items-center px-4 py-3">
            <div class="flex items-center">
                <div class="mr-3">
                    <i class="fas fa-wallet text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Bachelor Room</h1>
                    <p class="text-xs text-gray-500">Expense Management</p>
                </div>
            </div>
            <div class="flex items-center">
                <div class="mr-4 relative">
                    <i class="fas fa-bell text-gray-600 text-xl"></i>
                    <span class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full"></span>
                </div>
                <a href="profile.php">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden">
                        <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-full w-full object-cover">
                    </div>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-20 pb-24 px-4 max-w-md mx-auto">
        <!-- Dashboard Overview -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-2">Dashboard</h2>
            <p class="text-sm text-gray-600 mb-4">Welcome to your Bachelor Room Expense Management dashboard.</p>

            <div class="flex justify-between items-center">
                <div class="text-center">
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-home text-blue-600"></i>
                    </div>
                    <p class="text-xs font-medium">My Rooms</p>
                    <p class="text-lg font-bold">2</p>
                </div>

                <div class="text-center">
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-users text-green-600"></i>
                    </div>
                    <p class="text-xs font-medium">Groups</p>
                    <p class="text-lg font-bold">4</p>
                </div>

                <div class="text-center">
                    <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-receipt text-purple-600"></i>
                    </div>
                    <p class="text-xs font-medium">Expenses</p>
                    <p class="text-lg font-bold">12</p>
                </div>
            </div>
        </div>

        <!-- Create New Room (Collapsible) -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex justify-between items-center cursor-pointer" onclick="toggleCollapsible('createRoomSection')">
                <h2 class="text-lg font-semibold">Create New Room</h2>
                <i id="createRoomIcon" class="fas fa-chevron-down text-blue-600"></i>
            </div>

            <!-- Collapsible Content -->
            <div id="createRoomSection" class="hidden mt-4 border-t pt-4">
                <form action="process/create_room_process.php" method="post">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="room_name">
                            Room Name*
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="room_name" name="room_name" type="text" placeholder="Enter room name" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="room_description">
                            Description (Optional)
                        </label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="room_description" name="room_description" placeholder="Enter room description" rows="2"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="room_address">
                            Address*
                        </label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            id="room_address" name="room_address" placeholder="Enter room address" rows="2" required></textarea>
                    </div>

                    <div class="mb-4 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="latitude">
                                Latitude
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                id="latitude" name="latitude" type="text" placeholder="Latitude">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="longitude">
                                Longitude
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                id="longitude" name="longitude" type="text" placeholder="Longitude">
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="button" onclick="getCurrentLocation()" class="text-blue-600 text-sm font-medium">
                            <i class="fas fa-map-marker-alt mr-1"></i> Get Current Location
                        </button>
                    </div>

                    <!-- Rental Information -->
                    <div class="border-t border-gray-200 pt-4 mt-4 mb-4">
                        <h4 class="font-medium mb-3">Rental Information</h4>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="rental_date">
                                Rental Date
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                id="rental_date" name="rental_date" type="date">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="monthly_rent">
                                Monthly Rent (₹)
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                id="monthly_rent" name="monthly_rent" type="number" step="0.01" min="0" placeholder="Enter monthly rent amount">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="advance_payment">
                                Advance Payment (₹)
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                id="advance_payment" name="advance_payment" type="number" step="0.01" min="0" placeholder="Enter advance payment amount">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="create_room" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Create Room
                        </button>
                    </div>
                </form>
            </div>
        </div>



        <!-- My Rooms Section -->
        <?php
        $stmt = $conn->prepare("SELECT r.*, 
                                        (SELECT COUNT(*) FROM room_members WHERE room_id = r.id) as member_count,
                                        (SELECT MAX(last_activity) FROM room_members WHERE room_id = r.id) as last_activity
                                        FROM rooms r 
                                        JOIN room_members rm ON r.id = rm.room_id 
                                        WHERE rm.user_id = ? 
                                        ORDER BY r.created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($room = $result->fetch_assoc()) {
                // Calculate time ago for last activity
                $last_activity = new DateTime($room['last_activity']);
                $now = new DateTime();
                $interval = $now->diff($last_activity);

                if ($interval->d > 0) {
                    $activity_text = $interval->d . 'd ago';
                } elseif ($interval->h > 0) {
                    $activity_text = $interval->h . 'h ago';
                } else {
                    $activity_text = $interval->i . 'm ago';
                }

                // Check if user is admin for this room
                $is_admin = false;
                $admin_stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
                if ($admin_stmt) {
                    $admin_stmt->bind_param("ii", $room['id'], $user_id);
                    $admin_stmt->execute();
                    $admin_result = $admin_stmt->get_result();
                    if ($admin_result->num_rows > 0) {
                        $admin_row = $admin_result->fetch_assoc();
                        $is_admin = $admin_row['is_admin'] == 1;
                    }
                    $admin_stmt->close();
                }
        ?>

                <div class="bg-white rounded-xl shadow-md p-4 mb-4">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center">
                            <h2 class="text-lg font-semibold"><?php echo htmlspecialchars($room['name']); ?></h2>
                            <?php if ($is_admin): ?>
                                <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                                    <i class="fas fa-crown"></i> Admin
                                </span>
                            <?php endif; ?>
                            <a href="room-details.php?id=<?php echo $room['id']; ?>" class="text-sm text-gray-400 ml-2">
                                <i class="fas fa-chevron-right text-sm text-gray-400 ml-2"></i>
                            </a>
                        </div>

                        <?php if ($is_admin): ?>
                            <button onclick="toggleModal('createGroupModal-<?php echo $room['id']; ?>')" class="h-8 w-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md hover:bg-blue-700 text-sm">
                                <i class="fas fa-plus"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_admin): ?>
                        <div class="mb-3">
                            <a href="room-details.php?id=<?php echo $room['id']; ?>&section=admin" class="inline-flex items-center text-blue-600 text-sm font-medium">
                                <i class="fas fa-tools mr-1"></i> Manage Room
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Groups Section -->
                    <div class="mb-3">


                        <?php
                        // Get room groups
                        $groups_stmt = $conn->prepare("SELECT rg.*, 
                                                 (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id) as member_count,
                                                 (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id AND user_id = ?) as is_member
                                                 FROM room_groups rg 
                                                 WHERE rg.room_id = ?
                                                 ORDER BY rg.created_at ASC
                                                 ");
                        if ($groups_stmt) {
                            $groups_stmt->bind_param("ii", $user_id, $room['id']);
                            $groups_stmt->execute();
                            $groups_result = $groups_stmt->get_result();

                            if ($groups_result->num_rows > 0) {
                        ?>
                                <div class="grid gap-3">
                                    <?php while ($group = $groups_result->fetch_assoc()): ?>
                                        <a href="group.php?id=<?php echo $group['id']; ?>" class="flex items-center justify-between p-3 border rounded-lg hover:shadow-sm transition-shadow border-blue-200 bg-blue-50 ">
                                            <div>
                                                <p class="font-medium"><?php echo htmlspecialchars($group['name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $group['member_count']; ?> Members</p>
                                            </div>
                                            <span class="text-sm text-gray-400">›</span>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                        <?php
                            } else {
                                echo '<p class="text-center text-gray-500 py-2">No groups created yet.</p>';
                            }
                            $groups_stmt->close();
                        }
                        ?>
                    </div>
                </div>

                <!-- Create Group Modal -->
                <div id="createGroupModal-<?php echo $room['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
                    <div class="bg-white rounded-xl p-5 w-full max-w-md mx-4">
                        <h3 class="text-lg font-semibold mb-4">Create New Group</h3>
                        <form action="process/create_group_process.php" method="post">
                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="group_name">
                                    Group Name
                                </label>
                                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    id="group_name" name="group_name" type="text" placeholder="Enter group name" required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="group_description">
                                    Description
                                </label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    id="group_description" name="group_description" placeholder="Enter group description" rows="3"></textarea>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="toggleModal('createGroupModal-<?php echo $room['id']; ?>')" class="px-4 py-2 text-gray-600 font-medium">
                                    Cancel
                                </button>
                                <button type="submit" name="create_group" class="px-4 py-2 bg-blue-600 text-white font-medium rounded hover:bg-blue-700">
                                    Create Group
                                </button>
                            </div>
                        </form>
                    </div>
                </div>


        <?php
            }
        } else {
            echo '<p class="text-center text-gray-500 py-4">You haven\'t joined any rooms yet. Create one to get started!</p>';
        }
        ?>


    </main>


    <script>
        function toggleCollapsible(sectionId) {
            const section = document.getElementById(sectionId);
            const icon = document.getElementById('createRoomIcon');

            if (section.classList.contains('hidden')) {
                section.classList.remove('hidden');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                section.classList.add('hidden');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                }, function(error) {
                    alert('Error getting location: ' + error.message);
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }

        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }
    </script>
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white bottom-nav">
        <div class="relative">
            <!-- Add Expense Button (More Prominent) -->
            <div class="absolute left-1/2 transform -translate-x-1/2 -top-1">
                <button class="h-16 px-6 rounded-full bg-blue-600 text-white flex items-center justify-center add-expense-btn">
                    <i class="fas fa-plus-circle text-xl mr-2"></i>
                    <span class="font-medium">Add Expense</span>
                </button>
            </div>

            <div class="flex justify-around items-center h-16 px-4">
                <a href="#" class="flex flex-col items-center justify-center text-blue-600">
                    <i class="fas fa-home text-xl"></i>
                    <span class="text-xs mt-1">Home</span>
                </a>

                <!-- Spacer for center button -->
                <div class="w-14"></div>

                <a href="report.php" class="flex flex-col items-center justify-center text-gray-500">
                    <i class="fas fa-chart-bar text-xl"></i>
                    <span class="text-xs mt-1">Report</span>
                </a>
            </div>
        </div>
    </nav>


</body>

</html>