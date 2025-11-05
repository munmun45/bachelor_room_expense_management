<?php
// Get all groups with room and creator info
$stmt = $admin_conn->prepare("SELECT rg.*, r.name as room_name, u.full_name as creator_name FROM room_groups rg JOIN rooms r ON rg.room_id = r.id JOIN users u ON rg.created_by = u.id ORDER BY rg.created_at DESC");
$stmt->execute();
$groups_result = $stmt->get_result();
?>

<div class="space-y-6">
    <!-- Page Title -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Group Management</h1>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            <i class="fas fa-plus mr-2"></i>Add Group
        </button>
    </div>
    
    <!-- Groups Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creator</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Messages</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($group = $groups_result->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($group['name']); ?></div>
                            <div class="text-sm text-gray-500">ID: <?php echo $group['id']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($group['room_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($group['creator_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            // Get member count
                            $member_stmt = $admin_conn->prepare("SELECT COUNT(*) as count FROM group_members WHERE group_id = ?");
                            $member_stmt->bind_param("i", $group['id']);
                            $member_stmt->execute();
                            $member_count = $member_stmt->get_result()->fetch_assoc()['count'];
                            ?>
                            <span class="text-sm text-gray-900"><?php echo $member_count; ?> members</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            // Get message count
                            $message_stmt = $admin_conn->prepare("SELECT COUNT(*) as count FROM group_messages WHERE group_id = ?");
                            $message_stmt->bind_param("i", $group['id']);
                            $message_stmt->execute();
                            $message_count = $message_stmt->get_result()->fetch_assoc()['count'];
                            ?>
                            <span class="text-sm text-gray-900"><?php echo $message_count; ?> messages</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('M d, Y', strtotime($group['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="#" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            <a href="#" class="text-red-600 hover:text-red-900">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>