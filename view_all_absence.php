<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id === 0) {
    header("Location: index.php");
    exit();
}

// Get room details and check if user is admin
$stmt = $conn->prepare("SELECT r.*, rm.is_admin
                       FROM rooms r
                       JOIN room_members rm ON r.id = rm.room_id
                       WHERE r.id = ? AND rm.user_id = ? AND rm.invitation_status = 'accepted'");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Room not found or access denied");
    exit();
}

$room = $result->fetch_assoc();
$is_admin = $room['is_admin'] == 1;

if (!$is_admin) {
    header("Location: index.php?error=Only room admins can view absence requests");
    exit();
}

// Get all absence requests for this room
$stmt = $conn->prepare("SELECT ar.*, u.full_name, u.profile_image, u.phone, u.email,
                       r.full_name as reviewer_name
                       FROM absence_requests ar
                       JOIN users u ON ar.user_id = u.id
                       LEFT JOIN users r ON ar.reviewed_by = r.id
                       WHERE ar.room_id = ?
                       ORDER BY ar.status ASC, ar.requested_at DESC");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$requests_result = $stmt->get_result();

$requests = [];
while ($request = $requests_result->fetch_assoc()) {
    $requests[] = $request;
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
    <title>All Absence Requests - <?php echo htmlspecialchars($room['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/back-navigation.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }
        .request-card {
            transition: all 0.2s ease;
        }
        .request-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 12px;
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
                    <i class="fas fa-calendar-times text-orange-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($room['name']); ?></h1>
                    <p class="text-xs text-gray-500">All Absence Requests</p>
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
    <main class="pt-20 pb-24 px-4 max-w-4xl mx-auto">
        <!-- Summary Card -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold">📋 Absence Requests Summary</h2>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    <?php echo count($requests); ?> Total Requests
                </span>
            </div>
            
            <?php if (count($requests) > 0): ?>
                <?php
                $pending_count = count(array_filter($requests, function($r) { return $r['status'] === 'pending'; }));
                $approved_count = count(array_filter($requests, function($r) { return $r['status'] === 'approved'; }));
                $rejected_count = count(array_filter($requests, function($r) { return $r['status'] === 'rejected'; }));
                ?>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-500">Pending</p>
                        <p class="text-lg font-bold text-yellow-600"><?php echo $pending_count; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Approved</p>
                        <p class="text-lg font-bold text-green-600"><?php echo $approved_count; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Rejected</p>
                        <p class="text-lg font-bold text-red-600"><?php echo $rejected_count; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-6">
                    <i class="fas fa-calendar-check text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500 font-medium">No absence requests yet</p>
                    <p class="text-sm text-gray-400">Absence requests will appear here when submitted</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Requests List -->
        <?php if (count($requests) > 0): ?>
            <div class="space-y-3">
                <?php foreach ($requests as $index => $request): 
                    $user_img = $request['profile_image'] ? 'uploads/profile/' . $request['profile_image'] : 'uploads/profile/default_avatar.png';
                    $days_span = (strtotime($request['to_date']) - strtotime($request['from_date'])) / (60 * 60 * 24) + 1;
                ?>
                    <div class="request-card bg-white rounded-xl shadow-md p-4 animate-fade-in" 
                         style="animation-delay: <?php echo $index * 0.1; ?>s">
                        
                        <!-- Request Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <img src="<?php echo $user_img; ?>" alt="User" 
                                     class="w-10 h-10 rounded-full object-cover border-2 border-gray-200">
                                <div>
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($request['full_name']); ?></div>
                                    <div class="text-xs text-gray-500">
                                        Requested <?php echo date('M d, Y \a\t g:i A', strtotime($request['requested_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="status-badge <?php 
                                    echo $request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); 
                                ?>">
                                    <i class="fas <?php 
                                        echo $request['status'] == 'pending' ? 'fa-clock' : 
                                            ($request['status'] == 'approved' ? 'fa-check' : 'fa-times'); 
                                    ?> mr-1"></i>
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Request Details -->
                        <div class="bg-gray-50 rounded-lg p-3 mb-3">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500">Absence Period:</span>
                                    <div class="font-medium">
                                        <?php echo date('M d, Y', strtotime($request['from_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($request['to_date'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-400"><?php echo $days_span; ?> days</div>
                                </div>
                                <div>
                                    <span class="text-gray-500">Contact:</span>
                                    <div class="font-medium"><?php echo htmlspecialchars($request['phone']); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($request['email']); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Reason -->
                        <?php if ($request['reason']): ?>
                            <div class="mb-3">
                                <span class="text-gray-500 text-sm">Reason:</span>
                                <p class="text-sm text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Review Information -->
                        <?php if ($request['status'] !== 'pending'): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                                <div class="flex items-center justify-between text-sm">
                                    <div>
                                        <span class="text-blue-600 font-medium">Reviewed by:</span>
                                        <span class="text-blue-800"><?php echo htmlspecialchars($request['reviewer_name']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-blue-600 font-medium">Date:</span>
                                        <span class="text-blue-800"><?php echo date('M d, Y \a\t g:i A', strtotime($request['reviewed_at'])); ?></span>
                                    </div>
                                </div>
                                <?php if ($request['admin_notes']): ?>
                                    <div class="mt-2">
                                        <span class="text-blue-600 font-medium text-sm">Admin Notes:</span>
                                        <p class="text-sm text-blue-800 mt-1"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <?php if ($request['status'] === 'pending'): ?>
                            <div class="flex space-x-2">
                                <button onclick="reviewRequest(<?php echo $request['id']; ?>, 'approved')" 
                                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                    <i class="fas fa-check mr-1"></i>Approve
                                </button>
                                <button onclick="reviewRequest(<?php echo $request['id']; ?>, 'rejected')" 
                                        class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                                    <i class="fas fa-times mr-1"></i>Reject
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <span class="text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    This request has been <?php echo $request['status']; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Footer Info -->
        <div class="text-center text-xs text-gray-500 bg-white rounded-lg shadow-sm p-3 mt-4">
            <i class="fas fa-info-circle mr-1"></i>
            Absence requests help track member availability for expense calculations
        </div>
    </main>

    <script>
        function reviewRequest(requestId, action) {
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
