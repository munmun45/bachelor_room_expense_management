<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Log user activity - report access
logUserActivity($conn, $_SESSION['user_id'], 'page_access', 'report', 'User accessed the report page');

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$settlement_id = isset($_GET['settlement_id']) ? intval($_GET['settlement_id']) : 0;

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

// Get current month and year
$current_month = date('m');
$current_year = date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;

// Calculate date range for the selected month
$from_date = $selected_year . '-' . $selected_month . '-01';
$to_date = date('Y-m-t', strtotime($from_date)); // Last day of the month

// Get group members
$stmt = $conn->prepare("SELECT gm.user_id, u.full_name, u.profile_image, rg.room_id
                       FROM group_members gm 
                       JOIN users u ON gm.user_id = u.id 
                       JOIN room_groups rg ON gm.group_id = rg.id
                       WHERE gm.group_id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();

$members = [];
$room_id = null;
while ($member = $members_result->fetch_assoc()) {
    $members[$member['user_id']] = [
        'name' => $member['full_name'],
        'profile_image' => $member['profile_image']
    ];
    if ($room_id === null) {
        $room_id = $member['room_id'];
    }
}

// Calculate or load settlement data
include_once 'includes/settlement_calculator.php';

$settlement_source = 'calculated'; // 'calculated' or 'saved'
$settlement_details = null;

if ($settlement_id > 0) {
    // Try to load settlement from database
    $stmt = $conn->prepare("SELECT s.*, u.full_name as creator_name 
                           FROM settlements s 
                           JOIN users u ON s.created_by = u.id 
                           WHERE s.id = ? AND s.group_id = ?");
    $stmt->bind_param("ii", $settlement_id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $settlement_record = $result->fetch_assoc();
        $settlement_data = json_decode($settlement_record['settlement_data'], true);

        // Validate the JSON data and provide defaults if needed
        if (!is_array($settlement_data)) {
            $settlement_data = [
                'total_food' => 0,
                'total_special' => 0,
                'food_expenses' => [],
                'special_expenses' => [],
                'settlements' => []
            ];
        }

        // Ensure required fields exist with defaults
        $settlement_data['total_food'] = $settlement_data['total_food'] ?? 0;
        $settlement_data['total_special'] = $settlement_data['total_special'] ?? 0;
        $settlement_data['food_expenses'] = $settlement_data['food_expenses'] ?? [];
        $settlement_data['special_expenses'] = $settlement_data['special_expenses'] ?? [];
        $settlement_data['settlements'] = $settlement_data['settlements'] ?? [];

        $settlement_source = 'saved';
        $settlement_details = [
            'id' => $settlement_record['id'],
            'creator' => $settlement_record['creator_name'],
            'created_at' => $settlement_record['created_at'],
            'from_date' => $settlement_record['from_date'],
            'to_date' => $settlement_record['to_date'],
            'include_food' => $settlement_record['include_food'],
            'include_special' => $settlement_record['include_special'],
            'message_id' => $settlement_record['message_id']
        ];

        // Override the date range from saved settlement
        $from_date = $settlement_record['from_date'];
        $to_date = $settlement_record['to_date'];
    } else {
        // Invalid settlement ID, redirect to general report
        header("Location: report.php?group_id=" . $group_id);
        exit();
    }
}

if ($settlement_source === 'calculated') {
    // Prepare member data for settlement calculation
    $member_names = [];
    foreach ($members as $user_id => $member_data) {
        $member_names[$user_id] = $member_data['name'];
    }

    $settlement_data = calculateSettlement($conn, $group_id, $room_id, $from_date, $to_date, true, true, $member_names);

    // Ensure required fields exist with defaults for calculated data too
    $settlement_data['total_food'] = $settlement_data['total_food'] ?? 0;
    $settlement_data['total_special'] = $settlement_data['total_special'] ?? 0;
    $settlement_data['food_expenses'] = $settlement_data['food_expenses'] ?? [];
    $settlement_data['special_expenses'] = $settlement_data['special_expenses'] ?? [];
    $settlement_data['settlements'] = $settlement_data['settlements'] ?? [];
}

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Report - <?php echo htmlspecialchars($group['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/back-navigation.js"></script>

    <link rel="stylesheet" href="./css/style.css">

</head>

<body>


    <!-- Header -->

    <header class="app-header fixed top-0 left-0 right-0 z-10 py-2.5 px-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2.5">
                <button onclick="goBack()" class="mr-3 back-button" title="Go Back" style="margin-left: 15px;">
                    <i class="fas fa-arrow-left text-white text-xl"></i>
                </button>

                <div>
                    <h1 class="text-base font-bold text-white tracking-tight"><?php echo htmlspecialchars($group['name']); ?></h1>
                    <p class="text-[12px] text-white text-opacity-80 -mt-0.5">
                        <?php echo $settlement_source === 'saved' ? 'Saved Settlement Report' : 'Settlement Report'; ?>
                    </p>
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


        <!-- Report Period Selector -->
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200 text-[12px]">
            <div class="flex flex-col space-y-4">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        📊 <?php echo htmlspecialchars($group['name']); ?> Settlement Report
                    </h2>
                </div>

                <!-- Saved Settlement Details -->
                <?php if ($settlement_source === 'saved' && $settlement_details): ?>
                    <div class="bg-gradient-to-r from-green-50 to-blue-50  border-green-200 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-save text-green-600 mr-2"></i>
                            <span class="font-medium text-green-800">
                                Saved Settlement #<?php echo $settlement_details['id']; ?>
                            </span>
                        </div>
                        <div class="grid gap-1 text-gray-700">
                            <div><span class="font-medium">Created by:</span> <?php echo htmlspecialchars($settlement_details['creator']); ?></div>
                            <div><span class="font-medium">Created on:</span> <?php echo date('M d, Y \a\t g:i A', strtotime($settlement_details['created_at'])); ?></div>
                            <?php if ($settlement_details['message_id']): ?>
                                <div><span class="font-medium">Message ID:</span> #<?php echo $settlement_details['message_id']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Report Period -->
                <div class="bg-blue-50  border-blue-100 p-3 rounded-lg text-gray-700">
                    <span class="font-medium">Report Period:</span>
                    <?php echo date('M d, Y', strtotime($from_date)); ?> → <?php echo date('M d, Y', strtotime($to_date)); ?>
                    <?php if ($settlement_source === 'saved'): ?>
                        <span class="text-[11px] text-green-600 block mt-1">
                            <i class="fas fa-database mr-1"></i> Data loaded from saved settlement
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Expense Stats -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Food Expenses -->
                    <div class="bg-blue-50  border-blue-100 rounded-lg p-4">
                        <p class="text-blue-700 mb-1">Total Food Expenses</p>
                        <p class="text-base font-bold text-blue-800 flex items-center gap-1">
                            <i class="fas fa-rupee-sign"></i><?php echo number_format($settlement_data['total_food'], 2); ?>
                        </p>
                        <p class="text-gray-500"><?php echo count($settlement_data['food_expenses']); ?> transactions</p>
                    </div>

                    <!-- Special Items -->
                    <div class="bg-purple-50  border-purple-100 rounded-lg p-4">
                        <p class="text-purple-700 mb-1">Special Items</p>
                        <p class="text-base font-bold text-purple-800 flex items-center gap-1">
                            <i class="fas fa-rupee-sign"></i><?php echo number_format($settlement_data['total_special'], 2); ?>
                        </p>
                        <p class="text-gray-500"><?php echo count($settlement_data['special_expenses']); ?> transactions</p>
                    </div>
                </div>
            </div>
        </div>



        <!-- Food Expense Settlement Summary -->
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200 text-[12px]">
            <h2 class="text-lg font-semibold mb-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 10px 14px; border-radius: 10px;">🍽️ Food Expense Settlement</h2>

            <?php if ($settlement_data['total_food'] == 0): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-utensils text-4xl mb-3 text-gray-300"></i>
                    <p class="font-medium">No food expenses for this period</p>
                    <p class="text-sm">Food expenses will appear here when added to group chat</p>
                </div>
            <?php else: ?>




                <!-- Member Settlement Cards for Food -->
                <div class="space-y-3 mb-4">
                    <?php
                    // Group settlements by user and type
                    $user_settlements = [];

                    if (is_array($settlement_data['settlements']) && !empty($settlement_data['settlements'])) {
                        foreach ($settlement_data['settlements'] as $settlement) {
                            if (!isset($user_settlements[$settlement['user_id']])) {
                                $user_settlements[$settlement['user_id']] = [
                                    'name' => $settlement['name'],
                                    'food' => ['spent' => 0, 'share' => 0, 'net' => 0, 'days_present' => 0, 'total_days' => 0],
                                    'special' => ['spent' => 0, 'share' => 0, 'net' => 0]
                                ];
                            }
                            if ($settlement['type'] == 'food') {
                                $user_settlements[$settlement['user_id']]['food'] = $settlement;
                            } else {
                                $user_settlements[$settlement['user_id']]['special'] = $settlement;
                            }
                        }
                    }

                    $colors = ['blue', 'green', 'purple', 'yellow', 'red', 'indigo'];
                    $i = 0;

                    if (empty($user_settlements)): ?>
                        <div class="text-center py-6 text-gray-500">
                            <i class="fas fa-users text-3xl mb-3 text-gray-300"></i>
                            <p class="font-medium">No settlement data available</p>
                            <p class="text-sm">Settlement calculations will appear here when expenses are added</p>
                        </div>
                        <?php else:
                        foreach ($user_settlements as $user_id => $user_data):
                            $color = $colors[$i % count($colors)];
                            $food_net = $user_data['food']['net'];

                            if (isset($members[$user_id])) {
                                $profile_img = $members[$user_id]['profile_image'] ? 'uploads/profile/' . $members[$user_id]['profile_image'] : 'uploads/profile/default_avatar.png';
                                $member_name = $members[$user_id]['name'];
                            } else {
                                $profile_img = 'uploads/profile/default_avatar.png';
                                $member_name = 'Former Member';
                            }
                            $i++;
                        ?>
                            <div class="member-card ">
                                <!-- Header -->
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo $profile_img; ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover border-2 border-<?php echo $color; ?>-200">
                                        <div>
                                            <div class="font-semibold text-sm"><?php echo htmlspecialchars($user_data['name']); ?></div>
                                            <div class="text-xs text-gray-500 flex items-center gap-1">
                                                <span>Days present:</span>
                                                <span class="font-medium"><?php echo $user_data['food']['days_present'] ?? $user_data['food']['total_days'] ?? 0; ?> / <?php echo $user_data['food']['total_days'] ?? 0; ?></span>
                                            </div>

                                            <div style="display: flex ; align-items: center; gap: 10px;">
                                                <div class="font-bold text-[14px] <?php echo $food_net >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                    <?php echo $food_net >= 0 ? '+' : ''; ?>₹<?php echo number_format($food_net, 2); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">Food Settlement</div>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                <!-- Detailed Calculation -->
                                <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-<?php echo $color; ?>-400 text-[12px]">
                                    <div class="text-xs font-medium text-gray-700 mb-2 flex items-center gap-1">
                                        <i class="fas fa-chart-line"></i> Food Calculation Details
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex justify-between">
                                            <span>Amount spent by <?php echo htmlspecialchars($user_data['name']); ?>:</span>
                                            <span class="font-semibold text-blue-600">₹<?php echo number_format($user_data['food']['spent'], 2); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Fair share for food:</span>
                                            <span class="font-semibold text-purple-600">₹<?php echo number_format($user_data['food']['share'], 2); ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="font-semibold">Net Settlement:</span>
                                            <div class="text-right">
                                                <div class="font-bold <?php echo $food_net >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                    <?php echo $food_net >= 0 ? '+' : ''; ?>₹<?php echo number_format($food_net, 2); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ₹<?php echo number_format($user_data['food']['spent'], 2); ?> - ₹<?php echo number_format($user_data['food']['share'], 2); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php endforeach;
                    endif; ?>
                </div>

            <?php endif; // End of food expenses check 
            ?>
        </div>

        <!-- Special Item Settlement Summary -->
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200 text-[12px]">
            <h2 class="text-lg font-semibold mb-3" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 10px 14px; border-radius: 10px;">⭐ Special Item Settlement</h2>

            <?php if ($settlement_data['total_special'] > 0): ?>
                <!-- Special Item Breakdown -->
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-4 mb-4 border-purple-100">
                    <h3 class="font-semibold mb-3 text-purple-800">
                        <i class="fas fa-star mr-2"></i>Special Item Details
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div class="calculation-row flex justify-between items-center">
                            <span class="font-medium">Total Special Items:</span>
                            <span class="font-semibold text-purple-600 text-lg">₹<?php echo number_format($settlement_data['total_special'], 2); ?></span>
                        </div>
                        <div class="calculation-row flex justify-between items-center">
                            <span class="font-medium">Per Member Share:</span>
                            <span class="font-semibold text-purple-600">₹<?php echo number_format($settlement_data['total_special'] / count($members), 2); ?></span>
                        </div>
                        <div class="text-xs text-purple-600 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Special items are shared equally among all members regardless of presence.
                        </div>
                    </div>
                </div>

                <!-- Member Settlement Cards for Special Items -->
                <div class="space-y-3 mb-4">
                    <?php
                    if (empty($user_settlements)): ?>
                        <div class="text-center py-6 text-gray-500">
                            <i class="fas fa-users text-3xl mb-3 text-gray-300"></i>
                            <p class="font-medium">No settlement data available</p>
                            <p class="text-sm">Special item settlements will appear here when calculated</p>
                        </div>
                        <?php else:
                        $i = 0;
                        foreach ($user_settlements as $user_id => $user_data):
                            $color = $colors[$i % count($colors)];
                            $special_net = $user_data['special']['net'];
                            // Check if user is still a member of the group
                            if (isset($members[$user_id])) {
                                $profile_img = $members[$user_id]['profile_image'] ? 'uploads/profile/' . $members[$user_id]['profile_image'] : 'uploads/profile/default_avatar.png';
                            } else {
                                // User was removed from group, use default values
                                $profile_img = 'uploads/profile/default_avatar.png';
                            }
                            $i++;
                        ?>
                            <div class="member-card  rounded-lg shadow-sm">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center">
                                        <img src="<?php echo $profile_img; ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover mr-3 border-2 border-purple-200">
                                        <div class="member-info">

                                            <div>
                                                <div class="font-semibold text-sm"><?php echo htmlspecialchars($user_data['name']); ?></div>
                                            </div>

                                            <div class="settlement-amount <?php echo $special_net >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-bold text-xl">
                                                <?php echo $special_net >= 0 ? '+' : ''; ?>₹<?php echo number_format($special_net, 2); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">Special Settlement</div>

                                        </div>

                                    </div>

                                </div>

                                <!-- Detailed Calculation -->
                                <div class="bg-purple-50 rounded-lg p-3 border-l-4 border-purple-400">
                                    <div class="text-xs font-medium text-purple-700 mb-2">
                                        <i class="fas fa-star mr-1"></i>Special Item Calculation
                                    </div>
                                    <div class="space-y-1 text-xs">
                                        <div class="calculation-row flex justify-between">
                                            <span>Amount spent on special items:</span>
                                            <span class="font-semibold text-purple-600">₹<?php echo number_format($user_data['special']['spent'], 2); ?></span>
                                        </div>
                                        <div class="calculation-row flex justify-between">
                                            <span>Fair share (equal split):</span>
                                            <span class="font-semibold text-purple-600">₹<?php echo number_format($user_data['special']['share'], 2); ?></span>
                                        </div>
                                        <div class="calculation-row flex justify-between">
                                            <span class="font-semibold">Net Settlement:</span>
                                            <div class="text-right">
                                                <div class="font-bold <?php echo $special_net >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                    <?php echo $special_net >= 0 ? '+' : ''; ?>₹<?php echo number_format($special_net, 2); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ₹<?php echo number_format($user_data['special']['spent'], 2); ?> - ₹<?php echo number_format($user_data['special']['share'], 2); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php endforeach;
                    endif; // End of user settlements check 
                    ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-star text-4xl mb-3 text-gray-300"></i>
                    <p class="font-medium">No special items for this period</p>
                    <p class="text-sm">Special items marked in group chat will appear here</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Combined Settlement Summary -->
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200 text-[12px]">
            <h2 class="text-lg font-semibold mb-3">💰 Final Settlement Summary</h2>

            <div class="space-y-3">
                <?php
                if (empty($user_settlements)): ?>
                    <div class="text-center py-6 text-gray-500">
                        <i class="fas fa-balance-scale text-3xl mb-3 text-gray-300"></i>
                        <p class="font-medium">No settlement data to summarize</p>
                        <p class="text-sm">Final settlement summary will appear when settlements are calculated</p>
                    </div>
                    <?php else:
                    foreach ($user_settlements as $user_id => $user_data):
                        $total_net = $user_data['food']['net'] + $user_data['special']['net'];
                        // Check if user is still a member of the group
                        if (isset($members[$user_id])) {
                            $profile_img = $members[$user_id]['profile_image'] ? 'uploads/profile/' . $members[$user_id]['profile_image'] : 'uploads/profile/default_avatar.png';
                        } else {
                            // User was removed from group, use default values
                            $profile_img = 'uploads/profile/default_avatar.png';
                        }
                    ?>
                        <div class="flex items-center justify-between p-3 <?php echo $total_net >= 0 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?> rounded-lg">
                            <div class="flex items-center space-x-3">
                                <img src="<?php echo $profile_img; ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover">
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($user_data['name']); ?></div>
                                    <div class="text-xs text-gray-500">
                                        Food: <?php echo $user_data['food']['net'] >= 0 ? '+' : ''; ?>₹<?php echo number_format($user_data['food']['net'], 2); ?> |
                                        Special: <?php echo $user_data['special']['net'] >= 0 ? '+' : ''; ?>₹<?php echo number_format($user_data['special']['net'], 2); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-lg <?php echo $total_net >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $total_net >= 0 ? '+' : ''; ?>₹<?php echo number_format($total_net, 2); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo $total_net >= 0 ? 'To Receive' : 'To Pay'; ?>
                                </div>
                            </div>
                <?php endforeach;
                endif; // End of user settlements check 
                ?>
            </div>
        </div>

        <!-- Payment Instructions -->
        <div class="p-5 mb-4 mt-3 rounded-3xl  relative bg-gradient-to-br from-gray-50 via-white to-gray-100  border-gray-200 text-[12px]">
            <h2 class="text-lg font-semibold mb-3">💸 Payment Instructions</h2>

            <?php
            // Generate payment instructions for food expenses
            $food_settlements = [];
            $special_settlements = [];
            $combined_settlements = [];

            if (!empty($user_settlements)) {
                foreach ($user_settlements as $user_id => $user_data) {
                    if ($user_data['food']['net'] != 0) {
                        $food_settlements[$user_id] = $user_data['food'];
                    }
                    if ($user_data['special']['net'] != 0) {
                        $special_settlements[$user_id] = $user_data['special'];
                    }

                    $total_net = $user_data['food']['net'] + $user_data['special']['net'];
                    if ($total_net != 0) {
                        $combined_settlements[$user_id] = [
                            'name' => $user_data['name'],
                            'net' => $total_net
                        ];
                    }
                }
            }

            // Food payment instructions
            if (!empty($food_settlements)):
                $food_payments = generatePaymentInstructions($food_settlements, 'food');
                if (!empty($food_payments)):
            ?>
                    <div class="mb-6">
                        <h3 class="text-md font-semibold mb-3 text-blue-800">
                            <i class="fas fa-utensils mr-2"></i>Food Expense Payments
                        </h3>
                        <div class="space-y-2">
                            <?php foreach ($food_payments as $payment): ?>
                                <div class="flex items-center justify-between p-3 bg-blue-50  border-blue-200 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                            <i class="fas fa-arrow-right text-red-600 text-sm"></i>
                                        </div>
                                        <div class="text-sm">
                                            <span class="font-medium text-red-600"><?php echo htmlspecialchars($payment['payer']); ?></span>
                                            <span class="text-gray-600"> pays </span> <br>
                                            <span class="font-medium text-green-600"><?php echo htmlspecialchars($payment['receiver']); ?></span>
                                        </div>
                                    </div>
                                    <div class="font-bold text-lg text-blue-600">
                                        ₹<?php echo number_format($payment['amount'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                endif;
            endif;

            // Special item payment instructions
            if (!empty($special_settlements)):
                $special_payments = generatePaymentInstructions($special_settlements, 'special');
                if (!empty($special_payments)):
                ?>
                    <div class="mb-6">
                        <h3 class="text-md font-semibold mb-3 text-purple-800">
                            <i class="fas fa-star mr-2"></i>Special Item Payments
                        </h3>
                        <div class="space-y-2">
                            <?php foreach ($special_payments as $payment): ?>
                                <div class="flex items-center justify-between p-3 bg-purple-50  border-purple-200 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                            <i class="fas fa-arrow-right text-red-600 text-sm"></i>
                                        </div>
                                        <div class="text-sm">
                                            <span class="font-medium text-red-600"><?php echo htmlspecialchars($payment['payer']); ?></span>
                                            <span class="text-gray-600"> pays </span><br>
                                            <span class="font-medium text-green-600"><?php echo htmlspecialchars($payment['receiver']); ?></span>
                                        </div>
                                    </div>
                                    <div class="font-bold text-lg text-purple-600">
                                        ₹<?php echo number_format($payment['amount'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                endif;
            endif;

            // Combined payment instructions
            if (!empty($combined_settlements)):
                $combined_payments = generatePaymentInstructions($combined_settlements, 'combined');
                if (!empty($combined_payments)):
                ?>
                    <div class="mb-4">
                        <h3 class="text-md font-semibold mb-3 text-gray-800">
                            <i class="fas fa-handshake mr-2"></i>Final Payment Instructions (Combined)
                        </h3>
                        <div class="space-y-2">
                            <?php foreach ($combined_payments as $payment): ?>
                                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-blue-50  border-gray-300 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-red-500 to-orange-500 flex items-center justify-center">
                                            <i class="fas fa-arrow-right text-white"></i>
                                        </div>
                                        <div class="text-md">
                                            <span class="font-bold text-red-700"><?php echo htmlspecialchars($payment['payer']); ?></span>
                                            <span class="text-gray-700 font-medium"> pays </span> <br>
                                            <span class="font-bold text-green-700"><?php echo htmlspecialchars($payment['receiver']); ?></span>
                                        </div>
                                    </div>
                                    <div class="font-bold text-xl text-gray-800">
                                        ₹<?php echo number_format($payment['amount'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php
                else:
                ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-4xl mb-3 text-green-400"></i>
                        <p class="font-medium">All settlements are balanced!</p>
                        <p class="text-sm">No payments required for this period.</p>
                    </div>
            <?php
                endif;
            endif;
            ?>
        </div>

        <!-- Settlement Date -->
        <div class="text-center text-sm text-gray-600 bg-white rounded-lg shadow-sm p-3 mb-4">
            <i class="fas fa-calendar mr-2 text-gray-600"></i>
            <?php if ($settlement_source === 'saved'): ?>
                Settlement Created: <span class="font-semibold"><?php echo date('M d, Y \a\t g:i A', strtotime($settlement_details['created_at'])); ?></span>
                <div class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-database mr-1"></i>Settlement ID: #<?php echo $settlement_details['id']; ?>
                </div>
            <?php else: ?>
                Report Generated: <span class="font-semibold"><?php echo date('M d, Y \a\t g:i A'); ?></span>
            <?php endif; ?>
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


</body>

</html>