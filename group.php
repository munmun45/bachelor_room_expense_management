<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Group Chat - Bachelor Room</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }
        .member-card {
            transition: all 0.3s ease;
        }
        .member-card.temp-leave {
            opacity: 0.6;
            border-left: 3px solid #f97316;
        }
    </style>
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
                <a href="#" id="membersBtn" class="mr-4 relative">
                    <i class="fas fa-users text-gray-600 text-xl"></i>
                </a>
                <div class="mr-4 relative">
                    <i class="fas fa-bell text-gray-600 text-xl"></i>
                    <span class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full"></span>
                </div>
                <div>
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Profile" class="h-8 w-8 rounded-full border-2 border-blue-500">
                </div>
            </div>
        </div>
    </header>

    <!-- Members Modal -->
    <div id="membersModal" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4 max-h-[80vh] overflow-y-auto">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">Room Members</h2>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 space-y-4" id="membersList">
                <!-- Member 1 -->
                <div class="member-card bg-white p-3 rounded-lg shadow-sm border border-gray-200 flex justify-between items-center" data-member-id="1">
                    <div class="flex items-center">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" class="h-10 w-10 rounded-full mr-3" />
                        <div>
                            <p class="font-medium">Rahul</p>
                            <p class="text-xs text-gray-500">Active</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button class="temp-leave-btn px-3 py-1 text-sm rounded-full bg-gray-200 hover:bg-orange-200 text-gray-700 hover:text-orange-700 mr-2">
                            <i class="fas fa-sign-out-alt mr-1"></i> Temp Leave
                        </button>
                    </div>
                </div>
                
                <!-- Member 2 -->
                <div class="member-card bg-white p-3 rounded-lg shadow-sm border border-gray-200 flex justify-between items-center" data-member-id="2">
                    <div class="flex items-center">
                        <img src="https://randomuser.me/api/portraits/women/45.jpg" class="h-10 w-10 rounded-full mr-3" />
                        <div>
                            <p class="font-medium">Priya</p>
                            <p class="text-xs text-gray-500">Active</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button class="temp-leave-btn px-3 py-1 text-sm rounded-full bg-gray-200 hover:bg-orange-200 text-gray-700 hover:text-orange-700 mr-2">
                            <i class="fas fa-sign-out-alt mr-1"></i> Temp Leave
                        </button>
                    </div>
                </div>
                
                <!-- Member 3 -->
                <div class="member-card bg-white p-3 rounded-lg shadow-sm border border-gray-200 flex justify-between items-center" data-member-id="3">
                    <div class="flex items-center">
                        <img src="https://randomuser.me/api/portraits/men/67.jpg" class="h-10 w-10 rounded-full mr-3" />
                        <div>
                            <p class="font-medium">Amit</p>
                            <p class="text-xs text-gray-500">Active</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button class="temp-leave-btn px-3 py-1 text-sm rounded-full bg-gray-200 hover:bg-orange-200 text-gray-700 hover:text-orange-700 mr-2">
                            <i class="fas fa-sign-out-alt mr-1"></i> Temp Leave
                        </button>
                    </div>
                </div>
                
                <!-- Member 4 -->
                <div class="member-card bg-white p-3 rounded-lg shadow-sm border border-gray-200 flex justify-between items-center" data-member-id="4">
                    <div class="flex items-center">
                        <img src="https://randomuser.me/api/portraits/women/22.jpg" class="h-10 w-10 rounded-full mr-3" />
                        <div>
                            <p class="font-medium">Neha</p>
                            <p class="text-xs text-gray-500">Active</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button class="temp-leave-btn px-3 py-1 text-sm rounded-full bg-gray-200 hover:bg-orange-200 text-gray-700 hover:text-orange-700 mr-2">
                            <i class="fas fa-sign-out-alt mr-1"></i> Temp Leave
                        </button>
                    </div>
                </div>
                
                <!-- Member 5 -->
                <div class="member-card bg-white p-3 rounded-lg shadow-sm border border-gray-200 flex justify-between items-center" data-member-id="5">
                    <div class="flex items-center">
                        <img src="https://randomuser.me/api/portraits/men/55.jpg" class="h-10 w-10 rounded-full mr-3" />
                        <div>
                            <p class="font-medium">Vikram</p>
                            <p class="text-xs text-gray-500">Active</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button class="temp-leave-btn px-3 py-1 text-sm rounded-full bg-gray-200 hover:bg-orange-200 text-gray-700 hover:text-orange-700 mr-2">
                            <i class="fas fa-sign-out-alt mr-1"></i> Temp Leave
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-4 border-t border-gray-200">
                <a href="report.php" class="block w-full bg-blue-600 text-white text-center py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-calculator mr-2"></i>View Settlement
                </a>
            </div>
        </div>
    </div>
    
    <!-- Temporary Leave Modal -->
    <div id="tempLeaveModal" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-sm mx-4">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">Temporary Leave</h2>
            </div>
            <div class="p-4">
                <p class="mb-4">Set temporary leave details for <span id="memberName" class="font-semibold">Member</span></p>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Leave Start Date</label>
                    <input type="date" id="leaveStartDate" class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Return Date</label>
                    <input type="date" id="leaveEndDate" class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                    <textarea id="leaveReason" class="w-full border border-gray-300 rounded-md px-3 py-2" rows="2"></textarea>
                </div>
            </div>
            <div class="p-4 border-t border-gray-200 flex justify-end space-x-3">
                <button id="cancelLeave" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                <button id="confirmLeave" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Mark as Left</button>
            </div>
        </div>
    </div>
    
    <!-- Chat Content -->
    <main class="pt-20 pb-20 px-4 max-w-md mx-auto">
        <!-- Expense Message -->
        <div class="space-y-6">
            <div class="flex items-start space-x-3">
                <img
                    src="https://randomuser.me/api/portraits/men/32.jpg"
                    class="h-8 w-8 rounded-full"
                />
                <div class="bg-white p-3 rounded-lg shadow w-full">
                    <div class="flex justify-between mb-1 text-sm text-gray-500">
                        <span>Rahul</span><span>May 15, 10:24 AM</span>
                    </div>
                    <p class="font-medium mb-2">Grocery shopping for dinner</p>
                    <p class="text-blue-600 font-semibold">
                        <i class="fas fa-rupee-sign"></i> 780
                    </p>
                </div>
            </div>

            <div class="flex items-start space-x-3">
                <img
                    src="https://randomuser.me/api/portraits/women/45.jpg"
                    class="h-8 w-8 rounded-full"
                />
                <div class="bg-white p-3 rounded-lg shadow w-full">
                    <div class="flex justify-between mb-1 text-sm text-gray-500">
                        <span>Priya</span><span>May 15, 5:40 PM</span>
                    </div>
                    <p class="font-medium mb-2">Gas cylinder refill</p>
                    <p class="text-blue-600 font-semibold">
                        <i class="fas fa-rupee-sign"></i> 1100
                    </p>
                    <img
                        src="https://images.unsplash.com/photo-1581091870627-3d84637eaf50?auto=format&fit=crop&w=200&q=60"
                        class="mt-2 rounded"
                    />
                </div>
            </div>
        </div>
    </main>

    <!-- Input Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white p-3 shadow-inner">
        <form id="expenseForm" class="flex items-center space-x-2 w-full">
            <!-- Attachment Button -->
            <label class="cursor-pointer bg-gray-200 h-10 w-10 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-300" title="Add attachment">
                <i class="fas fa-paperclip"></i>
                <input type="file" class="hidden" name="attachment" />
            </label>

            <!-- Amount (required) -->
            <input type="number" step="0.01" placeholder="₹" required name="amount" class=" grow border border-gray-300 rounded-full py-2 px-3 text-sm focus:outline-none" />

            <!-- Note Toggle Button -->
            <button type="button" id="toggleNote" class="bg-gray-200 h-10 w-10 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-300" title="Add note">
                <i class="fas fa-sticky-note"></i>
            </button>

            <!-- Send Button -->
            <button type="submit" class="bg-blue-600 h-10 w-10 flex items-center justify-center rounded-full text-white hover:bg-blue-700" title="Send">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>

        <!-- Hidden Note Input -->
        <div id="noteContainer" class="mt-2 hidden">
            <textarea name="note" rows="2" placeholder="Enter note (optional)" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:outline-none"></textarea>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggleNote');
        const noteContainer = document.getElementById('noteContainer');
        const membersBtn = document.getElementById('membersBtn');
        const membersModal = document.getElementById('membersModal');
        const closeModal = document.getElementById('closeModal');
        const tempLeaveModal = document.getElementById('tempLeaveModal');
        const cancelLeave = document.getElementById('cancelLeave');
        const confirmLeave = document.getElementById('confirmLeave');
        const tempLeaveButtons = document.querySelectorAll('.temp-leave-btn');
        
        let currentMemberId = null;
        let memberLeaveStatus = {};

        // Toggle note input
        toggleBtn.addEventListener('click', () => {
            noteContainer.classList.toggle('hidden');
        });

        // Prevent empty amount submission feedback (basic)
        document.getElementById('expenseForm').addEventListener('submit', function (e) {
            const amountField = this.elements['amount'];
            if (!amountField.value) {
                amountField.focus();
                e.preventDefault();
            }
        });
        
        // Open members modal
        membersBtn.addEventListener('click', (e) => {
            e.preventDefault();
            membersModal.classList.remove('hidden');
        });
        
        // Close members modal
        closeModal.addEventListener('click', () => {
            membersModal.classList.add('hidden');
        });
        
        // Close modal when clicking outside
        membersModal.addEventListener('click', (e) => {
            if (e.target === membersModal) {
                membersModal.classList.add('hidden');
            }
        });
        
        // Handle temporary leave buttons
        tempLeaveButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const memberCard = btn.closest('.member-card');
                const memberId = memberCard.getAttribute('data-member-id');
                const memberNameEl = memberCard.querySelector('.font-medium');
                const memberName = memberNameEl ? memberNameEl.textContent : 'Member';
                
                // Set current member being processed
                currentMemberId = memberId;
                document.getElementById('memberName').textContent = memberName;
                
                // Set default dates
                const today = new Date();
                const todayStr = today.toISOString().split('T')[0];
                
                const nextMonth = new Date(today);
                nextMonth.setDate(today.getDate() + 30);
                const nextMonthStr = nextMonth.toISOString().split('T')[0];
                
                document.getElementById('leaveStartDate').value = todayStr;
                document.getElementById('leaveEndDate').value = nextMonthStr;
                
                // Show temporary leave modal
                tempLeaveModal.classList.remove('hidden');
            });
        });
        
        // Cancel leave modal
        cancelLeave.addEventListener('click', () => {
            tempLeaveModal.classList.add('hidden');
            currentMemberId = null;
        });
        
        // Close temp leave modal when clicking outside
        tempLeaveModal.addEventListener('click', (e) => {
            if (e.target === tempLeaveModal) {
                tempLeaveModal.classList.add('hidden');
                currentMemberId = null;
            }
        });
        
        // Confirm temporary leave
        confirmLeave.addEventListener('click', () => {
            if (!currentMemberId) return;
            
            const startDate = document.getElementById('leaveStartDate').value;
            const endDate = document.getElementById('leaveEndDate').value;
            const reason = document.getElementById('leaveReason').value;
            
            // Store leave information
            memberLeaveStatus[currentMemberId] = {
                isOnLeave: true,
                startDate: startDate,
                endDate: endDate,
                reason: reason
            };
            
            // Update UI
            const memberCard = document.querySelector(`.member-card[data-member-id="${currentMemberId}"]`);
            if (memberCard) {
                memberCard.classList.add('temp-leave');
                const statusEl = memberCard.querySelector('.text-xs.text-gray-500');
                if (statusEl) {
                    statusEl.textContent = 'Temporarily Left';
                    statusEl.classList.remove('text-gray-500');
                    statusEl.classList.add('text-orange-500');
                }
                
                const leaveBtn = memberCard.querySelector('.temp-leave-btn');
                if (leaveBtn) {
                    leaveBtn.textContent = 'Return';
                    leaveBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-1"></i> Return';
                    leaveBtn.classList.remove('bg-gray-200', 'hover:bg-orange-200', 'text-gray-700', 'hover:text-orange-700');
                    leaveBtn.classList.add('bg-green-200', 'hover:bg-green-300', 'text-green-700', 'hover:text-green-800');
                }
            }
            
            // Close modal
            tempLeaveModal.classList.add('hidden');
            currentMemberId = null;
            
            // Save to localStorage for persistence
            localStorage.setItem('memberLeaveStatus', JSON.stringify(memberLeaveStatus));
        });
        
        // Load saved leave status on page load
        document.addEventListener('DOMContentLoaded', () => {
            const savedStatus = localStorage.getItem('memberLeaveStatus');
            if (savedStatus) {
                memberLeaveStatus = JSON.parse(savedStatus);
                
                // Apply saved status to UI
                for (const memberId in memberLeaveStatus) {
                    if (memberLeaveStatus[memberId].isOnLeave) {
                        const memberCard = document.querySelector(`.member-card[data-member-id="${memberId}"]`);
                        if (memberCard) {
                            memberCard.classList.add('temp-leave');
                            const statusEl = memberCard.querySelector('.text-xs.text-gray-500');
                            if (statusEl) {
                                statusEl.textContent = 'Temporarily Left';
                                statusEl.classList.remove('text-gray-500');
                                statusEl.classList.add('text-orange-500');
                            }
                            
                            const leaveBtn = memberCard.querySelector('.temp-leave-btn');
                            if (leaveBtn) {
                                leaveBtn.textContent = 'Return';
                                leaveBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-1"></i> Return';
                                leaveBtn.classList.remove('bg-gray-200', 'hover:bg-orange-200', 'text-gray-700', 'hover:text-orange-700');
                                leaveBtn.classList.add('bg-green-200', 'hover:bg-green-300', 'text-green-700', 'hover:text-green-800');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>