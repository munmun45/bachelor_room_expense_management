<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Room Details - Bachelor Room</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-10">
        <div class="flex justify-between items-center px-4 py-3">
            <div class="flex items-center">
                <a href="index.html" class="mr-3">
                    <i class="fas fa-arrow-left text-blue-600 text-xl"></i>
                </a>
                <div class="mr-3">
                    <i class="fas fa-wallet text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Bachelor Room</h1>
                    <p class="text-xs text-gray-500">Expense Management</p>
                </div>
            </div>
            <div class="flex items-center">
                <div class="mr-4 relative">
                    <i class="fas fa-bell text-gray-600 text-xl"></i>
                    <span class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full"></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-16 pb-24 px-4 max-w-md mx-auto">
        <!-- Room Overview -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-2">Room Overview</h2>
            <p class="text-sm text-gray-600 mb-2">Created on: Jan 15, 2024</p>
            <p class="text-sm text-gray-600 mb-2">Total Members: 5</p>
            <p class="text-sm text-gray-600">Recent Activity: 2h ago</p>
        </div>

        <!-- Members List -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-semibold">Members</h2>
                <button class="h-8 w-8 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-md hover:bg-blue-700 text-sm">
                    <i class="fas fa-user-plus"></i>
                </button>
            </div>
            <ul class="space-y-3">
                <li class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <span class="font-medium">Rahul (Admin)</span>
                    </div>
                    <i class="fas fa-crown text-yellow-500"></i>
                </li>
                <li class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-9 w-9 rounded-full bg-green-100 flex items-center justify-center mr-3">
                            <i class="fas fa-user text-green-600"></i>
                        </div>
                        <span class="font-medium">Priya</span>
                    </div>
                </li>
                <li class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-9 w-9 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                            <i class="fas fa-user text-purple-600"></i>
                        </div>
                        <span class="font-medium">Arjun</span>
                    </div>
                </li>
                <!-- Add more members as needed -->
            </ul>
        </div>

        <!-- Leave Room -->
        <div class="bg-white rounded-xl shadow-md p-4 text-center">
            <button class="text-red-600 font-medium">Leave Room</button>
        </div>
    </main>
</body>
</html>