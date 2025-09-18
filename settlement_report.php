<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

if ($group_id === 0) {
    header("Location: index.php");
    exit();
}

// Get group details
$stmt = $conn->prepare("SELECT rg.*, r.name as room_name,
                       (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id) as member_count
                       FROM room_groups rg
                       JOIN rooms r ON rg.room_id = r.id
                       WHERE rg.id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$group = $result->fetch_assoc();

// Check if user is member of this group
$stmt = $conn->prepare("SELECT COUNT(*) as is_member FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$is_member = $result->fetch_assoc()['is_member'] > 0;

if (!$is_member) {
    header("Location: index.php");
    exit();
}

// Get all settlements for this group, ordered by date (newest first)
$stmt = $conn->prepare("SELECT s.*, u.full_name as creator_name, u.profile_image as creator_image,
                       gm.message as settlement_message
                       FROM settlements s 
                       JOIN users u ON s.created_by = u.id 
                       LEFT JOIN group_messages gm ON s.message_id = gm.id
                       WHERE s.group_id = ? 
                       ORDER BY s.created_at DESC");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$settlements_result = $stmt->get_result();

$settlements = [];
while ($settlement = $settlements_result->fetch_assoc()) {
    // Parse settlement data to get totals
    $settlement_data = json_decode($settlement['settlement_data'], true);
    $settlement['total_food'] = $settlement_data['total_food'] ?? 0;
    $settlement['total_special'] = $settlement_data['total_special'] ?? 0;
    $settlement['total_amount'] = $settlement['total_food'] + $settlement['total_special'];
    $settlements[] = $settlement;
}

// Get user profile image
$profile_image = 'uploads/profile/default_avatar.png';
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if ($user['profile_image']) {
        $profile_image = 'uploads/profile/' . $user['profile_image'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Reports - <?php echo htmlspecialchars($group['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }
        .settlement-card {
            transition: all 0.2s ease;
        }
        .settlement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-10">
        <div class="flex justify-between items-center px-4 py-3">
            <div class="flex items-center">
                <a href="group.php?id=<?php echo $group_id; ?>" class="mr-3">
                    <i class="fas fa-arrow-left text-blue-600 text-xl"></i>
                </a>
                <div class="mr-3">
                    <i class="fas fa-file-invoice-dollar text-purple-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($group['name']); ?></h1>
                    <p class="text-xs text-gray-500">Settlement Reports</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                
                <a href="profile.php">
                    <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-8 w-8 rounded-full border-2 border-blue-500 object-cover">
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-20 pb-24 px-4 max-w-md mx-auto">
        <!-- Summary Card -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold">📋 Settlement History</h2>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    <?php echo count($settlements); ?> Reports
                </span>
            </div>
            
            <?php if (count($settlements) > 0): ?>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-500">Latest Settlement</p>
                        <p class="text-lg font-bold text-green-600">
                            <?php echo date('M d, Y', strtotime($settlements[0]['created_at'])); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Settlements</p>
                        <p class="text-lg font-bold text-purple-600"><?php echo count($settlements); ?></p>
                    </div>
                </div>
                
                
            <?php else: ?>
                <div class="text-center py-6">
                    <i class="fas fa-file-invoice text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-medium">No settlements created yet</p>
                    <p class="text-sm text-gray-400 mb-4">Create your first settlement to track expenses</p>
                    <a href="report.php?group_id=<?php echo $group_id; ?>" 
                       class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-1"></i>Create Settlement
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Settlements List -->
        <?php if (count($settlements) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($settlements as $index => $settlement): 
                    $creator_img = $settlement['creator_image'] ? 'uploads/profile/' . $settlement['creator_image'] : 'uploads/profile/default_avatar.png';
                    $days_span = (strtotime($settlement['to_date']) - strtotime($settlement['from_date'])) / (60 * 60 * 24) + 1;
                ?>
                    <div class="settlement-card bg-white rounded-xl shadow-md p-4 animate-fade-in" 
                         style="animation-delay: <?php echo $index * 0.1; ?>s">
                        
                        <!-- Settlement Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <img src="<?php echo $creator_img; ?>" alt="Creator" 
                                     class="w-10 h-10 rounded-full object-cover border-2 border-purple-200">
                                <div>
                                    <div class="font-semibold text-gray-800">Settlement #<?php echo $settlement['id']; ?></div>
                                    <div class="text-xs text-gray-500">
                                        Created by <?php echo htmlspecialchars($settlement['creator_name']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-purple-600">
                                    ₹<?php echo number_format($settlement['total_amount'], 2); ?>
                                </div>
                                <div class="text-xs text-gray-500">Total</div>
                            </div>
                        </div>

                        <!-- Settlement Details -->
                        <div class="bg-gray-50 rounded-lg p-3 mb-3">
                            <div class="grid gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500">Period:</span>
                                    <div class="font-medium">
                                        <?php echo date('M d', strtotime($settlement['from_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($settlement['to_date'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-400"><?php echo $days_span; ?> days</div>
                                </div>
                                <div>
                                    <span class="text-gray-500">Created:</span>
                                    <div class="font-medium"><?php echo date('M d, Y', strtotime($settlement['created_at'])); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($settlement['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Expense Breakdown -->
                        <div class="flex justify-between items-center mb-3">
                            <div class="flex space-x-4 text-sm">
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                    <span class="text-gray-600">Food:</span>
                                    <span class="font-semibold ml-1">₹<?php echo number_format($settlement['total_food'], 2); ?></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                                    <span class="text-gray-600">Special:</span>
                                    <span class="font-semibold ml-1">₹<?php echo number_format($settlement['total_special'], 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-2">
                            <a href="report.php?group_id=<?php echo $group_id; ?>&settlement_id=<?php echo $settlement['id']; ?>" 
                               class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium text-center hover:from-blue-600 hover:to-purple-700 transition-all">
                                <i class="fas fa-eye mr-1"></i>View Report
                            </a>
                            <?php if ($settlement['message_id']): ?>
                                <a href="group.php?id=<?php echo $group_id; ?>&highlight=<?php echo $settlement['message_id']; ?>" 
                                   class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-comment" title="View in Chat"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Load More Button (if needed for pagination in future) -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-500">
                    <i class="fas fa-check-circle mr-1 text-green-500"></i>
                    All settlements loaded
                </p>
            </div>
        <?php endif; ?>

        <!-- Footer Info -->
        <div class="text-center text-xs text-gray-500 bg-white rounded-lg shadow-sm p-3 mt-4">
            <i class="fas fa-info-circle mr-1"></i>
            Settlement reports are automatically saved and can be viewed anytime
        </div>
    </main>

    <script>
        // Add some interactive behavior
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animation to settlement cards
            const cards = document.querySelectorAll('.settlement-card');
            cards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Only trigger if not clicking a link
                    if (!e.target.closest('a')) {
                        const viewButton = card.querySelector('a[href*="report.php"]');
                        if (viewButton) {
                            viewButton.click();
                        }
                    }
                });
            });

            // Add loading animation for external links
            const externalLinks = document.querySelectorAll('a[href*="group.php"], a[href*="report.php"]');
            externalLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    if (icon && !icon.classList.contains('fa-arrow-left')) {
                        icon.className = 'fas fa-spinner fa-spin';
                    }
                });
            });
        });
    </script>
</body>
</html>
