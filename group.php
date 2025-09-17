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
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 bg-white shadow-md z-10">
        <div class="flex justify-between items-center px-4 py-3">
            <div class="flex items-center">
                <a href="index.php" class="mr-3">
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
                <div>
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Profile" class="h-8 w-8 rounded-full border-2 border-blue-500">
                </div>
            </div>
        </div>
    </header>

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
    </script>
</body>
</html>