<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if room exists and user is a member
$stmt = $conn->prepare("SELECT r.*, DATE_FORMAT(r.created_at, '%b %d, %Y') as formatted_date, 
                        (SELECT COUNT(*) FROM room_members WHERE room_id = r.id) as member_count,
                        rm.is_admin 
                        FROM rooms r 
                        JOIN room_members rm ON r.id = rm.room_id AND rm.user_id = ? 
                        WHERE r.id = ?");
if ($stmt === false) {
    die("Prepare failed for room check: " . $conn->error);
}
$stmt->bind_param("ii", $user_id, $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Room not found or you are not a member");
    exit();
}

$room = $result->fetch_assoc();
$is_admin = $room['is_admin'];

// Get room members
$stmt = $conn->prepare("SELECT rm.*, u.full_name, u.phone, u.email, u.profile_image 
                        FROM room_members rm 
                        JOIN users u ON rm.user_id = u.id 
                        WHERE rm.room_id = ?
                        ORDER BY rm.is_admin DESC, u.full_name ASC");
if ($stmt === false) {
    die("Prepare failed for room members: " . $conn->error);
}
$stmt->bind_param("i", $room_id);
$stmt->execute();
$members_result = $stmt->get_result();

// Get room groups
$stmt = $conn->prepare("SELECT rg.*, 
                       (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id) as member_count,
                       (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id AND user_id = ?) as is_member
                       FROM room_groups rg 
                       WHERE rg.room_id = ?
                       ORDER BY rg.created_at ASC");
if ($stmt === false) {
    die("Prepare failed for room groups: " . $conn->error);
}
$stmt->bind_param("ii", $user_id, $room_id);
$stmt->execute();
$groups_result = $stmt->get_result();

// Process success/error messages
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($room['name']); ?> - Bachelor Room</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <script src="js/back-navigation.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
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
    
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-10">
        <div class="flex justify-between items-center px-4 py-3">
            <div class="flex items-center">
                <button onclick="goBack()" class="mr-3 back-button" title="Go Back">
                    <i class="fas fa-arrow-left text-blue-600 text-xl hover:text-blue-800 transition-colors"></i>
                </button>
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
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-16 pb-24 px-4 max-w-md mx-auto">
        <?php if ($success_msg): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
            <p><?php echo htmlspecialchars($success_msg); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
            <p><?php echo htmlspecialchars($error_msg); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Room Overview -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3"><?php echo htmlspecialchars($room['name']); ?></h2>
            
            <p class="text-sm text-gray-600 mb-3"><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
            
            <div class="text-sm text-gray-600 mb-3">
                <i class="fas fa-map-marker-alt text-red-500 mr-1"></i> 
                <?php echo nl2br(htmlspecialchars($room['address'])); ?>
            </div>
            
            <!-- Rental Information -->
            <?php if (!empty($room['monthly_rent']) || !empty($room['rental_date']) || !empty($room['advance_payment'])): ?>
            <div class="bg-yellow-50 rounded-lg p-3 mb-3">
                <h3 class="text-sm font-medium text-yellow-800 mb-2">Rental Information</h3>
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <?php if (!empty($room['rental_date'])): ?>
                    <div>
                        <p class="text-xs text-gray-600">Rental Date</p>
                        <p class="font-medium"><?php echo date('M d, Y', strtotime($room['rental_date'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($room['monthly_rent'])): ?>
                    <div>
                        <p class="text-xs text-gray-600">Monthly Rent</p>
                        <p class="font-medium">₹<?php echo number_format($room['monthly_rent'], 2); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($room['advance_payment'])): ?>
                    <div>
                        <p class="text-xs text-gray-600">Advance</p>
                        <p class="font-medium">₹<?php echo number_format($room['advance_payment'], 2); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div class="bg-blue-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-blue-800">Created</p>
                    <p class="text-sm font-medium text-blue-600"><?php echo $room['formatted_date']; ?></p>
                </div>
                <div class="bg-green-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-green-800">Members</p>
                    <p class="text-sm font-medium text-green-600"><?php echo $room['member_count']; ?></p>
                </div>
                <div class="bg-purple-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-purple-800">Last Activity</p>
                    <p class="text-sm font-medium text-purple-600">Today</p>
                </div>
            </div>
        </div>

        <!-- Members List -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-semibold">Members</h2>
                <?php if ($is_admin): ?>
                <button onclick="toggleModal('addMemberModal')" class="h-8 w-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md hover:bg-blue-700 text-sm">
                    <i class="fas fa-user-plus"></i>
                </button>
                <?php endif; ?>
            </div>
            <ul class="space-y-3">
                <?php while ($member = $members_result->fetch_assoc()): ?>
                <li class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-9 w-9 rounded-full bg-blue-100 overflow-hidden flex items-center justify-center mr-3">
                            <?php if (!empty($member['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($member['profile_image']); ?>" alt="Profile" class="h-full w-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-blue-600"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($member['full_name']); ?> 
                                <?php if ($member['is_admin']): ?><span class="text-xs text-gray-500">(Admin)</span><?php endif; ?>
                            </span>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($member['phone']); ?></p>
                            <p class="text-xs <?php 
                                echo $member['invitation_status'] == 'accepted' ? 'text-green-600' : 
                                     ($member['invitation_status'] == 'pending' ? 'text-yellow-600' : 'text-red-600'); 
                            ?>">
                                Status: <?php echo ucfirst($member['invitation_status']); ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($member['is_admin']): ?>
                    <i class="fas fa-crown text-yellow-500"></i>
                    <?php elseif ($is_admin): ?>
                    <button class="text-gray-400 hover:text-red-500" onclick="confirmRemoveMember(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php endif; ?>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Groups Section -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-semibold">Groups</h2>
                <?php if ($is_admin): ?>
                <button onclick="toggleModal('createGroupModal')" class="h-8 w-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md hover:bg-blue-700 text-sm">
                    <i class="fas fa-plus"></i>
                </button>
                <?php endif; ?>
            </div>
            
            <?php if ($groups_result->num_rows > 0): ?>
            <ul class="space-y-3">
                <?php while ($group = $groups_result->fetch_assoc()): ?>
                <li class="flex items-center justify-between p-3 border rounded-lg <?php echo $group['is_member'] ? 'border-blue-200 bg-blue-50' : 'border-gray-200'; ?>">
                    <div>
                        <div class="font-medium"><?php echo htmlspecialchars($group['name']); ?></div>
                        <p class="text-xs text-gray-500"><?php echo $group['member_count']; ?> members</p>
                    </div>
                    <div class="flex items-center">
                        <?php if ($group['is_member']): ?>
                        <a href="group-details.php?id=<?php echo $group['id']; ?>" class="text-blue-600 text-sm mr-2">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <?php elseif ($is_admin): ?>
                        <button onclick="joinGroup(<?php echo $group['id']; ?>)" class="text-green-600 text-sm mr-2">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($is_admin): ?>
                        <button onclick="manageGroupMembers(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>')" class="text-gray-600 text-sm">
                            <i class="fas fa-cog"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endwhile; ?>
            </ul>
            <?php else: ?>
            <p class="text-center text-gray-500 py-4">No groups created yet.</p>
            <?php endif; ?>
        </div>

        <!-- Leave Room -->
        <div class="bg-white rounded-xl shadow-md p-4 text-center">
            <button onclick="confirmLeaveRoom()" class="text-red-600 font-medium">Leave Room</button>
        </div>
    </main>

    <!-- Add Member Modal -->
    <?php if ($is_admin): ?>
    <div id="addMemberModal" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-5 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">Add New Member</h3>
            <form action="process/invite_member_process.php" method="post">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                        Phone Number
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           id="phone" name="phone" type="text" placeholder="Enter phone number">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleModal('addMemberModal')" class="px-4 py-2 text-gray-600 font-medium">
                        Cancel
                    </button>
                    <button type="submit" name="invite_member" class="px-4 py-2 bg-blue-600 text-white font-medium rounded hover:bg-blue-700">
                        Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div id="createGroupModal" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-5 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">Create New Group</h3>
            <form action="process/create_group_process.php" method="post">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                
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
                    <button type="button" onclick="toggleModal('createGroupModal')" class="px-4 py-2 text-gray-600 font-medium">
                        Cancel
                    </button>
                    <button type="submit" name="create_group" class="px-4 py-2 bg-blue-600 text-white font-medium rounded hover:bg-blue-700">
                        Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
            } else {
                modal.classList.add('hidden');
            }
        }
        
        function confirmRemoveMember(userId, userName) {
            if (confirm(`Are you sure you want to remove ${userName} from this room?`)) {
                window.location.href = `process/remove_member_process.php?room_id=<?php echo $room_id; ?>&user_id=${userId}`;
            }
        }
        
        function confirmLeaveRoom() {
            if (confirm('Are you sure you want to leave this room? This action cannot be undone.')) {
                window.location.href = `process/leave_room_process.php?room_id=<?php echo $room_id; ?>`;
            }
        }
        
        function joinGroup(groupId) {
            window.location.href = `process/join_group_process.php?group_id=${groupId}&room_id=<?php echo $room_id; ?>`;
        }
        
        function manageGroupMembers(groupId, groupName) {
            window.location.href = `manage-group-members.php?group_id=${groupId}&room_id=<?php echo $room_id; ?>`;
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
</body>
</html>