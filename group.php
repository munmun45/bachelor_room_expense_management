<?php
session_start();
require_once 'config/config.php';

// Function to render basic markdown formatting
function renderMarkdown($text) {
    // Convert **text** to <strong>text</strong>
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    
    // Convert *text* to <em>text</em>
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
    
    return $text;
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if group exists and user is a member
$stmt = $conn->prepare("SELECT rg.*, r.id as room_id, r.name as room_name,
                        (SELECT COUNT(*) FROM group_members WHERE group_id = rg.id) as member_count,
                        (SELECT is_admin FROM room_members WHERE room_id = r.id AND user_id = ?) as is_admin,
                        (SELECT is_leader FROM group_members WHERE group_id = rg.id AND user_id = ?) as is_leader
                        FROM room_groups rg 
                        JOIN rooms r ON rg.room_id = r.id
                        JOIN group_members gm ON rg.id = gm.group_id AND gm.user_id = ?
                        WHERE rg.id = ?");
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Group not found or you are not a member");
    exit();
}

$group = $result->fetch_assoc();
$is_admin = $group['is_admin'] == 1;
$is_leader = $group['is_leader'] == 1;
$room_id = $group['room_id'];

// Get group members
$stmt = $conn->prepare("SELECT gm.*, u.id as user_id, u.full_name, u.phone, u.email, u.profile_image 
                        FROM group_members gm 
                        JOIN users u ON gm.user_id = u.id 
                        WHERE gm.group_id = ?
                        ORDER BY gm.is_leader DESC, u.full_name ASC");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = [];
while ($member = $members_result->fetch_assoc()) {
    $members[] = $member;
}

// Get room members who are not in the group (for admin to add)
$stmt = $conn->prepare("SELECT rm.*, u.id as user_id, u.full_name, u.phone, u.email, u.profile_image 
                        FROM room_members rm 
                        JOIN users u ON rm.user_id = u.id 
                        WHERE rm.room_id = ? AND rm.invitation_status = 'accepted' AND rm.user_id NOT IN 
                        (SELECT user_id FROM group_members WHERE group_id = ?)
                        ORDER BY u.full_name ASC");
$stmt->bind_param("ii", $room_id, $group_id);
$stmt->execute();
$non_members_result = $stmt->get_result();
$non_members = [];
while ($non_member = $non_members_result->fetch_assoc()) {
    $non_members[] = $non_member;
}

// Get recent group messages (only last 7 for initial load)
$stmt = $conn->prepare("SELECT gm.*, u.full_name, u.profile_image,
                        DATE_FORMAT(gm.created_at, '%b %d, %h:%i %p') as formatted_time,
                        s.id as settlement_id
                        FROM group_messages gm
                        JOIN users u ON gm.user_id = u.id
                        LEFT JOIN settlements s ON s.message_id = gm.id
                        WHERE gm.group_id = ?
                        ORDER BY gm.created_at DESC
                        LIMIT 7");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$messages_result = $stmt->get_result();

// Check for pending deletion requests (for group leaders)
$has_deletion_request = false;
if ($is_leader) {
    $stmt = $conn->prepare("SELECT COUNT(*) as request_count FROM group_messages 
                           WHERE group_id = ? 
                           AND message LIKE '%Group Deletion Request%' 
                           AND message NOT LIKE '%Group Deletion Request Rejected%'
                           AND message NOT LIKE '%Group Deletion Request Approved%'
                           AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_deletion_request = $result->fetch_assoc()['request_count'] > 0;
}

// Store messages in an array first
$temp_messages = [];
while ($message = $messages_result->fetch_assoc()) {
    $temp_messages[] = $message;
}

// Reverse the order to show chronologically (oldest to newest)
$initial_messages = array_reverse($temp_messages);

// Process success/error messages
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($group['name']); ?> - Bachelor Room</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <script src="js/back-navigation.js"></script>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }
        .message-container {
            max-height: calc(100vh - 190px);
            overflow-y: auto;
        }
        .recipient-selector {
            max-height: 200px;
            overflow-y: auto;
        }
        
        /* Settlement payment instructions styling */
        .settlement-message .message-content {
            max-width: 100% !important;
        }
        
        .payment-instruction-card {
            transition: all 0.2s ease;
        }
        
        .payment-instruction-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Member removal styling */
        .member-remove-btn {
            transition: all 0.2s ease;
        }
        
        .member-remove-btn:hover {
            transform: scale(1.1);
        }
        
        /* Member action buttons styling */
        .member-action-btn {
            transition: all 0.2s ease;
        }
        
        .member-action-btn:hover {
            transform: scale(1.1);
        }
        
        /* Leader badge styling */
        .leader-badge {
            background: linear-gradient(135deg, #8b5cf6, #a855f7);
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        /* Admin badge styling */
        .admin-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
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
                    <h1 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($group['name']); ?></h1>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($group['room_name']); ?> • <?php echo $group['member_count']; ?> members</p>
                </div>
            </div>
            <div class="flex items-center">
                
                <?php if ($is_admin || $is_leader): ?>
                <button onclick="toggleModal('settlementModal')" class="mr-4 text-green-600" title="Create Settlement">
                    <i class="fas fa-calculator"></i>
                </button>
                <?php endif; ?>
                
                <?php if ($is_leader && $has_deletion_request): ?>
                <button onclick="showDeletionRequests()" class="mr-4 text-red-600 relative" title="Deletion Requests">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full animate-pulse"></span>
                </button>
                <?php endif; ?>
                
                <button onclick="toggleModal('membersModal')" class="mr-4 text-gray-600">
                    <i class="fas fa-users"></i>
                </button>
                <div>
                    <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-8 w-8 rounded-full border-2 border-blue-500 object-cover">
                </div>
            </div>
        </div>
    </header>

    <!-- Success/Error Messages -->
    <?php if ($success_msg): ?>
    <div class="fixed top-16 left-0 right-0 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 z-10" role="alert">
        <p><?php echo htmlspecialchars($success_msg); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
    <div class="fixed top-16 left-0 right-0 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 z-10" role="alert">
        <p><?php echo htmlspecialchars($error_msg); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Debug Info (only show if debug=1 in URL) -->
    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
    <div class="fixed top-20 left-0 right-0 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 z-10" role="alert">
        <p><strong>DEBUG INFO:</strong></p>
        <p>User ID: <?php echo $user_id; ?> | Group ID: <?php echo $group_id; ?></p>
        <p>Is Admin: <?php echo $is_admin ? 'YES' : 'NO'; ?> | Is Leader: <?php echo $is_leader ? 'YES' : 'NO'; ?></p>
        <p>Can Manage: <?php echo ($is_admin || $is_leader) ? 'YES' : 'NO'; ?></p>
    </div>
    <?php endif; ?>

    <!-- Chat Content -->
    <main class="pt-20 pb-20">
        <!-- Load More Button (hidden initially) -->
        <div id="loadMoreContainer" class="text-center hidden">
            <button id="loadMoreBtn" class="text-blue-600 text-sm font-medium">
                <i class="fas fa-chevron-up mr-1"></i> more messages
            </button>
        </div>

        <!-- Messages -->
        <div id="messagesContainer" class="message-container space-y-2 px-4">
            <?php if (count($initial_messages) > 0): ?>
                <?php foreach ($initial_messages as $message): ?>
                    <?php 
                    $is_my_message = $message['user_id'] == $user_id;
                    $message_bg_color = $is_my_message ? 'bg-blue-500 text-white' : 'bg-white';
                    $message_alignment = $is_my_message ? 'justify-end' : 'justify-start';
                    $flex_direction = $is_my_message ? 'flex-row-reverse' : 'flex-row';
                    ?>
                    
                    <div class="flex <?php echo $message_alignment; ?> mb-4 <?php echo strpos($message['message'], '🧮 **Settlement Created**') !== false ? 'settlement-message' : ''; ?>">
                        <div class="flex <?php echo $flex_direction; ?> items-end space-x-2 <?php echo strpos($message['message'], '🧮 **Settlement Created**') !== false ? 'max-w-full' : 'max-w-xs lg:max-w-md'; ?>">
                            <!-- Profile Image -->
                        <img
                            src="<?php echo !empty($message['profile_image']) ? 'uploads/profile/' . $message['profile_image'] : 'uploads/profile/default_avatar.png'; ?>"
                                class="h-8 w-8 rounded-full object-cover flex-shrink-0 cursor-pointer hover:ring-2 hover:ring-blue-300 transition-all"
                                onclick="showUserProfile(<?php echo $message['user_id']; ?>, '<?php echo htmlspecialchars($message['full_name']); ?>', '<?php echo !empty($message['profile_image']) ? 'uploads/profile/' . $message['profile_image'] : 'uploads/profile/default_avatar.png'; ?>')"
                                title="View profile"
                            />
                            
                            <!-- Message Content -->
                            <div class="<?php echo $message_bg_color; ?> p-3 rounded-lg shadow-sm <?php echo $is_my_message ? 'rounded-br-none' : 'rounded-bl-none'; ?>">
                                <?php if (!$is_my_message): ?>
                                    <div class="text-xs text-gray-500 mb-1">
                                        <?php echo htmlspecialchars($message['full_name']); ?>
                                    </div>
                                <?php endif; ?>
                            
                            <?php if (!empty($message['message'])): ?>
                                    <p class="text-sm mb-1"><?php echo nl2br(renderMarkdown(htmlspecialchars($message['message']))); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($message['amount']) && $message['amount'] > 0): ?>
                                    <p class="font-semibold text-sm <?php echo $is_my_message ? 'text-blue-100' : 'text-blue-600'; ?>">
                                        <?php if (isset($message['is_special_item']) && $message['is_special_item']): ?>
                                            <i class="fas fa-star mr-1"></i>
                                        <?php endif; ?>
                                    <i class="fas fa-rupee-sign"></i> <?php echo number_format($message['amount'], 2); ?>
                                        <?php if (isset($message['is_special_item']) && $message['is_special_item']): ?>
                                            <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full ml-2">Special</span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (strpos($message['message'], '🧮 **Settlement Created**') !== false): ?>
                                    <?php 
                                    // Extract and display payment instructions with design
                                    $settlement_text = $message['message'];
                                    ?>
                                    <div class="mt-3 space-y-3">
                                        <!-- Parse and display payment instructions -->
                                        <?php
                                        // Extract food payment instructions
                                        if (preg_match('/💸 \*\*Food Payment Instructions:\*\*\n(.*?)(?=\n\n|\n💸|\n⭐|\n🎯|$)/s', $settlement_text, $food_matches)) {
                                            $food_payments = $food_matches[1];
                                            $food_lines = array_filter(explode("\n", $food_payments));
                                            if (!empty($food_lines)):
                                        ?>
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                <div class="flex items-center mb-2">
                                                    <i class="fas fa-utensils text-blue-600 mr-2"></i>
                                                    <span class="font-semibold text-blue-800 text-sm">Food Payments</span>
                                                </div>
                                                <div class="space-y-2">
                                                    <?php foreach ($food_lines as $line): 
                                                        $line = trim($line);
                                                        if (strpos($line, '• ') === 0):
                                                            $payment_text = mb_substr($line, 2);
                                                            // Parse "Name pays Name ₹Amount"
                                                            if (preg_match('/(.+?) pays (.+?) ₹(.+)/', $payment_text, $matches)):
                                                                $payer = trim($matches[1]);
                                                                $receiver = trim($matches[2]);
                                                                $amount = trim($matches[3]);
                                                    ?>
                                                        <div class="flex items-center justify-between bg-white rounded-lg p-2 border border-blue-100">
                                                            <div class="flex items-center space-x-2 text-xs">
                                                                <span class="font-medium text-red-600"><?php echo htmlspecialchars($payer); ?></span>
                                                                <i class="fas fa-arrow-right text-gray-400"></i>
                                                                <span class="font-medium text-green-600"><?php echo htmlspecialchars($receiver); ?></span>
                                                            </div>
                                                            <span class="font-bold text-blue-600 text-sm">₹<?php echo htmlspecialchars($amount); ?></span>
                                                        </div>
                                                    <?php 
                                                            endif;
                                                        endif;
                                                    endforeach; ?>
                                                </div>
                                            </div>
                                        <?php 
                                            endif;
                                        }
                                        
                                        // Extract special payment instructions
                                        if (preg_match('/💸 \*\*Special Item Payment Instructions:\*\*\n(.*?)(?=\n\n|\n💸|\n⭐|\n🎯|$)/s', $settlement_text, $special_matches)) {
                                            $special_payments = $special_matches[1];
                                            $special_lines = array_filter(explode("\n", $special_payments));
                                            if (!empty($special_lines)):
                                        ?>
                                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                                                <div class="flex items-center mb-2">
                                                    <i class="fas fa-star text-purple-600 mr-2"></i>
                                                    <span class="font-semibold text-purple-800 text-sm">Special Payments</span>
                                                </div>
                                                <div class="space-y-2">
                                                    <?php foreach ($special_lines as $line): 
                                                        $line = trim($line);
                                                        if (strpos($line, '• ') === 0):
                                                            $payment_text = mb_substr($line, 2);
                                                            if (preg_match('/(.+?) pays (.+?) ₹(.+)/', $payment_text, $matches)):
                                                                $payer = trim($matches[1]);
                                                                $receiver = trim($matches[2]);
                                                                $amount = trim($matches[3]);
                                                    ?>
                                                        <div class="flex items-center justify-between bg-white rounded-lg p-2 border border-purple-100">
                                                            <div class="flex items-center space-x-2 text-xs">
                                                                <span class="font-medium text-red-600"><?php echo htmlspecialchars($payer); ?></span>
                                                                <i class="fas fa-arrow-right text-gray-400"></i>
                                                                <span class="font-medium text-green-600"><?php echo htmlspecialchars($receiver); ?></span>
                                                            </div>
                                                            <span class="font-bold text-purple-600 text-sm">₹<?php echo htmlspecialchars($amount); ?></span>
                                                        </div>
                                                    <?php 
                                                            endif;
                                                        endif;
                                                    endforeach; ?>
                                                </div>
                                            </div>
                                <?php
                                            endif;
                                        }
                                        
                                        // Extract final payment instructions
                                        if (preg_match('/💸 \*\*Final Payment Instructions:\*\*\n(.*?)(?=\n\n|$)/s', $settlement_text, $final_matches)) {
                                            $final_payments = $final_matches[1];
                                            $final_lines = array_filter(explode("\n", $final_payments));
                                            if (!empty($final_lines)):
                                        ?>
                                            <div class="bg-gradient-to-r from-green-50 to-blue-50 border-2 border-gray-300 rounded-lg p-3">
                                                <div class="flex items-center mb-2">
                                                    <i class="fas fa-handshake text-gray-700 mr-2"></i>
                                                    <span class="font-semibold text-gray-800 text-sm">Final Payments</span>
                                                </div>
                                                <div class="space-y-2">
                                                    <?php foreach ($final_lines as $line): 
                                                        $line = trim($line);
                                                        if (strpos($line, '• ') === 0):
                                                            $payment_text = mb_substr($line, 2);
                                                            if (preg_match('/(.+?) pays (.+?) ₹(.+)/', $payment_text, $matches)):
                                                                $payer = trim($matches[1]);
                                                                $receiver = trim($matches[2]);
                                                                $amount = trim($matches[3]);
                                                    ?>
                                                        <div class="flex items-center justify-between bg-white rounded-lg p-3 border-2 border-gray-200 shadow-sm">
                                                            <div class="flex items-center space-x-2 text-sm">
                                                                <span class="font-bold text-red-700"><?php echo htmlspecialchars($payer); ?></span>
                                                                <i class="fas fa-arrow-right text-gray-500"></i>
                                                                <span class="font-bold text-green-700"><?php echo htmlspecialchars($receiver); ?></span>
                                                            </div>
                                                            <span class="font-bold text-gray-800 text-base">₹<?php echo htmlspecialchars($amount); ?></span>
                                                        </div>
                                                    <?php 
                                                            endif;
                                                        endif;
                                                    endforeach; ?>
                                                </div>
                                            </div>
                                        <?php 
                                            endif;
                                        }
                                        ?>
                                        
                                        <!-- View Report Button -->
                                        <div class="flex justify-center space-x-2">
                                            <a href="settlement_report.php?group_id=<?php echo $group_id; ?>" class="inline-block bg-purple-600 text-white text-xs px-3 py-2 rounded-full hover:bg-purple-700 shadow-md">
                                                <i class="fas fa-file-invoice-dollar mr-1"></i> All Settlements
                                            </a>
                                            <?php 
                                            $report_url = "report.php?group_id=" . $group_id;
                                            if (!empty($message['settlement_id'])) {
                                                $report_url .= "&settlement_id=" . $message['settlement_id'];
                                            }
                                            ?>
                                            <a href="<?php echo $report_url; ?>" class="inline-block bg-green-600 text-white text-xs px-3 py-2 rounded-full hover:bg-green-700 shadow-md">
                                                <i class="fas fa-calculator mr-1"></i> <?php echo !empty($message['settlement_id']) ? 'View Report' : 'Current Report'; ?>
                                            </a>
                                        </div>
                                    </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($message['attachment'])): ?>
                                <img
                                    src="uploads/attachments/<?php echo htmlspecialchars($message['attachment']); ?>"
                                        class="mt-2 rounded max-w-full h-auto" style="width: 165px; height: 183px;"
                                />
                            <?php endif; ?>
                                
                                <div class="text-xs <?php echo $is_my_message ? 'text-blue-100' : 'text-gray-400'; ?> mt-1">
                                    <?php echo $message['formatted_time']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-10 text-gray-500">
                    <p>No messages yet. Be the first to send a message!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Input Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white p-3 shadow-inner">
        <form id="expenseForm" action="process/group_message_process.php" method="post" enctype="multipart/form-data" class="flex flex-col w-full">
            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
            
            <div class="flex items-center space-x-2 w-full">
                <!-- Attachment Button -->
                <label class="cursor-pointer bg-gray-200 h-10 w-10 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-300" title="Add attachment">
                    <i class="fas fa-paperclip"></i>
                    <input type="file" class="hidden" name="attachment" />
                </label>

                <!-- Amount (optional) -->
                <input type="number" step="0.01" placeholder="₹ Amount" name="amount" id="amount" class="grow border border-gray-300 rounded-full py-2 px-3 text-sm focus:outline-none" />

                <!-- Note Toggle Button -->
                <button type="button" id="toggleNote" class="bg-gray-200 h-10 w-10 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-300" title="Add note">
                    <i class="fas fa-sticky-note"></i>
                </button>


                <!-- Send Button -->
                <button type="submit" name="submit_message" class="bg-blue-600 h-10 w-10 flex items-center justify-center rounded-full text-white hover:bg-blue-700" title="Send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>

            <!-- Hidden Note Input -->
            <div id="noteContainer" class="mt-2 hidden">
                <textarea name="message" rows="2" placeholder="Enter note (optional)" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:outline-none"></textarea>
                
                <!-- Special Item Checkbox -->
                <div class="mt-2 p-2 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="flex items-start space-x-2">
                        <input type="checkbox" name="is_special_item" id="isSpecialItem" class="rounded text-purple-600 mt-0.5">
                        <div class="flex-1">
                            <label for="isSpecialItem" class="text-sm text-purple-700 font-medium cursor-pointer">
                                <i class="fas fa-star mr-1"></i> Special Item (Shared by Everyone)
                                </label>
                            <p class="text-xs text-purple-600 mt-1">
                                💡 Check this if everyone should contribute equally to this expense
                            </p>
                    </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Members Modal -->
    <div id="membersModal" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-5 w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Group Members</h3>
                <div class="flex items-center space-x-2">
                    <?php if ($is_admin || $is_leader): ?>
                    <button onclick="toggleModal('membersModal'); toggleModal('addMemberModal')" class="text-blue-600 hover:text-blue-700" title="Add Member">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <?php endif; ?>
                <button onclick="toggleModal('membersModal')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
                </div>
            </div>
            
            <ul class="space-y-3">
                <?php foreach ($members as $member): ?>
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
                                    <?php if ($member['user_id'] == $user_id): ?>
                                        <span class="text-xs text-blue-600 font-medium">(You)</span>
                                    <?php endif; ?>
                                    <?php if ($member['is_leader'] == 1): ?>
                                        <span class="text-xs font-medium px-2 py-1 rounded-full ml-1 leader-badge">
                                            <i class="fas fa-crown mr-1"></i>Leader
                                        </span>
                                    <?php endif; ?>
                                    <?php 
                                    // Check if this member is a room admin
                                    $stmt_check_admin_display = $conn->prepare("SELECT rm.is_admin FROM room_members rm 
                                                                              JOIN room_groups rg ON rm.room_id = rg.room_id 
                                                                              WHERE rg.id = ? AND rm.user_id = ?");
                                    $stmt_check_admin_display->bind_param("ii", $group_id, $member['user_id']);
                                    $stmt_check_admin_display->execute();
                                    $admin_result_display = $stmt_check_admin_display->get_result();
                                    $is_room_admin_display = $admin_result_display->num_rows > 0 && $admin_result_display->fetch_assoc()['is_admin'] == 1;
                                    ?>
                                    <?php if ($is_room_admin_display): ?>
                                        <span class="text-xs font-medium px-2 py-1 rounded-full ml-1 admin-badge" style="text-wrap-mode: nowrap;">
                                            <i class="fas fa-user-shield mr-1"></i>Admin
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($member['phone']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-1">
                            <?php if (($is_admin || $is_leader) && $member['user_id'] != $user_id): ?>
                                <?php if (!$member['is_leader']): ?>
                                    <button onclick="assignLeader(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')" 
                                            class="member-action-btn text-purple-600 hover:text-purple-800 hover:bg-purple-50 p-2 rounded-full transition-colors" 
                                            title="Make Group Leader (Only one leader allowed)">
                                        <i class="fas fa-crown text-sm"></i>
                                    </button>
                                <?php else: ?>
                                    <?php if ($is_admin): ?>
                                        <button onclick="removeLeader(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')" 
                                                class="member-action-btn text-orange-600 hover:text-orange-800 hover:bg-orange-50 p-2 rounded-full transition-colors" 
                                                title="Remove Leadership & Become New Leader">
                                            <i class="fas fa-user-minus text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (($is_admin || $is_leader) && $member['user_id'] != $user_id): ?>
                                    <?php 
                                    // Check if this member is a room admin
                                    $stmt_check_admin = $conn->prepare("SELECT rm.is_admin FROM room_members rm 
                                                                      JOIN room_groups rg ON rm.room_id = rg.room_id 
                                                                      WHERE rg.id = ? AND rm.user_id = ?");
                                    $stmt_check_admin->bind_param("ii", $group_id, $member['user_id']);
                                    $stmt_check_admin->execute();
                                    $admin_result = $stmt_check_admin->get_result();
                                    $is_room_admin = $admin_result->num_rows > 0 && $admin_result->fetch_assoc()['is_admin'] == 1;
                                    ?>
                                    
                                    <?php if ($is_room_admin): ?>
                                        <button onclick="removeAdmin(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')" 
                                                class="member-remove-btn text-red-600 hover:text-red-800 hover:bg-red-50 p-2 rounded-full transition-colors" 
                                                title="Remove Admin from Group">
                                            <i class="fas fa-user-shield text-sm"></i>
                                        </button>
                                    <?php else: ?>
                                        <button onclick="removeMember(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')" 
                                                class="member-remove-btn text-red-600 hover:text-red-800 hover:bg-red-50 p-2 rounded-full transition-colors" 
                                                title="Remove from group">
                                            <i class="fas fa-user-minus text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Add Member Modal (Admin/Leader Only) -->
    <?php if ($is_admin || $is_leader): ?>
    <div id="addMemberModal" class="fixed inset-0 bg-black bg-opacity-50 z-20 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-5 w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Add Members to Group</h3>
                <button onclick="toggleModal('addMemberModal')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <?php if (count($non_members) > 0): ?>
                <form action="process/add_group_member_process.php" method="post">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Select members from the room to add to this group:</p>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <?php foreach ($non_members as $non_member): ?>
                                <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded">
                                    <input type="checkbox" name="member_ids[]" value="<?php echo $non_member['user_id']; ?>" class="rounded text-blue-600">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-blue-100 overflow-hidden flex items-center justify-center mr-2">
                                            <?php if (!empty($non_member['profile_image'])): ?>
                                                <img src="uploads/profile/<?php echo htmlspecialchars($non_member['profile_image']); ?>" alt="Profile" class="h-full w-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-user text-blue-600"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="font-medium text-sm"><?php echo htmlspecialchars($non_member['full_name']); ?></span>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($non_member['phone']); ?></p>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="toggleModal('addMemberModal')" class="px-4 py-2 text-gray-600 font-medium">
                            Cancel
                        </button>
                        <button type="submit" name="add_members" class="px-4 py-2 bg-blue-600 text-white font-medium rounded hover:bg-blue-700">
                            Add Selected Members
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">All room members are already in this group.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- User Profile Modal -->
    <div id="userProfileModal" class="fixed inset-0 bg-black bg-opacity-50 z-30 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-6 w-full max-w-sm mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Profile</h3>
                <button onclick="toggleModal('userProfileModal')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="text-center">
                <!-- Profile Image -->
                <div class="mb-4">
                    <img id="profileModalImage" src="" alt="Profile" class="h-20 w-20 rounded-full object-cover mx-auto border-4 border-blue-100">
                </div>
                
                <!-- User Details -->
                <div class="space-y-3">
                    <div>
                        <h4 id="profileModalName" class="text-xl font-semibold text-gray-800"></h4>
                    </div>
                    
                    <div id="profileModalDetails" class="space-y-2 text-sm text-gray-600">
                        <!-- Profile details will be loaded here -->
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex justify-center space-x-3 mt-6" id="profileModalActions">
                        <!-- Action buttons will be added here if needed -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement Modal (Admin/Leader Only) -->
    <?php if ($is_admin || $is_leader): 
        // Get last settlement date for this group
        $last_settlement_date = null;
        $debug_info = "Group ID: $group_id | ";
        
        $stmt = $conn->prepare("SELECT message FROM group_messages 
                               WHERE group_id = ? AND message LIKE '%🧮 **Settlement Created**%' 
                               ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $debug_info .= "Found settlement | ";
            // Extract the "to" date from the last settlement message
            if (preg_match('/📅 Period: .+ - (.+)/', $row['message'], $matches)) {
                $last_settlement_end_date = trim($matches[1]);
                $debug_info .= "End date: $last_settlement_end_date | ";
                // Convert to Y-m-d format
                $date_obj = DateTime::createFromFormat('M d, Y', $last_settlement_end_date);
                if ($date_obj) {
                    // Add one day to start from the day after last settlement
                    $date_obj->add(new DateInterval('P1D'));
                    $last_settlement_date = $date_obj->format('Y-m-d');
                    $debug_info .= "Next start: $last_settlement_date | ";
                }
            }
        } else {
            $debug_info .= "No settlement found | ";
        }
        
        // If no previous settlement, find the first payment date
        if (!$last_settlement_date) {
            $debug_info .= "Getting first payment | ";
            $stmt = $conn->prepare("SELECT DATE(created_at) as first_payment_date 
                                   FROM group_messages 
                                   WHERE group_id = ? AND amount > 0 
                                   ORDER BY created_at ASC LIMIT 1");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $last_settlement_date = $row['first_payment_date'];
                $debug_info .= "First payment: $last_settlement_date | ";
            } else {
                // If no payments exist, default to 30 days ago
                $date_obj = new DateTime();
                $date_obj->sub(new DateInterval('P30D'));
                $last_settlement_date = $date_obj->format('Y-m-d');
                $debug_info .= "Default 30 days: $last_settlement_date | ";
            }
        }
        
        $today_date = date('Y-m-d');
    ?>
    <div id="settlementModal" class="fixed inset-0 bg-black bg-opacity-50 z-30 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-green-800">
                    <i class="fas fa-calculator mr-2"></i>Create Settlement
                </h3>
                <button onclick="toggleModal('settlementModal')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="settlementForm">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                
                <div class="space-y-4">
                    <!-- Settlement Period -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Settlement Period</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-gray-500">From Date</label>
                                <input type="date" name="from_date" id="fromDate" value="<?php echo $last_settlement_date; ?>" class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-100" readonly required>
                                <p class="text-xs text-gray-600 mt-1">
                                    📅 Auto-detected from last settlement or first payment
                                </p>
                                
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">To Date</label>
                                <input type="date" name="to_date" id="toDate" value="<?php echo $today_date; ?>" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                                <p class="text-xs text-gray-600 mt-1">
                                    📅 You can change this date if needed
                                </p>
                            </div>

                            
                        </div>

                        <p class="text-xs text-blue-600 mt-1">
                                    🐛 Debug: <?php echo $debug_info; ?>
                                </p>
                    </div>
                    
                    <!-- Settlement Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Settlement Type</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="include_food" id="includeFood" class="rounded text-green-600" checked>
                                <span class="ml-2 text-sm">Food Expenses</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="include_special" id="includeSpecial" class="rounded text-purple-600" checked>
                                <span class="ml-2 text-sm">Special Item Expenses</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Settlement Preview -->
                    <div id="settlementPreview" class="bg-gray-50 rounded-lg p-3 hidden">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Settlement Preview</h4>
                        <div id="previewContent" class="text-xs text-gray-600">
                            <!-- Preview will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    
                    <button type="button" onclick="toggleModal('settlementModal')" class="px-4 py-2 text-gray-600 font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white font-medium rounded hover:bg-green-700">
                        Create Settlement
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Deletion Request Modal (Leader Only) -->
    <?php if ($is_leader): ?>
    <div id="deletionRequestModal" class="fixed inset-0 bg-black bg-opacity-50 z-30 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-red-800">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Group Deletion Request
                </h3>
                <button onclick="toggleModal('deletionRequestModal')" class="text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="deletionRequestContent">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                    <p class="text-gray-500 mt-2">Loading deletion requests...</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Toggle containers
        const toggleNote = document.getElementById('toggleNote');
        const noteContainer = document.getElementById('noteContainer');

        toggleNote.addEventListener('click', () => {
            noteContainer.classList.toggle('hidden');
        });

        // Form submission with AJAX
        document.getElementById('expenseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const amountField = document.getElementById('amount');
            const amountValue = parseFloat(amountField.value) || 0;
            const messageField = document.querySelector('textarea[name="message"]');
            const messageValue = messageField ? messageField.value.trim() : '';
            const fileField = document.querySelector('input[name="attachment"]');
            const hasFile = fileField && fileField.files.length > 0;
            
            // If neither message, amount, nor attachment is provided
            if (messageValue === '' && amountValue <= 0 && !hasFile) {
                alert('Please provide a message, amount, or attachment.');
                return false;
            }
            
            // Disable send button during submission
            const sendBtn = document.querySelector('button[name="submit_message"]');
            const originalBtnHTML = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Create FormData object
            const formData = new FormData();
            formData.append('group_id', groupId);
            if (messageValue) formData.append('message', messageValue);
            if (amountValue > 0) formData.append('amount', amountValue);
            if (hasFile) formData.append('attachment', fileField.files[0]);
            
            // Add special item flag
            const isSpecialItem = document.getElementById('isSpecialItem').checked;
            if (isSpecialItem) formData.append('is_special_item', 'on');
            
            // Send AJAX request
            fetch('process/send_message_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add message to chat
                    const container = document.getElementById('messagesContainer');
                    container.insertAdjacentHTML('beforeend', createMessageHTML(data.message));
                    
                    // Update last message ID
                    lastMessageId = data.message.id;
                    
                    // Clear form
                    if (messageField) messageField.value = '';
                    amountField.value = '';
                    fileField.value = '';
                    document.getElementById('noteContainer').classList.add('hidden');
                    
                    // Scroll to bottom
                    container.scrollTop = container.scrollHeight;
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(() => {
                // Re-enable send button
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalBtnHTML;
            });
            
            return false;
        });

        // Modal toggle function
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
            } else {
                modal.classList.add('hidden');
            }
        }
        
        // Chat variables
        const groupId = <?php echo $group_id; ?>;
        const userId = <?php echo $user_id; ?>;
        let lastMessageId = <?php echo count($initial_messages) > 0 ? $initial_messages[count($initial_messages)-1]['id'] : '0'; ?>;
        let oldestMessageId = <?php echo count($initial_messages) > 0 ? $initial_messages[0]['id'] : '0'; ?>;
        let isLoadingMessages = false;
        let hasMoreOldMessages = <?php echo count($initial_messages) >= 7 ? 'true' : 'false'; ?>;

        // Show load more button if there might be more messages
        if (hasMoreOldMessages) {
            document.getElementById('loadMoreContainer').classList.remove('hidden');
        }

        // Function to create message HTML
        function createMessageHTML(message) {
            const isMyMessage = message.user_id == userId;
            const messageAlignment = isMyMessage ? 'justify-end' : 'justify-start';
            const flexDirection = isMyMessage ? 'flex-row-reverse' : 'flex-row';
            const messageBgColor = isMyMessage ? 'bg-blue-500 text-white' : 'bg-white';
            const profileImage = message.profile_image ? 'uploads/profile/' + message.profile_image : 'uploads/profile/default_avatar.png';
            
            let messageContent = '';
            if (message.message) {
                // Render markdown formatting for AJAX messages
                let renderedMessage = message.message
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/\n/g, '<br>');
                messageContent += '<p class="text-sm mb-1">' + renderedMessage + '</p>';
            }
            if (message.amount && message.amount > 0) {
                const amountColor = isMyMessage ? 'text-blue-100' : 'text-blue-600';
                const specialIcon = message.is_special_item ? '<i class="fas fa-star mr-1"></i>' : '';
                const specialBadge = message.is_special_item ? '<span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full ml-2">Special</span>' : '';
                messageContent += '<p class="font-semibold text-sm ' + amountColor + '">' + specialIcon + '<i class="fas fa-rupee-sign"></i> ' + parseFloat(message.amount).toFixed(2) + specialBadge + '</p>';
            }
            if (message.attachment) {
                messageContent += '<img src="uploads/attachments/' + message.attachment + '" class="mt-2 rounded max-w-full h-auto" style="width: 165px; height: 183px;" />';
            }
            
            // Add visual payment instructions for settlement messages
            if (message.message && message.message.includes('🧮 **Settlement Created**')) {
                let paymentHTML = '<div class="mt-3 space-y-3">';
                
                // Extract and display food payment instructions
                const foodMatch = message.message.match(/💸 \*\*Food Payment Instructions:\*\*\n(.*?)(?=\n\n|\n💸|\n⭐|\n🎯|$)/s);
                if (foodMatch) {
                    const foodLines = foodMatch[1].split('\n').filter(line => line.trim().startsWith('•'));
                    if (foodLines.length > 0) {
                        paymentHTML += '<div class="bg-blue-50 border border-blue-200 rounded-lg p-3">';
                        paymentHTML += '<div class="flex items-center mb-2"><i class="fas fa-utensils text-blue-600 mr-2"></i>';
                        paymentHTML += '<span class="font-semibold text-blue-800 text-sm">Food Payments</span></div>';
                        paymentHTML += '<div class="space-y-2">';
                        
                        foodLines.forEach(line => {
                            const paymentMatch = line.match(/• (.+?) pays (.+?) ₹(.+)/);
                            if (paymentMatch) {
                                const [, payer, receiver, amount] = paymentMatch;
                                paymentHTML += '<div class="flex items-center justify-between bg-white rounded-lg p-2 border border-blue-100">';
                                paymentHTML += '<div class="flex items-center space-x-2 text-xs">';
                                paymentHTML += '<span class="font-medium text-red-600">' + payer.trim() + '</span>';
                                paymentHTML += '<i class="fas fa-arrow-right text-gray-400"></i>';
                                paymentHTML += '<span class="font-medium text-green-600">' + receiver.trim() + '</span>';
                                paymentHTML += '</div>';
                                paymentHTML += '<span class="font-bold text-blue-600 text-sm">₹' + amount.trim() + '</span>';
                                paymentHTML += '</div>';
                            }
                        });
                        
                        paymentHTML += '</div></div>';
                    }
                }
                
                // Extract and display special payment instructions
                const specialMatch = message.message.match(/💸 \*\*Special Item Payment Instructions:\*\*\n(.*?)(?=\n\n|\n💸|\n⭐|\n🎯|$)/s);
                if (specialMatch) {
                    const specialLines = specialMatch[1].split('\n').filter(line => line.trim().startsWith('•'));
                    if (specialLines.length > 0) {
                        paymentHTML += '<div class="bg-purple-50 border border-purple-200 rounded-lg p-3">';
                        paymentHTML += '<div class="flex items-center mb-2"><i class="fas fa-star text-purple-600 mr-2"></i>';
                        paymentHTML += '<span class="font-semibold text-purple-800 text-sm">Special Payments</span></div>';
                        paymentHTML += '<div class="space-y-2">';
                        
                        specialLines.forEach(line => {
                            const paymentMatch = line.match(/• (.+?) pays (.+?) ₹(.+)/);
                            if (paymentMatch) {
                                const [, payer, receiver, amount] = paymentMatch;
                                paymentHTML += '<div class="flex items-center justify-between bg-white rounded-lg p-2 border border-purple-100">';
                                paymentHTML += '<div class="flex items-center space-x-2 text-xs">';
                                paymentHTML += '<span class="font-medium text-red-600">' + payer.trim() + '</span>';
                                paymentHTML += '<i class="fas fa-arrow-right text-gray-400"></i>';
                                paymentHTML += '<span class="font-medium text-green-600">' + receiver.trim() + '</span>';
                                paymentHTML += '</div>';
                                paymentHTML += '<span class="font-bold text-purple-600 text-sm">₹' + amount.trim() + '</span>';
                                paymentHTML += '</div>';
                            }
                        });
                        
                        paymentHTML += '</div></div>';
                    }
                }
                
                // Extract and display final payment instructions
                const finalMatch = message.message.match(/💸 \*\*Final Payment Instructions:\*\*\n(.*?)(?=\n\n|$)/s);
                if (finalMatch) {
                    const finalLines = finalMatch[1].split('\n').filter(line => line.trim().startsWith('•'));
                    if (finalLines.length > 0) {
                        paymentHTML += '<div class="bg-gradient-to-r from-green-50 to-blue-50 border-2 border-gray-300 rounded-lg p-3">';
                        paymentHTML += '<div class="flex items-center mb-2"><i class="fas fa-handshake text-gray-700 mr-2"></i>';
                        paymentHTML += '<span class="font-semibold text-gray-800 text-sm">Final Payments</span></div>';
                        paymentHTML += '<div class="space-y-2">';
                        
                        finalLines.forEach(line => {
                            const paymentMatch = line.match(/• (.+?) pays (.+?) ₹(.+)/);
                            if (paymentMatch) {
                                const [, payer, receiver, amount] = paymentMatch;
                                paymentHTML += '<div class="flex items-center justify-between bg-white rounded-lg p-3 border-2 border-gray-200 shadow-sm">';
                                paymentHTML += '<div class="flex items-center space-x-2 text-sm">';
                                paymentHTML += '<span class="font-bold text-red-700">' + payer.trim() + '</span>';
                                paymentHTML += '<i class="fas fa-arrow-right text-gray-500"></i>';
                                paymentHTML += '<span class="font-bold text-green-700">' + receiver.trim() + '</span>';
                                paymentHTML += '</div>';
                                paymentHTML += '<span class="font-bold text-gray-800 text-base">₹' + amount.trim() + '</span>';
                                paymentHTML += '</div>';
                            }
                        });
                        
                        paymentHTML += '</div></div>';
                    }
                }
                
                // Add View Report buttons
                paymentHTML += '<div class="flex justify-center space-x-2">';
                paymentHTML += '<a href="settlement_report.php?group_id=<?php echo $group_id; ?>" class="inline-block bg-purple-600 text-white text-xs px-3 py-2 rounded-full hover:bg-purple-700 shadow-md">';
                paymentHTML += '<i class="fas fa-file-invoice-dollar mr-1"></i> All Settlements</a>';
                
                // Build report URL with settlement_id if available
                let reportUrl = 'report.php?group_id=<?php echo $group_id; ?>';
                let reportText = 'Current Report';
                if (message.settlement_id) {
                    reportUrl += '&settlement_id=' + message.settlement_id;
                    reportText = 'View Report';
                }
                
                paymentHTML += '<a href="' + reportUrl + '" class="inline-block bg-green-600 text-white text-xs px-3 py-2 rounded-full hover:bg-green-700 shadow-md">';
                paymentHTML += '<i class="fas fa-calculator mr-1"></i> ' + reportText + '</a>';
                paymentHTML += '</div>';
                
                paymentHTML += '</div>';
                
                messageContent += paymentHTML;
            }
            
            const senderName = !isMyMessage ? '<div class="text-xs text-gray-500 mb-1">' + message.full_name + '</div>' : '';
            const timeColor = isMyMessage ? 'text-blue-100' : 'text-gray-400';
            
            const isSettlementMessage = message.message && message.message.includes('🧮 **Settlement Created**');
            const maxWidthClass = isSettlementMessage ? 'max-w-full' : 'max-w-xs lg:max-w-md';
            const settlementClass = isSettlementMessage ? 'settlement-message' : '';
            
            return `
                <div class="flex ${messageAlignment} mb-4 ${settlementClass}" data-message-id="${message.id}">
                    <div class="flex ${flexDirection} items-end space-x-2 ${maxWidthClass}">
                        <img src="${profileImage}" class="h-8 w-8 rounded-full object-cover flex-shrink-0 cursor-pointer hover:ring-2 hover:ring-blue-300 transition-all" onclick="showUserProfile(${message.user_id}, '${message.full_name}', '${profileImage}')" title="View profile" />
                        <div class="${messageBgColor} p-3 rounded-lg shadow-sm ${isMyMessage ? 'rounded-br-none' : 'rounded-bl-none'}">
                            ${senderName}
                            ${messageContent}
                            <div class="text-xs ${timeColor} mt-1">${message.formatted_time}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Function to load newer messages
        function loadNewerMessages() {
            if (isLoadingMessages) return;
            
            isLoadingMessages = true;
            fetch(`process/get_messages_ajax.php?group_id=${groupId}&last_message_id=${lastMessageId}&direction=newer&limit=10`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        const container = document.getElementById('messagesContainer');
                        const wasScrolledToBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
                        
                        data.messages.forEach(message => {
                            container.insertAdjacentHTML('beforeend', createMessageHTML(message));
                        });
                        
                        lastMessageId = data.messages[data.messages.length - 1].id;
                        
                        // Auto-scroll to bottom if user was already at bottom
                        if (wasScrolledToBottom) {
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                    isLoadingMessages = false;
                })
                .catch(error => {
                    console.error('Error loading newer messages:', error);
                    isLoadingMessages = false;
                });
        }

        // Function to load older messages
        function loadOlderMessages() {
            if (isLoadingMessages || !hasMoreOldMessages) return;
            
            isLoadingMessages = true;
            const loadBtn = document.getElementById('loadMoreBtn');
            loadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading...';
            
            fetch(`process/get_messages_ajax.php?group_id=${groupId}&last_message_id=${oldestMessageId}&direction=older&limit=3`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        const container = document.getElementById('messagesContainer');
                        const scrollHeight = container.scrollHeight;
                        
                        data.messages.forEach(message => {
                            container.insertAdjacentHTML('afterbegin', createMessageHTML(message));
                        });
                        
                        oldestMessageId = data.messages[0].id;
                        hasMoreOldMessages = data.has_more;
                        
                        // Maintain scroll position
                        container.scrollTop = container.scrollHeight - scrollHeight;
                        
                        if (!hasMoreOldMessages) {
                            document.getElementById('loadMoreContainer').classList.add('hidden');
                        }
                    } else {
                        hasMoreOldMessages = false;
                        document.getElementById('loadMoreContainer').classList.add('hidden');
                    }
                    
                    loadBtn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> more messages';
                    isLoadingMessages = false;
                })
                .catch(error => {
                    console.error('Error loading older messages:', error);
                    loadBtn.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> more messages';
                    isLoadingMessages = false;
                });
        }

        // Load more button click event
        document.getElementById('loadMoreBtn').addEventListener('click', loadOlderMessages);

        // Scroll detection for loading older messages
        const messageContainer = document.getElementById('messagesContainer');
        messageContainer.addEventListener('scroll', function() {
            // If scrolled to top and there are more messages, load them
            if (messageContainer.scrollTop <= 10 && hasMoreOldMessages && !isLoadingMessages) {
                loadOlderMessages();
            }
        });

        // Real-time message updates
        setInterval(loadNewerMessages, 3000); // Check for new messages every 3 seconds

        // Function to show user profile
        function showUserProfile(userId, userName, userImage) {
            // Set basic info immediately
            document.getElementById('profileModalName').textContent = userName;
            document.getElementById('profileModalImage').src = userImage;
            document.getElementById('profileModalDetails').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            // Show modal
            document.getElementById('userProfileModal').classList.remove('hidden');
            
            // Fetch detailed profile information
            fetch(`process/get_user_profile_ajax.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        let detailsHTML = '';
                        
                        // Phone
                        if (user.phone) {
                            detailsHTML += `<div class="flex items-center justify-center space-x-2">
                                <i class="fas fa-phone text-blue-500"></i>
                                <span>${user.phone}</span>
                            </div>`;
                        }
                        
                        // Email
                        if (user.email) {
                            detailsHTML += `<div class="flex items-center justify-center space-x-2">
                                <i class="fas fa-envelope text-blue-500"></i>
                                <span>${user.email}</span>
                            </div>`;
                        }
                        
                        // Occupation
                        if (user.occupation) {
                            detailsHTML += `<div class="flex items-center justify-center space-x-2">
                                <i class="fas fa-briefcase text-blue-500"></i>
                                <span>${user.occupation}</span>
                            </div>`;
                        }
                        
                        // Room Number
                        if (user.room_number) {
                            detailsHTML += `<div class="flex items-center justify-center space-x-2">
                                <i class="fas fa-door-open text-blue-500"></i>
                                <span>Room ${user.room_number}</span>
                            </div>`;
                        }
                        
                        // KYC Status
                        if (user.kyc_status) {
                            const statusColor = user.kyc_status === 'verified' ? 'text-green-500' : 
                                              user.kyc_status === 'rejected' ? 'text-red-500' : 'text-yellow-500';
                            const statusIcon = user.kyc_status === 'verified' ? 'fa-check-circle' : 
                                             user.kyc_status === 'rejected' ? 'fa-times-circle' : 'fa-clock';
                            detailsHTML += `<div class="flex items-center justify-center space-x-2">
                                <i class="fas ${statusIcon} ${statusColor}"></i>
                                <span class="capitalize">${user.kyc_status}</span>
                            </div>`;
                        }
                        
                        // Shared Groups
                        if (user.shared_groups && user.shared_groups.length > 0) {
                            detailsHTML += `<div class="mt-4 pt-3 border-t">
                                <p class="text-xs text-gray-500 mb-2">Shared Groups:</p>
                                <div class="flex flex-wrap gap-1 justify-center">`;
                            user.shared_groups.forEach(group => {
                                detailsHTML += `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">${group.name}</span>`;
                            });
                            detailsHTML += `</div></div>`;
                        }
                        
                        document.getElementById('profileModalDetails').innerHTML = detailsHTML;
                        
                        // Add action buttons if needed
                        let actionsHTML = '';
                        if (user.is_own_profile) {
                            actionsHTML = `<a href="profile.php" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">Edit Profile</a>`;
                        }
                        document.getElementById('profileModalActions').innerHTML = actionsHTML;
                        
                    } else {
                        document.getElementById('profileModalDetails').innerHTML = '<div class="text-red-500 text-center">Failed to load profile details</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching user profile:', error);
                    document.getElementById('profileModalDetails').innerHTML = '<div class="text-red-500 text-center">Error loading profile</div>';
                });
        }

        // Settlement functionality
        function initializeSettlementModal() {
            // Dates are now auto-set via PHP based on last settlement
            // No need to manually set dates here
            
            // Focus on the to date field in case user wants to change it
            document.getElementById('toDate').focus();
        }

        function previewSettlement() {
            // Check date validation first
            if (!validateSettlementDates()) {
                return;
            }
            
            const formData = new FormData(document.getElementById('settlementForm'));
            formData.append('action', 'preview');
            formData.append('include_food', document.getElementById('includeFood').checked);
            formData.append('include_special', document.getElementById('includeSpecial').checked);
            
            fetch('process/create_settlement_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSettlementPreview(data.settlement);
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to preview settlement');
            });
        }

        function showSettlementPreview(settlement) {
            const previewDiv = document.getElementById('settlementPreview');
            const contentDiv = document.getElementById('previewContent');
            const createBtn = document.querySelector('#settlementForm button[type="submit"]');
            
            let html = `
                <div class="space-y-3">
                    <div class="bg-blue-50 p-2 rounded">
                        <div class="font-medium text-blue-800">🍽️ Food Expenses: ₹${settlement.total_food.toFixed(2)}</div>
                        <div class="text-xs text-blue-600">Split equally among members</div>
                    </div>
                    <div class="bg-purple-50 p-2 rounded">
                        <div class="font-medium text-purple-800">⭐ Special Items: ₹${settlement.total_special.toFixed(2)}</div>
                        <div class="text-xs text-purple-600">Shared by everyone equally</div>
                    </div>
                    <div class="border-t pt-2">
                        <div class="font-medium mb-2 text-gray-700">💰 Net Settlements:</div>
            `;
            
            // Group settlements by user
            const userNets = {};
            let hasNonZeroBalance = false;
            
            settlement.settlements.forEach(s => {
                if (!userNets[s.user_id]) {
                    userNets[s.user_id] = { name: s.name, net: 0 };
                }
                userNets[s.user_id].net += s.net;
            });
            
            Object.values(userNets).forEach(user => {
                if (user.net > 0.01) {
                    html += `<div class="text-green-600 text-sm">• ${user.name}: +₹${user.net.toFixed(2)} (to receive)</div>`;
                    hasNonZeroBalance = true;
                } else if (user.net < -0.01) {
                    html += `<div class="text-red-600 text-sm">• ${user.name}: -₹${Math.abs(user.net).toFixed(2)} (to pay)</div>`;
                    hasNonZeroBalance = true;
                } else {
                    html += `<div class="text-gray-600 text-sm">• ${user.name}: ₹0.00 (settled)</div>`;
                }
            });
            
            html += `</div></div>`;
            
            contentDiv.innerHTML = html;
            previewDiv.classList.remove('hidden');
            
            // Disable/enable create button based on balances
            if (settlement.total_food === 0 && settlement.total_special === 0) {
                createBtn.disabled = true;
                createBtn.classList.add('opacity-50', 'cursor-not-allowed');
                createBtn.innerHTML = 'No Expenses to Settle';
                
                // Show warning message
                html += `<div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                        <span class="text-yellow-800 text-sm font-medium">No expenses found for the selected period</span>
                    </div>
                </div>`;
                contentDiv.innerHTML = html;
            } else if (!hasNonZeroBalance) {
                createBtn.disabled = true;
                createBtn.classList.add('opacity-50', 'cursor-not-allowed');
                createBtn.innerHTML = 'All Balances Settled';
                
                // Show info message
                html += `<div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        <span class="text-blue-800 text-sm font-medium">All members are already settled</span>
                    </div>
                </div>`;
                contentDiv.innerHTML = html;
            } else {
                createBtn.disabled = false;
                createBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                createBtn.innerHTML = 'Create Settlement';
            }
        }

        // Settlement form submission
        document.getElementById('settlementForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const createBtn = document.querySelector('#settlementForm button[type="submit"]');
            
            // Prevent submission if button is disabled
            if (createBtn.disabled) {
                return false;
            }
            
            // Check date validation
            if (!validateSettlementDates()) {
                return false;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'create');
            formData.append('include_food', document.getElementById('includeFood').checked);
            formData.append('include_special', document.getElementById('includeSpecial').checked);
            
            fetch('process/create_settlement_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settlement created successfully!');
                    toggleModal('settlementModal');
                    // Reload newer messages to show the settlement
                    loadNewerMessages();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create settlement');
            });
        });

        // Initialize settlement modal when opened
        document.querySelector('[onclick="toggleModal(\'settlementModal\')"]').addEventListener('click', function() {
            setTimeout(initializeSettlementModal, 100);
        });
        
        // Reset button state when settlement type checkboxes change
        document.getElementById('includeFood').addEventListener('change', function() {
            resetSettlementButton();
        });
        
        document.getElementById('includeSpecial').addEventListener('change', function() {
            resetSettlementButton();
        });
        
        // Add date validation listeners
        document.getElementById('fromDate').addEventListener('change', function() {
            validateSettlementDates();
        });
        
        document.getElementById('toDate').addEventListener('change', function() {
            validateSettlementDates();
        });
        
        function resetSettlementButton() {
            const createBtn = document.querySelector('#settlementForm button[type="submit"]');
            createBtn.disabled = false;
            createBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            createBtn.innerHTML = 'Create Settlement';
            
            // Hide preview if it's showing
            document.getElementById('settlementPreview').classList.add('hidden');
        }
        
        // Date validation function
        function validateSettlementDates() {
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            const createBtn = document.querySelector('#settlementForm button[type="submit"]');
            
            if (fromDate && toDate) {
                const fromDateObj = new Date(fromDate);
                const toDateObj = new Date(toDate);
                
                if (fromDateObj >= toDateObj) {
                    createBtn.disabled = true;
                    createBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    createBtn.innerHTML = 'Invalid Date Range';
                    
                    // Show error message
                    const previewDiv = document.getElementById('settlementPreview');
                    const contentDiv = document.getElementById('previewContent');
                    
                    let html = `<div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                            <span class="text-red-800 text-sm font-medium">From Date must be before To Date</span>
                        </div>
                    </div>`;
                    
                    contentDiv.innerHTML = html;
                    previewDiv.classList.remove('hidden');
                    return false;
                } else {
                    // Reset button if dates are valid
                    resetSettlementButton();
                    return true;
                }
            }
            return true;
        }
        
        // Assign leader function
        function assignLeader(userId, userName) {
            if (confirm(`👑 **Make Group Leader**\n\nAre you sure you want to make "${userName}" the group leader?\n\n⚠️ **Important:**\n• Only ONE member can be group leader at a time\n• Current leader will lose leadership automatically\n• New leader will have full group control\n\nLeaders can:\n• Add/remove group members\n• Create settlements\n• Remove room admins from group`)) {
                // Show loading state
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i>';
                
                fetch('process/assign_group_leader_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `group_id=${groupId}&user_id=${userId}&action=assign&member_name=${encodeURIComponent(userName)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('Leader assigned successfully!');
                        
                        // Reload the page to show updated leader status
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                        // Restore button
                        button.disabled = false;
                        button.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error assigning leader:', error);
                    alert('Failed to assign leader. Please try again.');
                    // Restore button
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                });
            }
        }
        
        // Remove leader function
        function removeLeader(userId, userName) {
            if (confirm(`👑 **Remove Leadership & Become New Leader**\n\nAre you sure you want to remove leadership from "${userName}"?\n\n⚠️ **This will:**\n• Remove their leadership privileges\n• Automatically make you the new group leader\n• They will remain in the group as a regular member\n\nYou will gain full control of the group.`)) {
                // Show loading state
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i>';
                
                fetch('process/assign_group_leader_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `group_id=${groupId}&user_id=${userId}&action=remove_and_assign_admin&member_name=${encodeURIComponent(userName)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('Leadership transferred successfully! You are now the group leader.');
                        
                        // Reload the page to show updated leader status
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                        // Restore button
                        button.disabled = false;
                        button.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error removing leader:', error);
                    alert('Failed to remove leadership. Please try again.');
                    // Restore button
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                });
            }
        }
        
        // Remove admin function (for leaders to remove room admins from group)
        function removeAdmin(adminId, adminName) {
            if (confirm(`⚠️ **WARNING: Remove Room Admin from Group**\n\nAre you sure you want to remove "${adminName}" (Room Admin) from this group?\n\nThis will:\n• Remove their access to this group\n• They will lose group leader privileges (if any)\n• They will retain their room admin status\n• This action cannot be undone\n\nOnly group leaders can perform this action.`)) {
                // Show loading state
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i>';
                
                fetch('process/remove_admin_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `group_id=${groupId}&admin_id=${adminId}&admin_name=${encodeURIComponent(adminName)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('Admin removed from group successfully!');
                        
                        // Reload the page to show updated member list
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                        // Restore button
                        button.disabled = false;
                        button.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error removing admin:', error);
                    alert('Failed to remove admin. Please try again.');
                    // Restore button
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                });
            }
        }
        
        // Remove member function
        function removeMember(userId, userName) {
            console.log('removeMember called with:', userId, userName, 'groupId:', groupId);
            if (confirm(`Are you sure you want to remove "${userName}" from this group?\n\nThis action cannot be undone. The member will no longer have access to this group.`)) {
                // Show loading state
                const button = event.target.closest('button');
                const originalHTML = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin text-sm"></i>';
                
                console.log('Sending request to remove member:', `group_id=${groupId}&user_id=${userId}&member_name=${encodeURIComponent(userName)}`);
                fetch('process/remove_group_member_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `group_id=${groupId}&user_id=${userId}&member_name=${encodeURIComponent(userName)}`
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        // Show success message
                        alert('Member removed successfully!');
                        
                        // Remove the member from the UI
                        button.closest('li').remove();
                        
                        // Reload messages to show the removal notification
                        loadNewerMessages();
                        
                        // Close modal if no more members to manage
                        const membersList = document.querySelector('#membersModal ul');
                        if (membersList.children.length === 0) {
                            toggleModal('membersModal');
                        }
                    } else {
                        alert('Error: ' + data.error);
                        // Restore button
                        button.disabled = false;
                        button.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error removing member:', error);
                    alert('Failed to remove member. Please try again.');
                    // Restore button
                    button.disabled = false;
                    button.innerHTML = originalHTML;
                });
            }
        }
        
        // Auto-scroll to bottom of messages on page load
        window.onload = function() {
            const messageContainer = document.querySelector('.message-container');
            if (messageContainer) {
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        };
        
        // Show deletion requests (Leader only)
        function showDeletionRequests() {
            document.getElementById('deletionRequestModal').classList.remove('hidden');
            
            // Fetch deletion requests
            fetch(`process/get_deletion_requests_ajax.php?group_id=${groupId}`)
                .then(response => response.json())
                .then(data => {
                    const contentDiv = document.getElementById('deletionRequestContent');
                    
                    if (data.success && data.requests.length > 0) {
                        let html = '<div class="space-y-4">';
                        
                        data.requests.forEach(request => {
                            html += `
                                <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h4 class="font-semibold text-red-800">${request.group_name}</h4>
                                            <p class="text-sm text-red-600">Requested by: ${request.requested_by}</p>
                                            <p class="text-xs text-gray-500">${request.requested_at}</p>
                                        </div>
                                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Pending</span>
                                    </div>
                                    
                                    <div class="bg-white rounded p-3 mb-3">
                                        <p class="text-sm text-gray-700">${request.message}</p>
                                    </div>
                                    
                                    <div class="flex justify-end space-x-2">
                                        <button onclick="approveDeletion(${request.group_id}, '${request.group_name}')" 
                                                class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                            <i class="fas fa-check mr-1"></i>Approve Deletion
                                        </button>
                                        <button onclick="rejectDeletion(${request.group_id}, '${request.group_name}')" 
                                                class="px-3 py-1 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                                            <i class="fas fa-times mr-1"></i>Reject
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        contentDiv.innerHTML = html;
                    } else {
                        contentDiv.innerHTML = `
                            <div class="text-center py-6">
                                <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                                <p class="text-gray-500 font-medium">No pending deletion requests</p>
                                <p class="text-sm text-gray-400">All requests have been processed</p>
                            </div>
                        `;
                        // Hide the notification badge since no pending requests
                        hideDeletionNotification();
                    }
                })
                .catch(error => {
                    console.error('Error fetching deletion requests:', error);
                    document.getElementById('deletionRequestContent').innerHTML = `
                        <div class="text-center py-6">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i>
                            <p class="text-red-500 font-medium">Error loading requests</p>
                            <p class="text-sm text-gray-400">Please try again later</p>
                        </div>
                    `;
                });
        }
        
        // Hide deletion notification badge
        function hideDeletionNotification() {
            const notificationBtn = document.querySelector('[onclick="showDeletionRequests()"]');
            if (notificationBtn) {
                notificationBtn.style.display = 'none';
            }
        }
        
        // Approve group deletion
        function approveDeletion(groupId, groupName) {
            if (confirm(`🗑️ **APPROVE GROUP DELETION**\n\nAre you sure you want to approve the deletion of "${groupName}"?\n\n⚠️ **THIS ACTION CANNOT BE UNDONE!**\n\nThis will permanently delete:\n• All group messages\n• All settlements\n• All member data\n• The group itself`)) {
                fetch('process/approve_deletion_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `group_id=${groupId}&action=approve`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Group deletion approved! The group will be deleted.');
                        // Redirect to room details or home
                        window.location.href = 'index.php?success=Group deleted successfully';
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error approving deletion:', error);
                    alert('Failed to approve deletion. Please try again.');
                });
            }
        }
        
        // Reject group deletion
        function rejectDeletion(groupId, groupName) {
            if (confirm(`❌ **REJECT GROUP DELETION**\n\nAre you sure you want to reject the deletion request for "${groupName}"?\n\nThe group will remain active and the deletion request will be cancelled.`)) {
                fetch('process/approve_deletion_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `group_id=${groupId}&action=reject`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Deletion request rejected successfully!');
                        // Close modal and refresh the page to show updated messages
                        toggleModal('deletionRequestModal');
                        // Reload newer messages to show the rejection message
                        loadNewerMessages();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error rejecting deletion:', error);
                    alert('Failed to reject deletion. Please try again.');
                });
            }
        }
    </script>
</body>
</html>