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

// Get user profile image
$profile_image = 'uploads/profile/default_avatar.png'; // Default image

$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (!empty($user['profile_image'])) {
        $profile_image = 'uploads/profile/' . $user['profile_image'];
    }
}

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

// Get room groups with leader info
$stmt = $conn->prepare("SELECT rg.*, 
                       (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id) as member_count,
                       (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id AND user_id = ?) as is_member,
                       (SELECT gm.is_leader FROM group_members gm WHERE gm.group_id = rg.id AND gm.user_id = ?) as is_leader,
                       (SELECT u.full_name FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = rg.id AND gm.is_leader = 1) as leader_name
                       FROM room_groups rg 
                       WHERE rg.room_id = ?
                       ORDER BY rg.created_at ASC");
if ($stmt === false) {
    die("Prepare failed for room groups: " . $conn->error);
}
$stmt->bind_param("iii", $user_id, $user_id, $room_id);
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
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <script src="js/back-navigation.js"></script>

</head>

<body class="bg-gray-100">


    <!-- Header -->
    <header class="app-header fixed top-0 left-0 right-0 z-10 py-2.5 px-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2.5">
                <button onclick="goBack()" class="mr-3 back-button" title="Go Back" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left text-white text-xl"></i>
                </button>

                <div>
                    <h1 class="text-base font-bold text-white tracking-tight">Room Manager</h1>
                    <p class="text-[12px] text-white text-opacity-80 -mt-0.5">Manage Your Room</p>
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
    <main class="pt-20 pb-24 px-4 max-w-md mx-auto">
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
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200">
            <!-- Room Name -->
            <h2 class="text-base font-semibold text-gray-800 mb-2">
                <?php echo htmlspecialchars($room['name']); ?>
            </h2>

            <!-- Description -->
            <p class="text-[12px] text-gray-600 mb-3 leading-snug">
                <?php echo nl2br(htmlspecialchars($room['description'])); ?>
            </p>

            <!-- Address -->
            <div class="flex items-start text-[12px] text-gray-600 mb-3">
                <i class="fas fa-map-marker-alt text-red-500 mt-0.5 mr-1"></i>
                <span><?php echo nl2br(htmlspecialchars($room['address'])); ?></span>
            </div>

            <!-- Rental Information -->
            <?php if (!empty($room['monthly_rent']) || !empty($room['rental_date']) || !empty($room['advance_payment'])): ?>
                <div class="rounded-lg bg-blue-50 p-3 mb-3 border border-blue-100">
                    <h3 class="text-[12px] font-semibold text-blue-800 mb-2">Rental Information</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <?php if (!empty($room['rental_date'])): ?>
                            <div class="bg-white rounded p-2 text-center shadow-sm">
                                <p class="text-[11px] text-gray-500">Rental Date</p>
                                <p class="text-[12px] font-medium text-gray-800">
                                    <?php echo date('M d, Y', strtotime($room['rental_date'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($room['monthly_rent'])): ?>
                            <div class="bg-white rounded p-2 text-center shadow-sm">
                                <p class="text-[11px] text-gray-500">Monthly Rent</p>
                                <p class="text-[12px] font-medium text-gray-800">
                                    ₹<?php echo number_format($room['monthly_rent'], 2); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($room['advance_payment'])): ?>
                            <div class="bg-white rounded p-2 text-center shadow-sm">
                                <p class="text-[11px] text-gray-500">Advance</p>
                                <p class="text-[12px] font-medium text-gray-800">
                                    ₹<?php echo number_format($room['advance_payment'], 2); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-2">
                <div class="rounded-lg bg-green-50 p-2 text-center border border-green-100">
                    <p class="text-[11px] text-green-700">Created</p>
                    <p class="text-[12px] font-semibold text-green-800"><?php echo $room['formatted_date']; ?></p>
                </div>

                <div class="rounded-lg bg-yellow-50 p-2 text-center border border-yellow-100">
                    <p class="text-[11px] text-yellow-700">Members</p>
                    <p class="text-[12px] font-semibold text-yellow-800"><?php echo $room['member_count']; ?></p>
                </div>

                <div class="rounded-lg bg-purple-50 p-2 text-center border border-purple-100">
                    <p class="text-[11px] text-purple-700">Last Activity</p>
                    <p class="text-[12px] font-semibold text-purple-800">Today</p>
                </div>
            </div>
        </div>


        <!-- Members List -->
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200">
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
                                    <img src="uploads/profile/<?php echo htmlspecialchars($member['profile_image']); ?>" alt="Profile" class="h-full w-full object-cover">
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
                                                    echo $member['invitation_status'] == 'accepted' ? 'text-green-600' : ($member['invitation_status'] == 'pending' ? 'text-yellow-600' : 'text-red-600');
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
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200">
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
                        <li class="flex items-center justify-between p-3  rounded-lg <?php echo $group['is_member'] ? 'border-blue-200 bg-blue-50' : 'border-gray-200'; ?>">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <div class="font-medium"><?php echo htmlspecialchars($group['name']); ?></div>
                                    <?php if ($group['is_leader'] == 1): ?>
                                        <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full">
                                            <i class="fas fa-crown mr-1"></i>Leader
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-gray-500">
                                    <?php echo $group['member_count']; ?> members
                                    <?php if ($group['leader_name']): ?>
                                        • Leader: <?php echo htmlspecialchars($group['leader_name']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="flex items-center space-x-1">
                                <?php if ($group['is_member']): ?>
                                    <a href="group.php?id=<?php echo $group['id']; ?>" class="text-blue-600 text-sm p-2 hover:bg-blue-100 rounded-full" title="Open Group">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php elseif ($is_admin): ?>
                                    <button onclick="joinGroup(<?php echo $group['id']; ?>)" class="text-green-600 text-sm p-2 hover:bg-green-100 rounded-full" title="Join Group">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($is_admin): ?>
                                    <?php if ($group['is_leader'] == 1): ?>
                                        <!-- Admin is leader - can delete directly -->
                                        <button onclick="deleteGroupDirectly(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>')"
                                            class="text-red-600 text-sm p-2 hover:bg-red-100 rounded-full" title="Delete Group">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Admin is not leader - request deletion -->
                                        <button onclick="requestGroupDeletion(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars($group['name']); ?>', '<?php echo htmlspecialchars($group['leader_name']); ?>')"
                                            class="text-orange-600 text-sm p-2 hover:bg-orange-100 rounded-full" title="Request Deletion">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
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

        function deleteGroupDirectly(groupId, groupName) {
            if (confirm(`🗑️ **Delete Group Permanently**

Are you sure you want to delete the group "${groupName}"?

⚠️ **This action cannot be undone!**

This will:
• Delete all group messages
• Delete all settlements
• Remove all members from the group
• Delete the group permanently`)) {
                // Show loading state
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('process/delete_group_process.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `group_id=${groupId}&action=delete_direct&room_id=<?php echo $room_id; ?>`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Group deleted successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                            button.disabled = false;
                            button.innerHTML = originalHTML;
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting group:', error);
                        alert('Failed to delete group. Please try again.');
                        button.disabled = false;
                        button.innerHTML = originalHTML;
                    });
            }
        }

        function requestGroupDeletion(groupId, groupName, leaderName) {
            if (confirm(`📝 **Request Group Deletion**

Are you sure you want to request deletion of the group "${groupName}"?

This will send a deletion request to the group leader: **${leaderName}**

The leader will need to approve this request before the group is deleted.`)) {
                // Show loading state
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('process/delete_group_process.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `group_id=${groupId}&action=request_deletion&room_id=<?php echo $room_id; ?>&group_name=${encodeURIComponent(groupName)}&leader_name=${encodeURIComponent(leaderName)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Deletion request sent to group leader successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                            button.disabled = false;
                            button.innerHTML = originalHTML;
                        }
                    })
                    .catch(error => {
                        console.error('Error requesting deletion:', error);
                        alert('Failed to send deletion request. Please try again.');
                        button.disabled = false;
                        button.innerHTML = originalHTML;
                    });
            }
        }
    </script>
</body>

</html>