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
    <script src="js/back-navigation.js"></script>
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
        
        .group-option {
            transition: all 0.2s ease;
        }
        
        .group-option:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .group-option:active {
            transform: translateY(0);
        }
        
        /* Compact Action Cards Styling */
        .compact-card {
            transition: all 0.2s ease;
        }
        
        .compact-card:hover {
            transform: translateY(-1px);
        }
        
        .compact-icon {
            transition: all 0.2s ease;
        }
        
        .compact-card:hover .compact-icon {
            transform: scale(1.05);
        }
        
        .notification-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Pull to Refresh Styles */
        .pull-to-refresh {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .pull-to-refresh.show {
            transform: translateY(60px);
        }
        
        .pull-to-refresh .refresh-content {
            display: flex;
            align-items: center;
            color: white;
            font-weight: 500;
        }
        
        .pull-to-refresh .refresh-icon {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }
        
        .pull-to-refresh .refresh-icon.spinning {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .pull-indicator {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            z-index: 1001;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .pull-indicator.show {
            opacity: 1;
        }
        
        .pull-indicator .icon {
            margin-right: 6px;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Pull to Refresh Indicator -->
    <div class="pull-indicator" id="pullIndicator">
        <i class="fas fa-arrow-down icon"></i>
        <span>Pull to refresh</span>
    </div>
    
    <!-- Pull to Refresh Bar -->
    <div class="pull-to-refresh" id="pullToRefresh">
        <div class="refresh-content">
            <i class="fas fa-sync-alt refresh-icon" id="refreshIcon"></i>
            <span id="refreshText">Release to refresh</span>
        </div>
    </div>
    
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
        $stmt = $conn->prepare("SELECT r.*, rm.invitation_status, rm.is_admin,
                                        (SELECT COUNT(*) FROM room_members WHERE room_id = r.id) as member_count,
                                        (SELECT MAX(last_activity) FROM room_members WHERE room_id = r.id) as last_activity
                                        FROM rooms r 
                                        JOIN room_members rm ON r.id = rm.room_id 
                                        WHERE rm.user_id = ? 
                                        ORDER BY rm.invitation_status ASC, r.created_at DESC");
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

                // Get admin status from query result
                $is_admin = $room['is_admin'] == 1;
                $invitation_status = $room['invitation_status'];
        ?>

                <div class="bg-white rounded-xl shadow-md p-4 mb-4 <?php echo $invitation_status == 'pending' ? 'border-l-4 border-yellow-400' : ''; ?>">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center">
                            <h2 class="text-lg font-semibold"><?php echo htmlspecialchars($room['name']); ?></h2>
                            
                            
                            <?php if ($is_admin): ?>
                                <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                                    <i class="fas fa-crown"></i> Admin
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($invitation_status == 'accepted'): ?>
                                <a href="room-details.php?id=<?php echo $room['id']; ?>" class="text-sm text-gray-400 ml-2">
                                    <i class="fas fa-chevron-right text-sm text-gray-400 ml-2"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($invitation_status == 'pending'): ?>
                            <div class="flex space-x-2">
                                <button onclick="acceptInvitation(<?php echo $room['id']; ?>)" class="bg-green-600 hover:bg-green-700 text-white text-xs font-medium px-3 py-1 rounded">
                                    <i class="fas fa-check mr-1"></i> Accept
                                </button>
                                <button onclick="rejectInvitation(<?php echo $room['id']; ?>)" class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-1 rounded">
                                    <i class="fas fa-times mr-1"></i> Reject
                                </button>
                            </div>
                        <?php elseif ($is_admin && $invitation_status == 'accepted'): ?>
                            <button onclick="toggleModal('createGroupModal-<?php echo $room['id']; ?>')" class="h-8 w-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md hover:bg-blue-700 text-sm">
                                <i class="fas fa-plus"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($invitation_status == 'accepted'): ?>
                        <!-- Compact Action Cards - Two in One Row -->
                        <div class="mb-3 grid grid-cols-2 gap-2">
                            <?php if ($is_admin): ?>
                                <?php
                                // Check for pending absence requests
                                $pending_stmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM absence_requests WHERE room_id = ? AND status = 'pending'");
                                $pending_stmt->bind_param("i", $room['id']);
                                $pending_stmt->execute();
                                $pending_result = $pending_stmt->get_result();
                                $pending_count = $pending_result->fetch_assoc()['pending_count'];
                                ?>
                                
                                <!-- Absence Requests Card -->
                                <button onclick="toggleModal('absenceRequestsModal-<?php echo $room['id']; ?>')" 
                                        class="group compact-card bg-gradient-to-r from-orange-50 to-orange-100 border border-orange-200 rounded-lg px-3 py-2 hover:from-orange-100 hover:to-orange-200 transition-all duration-200 hover:shadow-md relative">
                                    <div class="flex items-center space-x-2">
                                        <div class="compact-icon w-6 h-6 bg-orange-500 rounded flex items-center justify-center group-hover:bg-orange-600 transition-colors">
                                            <i class="fas fa-calendar-times text-white text-xs"></i>
                                        </div>
                                        <span class="font-medium text-orange-800 text-xs">Absence Requests</span>
                                        <?php if ($pending_count > 0): ?>
                                            <span class="bg-red-500 text-white text-xs font-bold rounded-full h-4 w-4 flex items-center justify-center text-[10px]"><?php echo $pending_count; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </button>
                            <?php endif; ?>
                            
                            <!-- Request Absence Card -->
                            <button onclick="toggleModal('requestAbsenceModal-<?php echo $room['id']; ?>')" 
                                    class="group compact-card bg-gradient-to-r from-purple-50 to-purple-100 border border-purple-200 rounded-lg px-3 py-2 hover:from-purple-100 hover:to-purple-200 transition-all duration-200 hover:shadow-md <?php echo !$is_admin ? 'col-span-2' : ''; ?>">
                                <div class="flex items-center space-x-2">
                                    <div class="compact-icon w-6 h-6 bg-purple-500 rounded flex items-center justify-center group-hover:bg-purple-600 transition-colors">
                                        <i class="fas fa-calendar-minus text-white text-xs"></i>
                                    </div>
                                    <span class="font-medium text-purple-800 text-xs">Request Absence</span>
                                </div>
                            </button>
                        </div>

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
                                        <div class="flex items-center space-x-2 p-3 border rounded-lg hover:shadow-sm transition-shadow border-blue-200 bg-blue-50">
                                            <a href="group.php?id=<?php echo $group['id']; ?>" class="flex-1 flex items-center justify-between">
                                                <div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($group['name']); ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo $group['member_count']; ?> Members</p>
                                                </div>
                                                <span class="text-sm text-gray-400">›</span>
                                            </a>
                                            <!-- Quick Actions -->
                                            <div class="flex space-x-1">
                                                <a href="settlement_report.php?group_id=<?php echo $group['id']; ?>" 
                                                   class="p-2 text-purple-600 hover:bg-purple-100 rounded-full transition-colors" 
                                                   title="Settlement Reports">
                                                    <i class="fas fa-file-invoice-dollar text-sm"></i>
                                                </a>
                                                
                                            </div>
                                        </div>
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
                    <?php else: ?>
                        <!-- Pending Invitation Message -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                            <i class="fas fa-clock text-yellow-600 text-2xl mb-2"></i>
                            <p class="text-yellow-800 font-medium">Room Invitation Pending</p>
                            <p class="text-yellow-600 text-sm">Please accept the invitation to access room features.</p>
                        </div>
                    <?php endif; ?>
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

                <!-- Request Absence Modal -->
                <div id="requestAbsenceModal-<?php echo $room['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
                    <div class="bg-white rounded-xl p-5 w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
                        <h3 class="text-lg font-semibold mb-4 text-purple-800">
                            <i class="fas fa-calendar-minus mr-2"></i>Request Absence
                        </h3>
                        <form action="process/request_absence_process.php" method="post">
                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    Absence Period
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500">From Date</label>
                                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                            name="from_date" type="date" required>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">To Date</label>
                                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                            name="to_date" type="date" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">
                                    Reason for Absence
                                </label>
                                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    name="reason" placeholder="Enter reason for absence (optional)" rows="3"></textarea>
                            </div>

                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 mb-4">
                                <p class="text-sm text-purple-700">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <strong>Note:</strong> During settlement, your food expenses will be calculated only for days you were present. Special items will still be split equally among all members.
                                </p>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="toggleModal('requestAbsenceModal-<?php echo $room['id']; ?>')" class="px-4 py-2 text-gray-600 font-medium">
                                    Cancel
                                </button>
                                <button type="submit" name="request_absence" class="px-4 py-2 bg-purple-600 text-white font-medium rounded hover:bg-purple-700">
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Admin: Manage Absence Requests Modal -->
                <?php if ($is_admin): ?>
                <div id="absenceRequestsModal-<?php echo $room['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
                    <div class="bg-white rounded-xl p-5 w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto">
                        <h3 class="text-lg font-semibold mb-4 text-orange-800">
                            <i class="fas fa-calendar-times mr-2"></i>Manage Absence Requests
                        </h3>
                        
                        <div id="absenceRequestsList-<?php echo $room['id']; ?>">
                            <?php
                            // Get only pending absence requests for this room
                            $requests_stmt = $conn->prepare("SELECT ar.*, u.full_name, u.profile_image,
                                                           r.full_name as reviewer_name
                                                           FROM absence_requests ar
                                                           JOIN users u ON ar.user_id = u.id
                                                           LEFT JOIN users r ON ar.reviewed_by = r.id
                                                           WHERE ar.room_id = ? AND ar.status = 'pending'
                                                           ORDER BY ar.requested_at DESC");
                            $requests_stmt->bind_param("i", $room['id']);
                            $requests_stmt->execute();
                            $requests_result = $requests_stmt->get_result();
                            
                            if ($requests_result->num_rows > 0):
                                while ($request = $requests_result->fetch_assoc()):
                            ?>
                                <div class="border rounded-lg p-4 mb-3 <?php 
                                    echo $request['status'] == 'pending' ? 'border-yellow-300 bg-yellow-50' : 
                                        ($request['status'] == 'approved' ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50'); 
                                ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center space-x-3">
                                            <img src="uploads/profile/<?php echo $request['profile_image'] ? $request['profile_image'] : 'default_avatar.png'; ?>" 
                                                 class="h-10 w-10 rounded-full object-cover">
                                            <div>
                                                <p class="font-medium"><?php echo htmlspecialchars($request['full_name']); ?></p>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo date('M d, Y', strtotime($request['from_date'])); ?> - 
                                                    <?php echo date('M d, Y', strtotime($request['to_date'])); ?>
                                                </p>
                                                <?php if ($request['reason']): ?>
                                                    <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($request['reason']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="text-right">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php 
                                                echo $request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                    ($request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); 
                                            ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                            
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <div class="mt-2 space-x-2">
                                                    <button onclick="reviewAbsenceRequest(<?php echo $request['id']; ?>, 'approved')" 
                                                            class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded">
                                                        <i class="fas fa-check mr-1"></i>Approve
                                                    </button>
                                                    <button onclick="reviewAbsenceRequest(<?php echo $request['id']; ?>, 'rejected')" 
                                                            class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded">
                                                        <i class="fas fa-times mr-1"></i>Reject
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <div class="text-center py-6">
                                    <i class="fas fa-calendar-check text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 font-medium">No pending absence requests</p>
                                    <p class="text-sm text-gray-400">All absence requests have been reviewed</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200">
                            <a href="view_all_absence.php?room_id=<?php echo $room['id']; ?>" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-list mr-2"></i>Show All Requests
                            </a>
                            <button type="button" onclick="toggleModal('absenceRequestsModal-<?php echo $room['id']; ?>')" class="px-4 py-2 text-gray-600 font-medium">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>


        <?php
            }
        } else {
            echo '<p class="text-center text-gray-500 py-4">You haven\'t joined any rooms yet. Create one to get started!</p>';
        }
        ?>


    </main>

    <!-- Group Selector Modal -->
    <div id="groupSelectorModal" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-5 w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Select Group for Report
                </h3>
                <button onclick="toggleModal('groupSelectorModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="text-sm text-gray-600 mb-4">Choose a group to view settlement reports:</p>
            
            <div class="space-y-3" id="groupsList">
                <?php
                // Get all groups the user is a member of
                $groups_stmt = $conn->prepare("SELECT rg.id, rg.name, r.name as room_name,
                                              (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id) as member_count,
                                              (SELECT COUNT(*) FROM settlements WHERE group_id = rg.id) as settlement_count
                                              FROM room_groups rg
                                              JOIN rooms r ON rg.room_id = r.id
                                              JOIN group_members gm ON rg.id = gm.group_id
                                              WHERE gm.user_id = ?
                                              ORDER BY r.name, rg.name");
                $groups_stmt->bind_param("i", $user_id);
                $groups_stmt->execute();
                $groups_result = $groups_stmt->get_result();
                
                if ($groups_result->num_rows > 0):
                    while ($group = $groups_result->fetch_assoc()):
                ?>
                    <div class="group-option border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition-all"
                         onclick="selectGroup(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>')">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($group['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($group['room_name']); ?></div>
                                <div class="text-xs text-gray-400 mt-1">
                                    <?php echo $group['member_count']; ?> members • 
                                    <?php echo $group['settlement_count']; ?> settlements
                                </div>
                            </div>
                            <div class="text-blue-600">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="text-center py-6">
                        <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500 font-medium">No groups found</p>
                        <p class="text-sm text-gray-400">Join a group to view settlement reports</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($groups_result->num_rows > 0): ?>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Select a group to view its settlement history and reports
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>


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
        
        function acceptInvitation(roomId) {
            if (confirm('Accept room invitation?')) {
                window.location.href = `process/accept_invitation_process.php?room_id=${roomId}&action=accept`;
            }
        }
        
        function rejectInvitation(roomId) {
            if (confirm('Reject room invitation?')) {
                window.location.href = `process/accept_invitation_process.php?room_id=${roomId}&action=reject`;
            }
        }
        
        function reviewAbsenceRequest(requestId, action) {
            const actionText = action === 'approved' ? 'approve' : 'reject';
            if (confirm(`${actionText.charAt(0).toUpperCase() + actionText.slice(1)} this absence request?`)) {
                fetch('process/review_absence_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload to update the UI
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to ' + actionText + ' request');
                });
            }
        }
        
        function selectGroup(groupId, groupName) {
            // Add loading animation
            const clickedOption = event.currentTarget;
            const chevron = clickedOption.querySelector('.fa-chevron-right');
            if (chevron) {
                chevron.className = 'fas fa-spinner fa-spin';
            }
            
            // Add visual feedback
            clickedOption.style.backgroundColor = '#dbeafe';
            clickedOption.style.borderColor = '#3b82f6';
            
            // Redirect to settlement report page after short delay for visual feedback
            setTimeout(() => {
                window.location.href = `settlement_report.php?group_id=${groupId}`;
            }, 300);
        }
        
        // Pull to Refresh Functionality
        let pullToRefresh = {
            startY: 0,
            currentY: 0,
            isPulling: false,
            isRefreshing: false,
            threshold: 80,
            maxPull: 120,
            
            init() {
                this.bindEvents();
            },
            
            bindEvents() {
                // Touch events for mobile
                document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
                document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
                document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
                
                // Mouse events for desktop (for testing)
                document.addEventListener('mousedown', this.handleMouseDown.bind(this));
                document.addEventListener('mousemove', this.handleMouseMove.bind(this));
                document.addEventListener('mouseup', this.handleMouseUp.bind(this));
            },
            
            handleTouchStart(e) {
                if (window.scrollY === 0 && !this.isRefreshing) {
                    this.startY = e.touches[0].clientY;
                    this.isPulling = true;
                }
            },
            
            handleTouchMove(e) {
                if (!this.isPulling || this.isRefreshing) return;
                
                this.currentY = e.touches[0].clientY;
                const pullDistance = this.currentY - this.startY;
                
                if (pullDistance > 0) {
                    e.preventDefault();
                    this.updatePullState(pullDistance);
                }
            },
            
            handleTouchEnd(e) {
                if (!this.isPulling || this.isRefreshing) return;
                
                const pullDistance = this.currentY - this.startY;
                this.isPulling = false;
                
                if (pullDistance > this.threshold) {
                    this.triggerRefresh();
                } else {
                    this.resetPullState();
                }
            },
            
            handleMouseDown(e) {
                if (window.scrollY === 0 && !this.isRefreshing && e.clientY < 50) {
                    this.startY = e.clientY;
                    this.isPulling = true;
                    e.preventDefault();
                }
            },
            
            handleMouseMove(e) {
                if (!this.isPulling || this.isRefreshing) return;
                
                this.currentY = e.clientY;
                const pullDistance = this.currentY - this.startY;
                
                if (pullDistance > 0) {
                    e.preventDefault();
                    this.updatePullState(pullDistance);
                }
            },
            
            handleMouseUp(e) {
                if (!this.isPulling || this.isRefreshing) return;
                
                const pullDistance = this.currentY - this.startY;
                this.isPulling = false;
                
                if (pullDistance > this.threshold) {
                    this.triggerRefresh();
                } else {
                    this.resetPullState();
                }
            },
            
            updatePullState(pullDistance) {
                const progress = Math.min(pullDistance / this.maxPull, 1);
                const refreshBar = document.getElementById('pullToRefresh');
                const pullIndicator = document.getElementById('pullIndicator');
                const refreshText = document.getElementById('refreshText');
                
                // Show pull indicator when starting to pull
                if (pullDistance > 20) {
                    pullIndicator.classList.add('show');
                }
                
                // Show refresh bar when threshold is reached
                if (pullDistance > this.threshold) {
                    refreshBar.classList.add('show');
                    refreshText.textContent = 'Release to refresh';
                    pullIndicator.innerHTML = '<i class="fas fa-arrow-up icon"></i><span>Release to refresh</span>';
                } else {
                    refreshText.textContent = 'Pull to refresh';
                    pullIndicator.innerHTML = '<i class="fas fa-arrow-down icon"></i><span>Pull to refresh</span>';
                }
                
                // Add haptic feedback on mobile
                if (navigator.vibrate && pullDistance > this.threshold && pullDistance < this.threshold + 5) {
                    navigator.vibrate(50);
                }
            },
            
            triggerRefresh() {
                this.isRefreshing = true;
                const refreshBar = document.getElementById('pullToRefresh');
                const refreshIcon = document.getElementById('refreshIcon');
                const refreshText = document.getElementById('refreshText');
                const pullIndicator = document.getElementById('pullIndicator');
                
                // Show refresh bar and start spinning
                refreshBar.classList.add('show');
                refreshIcon.classList.add('spinning');
                refreshText.textContent = 'Refreshing...';
                pullIndicator.classList.remove('show');
                
                // Add haptic feedback
                if (navigator.vibrate) {
                    navigator.vibrate([100, 50, 100]);
                }
                
                // Simulate refresh delay and then reload
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            },
            
            resetPullState() {
                const refreshBar = document.getElementById('pullToRefresh');
                const refreshIcon = document.getElementById('refreshIcon');
                const pullIndicator = document.getElementById('pullIndicator');
                
                refreshBar.classList.remove('show');
                refreshIcon.classList.remove('spinning');
                pullIndicator.classList.remove('show');
                
                this.isPulling = false;
                this.isRefreshing = false;
            }
        };
        
        // Initialize pull to refresh when page loads
        document.addEventListener('DOMContentLoaded', function() {
            pullToRefresh.init();
        });
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

                <button onclick="toggleModal('groupSelectorModal')" class="flex flex-col items-center justify-center text-gray-500">
                    <i class="fas fa-chart-bar text-xl"></i>
                    <span class="text-xs mt-1">Report</span>
                </button>
            </div>
        </div>
    </nav>


</body>

</html>