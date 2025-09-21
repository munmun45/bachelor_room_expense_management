<?php
require_once 'config.php';
requireAdminLogin();

// Get admin info
$admin_info = getAdminInfo($admin_conn, $_SESSION['admin_id']);

// Set default page
$page = $_GET['page'] ?? 'overview';
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
        .sidebar {
            transition: all 0.3s ease;
            height: calc(100vh - 64px);
        }
        .main-content {
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Top Navigation Bar -->
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
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i>Profile
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog mr-2"></i>Settings
                        </a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex pt-16">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar bg-white w-64 fixed left-0 shadow-md z-40 lg:static lg:translate-x-0">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Navigation</h2>
            </div>
            
            <nav class="p-2">
                <a href="dashboard.php?page=overview" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo $page === 'overview' ? 'bg-blue-50 text-blue-600 border-l-4 border-blue-600' : ''; ?>">
                    <i class="fas fa-home mr-3"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="dashboard.php?page=users" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo $page === 'users' ? 'bg-blue-50 text-blue-600 border-l-4 border-blue-600' : ''; ?>">
                    <i class="fas fa-users mr-3"></i>
                    <span>Users</span>
                </a>
                
                <a href="dashboard.php?page=rooms" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo $page === 'rooms' ? 'bg-blue-50 text-blue-600 border-l-4 border-blue-600' : ''; ?>">
                    <i class="fas fa-door-open mr-3"></i>
                    <span>Rooms</span>
                </a>
                
                <a href="dashboard.php?page=groups" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo $page === 'groups' ? 'bg-blue-50 text-blue-600 border-l-4 border-blue-600' : ''; ?>">
                    <i class="fas fa-layer-group mr-3"></i>
                    <span>Groups</span>
                </a>
                
                <a href="dashboard.php?page=reports" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo $page === 'reports' ? 'bg-blue-50 text-blue-600 border-l-4 border-blue-600' : ''; ?>">
                    <i class="fas fa-chart-bar mr-3"></i>
                    <span>Reports</span>
                </a>
                
                <a href="dashboard.php?page=settings" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 <?php echo $page === 'settings' ? 'bg-blue-50 text-blue-600 border-l-4 border-blue-600' : ''; ?>">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Settings</span>
                </a>
                
                <a href="activities.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                    <i class="fas fa-clipboard-list mr-3"></i>
                    <span>Activities</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content flex-1  p-6">
            <?php
            switch ($page) {
                case 'users':
                    include 'users.php';
                    break;
                case 'rooms':
                    include 'rooms.php';
                    break;
                case 'groups':
                    include 'groups.php';
                    break;
                case 'reports':
                    include 'reports.php';
                    break;
                case 'settings':
                    include 'settings.php';
                    break;
                default:
                    include 'overview.php';
                    break;
            }
            ?>
        </main>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // User menu toggle
        document.getElementById('userMenuButton').addEventListener('click', function() {
            document.getElementById('userMenu').classList.toggle('hidden');
        });
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const userMenuButton = document.getElementById('userMenuButton');
            
            if (userMenu && userMenuButton) {
                if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                    userMenu.classList.add('hidden');
                }
            }
        });
    </script>
</body>
</html>