<?php
require_once 'config.php';
requireAdminLogin();

// Get admin info
$admin_info = getAdminInfo($admin_conn, $_SESSION['admin_id']);

// Default page
$page = $_GET['page'] ?? 'overview';

// Dashboard statistics
$users_count = $rooms_count = $groups_count = $messages_count = 0;

// Users count
$stmt = $admin_conn->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$result = $stmt->get_result();
$users_count = $result->fetch_assoc()['count'];

// Rooms count
$stmt = $admin_conn->prepare("SELECT COUNT(*) as count FROM rooms");
$stmt->execute();
$result = $stmt->get_result();
$rooms_count = $result->fetch_assoc()['count'];

// Groups count
$stmt = $admin_conn->prepare("SELECT COUNT(*) as count FROM room_groups");
$stmt->execute();
$result = $stmt->get_result();
$groups_count = $result->fetch_assoc()['count'];

// Messages count
$stmt = $admin_conn->prepare("SELECT COUNT(*) as count FROM group_messages");
$stmt->execute();
$result = $stmt->get_result();
$messages_count = $result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Bachelor Room Expense Management</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .sidebar { transition: all 0.3s ease; height: calc(100vh - 64px); }
    .main-content { transition: all 0.3s ease; }
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.active { transform: translateX(0); }
    }
</style>
</head>
<body class="bg-gray-100">
<!-- Top Navigation -->
<header class="bg-white shadow-sm h-16 fixed top-0 left-0 right-0 z-50">
    <div class="flex items-center justify-between h-full px-4">
        <div class="flex items-center">
            <button id="sidebarToggle" class="text-gray-500 focus:outline-none lg:hidden mr-4">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-xl font-bold text-gray-800">Admin Panel</h1>
        </div>
        <div class="flex items-center space-x-4">
            <div class="relative">
                <button class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                </button>
            </div>
            <div class="relative">
                <button id="userMenuButton" class="flex items-center space-x-2 focus:outline-none">
                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                        <?php echo substr($admin_info['full_name'], 0, 1); ?>
                    </div>
                    <span class="hidden md:inline text-gray-700 font-medium"><?php echo htmlspecialchars($admin_info['full_name']); ?></span>
                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>
                <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden z-50">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-user mr-2"></i>Profile</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-cog mr-2"></i>Settings</a>
                    <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="flex pt-16">
   
    <!-- Main Content -->
    <main class="main-content flex-1 p-6 overflow-auto">
        <?php if($page === 'overview'): ?>
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Users -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600"><i class="fas fa-users text-xl"></i></div>
                    <div class="ml-4"><p class="text-sm font-medium text-gray-500">Total Users</p><p class="text-2xl font-bold text-gray-900"><?php echo $users_count; ?></p></div>
                </div>
                <!-- Rooms -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600"><i class="fas fa-door-open text-xl"></i></div>
                    <div class="ml-4"><p class="text-sm font-medium text-gray-500">Total Rooms</p><p class="text-2xl font-bold text-gray-900"><?php echo $rooms_count; ?></p></div>
                </div>
                <!-- Groups -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center">
                    <div class="p-3 rounded-lg bg-yellow-100 text-yellow-600"><i class="fas fa-layer-group text-xl"></i></div>
                    <div class="ml-4"><p class="text-sm font-medium text-gray-500">Total Groups</p><p class="text-2xl font-bold text-gray-900"><?php echo $groups_count; ?></p></div>
                </div>
                <!-- Messages -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center">
                    <div class="p-3 rounded-lg bg-purple-100 text-purple-600"><i class="fas fa-comments text-xl"></i></div>
                    <div class="ml-4"><p class="text-sm font-medium text-gray-500">Total Messages</p><p class="text-2xl font-bold text-gray-900"><?php echo $messages_count; ?></p></div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">User Registrations</h2>
                    <div class="flex-1"><canvas id="userChart" class="w-full h-64"></canvas></div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Room Activity</h2>
                    <div class="flex-1"><canvas id="roomChart" class="w-full h-64"></canvas></div>
                </div>
            </div>
        </div>

        <script>
        // User Chart
        new Chart(document.getElementById('userChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets:[{
                    label:'User Registrations',
                    data:[12,19,3,5,2,3,9,12,15,8,6,4],
                    borderColor:'rgb(59,130,246)',
                    backgroundColor:'rgba(59,130,246,0.1)',
                    tension:0.4, fill:true
                }]
            },
            options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
        });

        // Room Chart
        new Chart(document.getElementById('roomChart').getContext('2d'), {
            type:'bar',
            data:{
                labels:['Room 1','Room 2','Room 3','Room 4','Room 5'],
                datasets:[{
                    label:'Activity',
                    data:[12,19,3,5,2],
                    backgroundColor:[
                        'rgba(59,130,246,0.7)',
                        'rgba(34,197,94,0.7)',
                        'rgba(251,191,36,0.7)',
                        'rgba(168,85,247,0.7)',
                        'rgba(239,68,68,0.7)'
                    ],
                    borderWidth:0
                }]
            },
            options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
        });
        </script>

        <?php else: ?>
            <?php include $page.'.php'; ?>
        <?php endif; ?>
    </main>
</div>

<script>
// Sidebar toggle
document.getElementById('sidebarToggle').addEventListener('click', function(){
    document.getElementById('sidebar').classList.toggle('active');
});
// User menu toggle
document.getElementById('userMenuButton').addEventListener('click', function(){
    document.getElementById('userMenu').classList.toggle('hidden');
});
// Close menu on outside click
document.addEventListener('click', function(e){
    const menu = document.getElementById('userMenu');
    const btn = document.getElementById('userMenuButton');
    if(menu && btn && !btn.contains(e.target) && !menu.contains(e.target)){
        menu.classList.add('hidden');
    }
});
</script>
</body>
</html>
