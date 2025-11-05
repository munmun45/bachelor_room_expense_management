<?php
// Get all rooms with creator info
$stmt = $admin_conn->prepare("SELECT r.*, u.full_name as creator_name FROM rooms r JOIN users u ON r.created_by = u.id ORDER BY r.created_at DESC");
$stmt->execute();
$rooms_result = $stmt->get_result();
?>

<div class="space-y-6">
    <!-- Page Title -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Room Management</h1>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            <i class="fas fa-plus mr-2"></i>Add Room
        </button>
    </div>
    
    <!-- Rooms Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creator</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($room = $rooms_result->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($room['name']); ?></div>
                            <div class="text-sm text-gray-500">ID: <?php echo $room['id']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars(substr($room['description'], 0, 50)) . (strlen($room['description']) > 50 ? '...' : ''); ?></div>
                            <div class="text-sm text-gray-500">
                                <?php if (!empty($room['monthly_rent'])): ?>
                                ₹<?php echo number_format($room['monthly_rent'], 2); ?>/month
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($room['creator_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            // Get member count
                            $member_stmt = $admin_conn->prepare("SELECT COUNT(*) as count FROM room_members WHERE room_id = ?");
                            $member_stmt->bind_param("i", $room['id']);
                            $member_stmt->execute();
                            $member_count = $member_stmt->get_result()->fetch_assoc()['count'];
                            ?>
                            <span class="text-sm text-gray-900"><?php echo $member_count; ?> members</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('M d, Y', strtotime($room['created_at'])); ?>
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