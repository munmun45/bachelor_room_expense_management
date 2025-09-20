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

// Get this month's total spending for the user
$start_of_month = date('Y-m-01');
$end_of_month = date('Y-m-t');

$total_spending_stmt = $conn->prepare("SELECT SUM(amount) as total_spending FROM group_messages WHERE user_id = ? AND amount IS NOT NULL AND amount > 0 AND created_at BETWEEN ? AND ?");
$total_spending_stmt->bind_param("iss", $user_id, $start_of_month, $end_of_month);
$total_spending_stmt->execute();
$total_spending_result = $total_spending_stmt->get_result();
$total_spending = $total_spending_result->fetch_assoc()['total_spending'] ?? 0;
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
            background: linear-gradient(160deg, #f7f9fc 0%, #eef1f8 40%, #e9ecf5 100%);
            min-height: 100vh;
        }


        .bottom-nav {
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }

        .add-expense-btn {
            transform: translateY(-50%);
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.5);
            transition: all 0.3s ease;
        }

        .add-expense-btn:hover {
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 0 20px rgba(37, 99, 235, 0.7);
        }

        .group-option {
            transition: all 0.3s ease;
            border-radius: 10px;
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
            transition: all 0.3s ease;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .compact-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .compact-icon {
            transition: all 0.3s ease;
        }

        .compact-card:hover .compact-icon {
            transform: scale(1.05);
        }

        .notification-badge {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
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
            border-radius: 0 0 5px 5px;
        }

        .pull-to-refresh.show {
            transform: translateY(60px);
        }

        .pull-to-refresh .refresh-content {
            display: flex;
            align-items: center;
            color: white;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .pull-to-refresh .refresh-icon {
            margin-right: 6px;
            transition: transform 0.3s ease;
        }

        .pull-to-refresh .refresh-icon.spinning {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .pull-indicator {
            position: fixed;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            z-index: 1001;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .pull-indicator.show {
            opacity: 1;
        }

        .pull-indicator .icon {
            margin-right: 4px;
        }

        /* Mobile App Design Enhancements */
        .app-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            
        }

        .card-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
        }

        .action-button {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .action-button:active {
            transform: translateY(0);
        }

        .floating-card {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-5px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .room-card {
            transition: all 0.2s ease;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .room-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .modal-container {
            animation: modalSlideIn 0.2s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(15px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .collapsible-section {
            transition: all 0.2s ease;
        }

        .collapsible-content {
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }

            to {
                opacity: 1;
                max-height: 1000px;
            }
        }

        /* Compact form elements */
        .compact-input {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .compact-button {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .compact-card-content {
            padding: 0.75rem;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen pb-20">
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
    <header class="app-header fixed top-0 left-0 right-0 z-10 py-2.5 px-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2.5">
                <div class="bg-white bg-opacity-20 p-1.5 rounded-lg backdrop-blur-sm" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-wallet text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="text-base font-bold text-white tracking-tight">Bachelor Room</h1>
                    <p class="text-[12px] text-white text-opacity-80 -mt-0.5">Expense Management</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-7 h-7 rounded-full bg-white bg-opacity-20 flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-bell text-white text-sm"></i>
                    </div>
                    <span class="absolute -top-0.5 -right-0.5 h-2.5 w-2.5 bg-red-500 rounded-full border border-white animate-pulse"></span>
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

        <!-- Dashboard Section -->

        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-sm font-semibold text-gray-700">This Month's Spending</h2>
                <div class="bg-gradient-to-r from-indigo-400 to-purple-500 text-white text-[11px] font-bold px-3 py-1 rounded-full shadow-sm">
                    <?php echo date('M Y'); ?>
                </div>
            </div>

            <!-- Spending Card -->
            <div class="">
                <div class="flex items-center justify-between">
                    <!-- Icon -->
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center 
                            bg-gradient-to-r from-pink-400 to-red-500 shadow-md">
                            <p class="text-white font-bold">₹</p>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs text-gray-500 font-medium">My Contribute</p>
                            <p class="text-xl font-bold text-gray-800">
                                <?php echo number_format($total_spending, 2); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Optional Badge -->
                    <div class="text-xs font-semibold text-gray-600 bg-gray-50 px-3 py-1 rounded-full border border-gray-200">
                        Active
                    </div>
                </div>
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

                <div class="p-5 mb-4 rounded-3xl relative border-gray-200 " style="background: linear-gradient(135deg, #E9D5FF, #FECACA);">

                
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center">
                            <h2 class="text-sm font-semibold truncate max-w-[140px]" style=" color: #E91E63; font-weight: bold;" ><?php echo htmlspecialchars($room['name']); ?></h2>


                            <?php if ($is_admin): ?>
                                <span class="ml-1.5 bg-gradient-to-r from-amber-100 to-yellow-100 text-amber-800 text-[12px] font-medium px-1.5 py-0.5 rounded-full shadow-xs">
                                    <i class="fas fa-crown text-[8px]"></i> Admin
                                </span>
                            <?php endif; ?>

                            <?php if ($invitation_status == 'accepted'): ?>
                                <a href="room-details.php?id=<?php echo $room['id']; ?>" class="text-[12px] text-gray-400 ml-1.5">
                                    <i class="fas fa-chevron-right text-[12px]  ml-1.5" style=" color: #E91E63; font-weight: bold;"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($invitation_status == 'pending'): ?>
                            <div class="flex space-x-1.5">
                                <button onclick="acceptInvitation(<?php echo $room['id']; ?>)" class="action-button bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white text-[12px] font-medium px-2 py-1 rounded-md shadow-xs transition-all">
                                    <i class="fas fa-check mr-1 text-[8px]"></i> Accept
                                </button>
                                <button onclick="rejectInvitation(<?php echo $room['id']; ?>)" class="action-button bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white text-[12px] font-medium px-2 py-1 rounded-md shadow-xs transition-all">
                                    <i class="fas fa-times mr-1 text-[8px]"></i> Reject
                                </button>
                            </div>
                        <?php elseif ($is_admin && $invitation_status == 'accepted'): ?>
                            <button onclick="toggleModal('createGroupModal-<?php echo $room['id']; ?>')" class="h-7 w-7 rounded-full bg-gradient-to-r from-pink-400 to-red-500 text-white flex items-center justify-center transition-all transform hover:scale-105">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        <?php else: ?>
                            <button onclick="toggleModal('requestAbsenceModal-<?php echo $room['id']; ?>')"
                                class="group compact-card bg-gradient-to-br from-purple-50 to-indigo-100  border-purple-200 rounded-lg px-2.5 py-2 hover:from-purple-100 hover:to-indigo-200 transition-all duration-200 hover:shadow-xs <?php echo !$is_admin ? 'col-span-2' : ''; ?>">
                                <div class="flex items-center space-x-1.5">
                                    <div class="compact-icon w-6 h-6 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-md flex items-center justify-center group-hover:from-purple-600 group-hover:to-indigo-700 transition-all shadow-xs">
                                        <i class="fas fa-calendar-minus text-white text-[12px]"></i>
                                    </div>
                                    <span class="font-medium text-purple-800 text-[12px] flex">Request Absence</span>
                                </div>
                            </button>

                        <?php endif; ?>
                    </div>

                    <?php if ($invitation_status == 'accepted'): ?>
                        <!-- Compact Action Cards - Two in One Row -->
                        <div class="mb-3 grid grid-cols-2 gap-2 ">
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
                                    class="group compact-card bg-gradient-to-br from-amber-50 to-orange-100  border-amber-200 rounded-lg px-2.5 py-2 hover:from-amber-100 hover:to-orange-200 transition-all duration-200 hover:shadow-xs relative">
                                    <div class="flex items-center space-x-1.5">
                                        <div class="compact-icon w-6 h-6 bg-gradient-to-r from-amber-500 to-orange-600 rounded-md flex items-center justify-center group-hover:from-amber-600 group-hover:to-orange-700 transition-all shadow-xs">
                                            <i class="fas fa-calendar-times text-white text-[12px]"></i>
                                        </div>
                                        <div class="text-left flex">
                                            <span class="font-medium text-amber-800 text-[12px]">Absence Requests</span>
                                            <?php if ($pending_count > 0): ?>
                                                <span class="bg-red-500 text-white text-[8px] font-bold rounded-full h-4 w-4 flex items-center justify-center text-[8px] absolute -top-1 -right-1 animate-pulse"><?php echo $pending_count; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </button>
                                <!-- Request Absence Card -->
                                <button onclick="toggleModal('requestAbsenceModal-<?php echo $room['id']; ?>')"
                                    class="group compact-card bg-gradient-to-br from-purple-50 to-indigo-100  border-purple-200 rounded-lg px-2.5 py-2 hover:from-purple-100 hover:to-indigo-200 transition-all duration-200 hover:shadow-xs <?php echo !$is_admin ? 'col-span-2' : ''; ?>">
                                    <div class="flex items-center space-x-1.5">
                                        <div class="compact-icon w-6 h-6 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-md flex items-center justify-center group-hover:from-purple-600 group-hover:to-indigo-700 transition-all shadow-xs">
                                            <i class="fas fa-calendar-minus text-white text-[12px]"></i>
                                        </div>
                                        <span class="font-medium text-purple-800 text-[12px] flex">Request Absence</span>
                                    </div>
                                </button>
                            <?php endif; ?>


                        </div>

                        <!-- Groups Section -->
                        <div class="mb-2.5">


                            <?php
                            // Get only groups where user is a member
                            $groups_stmt = $conn->prepare("SELECT rg.*, 
                                                 (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id) as member_count,
                                                 gm.is_leader
                                                 FROM room_groups rg 
                                                 JOIN group_members gm ON rg.id = gm.group_id
                                                 WHERE rg.room_id = ? AND gm.user_id = ?
                                                 ORDER BY rg.created_at ASC
                                                 ");
                            if ($groups_stmt) {
                                $groups_stmt->bind_param("ii", $room['id'], $user_id);
                                $groups_stmt->execute();
                                $groups_result = $groups_stmt->get_result();

                                if ($groups_result->num_rows > 0) {
                            ?>
                                    <div class="grid gap-2 mt-2.5">
                                        <?php while ($group = $groups_result->fetch_assoc()): ?>
                                            <div class="flex items-center space-x-2.5 p-2.5  rounded-lg bg-white">
                                                <a href="group.php?id=<?php echo $group['id']; ?>" class="flex-1 flex items-center justify-between">
                                                    <div>
                                                        <div class="flex items-center space-x-1.5">
                                                            <p class="font-medium text-gray-800 text-sm truncate max-w-[120px]"><?php echo htmlspecialchars($group['name']); ?></p>
                                                            <?php if ($group['is_leader'] == 1): ?>
                                                                <span class="text-[9px] bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 px-1.5 py-0.5 rounded-full shadow-xs">
                                                                    <i class="fas fa-crown mr-0.5 text-[8px]"></i>Leader
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-[12px] text-gray-500 mt-0.5"><?php echo $group['member_count']; ?> Members</p>
                                                    </div>
                                                    <span class="text-gray-400">›</span>
                                                </a>
                                                <!-- Quick Actions -->
                                                <div class="flex space-x-1">
                                                    <a href="settlement_report.php?group_id=<?php echo $group['id']; ?>"
                                                        class="p-1.5 text-indigo-600 hover:bg-indigo-100 rounded-full transition-colors"
                                                        title="Settlement Reports">
                                                        <i class="fas fa-file-invoice-dollar"></i>
                                                    </a>

                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                            <?php
                                } else {
                                    echo '<p class="text-center text-gray-500 py-2.5 text-xs">You are not a member of any groups in this room.</p>';
                                }
                                $groups_stmt->close();
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <!-- Pending Invitation Message -->
                        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border-amber-200 rounded-lg p-3 text-center shadow-xs">
                            <i class="fas fa-clock text-amber-500 text-lg mb-1.5"></i>
                            <p class="text-amber-800 font-medium text-xs">Room Invitation Pending</p>
                            <p class="text-amber-600 text-[12px] mt-1">Please accept the invitation to access room features.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Create Group Modal -->
                <div id="createGroupModal-<?php echo $room['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
                    <div class="modal-container bg-white rounded-xl p-4 w-full max-w-md mx-3 shadow-xl">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-base font-bold text-gray-800">Create New Group</h3>
                            <button onclick="toggleModal('createGroupModal-<?php echo $room['id']; ?>')" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </div>
                        <form action="process/create_group_process.php" method="post">
                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">

                            <div class="mb-3">
                                <label class="block text-gray-700 text-xs font-bold mb-1.5" for="group_name">
                                    Group Name
                                </label>
                                <input class="compact-input shadow-sm appearance-none border border-gray-200 rounded-lg w-full text-gray-700 leading-tight focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    id="group_name" name="group_name" type="text" placeholder="Enter group name" required>
                            </div>

                            <div class="mb-3">
                                <label class="block text-gray-700 text-xs font-bold mb-1.5" for="group_description">
                                    Description
                                </label>
                                <textarea class="compact-input shadow-sm appearance-none border border-gray-200 rounded-lg w-full text-gray-700 leading-tight focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    id="group_description" name="group_description" placeholder="Enter group description" rows="2"></textarea>
                            </div>

                            <div class="flex justify-end space-x-2 pt-2">
                                <button type="button" onclick="toggleModal('createGroupModal-<?php echo $room['id']; ?>')" class="px-3 py-2 text-gray-600 font-medium text-xs rounded-lg hover:bg-gray-100 transition-all">
                                    Cancel
                                </button>
                                <button type="submit" name="create_group" class="action-button px-3 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium text-xs rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-xs">
                                    Create Group
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Request Absence Modal -->
                <div id="requestAbsenceModal-<?php echo $room['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
                    <div class="modal-container bg-white rounded-xl p-4 w-full max-w-md mx-3 max-h-[70vh] overflow-y-auto shadow-xl">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-base font-bold text-purple-800">
                                <i class="fas fa-calendar-minus mr-1.5 text-sm"></i>Request Absence
                            </h3>
                            <button onclick="toggleModal('requestAbsenceModal-<?php echo $room['id']; ?>')" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </div>
                        <form action="process/request_absence_process.php" method="post">
                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">

                            <div class="mb-3">
                                <label class="block text-gray-700 text-xs font-bold mb-1.5">
                                    Absence Period
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[9px] text-gray-500 mb-1 block">From Date</label>
                                        <input class="compact-input shadow-sm appearance-none border border-gray-200 rounded-lg w-full text-gray-700 leading-tight focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-transparent transition-all"
                                            name="from_date" type="date" required>
                                    </div>
                                    <div>
                                        <label class="text-[9px] text-gray-500 mb-1 block">To Date</label>
                                        <input class="compact-input shadow-sm appearance-none border border-gray-200 rounded-lg w-full text-gray-700 leading-tight focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-transparent transition-all"
                                            name="to_date" type="date" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="block text-gray-700 text-xs font-bold mb-1.5">
                                    Reason for Absence
                                </label>
                                <textarea class="compact-input shadow-sm appearance-none border border-gray-200 rounded-lg w-full text-gray-700 leading-tight focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-transparent transition-all"
                                    name="reason" placeholder="Enter reason (optional)" rows="2"></textarea>
                            </div>

                            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg p-3 mb-4">
                                <p class="text-[12px] text-purple-700">
                                    <i class="fas fa-info-circle mr-1.5"></i>
                                    <strong>Note:</strong> During settlement, your food expenses will be calculated only for days you were present. Special items will still be split equally among all members.
                                </p>
                            </div>

                            <div class="flex justify-end space-x-2">
                                <button type="button" onclick="toggleModal('requestAbsenceModal-<?php echo $room['id']; ?>')" class="px-3 py-2 text-gray-600 font-medium text-xs rounded-lg hover:bg-gray-100 transition-all">
                                    Cancel
                                </button>
                                <button type="submit" name="request_absence" class="action-button px-3 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium text-xs rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-xs">
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Admin: Manage Absence Requests Modal -->
                <?php if ($is_admin): ?>
                    <div id="absenceRequestsModal-<?php echo $room['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
                        <div class="modal-container bg-white rounded-xl p-4 w-full max-w-md mx-3 max-h-[70vh] overflow-y-auto shadow-xl">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-base font-bold text-orange-800">
                                    <i class="fas fa-calendar-times mr-1.5 text-sm"></i>Manage Absence Requests
                                </h3>
                                <button onclick="toggleModal('absenceRequestsModal-<?php echo $room['id']; ?>')" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times text-sm"></i>
                                </button>
                            </div>

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
                                        <div class="border rounded-lg p-3 mb-3 <?php
                                                                                echo $request['status'] == 'pending' ? 'border-yellow-300 bg-gradient-to-r from-yellow-50 to-amber-50' : ($request['status'] == 'approved' ? 'border-green-300 bg-gradient-to-r from-green-50 to-emerald-50' : 'border-red-300 bg-gradient-to-r from-red-50 to-rose-50');
                                                                                ?>">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-center space-x-2.5">
                                                    <img src="uploads/profile/<?php echo $request['profile_image'] ? $request['profile_image'] : 'default_avatar.png'; ?>"
                                                        class="h-8 w-8 rounded-full object-cover border border-white shadow-xs">
                                                    <div>
                                                        <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($request['full_name']); ?></p>
                                                        <p class="text-[12px] text-gray-600">
                                                            <?php echo date('M d, Y', strtotime($request['from_date'])); ?> -
                                                            <?php echo date('M d, Y', strtotime($request['to_date'])); ?>
                                                        </p>
                                                        <?php if ($request['reason']): ?>
                                                            <p class="text-[12px] text-gray-500 mt-1 truncate max-w-[120px]"><?php echo htmlspecialchars($request['reason']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="text-right">
                                                    <span class="px-2 py-1 text-[9px] font-medium rounded-full <?php
                                                                                                                echo $request['status'] == 'pending' ? 'bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800' : ($request['status'] == 'approved' ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800' : 'bg-gradient-to-r from-red-100 to-rose-100 text-red-800');
                                                                                                                ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>

                                                    <?php if ($request['status'] == 'pending'): ?>
                                                        <div class="mt-2 space-x-1.5 flex">
                                                            <button onclick="reviewAbsenceRequest(<?php echo $request['id']; ?>, 'approved')"
                                                                class="action-button bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white text-[9px] px-2 py-1 rounded-md shadow-xs transition-all">
                                                                <i class="fas fa-check mr-1 text-[8px]"></i>Approve
                                                            </button>
                                                            <button onclick="reviewAbsenceRequest(<?php echo $request['id']; ?>, 'rejected')"
                                                                class="action-button bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white text-[9px] px-2 py-1 rounded-md shadow-xs transition-all">
                                                                <i class="fas fa-times mr-1 text-[8px]"></i>Reject
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
                                        <i class="fas fa-calendar-check text-3xl text-gray-300 mb-2"></i>
                                        <p class="text-gray-500 font-medium text-sm">No pending absence requests</p>
                                        <p class="text-[12px] text-gray-400 mt-1">All absence requests have been reviewed</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-100">
                                <a href="view_all_absence.php?room_id=<?php echo $room['id']; ?>"
                                    class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-xs">
                                    <i class="fas fa-list mr-1.5 text-xs"></i>Show All Requests
                                </a>
                                <button type="button" onclick="toggleModal('absenceRequestsModal-<?php echo $room['id']; ?>')" class="px-3 py-2 text-gray-600 font-medium text-xs rounded-lg hover:bg-gray-100 transition-all">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>


        <?php
            }
        } else {
            echo '<p class="text-center text-gray-500 py-5 text-sm">You haven\'t joined any rooms yet. Create one to get started!</p>';
        }
        ?>

        <!-- Create New Room (Collapsible) -->
        <div class="p-4 mb-4 rounded-2xl  relative bg-gradient-to-br from-indigo-50 via-white to-purple-50  border-gray-200" style="margin-top: 10px;">
            <!-- Header -->
            <div class="flex justify-between items-center cursor-pointer" onclick="toggleCollapsible('createRoomSection')">
                <div class="flex items-center">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md">
                        <i class="fas fa-door-open text-sm"></i>
                    </div>
                    <h2 class="ml-3 text-sm font-semibold text-gray-800">Create New Room</h2>
                </div>
                <button class="h-8 w-8 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-sm hover:from-indigo-600 hover:to-purple-700 transition-all transform hover:scale-105">
                    <i class="fas fa-plus text-xs"></i>
                </button>
            </div>

            <!-- Collapsible Content -->
            <div id="createRoomSection" class="hidden mt-4 border-t border-gray-200 pt-4 collapsible-content">
                <form action="process/create_room_process.php" method="post" class="space-y-4">

                    <!-- Room Name -->
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-1.5" for="room_name">
                            Room Name*
                        </label>
                        <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                            id="room_name" name="room_name" type="text" placeholder="Enter room name" required>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-1.5" for="room_description">
                            Description (Optional)
                        </label>
                        <textarea class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                            id="room_description" name="room_description" placeholder="Enter room description" rows="2"></textarea>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-1.5" for="room_address">
                            Address*
                        </label>
                        <textarea class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                            id="room_address" name="room_address" placeholder="Enter room address" rows="2" required></textarea>
                    </div>

                    <!-- Latitude / Longitude -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-700 text-xs font-bold mb-1.5" for="latitude">Latitude</label>
                            <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                                id="latitude" name="latitude" type="text" placeholder="Latitude">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-xs font-bold mb-1.5" for="longitude">Longitude</label>
                            <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                                id="longitude" name="longitude" type="text" placeholder="Longitude">
                        </div>
                    </div>

                    <button type="button" onclick="getCurrentLocation()" class="text-indigo-600 text-xs font-medium flex items-center">
                        <i class="fas fa-map-marker-alt mr-2 text-sm"></i> Get Current Location
                    </button>

                    <!-- Rental Information -->
                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <h4 class="font-medium mb-3 text-gray-800 text-sm flex items-center">
                            <i class="fas fa-file-invoice-dollar mr-2 text-indigo-500"></i> Rental Information
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1.5" for="rental_date">Rental Date</label>
                                <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    id="rental_date" name="rental_date" type="date">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1.5" for="monthly_rent">Monthly Rent (₹)</label>
                                <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                                    id="monthly_rent" name="monthly_rent" type="number" step="0.01" min="0" placeholder="Enter monthly rent">
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-xs font-bold mb-1.5" for="advance_payment">Advance Payment (₹)</label>
                            <input class="shadow-sm border border-gray-200 rounded-lg w-full text-gray-700 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-transparent transition-all"
                                id="advance_payment" name="advance_payment" type="number" step="0.01" min="0" placeholder="Enter advance payment">
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <button type="submit" name="create_room" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold text-sm rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:ring-offset-1 transition-all shadow-md">
                            <i class="fas fa-check-circle mr-2"></i> Create Room
                        </button>
                    </div>
                </form>
            </div>
        </div>




        <div class="max-w-sm mx-auto mt-6">
            <div class="relative bg-gradient-to-br from-indigo-50 via-white rounded-3xl  overflow-hidden hover:scale-105 transform transition duration-300 cursor-pointer">

                
                <!-- Floating YouTube Icon -->
                <div class="absolute top-4 right-4 w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a2.996 2.996 0 00-2.115-2.122C19.418 3.5 12 3.5 12 3.5s-7.418 0-9.383.564a2.996 2.996 0 00-2.115 2.122A31.222 31.222 0 000 12a31.222 31.222 0 00.502 5.814 2.996 2.996 0 002.115 2.122C4.582 20.5 12 20.5 12 20.5s7.418 0 9.383-.564a2.996 2.996 0 002.115-2.122A31.222 31.222 0 0024 12a31.222 31.222 0 00-.502-5.814zM9.75 15.02V8.98l6.5 3.02-6.5 3.02z"/>
                </svg>
                </div>

                <!-- Content -->
                <div class="p-6 relative z-10">
                <h3 class="text-xl font-bold text-gray-800 mb-2">How to Use the App</h3>
                <p class="text-gray-600 text-sm mb-4">
                    Step-by-step video tutorials to help you get started and use all features efficiently.
                </p>
                <a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" target="_blank" 
                    class="inline-block bg-red-600 text-white px-5 py-2 rounded-full font-semibold hover:bg-red-700 transition">
                    Watch Now
                </a>
                </div>

            </div>
        </div>




<div class="max-w-md mx-auto mt-6 bg-gradient-to-br from-indigo-50 via-white rounded-2xl p-5 space-y-4">
    <h2 class="text-lg font-semibold text-gray-800 mb-3">Help Center</h2>

    <!-- Phone -->
    <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3 shadow-sm">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center 
                bg-gradient-to-r from-green-400 to-teal-500 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h2l3 7-3 7H3l3-7-3-7z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Phone Support</p>
                <p class="text-sm font-bold text-gray-800">+91 63705 00903</p>
            </div>
        </div>
        <a href="tel:+916370500903" class="text-xs font-semibold text-white bg-green-500 px-3 py-1 rounded-full hover:bg-green-600 transition">
            Call
        </a>
    </div>

    <!-- Email -->
    <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3 shadow-sm">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center 
                bg-gradient-to-r from-blue-400 to-indigo-500 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m0 0l4-4m0 0l4 4M12 16v-8" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Email Support</p>
                <p class="text-sm font-bold text-gray-800">help@example.com</p>
            </div>
        </div>
        <a href="mailto:help@example.com" class="text-xs font-semibold text-white bg-blue-500 px-3 py-1 rounded-full hover:bg-blue-600 transition">
            Mail
        </a>
    </div>

    <!-- WhatsApp -->
    <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3 shadow-sm">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center 
                bg-gradient-to-r from-green-500 to-green-700 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.47-.148-.668.15-.198.297-.767.967-.94 1.165-.173.198-.347.223-.644.075-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.173.198-.297.297-.495.099-.198.05-.372-.025-.52-.075-.149-.668-1.611-.915-2.21-.242-.579-.487-.5-.668-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.1 3.2 5.077 4.487.709.306 1.262.489 1.693.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.29.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">WhatsApp Support</p>
                <p class="text-sm font-bold text-gray-800">+91 63705 00903</p>
            </div>
        </div>
        <a href="https://wa.me/916370500903" target="_blank" class="text-xs font-semibold text-white bg-green-600 px-3 py-1 rounded-full hover:bg-green-700 transition">
            Chat
        </a>
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
            
            <!-- Add Expense Button (Floating Style) -->
            <button onclick="openAddExpenseModal()" class="flex flex-col items-center justify-center w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-xl transform -translate-y-4 hover:scale-105 transition-all z-20" style="width: 150px; border-radius: 10px;">
                <i class="fas fa-plus-circle text-2xl mb-1"></i>
                <span class="text-[10px] font-bold">Add Expense</span>
            </button>
            
            <!-- Donate -->
            <a href="#" class="flex flex-col items-center justify-center flex-1 text-gray-600 h-full">
                <i class="fas fa-donate text-lg mb-1"></i>
                <span class="text-xs font-medium">Donate</span>
            </a>
        </div>
    </div>



    <!-- Group Selector Modal -->
    <div id="groupSelectorModal" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
        <div class="modal-container bg-white rounded-xl p-4 w-full max-w-md mx-3 max-h-[70vh] overflow-y-auto shadow-xl">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-base font-bold text-gray-800">
                    <i class="fas fa-chart-bar text-indigo-600 mr-1.5 text-sm"></i>Select Group for Report
                </h3>
                <button onclick="toggleModal('groupSelectorModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <p class="text-[12px] text-gray-600 mb-4">Choose a group to view settlement reports:</p>

            <div class="space-y-2.5" id="groupsList">
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
                        <div class="group-option border border-gray-200 rounded-lg p-3 hover:border-indigo-300 hover:bg-gradient-to-r from-indigo-50 to-blue-50 cursor-pointer transition-all shadow-xs"
                            onclick="selectGroup(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>')">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($group['name']); ?></div>
                                    <div class="text-[12px] text-gray-500 mt-1"><?php echo htmlspecialchars($group['room_name']); ?></div>
                                    <div class="text-[9px] text-gray-400 mt-1.5 flex items-center">
                                        <span class="mr-2"><i class="fas fa-users mr-1 text-[8px]"></i> <?php echo $group['member_count']; ?> members</span>
                                        <span><i class="fas fa-file-invoice mr-1 text-[8px]"></i> <?php echo $group['settlement_count']; ?> settlements</span>
                                    </div>
                                </div>
                                <div class="text-indigo-600 ml-2">
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </div>
                            </div>
                        </div>
                    <?php
                    endwhile;
                else:
                    ?>
                    <div class="text-center py-6">
                        <i class="fas fa-users text-3xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500 font-medium text-sm">No groups found</p>
                        <p class="text-[12px] text-gray-400 mt-1">Join a group to view settlement reports</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($groups_result->num_rows > 0): ?>
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <p class="text-[9px] text-gray-500 text-center">
                        <i class="fas fa-info-circle mr-1 text-[8px]"></i>
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
                chevron.className = 'fas fa-spinner fa-spin text-xs';
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
            threshold: 100, // Increased threshold to prevent accidental refresh
            maxPull: 150,
            minMovement: 10, // Minimum movement to start pull detection

            init() {
                this.bindEvents();
            },

            bindEvents() {
                // Touch events for mobile
                document.addEventListener('touchstart', this.handleTouchStart.bind(this), {
                    passive: false
                });
                document.addEventListener('touchmove', this.handleTouchMove.bind(this), {
                    passive: false
                });
                document.addEventListener('touchend', this.handleTouchEnd.bind(this), {
                    passive: false
                });

                // Mouse events for desktop (for testing)
                document.addEventListener('mousedown', this.handleMouseDown.bind(this));
                document.addEventListener('mousemove', this.handleMouseMove.bind(this));
                document.addEventListener('mouseup', this.handleMouseUp.bind(this));
            },

            handleTouchStart(e) {
                // Only start pull detection at the top of the page
                if (window.scrollY === 0 && !this.isRefreshing) {
                    this.startY = e.touches[0].clientY;
                    this.isPulling = false; // Don't set to true yet
                }
            },

            handleTouchMove(e) {
                if (window.scrollY > 0 || this.isRefreshing) return;

                this.currentY = e.touches[0].clientY;
                const pullDistance = this.currentY - this.startY;

                // Only start pulling if moved beyond minimum threshold
                if (!this.isPulling && pullDistance > this.minMovement) {
                    this.isPulling = true;
                }

                if (this.isPulling && pullDistance > 0) {
                    e.preventDefault();
                    this.updatePullState(Math.min(pullDistance, this.maxPull));
                }
            },

            handleTouchEnd(e) {
                if (!this.isPulling || this.isRefreshing) return;

                const pullDistance = this.currentY - this.startY;
                this.isPulling = false;

                // Only refresh if pulled beyond threshold
                if (pullDistance > this.threshold) {
                    this.triggerRefresh();
                } else {
                    this.resetPullState();
                }
            },

            handleMouseDown(e) {
                // Only start pull detection at the top of the page
                if (window.scrollY === 0 && !this.isRefreshing && e.clientY < 50) {
                    this.startY = e.clientY;
                    this.isPulling = false; // Don't set to true yet
                    e.preventDefault();
                }
            },

            handleMouseMove(e) {
                if (window.scrollY > 0 || this.isRefreshing) return;

                this.currentY = e.clientY;
                const pullDistance = this.currentY - this.startY;

                // Only start pulling if moved beyond minimum threshold
                if (!this.isPulling && pullDistance > this.minMovement) {
                    this.isPulling = true;
                }

                if (this.isPulling && pullDistance > 0) {
                    e.preventDefault();
                    this.updatePullState(Math.min(pullDistance, this.maxPull));
                }
            },

            handleMouseUp(e) {
                if (!this.isPulling || this.isRefreshing) return;

                const pullDistance = this.currentY - this.startY;
                this.isPulling = false;

                // Only refresh if pulled beyond threshold
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
                if (pullDistance > this.minMovement) {
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

                // Add haptic feedback on mobile when threshold is reached
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
</body>

</html>