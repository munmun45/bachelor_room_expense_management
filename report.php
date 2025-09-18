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

// Calculate settlement data
include_once 'includes/settlement_calculator.php';
$settlement_data = calculateSettlement($conn, $group_id, $room_id, $from_date, $to_date, true, true, array_map(function($m) { return $m['name']; }, $members));

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
    <title>Settlement Report - <?php echo htmlspecialchars($group['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }
        .calculation-row {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 0;
        }
        .calculation-row:last-child {
            border-bottom: none;
            font-weight: 600;
            background-color: #f9fafb;
            margin: 4px -12px -8px -12px;
            padding: 8px 12px;
            border-radius: 0 0 6px 6px;
        }
        .member-card {
            transition: all 0.2s ease;
        }
        .member-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
                    <i class="fas fa-calculator text-green-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($group['name']); ?></h1>
                    <p class="text-xs text-gray-500">Settlement Report</p>
                </div>
            </div>
            <div class="flex items-center">
                <a href="profile.php">
                    <img src="<?php echo $profile_image; ?>" alt="Profile" class="h-8 w-8 rounded-full border-2 border-blue-500 object-cover">
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-20 pb-24 px-4 max-w-md mx-auto">
        <!-- Report Period Selector -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex flex-col space-y-3">
                <h2 class="text-lg font-semibold">📊 <?php echo htmlspecialchars($group['name']); ?> Settlement Report</h2>
                
                <!-- Month and Year Filter -->
                <form method="GET" class="flex justify-between items-center gap-2">
                    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                    <div class="flex items-center">
                        <label for="settlement-month" class="text-sm text-gray-500 mr-2">Month:</label>
                        <select id="settlement-month" name="month" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                            <option value="01" <?php echo $selected_month == '01' ? 'selected' : ''; ?>>January</option>
                            <option value="02" <?php echo $selected_month == '02' ? 'selected' : ''; ?>>February</option>
                            <option value="03" <?php echo $selected_month == '03' ? 'selected' : ''; ?>>March</option>
                            <option value="04" <?php echo $selected_month == '04' ? 'selected' : ''; ?>>April</option>
                            <option value="05" <?php echo $selected_month == '05' ? 'selected' : ''; ?>>May</option>
                            <option value="06" <?php echo $selected_month == '06' ? 'selected' : ''; ?>>June</option>
                            <option value="07" <?php echo $selected_month == '07' ? 'selected' : ''; ?>>July</option>
                            <option value="08" <?php echo $selected_month == '08' ? 'selected' : ''; ?>>August</option>
                            <option value="09" <?php echo $selected_month == '09' ? 'selected' : ''; ?>>September</option>
                            <option value="10" <?php echo $selected_month == '10' ? 'selected' : ''; ?>>October</option>
                            <option value="11" <?php echo $selected_month == '11' ? 'selected' : ''; ?>>November</option>
                            <option value="12" <?php echo $selected_month == '12' ? 'selected' : ''; ?>>December</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <label for="settlement-year" class="text-sm text-gray-500 mr-2">Year:</label>
                        <select id="settlement-year" name="year" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                            <option value="2023" <?php echo $selected_year == '2023' ? 'selected' : ''; ?>>2023</option>
                            <option value="2024" <?php echo $selected_year == '2024' ? 'selected' : ''; ?>>2024</option>
                            <option value="2025" <?php echo $selected_year == '2025' ? 'selected' : ''; ?>>2025</option>
                        </select>
                    </div>
                </form>
                
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Total Food Expenses</p>
                        <p class="text-xl font-bold text-blue-600"><i class="fas fa-rupee-sign"></i><?php echo number_format($settlement_data['total_food'], 2); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Special Items</p>
                        <p class="text-xl font-bold text-purple-600"><i class="fas fa-rupee-sign"></i><?php echo number_format($settlement_data['total_special'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Food Expense Settlement Summary -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3">🍽️ Food Expense Settlement</h2>
            
            <!-- Food Expense Breakdown -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 mb-4 border border-blue-100">
                <h3 class="font-semibold mb-3 text-blue-800">
                    <i class="fas fa-utensils mr-2"></i>Food Expense Details
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="calculation-row flex justify-between items-center">
                        <span class="font-medium">Total Food Expenses:</span>
                        <span class="font-semibold text-blue-600 text-lg">₹<?php echo number_format($settlement_data['total_food'], 2); ?></span>
                </div>
                    <div class="calculation-row flex justify-between items-center">
                        <span class="font-medium">Settlement Period:</span>
                        <span class="font-semibold"><?php echo date('M d, Y', strtotime($from_date)); ?> - <?php echo date('M d, Y', strtotime($to_date)); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Member Settlement Cards for Food -->
            <div class="space-y-3 mb-4">
                <?php
                // Group settlements by user and type
                $user_settlements = [];
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
                
                $colors = ['blue', 'green', 'purple', 'yellow', 'red', 'indigo'];
                $i = 0;
                
                foreach ($user_settlements as $user_id => $user_data):
                    $color = $colors[$i % count($colors)];
                    $food_net = $user_data['food']['net'];
                    $profile_img = $members[$user_id]['profile_image'] ? 'uploads/profile/' . $members[$user_id]['profile_image'] : 'uploads/profile/default_avatar.png';
                    $i++;
                ?>
                    <div class="member-card p-4 bg-white border-2 border-gray-100 rounded-lg shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                                <img src="<?php echo $profile_img; ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover mr-3 border-2 border-<?php echo $color; ?>-200">
                            <div>
                                    <div class="font-semibold text-lg"><?php echo htmlspecialchars($user_data['name']); ?></div>
                                <div class="text-sm text-gray-500 flex items-center">
                                        <span class="mr-2">Days present:</span>
                                        <span class="font-medium"><?php echo $user_data['food']['days_present'] ?? $user_data['food']['total_days'] ?? 0; ?> / <?php echo $user_data['food']['total_days'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                                <div class="settlement-amount <?php echo $food_net >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-bold text-xl">
                                    <?php echo $food_net >= 0 ? '+' : ''; ?>₹<?php echo number_format($food_net, 2); ?>
                                </div>
                                <div class="text-xs text-gray-500">Food Settlement</div>
                        </div>
                    </div>
                    
                    <!-- Detailed Calculation -->
                        <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-<?php echo $color; ?>-400">
                        <div class="text-xs font-medium text-gray-700 mb-2">
                                <i class="fas fa-chart-line mr-1"></i>Food Calculation Details
                            </div>
                            <div class="space-y-1 text-xs">
                                <div class="calculation-row flex justify-between">
                                    <span>Amount spent by <?php echo htmlspecialchars($user_data['name']); ?>:</span>
                                    <span class="font-semibold text-blue-600">₹<?php echo number_format($user_data['food']['spent'], 2); ?></span>
                                </div>
                                <div class="calculation-row flex justify-between">
                                    <span>Fair share for food:</span>
                                    <span class="font-semibold text-purple-600">₹<?php echo number_format($user_data['food']['share'], 2); ?></span>
                                </div>
                                <div class="calculation-row flex justify-between">
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
                <?php endforeach; ?>
                        </div>
                    </div>
                    
        <!-- Special Item Settlement Summary -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3">⭐ Special Item Settlement</h2>
            
            <?php if ($settlement_data['total_special'] > 0): ?>
                <!-- Special Item Breakdown -->
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-4 mb-4 border border-purple-100">
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
                    $i = 0;
                    foreach ($user_settlements as $user_id => $user_data):
                        $color = $colors[$i % count($colors)];
                        $special_net = $user_data['special']['net'];
                        $profile_img = $members[$user_id]['profile_image'] ? 'uploads/profile/' . $members[$user_id]['profile_image'] : 'uploads/profile/default_avatar.png';
                        $i++;
                    ?>
                        <div class="member-card p-4 bg-white border-2 border-purple-100 rounded-lg shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                                    <img src="<?php echo $profile_img; ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover mr-3 border-2 border-purple-200">
                            <div>
                                        <div class="font-semibold text-lg"><?php echo htmlspecialchars($user_data['name']); ?></div>
                                        <div class="text-sm text-purple-500">Special Items</div>
                            </div>
                        </div>
                        <div class="text-right">
                                    <div class="settlement-amount <?php echo $special_net >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-bold text-xl">
                                        <?php echo $special_net >= 0 ? '+' : ''; ?>₹<?php echo number_format($special_net, 2); ?>
                        </div>
                                    <div class="text-xs text-gray-500">Special Settlement</div>
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
                    <?php endforeach; ?>
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
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3">💰 Final Settlement Summary</h2>
            
            <div class="space-y-3">
                <?php
                foreach ($user_settlements as $user_id => $user_data):
                    $total_net = $user_data['food']['net'] + $user_data['special']['net'];
                    $profile_img = $members[$user_id]['profile_image'] ? 'uploads/profile/' . $members[$user_id]['profile_image'] : 'uploads/profile/default_avatar.png';
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
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            
        <!-- Payment Instructions -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3">💸 Payment Instructions</h2>
            
            <?php
            // Generate payment instructions for food expenses
            $food_settlements = [];
            $special_settlements = [];
            $combined_settlements = [];
            
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
                            <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                        <i class="fas fa-arrow-right text-red-600 text-sm"></i>
                        </div>
                                    <div class="text-sm">
                                        <span class="font-medium text-red-600"><?php echo htmlspecialchars($payment['payer']); ?></span>
                                        <span class="text-gray-600"> pays </span>
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
                            <div class="flex items-center justify-between p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                        <i class="fas fa-arrow-right text-red-600 text-sm"></i>
                                        </div>
                                        <div class="text-sm">
                                        <span class="font-medium text-red-600"><?php echo htmlspecialchars($payment['payer']); ?></span>
                                        <span class="text-gray-600"> pays </span>
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
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-blue-50 border-2 border-gray-300 rounded-lg shadow-sm">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-red-500 to-orange-500 flex items-center justify-center">
                                        <i class="fas fa-arrow-right text-white"></i>
                                        </div>
                                    <div class="text-md">
                                        <span class="font-bold text-red-700"><?php echo htmlspecialchars($payment['payer']); ?></span>
                                        <span class="text-gray-700 font-medium"> pays </span>
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
            Report Generated: <span class="font-semibold"><?php echo date('M d, Y \a\t g:i A'); ?></span>
                            </div>
    </main>
</body>
</html>