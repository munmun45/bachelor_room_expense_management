<div class="space-y-6">
    <!-- Page Title -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            <i class="fas fa-save mr-2"></i>Save Changes
        </button>
    </div>
    
    <!-- Settings Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- General Settings -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Site Settings -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Site Settings</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="Bachelor Room Expense Management">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site Description</label>
                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3">A comprehensive platform for managing shared expenses in bachelor rooms.</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                        <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="admin@example.com">
                    </div>
                </div>
            </div>
            
            <!-- User Settings -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">User Settings</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Email Verification</p>
                            <p class="text-sm text-gray-500">Require email verification for new users</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700">KYC Verification</p>
                            <p class="text-sm text-gray-500">Require KYC verification for all users</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Allow User Registration</p>
                            <p class="text-sm text-gray-500">Allow new users to register</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Notification Settings -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Notification Settings</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Email Notifications</p>
                            <p class="text-sm text-gray-500">Send email notifications for important events</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700">SMS Notifications</p>
                            <p class="text-sm text-gray-500">Send SMS notifications for critical alerts</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Admin Profile -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Admin Profile</h2>
                <div class="flex flex-col items-center">
                    <div class="h-16 w-16 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-xl mb-3">
                        <?php echo substr($admin_info['full_name'], 0, 1); ?>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($admin_info['full_name']); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($admin_info['email']); ?></p>
                    <div class="mt-4 w-full">
                        <a href="#" class="block w-full text-center bg-blue-50 text-blue-600 py-2 rounded-lg font-medium hover:bg-blue-100">
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- System Info -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">System Information</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Version</span>
                        <span class="text-sm font-medium text-gray-900">1.0.0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">PHP Version</span>
                        <span class="text-sm font-medium text-gray-900"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Database</span>
                        <span class="text-sm font-medium text-gray-900">MySQL</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Last Backup</span>
                        <span class="text-sm font-medium text-gray-900">Sep 15, 2025</span>
                    </div>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-red-100">
                <h2 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h2>
                <div class="space-y-3">
                    <button class="w-full text-left bg-red-50 text-red-600 py-2 px-3 rounded-lg font-medium hover:bg-red-100">
                        Clear Cache
                    </button>
                    <button class="w-full text-left bg-red-50 text-red-600 py-2 px-3 rounded-lg font-medium hover:bg-red-100">
                        Reset Statistics
                    </button>
                    <button class="w-full text-left bg-red-50 text-red-600 py-2 px-3 rounded-lg font-medium hover:bg-red-100">
                        Delete All Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>