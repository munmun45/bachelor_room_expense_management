<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Reports - Bachelor Room</title>
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
                <a href="index.php" class="mr-3">
                    <i class="fas fa-arrow-left text-blue-600 text-xl"></i>
                </a>
                <div class="mr-3">
                    <i class="fas fa-wallet text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Kitchen Group</h1>
                    <p class="text-xs text-gray-500">Food Expense Settlement</p>
                </div>
            </div>
            <div class="flex items-center">
                <div class="mr-4 relative">
                    <i class="fas fa-bell text-gray-600 text-xl"></i>
                    <span class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full"></span>
                </div>
                <a href="profile.php">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-user text-blue-600"></i>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pt-20 pb-24 px-4 max-w-md mx-auto">
        <!-- Report Period Selector -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex flex-col space-y-3">
                <h2 class="text-lg font-semibold">🍳 Kitchen Group Monthly Food Expenses</h2>
                
                <!-- Month and Year Filter -->
                <div class="flex justify-between items-center gap-2">
                    <div class="flex items-center">
                        <label for="settlement-month" class="text-sm text-gray-500 mr-2">Month:</label>
                        <select id="settlement-month" class="border rounded px-2 py-1 text-sm">
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <label for="settlement-year" class="text-sm text-gray-500 mr-2">Year:</label>
                        <select id="settlement-year" class="border rounded px-2 py-1 text-sm">
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500">Total Food Expenses</p>
                        <p class="text-xl font-bold total-expense-display-header"><i class="fas fa-rupee-sign"></i><span id="total-expense-amount">3,210.00</span></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Per Person Share</p>
                        <p class="text-xl font-bold text-blue-600"><i class="fas fa-rupee-sign"></i><span id="avg-share-amount">642.00</span></p>
                    </div>
                </div>
                <div class="h-2 w-full bg-gray-200 rounded-full">
                    <div class="h-2 bg-green-600 rounded-full" style="width: 75%"></div>
                </div>
            </div>
        </div>

        <!-- Kitchen Food Settlement Summary -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3">🍽️ Kitchen Food Settlement Summary</h2>
            
            <!-- Food Expense Breakdown -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 mb-4 border border-green-100">
                <h3 class="font-semibold mb-3 text-green-800">
                    <i class="fas fa-utensils mr-2"></i>Food Expense Breakdown
                </h3>
                <div id="expense-breakdown" class="space-y-2 text-sm">
                    <!-- Kitchen bill will be populated by JavaScript -->
                </div>
            </div>

            <!-- Per Day Calculation Display -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 mb-4 border border-blue-100">
                <h3 class="font-semibold mb-3 text-blue-800">
                    <i class="fas fa-calculator mr-2"></i>Per Day Amount Calculation
                </h3>
                <div id="per-day-calculation" class="space-y-2 text-sm">
                    <!-- Per day calculation will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Meal Participation Settings -->
            <div class="mb-4 p-3 bg-orange-50 rounded-lg border border-orange-200">
                <h3 class="font-semibold mb-2 text-sm text-orange-800">
                    <i class="fas fa-calendar-check mr-2"></i>Meal Participation Settings
                </h3>
                <p class="text-xs text-gray-600 mb-2">Adjust for members who didn't eat with the group for entire month</p>
                <div class="grid grid-cols-2 gap-2">
                    <div class="col-span-2">
                        <label class="text-xs text-gray-600 block mb-1">Days in month:</label>
                        <input type="number" id="days-in-month" value="30" min="28" max="31" class="w-full border rounded px-2 py-1 text-sm" />
                    </div>
                </div>
            </div>
            
            <!-- Member Settlement Cards -->
            <div class="space-y-3 mb-4" id="members-list">
                <!-- Member 1 -->
                <div class="member-card p-4 bg-white border-2 border-gray-100 rounded-lg shadow-sm" data-member-id="1">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-3">
                                <span>R</span>
                            </div>
                            <div>
                                <div class="font-semibold text-lg">Rahul</div>
                                <div class="text-sm text-gray-500 flex items-center">
                                    <span class="mr-2">Meal days:</span>
                                    <input type="number" class="days-present w-16 border rounded px-2 py-1 text-center" value="30" min="0" max="31">
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="settlement-amount text-green-600 font-bold text-xl">+₹250.00</div>
                            <div class="text-xs text-gray-500">Net Settlement</div>
                        </div>
                    </div>
                    
                    <!-- Detailed Calculation -->
                    <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-blue-400">
                        <div class="text-xs font-medium text-gray-700 mb-2">
                            <i class="fas fa-chart-line mr-1"></i>Calculation Details
                        </div>
                        <div class="calculation-details space-y-1 text-xs">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Member 2 -->
                <div class="member-card p-4 bg-white border-2 border-gray-100 rounded-lg shadow-sm" data-member-id="2">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mr-3">
                                <span>A</span>
                            </div>
                            <div>
                                <div class="font-semibold text-lg">Amit</div>
                                <div class="text-sm text-gray-500 flex items-center">
                                    <span class="mr-2">Meal days:</span>
                                    <input type="number" class="days-present w-16 border rounded px-2 py-1 text-center" value="25" min="0" max="31">
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="settlement-amount text-red-600 font-bold text-xl">-₹150.00</div>
                            <div class="text-xs text-gray-500">Net Settlement</div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-green-400">
                        <div class="text-xs font-medium text-gray-700 mb-2">
                            <i class="fas fa-chart-line mr-1"></i>Calculation Details
                        </div>
                        <div class="calculation-details space-y-1 text-xs">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Member 3 -->
                <div class="member-card p-4 bg-white border-2 border-gray-100 rounded-lg shadow-sm" data-member-id="3">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-purple-500 flex items-center justify-center text-white font-bold mr-3">
                                <span>S</span>
                            </div>
                            <div>
                                <div class="font-semibold text-lg">Suresh</div>
                                <div class="text-sm text-gray-500 flex items-center">
                                    <span class="mr-2">Meal days:</span>
                                    <input type="number" class="days-present w-16 border rounded px-2 py-1 text-center" value="22" min="0" max="31">
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="settlement-amount text-red-600 font-bold text-xl">-₹100.00</div>
                            <div class="text-xs text-gray-500">Net Settlement</div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-purple-400">
                        <div class="text-xs font-medium text-gray-700 mb-2">
                            <i class="fas fa-chart-line mr-1"></i>Calculation Details
                        </div>
                        <div class="calculation-details space-y-1 text-xs">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Member 4 -->
                <div class="member-card p-4 bg-white border-2 border-gray-100 rounded-lg shadow-sm" data-member-id="4">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-yellow-500 flex items-center justify-center text-white font-bold mr-3">
                                <span>V</span>
                            </div>
                            <div>
                                <div class="font-semibold text-lg">Vijay</div>
                                <div class="text-sm text-gray-500 flex items-center">
                                    <span class="mr-2">Meal days:</span>
                                    <input type="number" class="days-present w-16 border rounded px-2 py-1 text-center" value="28" min="0" max="31">
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="settlement-amount text-green-600 font-bold text-xl">+₹50.00</div>
                            <div class="text-xs text-gray-500">Net Settlement</div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-yellow-400">
                        <div class="text-xs font-medium text-gray-700 mb-2">
                            <i class="fas fa-chart-line mr-1"></i>Calculation Details
                        </div>
                        <div class="calculation-details space-y-1 text-xs">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Member 5 -->
                <div class="member-card p-4 bg-white border-2 border-gray-100 rounded-lg shadow-sm" data-member-id="5">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-red-500 flex items-center justify-center text-white font-bold mr-3">
                                <span>P</span>
                            </div>
                            <div>
                                <div class="font-semibold text-lg">Prakash</div>
                                <div class="text-sm text-gray-500 flex items-center">
                                    <span class="mr-2">Meal days:</span>
                                    <input type="number" class="days-present w-16 border rounded px-2 py-1 text-center" value="27" min="0" max="31">
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="settlement-amount text-red-600 font-bold text-xl">-₹50.00</div>
                            <div class="text-xs text-gray-500">Net Settlement</div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3 border-l-4 border-red-400">
                        <div class="text-xs font-medium text-gray-700 mb-2">
                            <i class="fas fa-chart-line mr-1"></i>Calculation Details
                        </div>
                        <div class="calculation-details space-y-1 text-xs">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary Verification -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-4 mb-4 border">
                <h3 class="font-semibold mb-3 text-gray-800">
                    <i class="fas fa-balance-scale mr-2"></i>Balance Verification
                </h3>
                <div id="balance-summary" class="grid grid-cols-2 gap-4 text-sm">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <div class="flex justify-end mb-4">
                <button id="recalculate-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 shadow-md">
                    <i class="fas fa-sync-alt mr-2"></i>Recalculate Settlement
                </button>
            </div>

            <!-- Who pays whom section -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                <h3 class="font-semibold mb-3 text-green-800">
                    <i class="fas fa-exchange-alt mr-2"></i>Payment Instructions
                </h3>
                <div id="payment-instructions" class="space-y-3">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Settlement Date -->
            <div class="mt-4 text-center text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                <i class="fas fa-calendar mr-2"></i>
                Settlement Date: <span class="font-semibold settlement-date">September 30, 2025</span>
            </div>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Member data with FOOD/KITCHEN expenses only
            const memberData = [
                { 
                    id: 1, 
                    name: 'Rahul', 
                    avatar: 'R',
                    color: 'blue',
                    totalSpent: 850.00
                },
                { 
                    id: 2, 
                    name: 'Amit', 
                    avatar: 'A',
                    color: 'green',
                    totalSpent: 620.00
                },
                { 
                    id: 3, 
                    name: 'Suresh', 
                    avatar: 'S',
                    color: 'purple',
                    totalSpent: 720.00
                },
                { 
                    id: 4, 
                    name: 'Vijay', 
                    avatar: 'V',
                    color: 'yellow',
                    totalSpent: 540.00
                },
                { 
                    id: 5, 
                    name: 'Prakash', 
                    avatar: 'P',
                    color: 'red',
                    totalSpent: 480.00
                }
            ];

            const settlementMonth = document.getElementById('settlement-month');
            const settlementYear = document.getElementById('settlement-year');
            const daysInMonthInput = document.getElementById('days-in-month');
            const recalculateBtn = document.getElementById('recalculate-btn');
            
            // Set current date
            const currentDate = new Date();
            const currentMonth = (currentDate.getMonth() + 1).toString().padStart(2, '0');
            const currentYear = currentDate.getFullYear().toString();
            
            if (settlementMonth && settlementYear) {
                settlementMonth.value = currentMonth;
                settlementYear.value = currentYear;
                settlementMonth.addEventListener('change', updateSettlementData);
                settlementYear.addEventListener('change', updateSettlementData);
            }
            
            function updateExpenseBreakdown() {
                const breakdownEl = document.getElementById('expense-breakdown');
                if (!breakdownEl) return;
                
                // Calculate total expense
                const totalExpense = memberData.reduce((sum, member) => sum + member.totalSpent, 0);
                
                // Simple breakdown showing just kitchen bill
                const breakdownHTML = `
                    <div class="calculation-row flex justify-between items-center bg-blue-50 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-utensils text-blue-800 mr-2"></i>
                            <span class="font-semibold text-blue-800">Kitchen Bill</span>
                        </div>
                        <div class="font-bold text-blue-800">₹${totalExpense.toFixed(2)}</div>
                    </div>
                `;
                
                breakdownEl.innerHTML = breakdownHTML;
                
                // Update header totals
                document.getElementById('total-expense-amount').textContent = totalExpense.toFixed(2);
                document.getElementById('avg-share-amount').textContent = (totalExpense / memberData.length).toFixed(2);
            }

            function updatePerDayCalculation() {
                const perDayCalculationEl = document.getElementById('per-day-calculation');
                if (!perDayCalculationEl) return;

                const totalDaysInMonth = parseInt(daysInMonthInput.value) || 30;
                
                // Get current member attendance
                const totalDaysPresent = memberData.reduce((sum, member) => {
                    const memberEl = document.querySelector(`div[data-member-id="${member.id}"]`);
                    const daysPresent = parseInt(memberEl.querySelector('.days-present').value) || 0;
                    return sum + daysPresent;
                }, 0);
                
                const totalExpense = memberData.reduce((sum, member) => sum + member.totalSpent, 0);
                const perDayAmount = totalDaysPresent > 0 ? totalExpense / totalDaysPresent : 0;
                
                let calculationHTML = `
                    <div class="calculation-row flex justify-between">
                        <span>Total Kitchen Bill:</span>
                        <span class="font-semibold text-blue-600">₹${totalExpense.toFixed(2)}</span>
                    </div>
                    <div class="calculation-row flex justify-between">
                        <span>Total Days in Month:</span>
                        <span class="font-semibold">${totalDaysInMonth} days</span>
                    </div>
                `;

                // Show each member's days present
                memberData.forEach(member => {
                    const memberEl = document.querySelector(`div[data-member-id="${member.id}"]`);
                    const daysPresent = parseInt(memberEl.querySelector('.days-present').value) || 0;
                    calculationHTML += `
                        <div class="calculation-row flex justify-between text-xs">
                            <span class="ml-4">${member.name} present:</span>
                            <span>${daysPresent} days</span>
                        </div>
                    `;
                });

                calculationHTML += `
                    <div class="calculation-row flex justify-between border-t pt-2">
                        <span class="font-semibold">Total Days Present (All Members):</span>
                        <span class="font-bold text-orange-600">${totalDaysPresent} days</span>
                    </div>
                    <div class="calculation-row flex justify-between bg-orange-50 rounded px-2 py-1">
                        <span class="font-semibold text-orange-800">Per Day Amount:</span>
                        <div class="text-right">
                            <div class="font-bold text-orange-800">₹${perDayAmount.toFixed(2)} per day</div>
                            <div class="text-xs text-orange-600">₹${totalExpense.toFixed(2)} ÷ ${totalDaysPresent} days</div>
                        </div>
                    </div>
                `;

                perDayCalculationEl.innerHTML = calculationHTML;
            }
            
            function calculateSettlement() {
                const totalDaysInMonth = parseInt(daysInMonthInput.value) || 30;
                
                // Get member attendance
                const members = memberData.map(member => {
                    const memberEl = document.querySelector(`div[data-member-id="${member.id}"]`);
                    const daysPresent = parseInt(memberEl.querySelector('.days-present').value) || 0;
                    
                    return {
                        ...member,
                        daysPresent: daysPresent,
                        attendanceRatio: daysPresent / totalDaysInMonth
                    };
                });
                
                // Calculate total expense and per-day amounts
                const totalExpense = members.reduce((sum, member) => sum + member.totalSpent, 0);
                const totalDaysPresent = members.reduce((sum, member) => sum + member.daysPresent, 0);
                const perDayAmount = totalDaysPresent > 0 ? totalExpense / totalDaysPresent : 0;
                
                let positiveTotal = 0;
                let negativeTotal = 0;
                
                // Calculate each member's settlement
                members.forEach(member => {
                    const fairShareBasedOnAttendance = perDayAmount * member.daysPresent;
                    const actualSpent = member.totalSpent;
                    const netSettlement = actualSpent - fairShareBasedOnAttendance;
                    
                    // Track totals
                    if (netSettlement > 0) {
                        positiveTotal += netSettlement;
                    } else {
                        negativeTotal += Math.abs(netSettlement);
                    }
                    
                    // Update member card
                    const memberEl = document.querySelector(`div[data-member-id="${member.id}"]`);
                    const amountEl = memberEl.querySelector('.settlement-amount');
                    const calculationDetails = memberEl.querySelector('.calculation-details');
                    
                    // Update settlement amount display
                    if (netSettlement >= 0) {
                        amountEl.textContent = `+₹${netSettlement.toFixed(2)}`;
                        amountEl.className = 'settlement-amount text-green-600 font-bold text-xl';
                    } else {
                        amountEl.textContent = `-₹${Math.abs(netSettlement).toFixed(2)}`;
                        amountEl.className = 'settlement-amount text-red-600 font-bold text-xl';
                    }
                    
                    // Update calculation details - showing clear per-day calculation
                    let detailsHTML = `
                        <div class="calculation-row flex justify-between">
                            <span>Total spent by ${member.name}:</span>
                            <span class="font-semibold text-blue-600">₹${actualSpent.toFixed(2)}</span>
                        </div>
                        <div class="calculation-row flex justify-between">
                            <span>Days ${member.name} was present:</span>
                            <span class="font-semibold">${member.daysPresent} days</span>
                        </div>
                        <div class="calculation-row flex justify-between">
                            <span>Per day amount (for all):</span>
                            <span class="font-semibold text-orange-600">₹${perDayAmount.toFixed(2)}/day</span>
                        </div>
                        <div class="calculation-row flex justify-between">
                            <span>${member.name}'s fair share:</span>
                            <span class="text-purple-600">₹${perDayAmount.toFixed(2)} × ${member.daysPresent} = ₹${fairShareBasedOnAttendance.toFixed(2)}</span>
                        </div>
                        <div class="calculation-row flex justify-between">
                            <span class="font-semibold">Net Settlement:</span>
                            <div class="text-right">
                                <div class="font-bold ${netSettlement >= 0 ? 'text-green-600' : 'text-red-600'}">
                                    ${netSettlement >= 0 ? '+' : ''}₹${netSettlement.toFixed(2)}
                                </div>
                                <div class="text-xs text-gray-500">
                                    ₹${actualSpent.toFixed(2)} - ₹${fairShareBasedOnAttendance.toFixed(2)}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    calculationDetails.innerHTML = detailsHTML;
                });
                
                // Update per-day calculation display
                updatePerDayCalculation();
                
                // Update balance summary
                updateBalanceSummary(positiveTotal, negativeTotal, totalExpense, perDayAmount, totalDaysPresent);
                
                // Update payment instructions
                updatePaymentInstructions(members);
            }
            
            function updateBalanceSummary(positiveTotal, negativeTotal, totalExpense, perDayAmount, totalDaysPresent) {
                const balanceSummary = document.getElementById('balance-summary');
                if (!balanceSummary) return;
                
                const balanceDiff = Math.abs(positiveTotal - negativeTotal);
                const isBalanced = balanceDiff < 0.01;
                
                balanceSummary.innerHTML = `
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Total Food Expense:</span>
                            <span class="font-semibold">₹${totalExpense.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Per Day Food Cost:</span>
                            <span class="font-semibold">₹${perDayAmount.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Meal Days:</span>
                            <span class="font-semibold">${totalDaysPresent} days</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-green-600">To Receive (+):</span>
                            <span class="font-semibold text-green-600">₹${positiveTotal.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-600">To Pay (-):</span>
                            <span class="font-semibold text-red-600">₹${negativeTotal.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t">
                            <span class="font-semibold">Balance:</span>
                            <span class="font-bold ${isBalanced ? 'text-green-600' : 'text-yellow-600'}">
                                ${isBalanced ? '✓ Balanced' : `₹${balanceDiff.toFixed(2)} diff`}
                            </span>
                        </div>
                    </div>
                `;
            }
            
            function updatePaymentInstructions(members) {
                const paymentInstructions = document.getElementById('payment-instructions');
                if (!paymentInstructions) return;
                
                // Simple debt settlement algorithm
                const creditors = members.filter(m => {
                    const memberEl = document.querySelector(`div[data-member-id="${m.id}"]`);
                    const settlementText = memberEl.querySelector('.settlement-amount').textContent;
                    return settlementText.startsWith('+');
                }).map(m => {
                    const memberEl = document.querySelector(`div[data-member-id="${m.id}"]`);
                    const amount = parseFloat(memberEl.querySelector('.settlement-amount').textContent.replace(/[+₹]/g, ''));
                    return { ...m, amount: amount };
                });
                
                const debtors = members.filter(m => {
                    const memberEl = document.querySelector(`div[data-member-id="${m.id}"]`);
                    const settlementText = memberEl.querySelector('.settlement-amount').textContent;
                    return settlementText.startsWith('-');
                }).map(m => {
                    const memberEl = document.querySelector(`div[data-member-id="${m.id}"]`);
                    const amount = parseFloat(memberEl.querySelector('.settlement-amount').textContent.replace(/[-₹]/g, ''));
                    return { ...m, amount: amount };
                });
                
                let instructionsHTML = '';
                
                if (creditors.length === 0 && debtors.length === 0) {
                    instructionsHTML = `
                        <div class="text-center py-4 text-green-600">
                            <i class="fas fa-check-circle text-2xl mb-2"></i>
                            <div class="font-semibold">All Settled!</div>
                            <div class="text-sm">No payments required this month.</div>
                        </div>
                    `;
                } else {
                    // Sort by amount for better settlement optimization
                    creditors.sort((a, b) => b.amount - a.amount);
                    debtors.sort((a, b) => b.amount - a.amount);
                    
                    let i = 0, j = 0;
                    while (i < creditors.length && j < debtors.length) {
                        const creditor = creditors[i];
                        const debtor = debtors[j];
                        const paymentAmount = Math.min(creditor.amount, debtor.amount);
                        
                        if (paymentAmount > 0.01) { // Only show payments above 1 paisa
                            instructionsHTML += `
                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-green-200 shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                            <span class="text-red-600 text-xs font-bold">${debtor.avatar}</span>
                                        </div>
                                        <i class="fas fa-arrow-right text-gray-400"></i>
                                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                            <span class="text-green-600 text-xs font-bold">${creditor.avatar}</span>
                                        </div>
                                        <div class="text-sm">
                                            <div class="font-medium">${debtor.name} pays ${creditor.name}</div>
                                            <div class="text-xs text-gray-500">Settlement payment</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-lg text-green-600">₹${paymentAmount.toFixed(2)}</div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Update amounts
                        creditor.amount -= paymentAmount;
                        debtor.amount -= paymentAmount;
                        
                        // Move to next creditor/debtor if current one is settled
                        if (creditor.amount < 0.01) i++;
                        if (debtor.amount < 0.01) j++;
                    }
                }
                
                if (instructionsHTML === '') {
                    instructionsHTML = `
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-info-circle text-2xl mb-2"></i>
                            <div>No payments required</div>
                        </div>
                    `;
                }
                
                paymentInstructions.innerHTML = instructionsHTML;
            }
            
            function updateSettlementData() {
                if (!settlementMonth || !settlementYear) return;
                
                const selectedMonth = settlementMonth.value;
                const selectedYear = settlementYear.value;
                
                // Update days in month
                const selectedMonthInt = parseInt(selectedMonth);
                const selectedYearInt = parseInt(selectedYear);
                const lastDay = new Date(selectedYearInt, selectedMonthInt, 0).getDate();
                daysInMonthInput.value = lastDay;
                daysInMonthInput.max = lastDay;
                
                // Update settlement date display
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                                   'July', 'August', 'September', 'October', 'November', 'December'];
                const monthIndex = selectedMonthInt - 1;
                
                const settlementDateEl = document.querySelector('.settlement-date');
                if (settlementDateEl) {
                    settlementDateEl.textContent = `${monthNames[monthIndex]} ${lastDay}, ${selectedYear}`;
                }
                
                // Recalculate with new month data
                calculateSettlement();
            }
            
            // Event listeners
            if (recalculateBtn) {
                recalculateBtn.addEventListener('click', calculateSettlement);
            }
            
            // Add event listeners for days present inputs
            document.querySelectorAll('.days-present').forEach(input => {
                input.addEventListener('change', function() {
                    const max = parseInt(daysInMonthInput.value) || 30;
                    if (parseInt(this.value) > max) {
                        this.value = max;
                    } else if (parseInt(this.value) < 0 || isNaN(parseInt(this.value))) {
                        this.value = 0;
                    }
                    calculateSettlement(); // Auto-recalculate on change
                });
            });
            
            // Add event listener for days in month
            if (daysInMonthInput) {
                daysInMonthInput.addEventListener('change', function() {
                    // Update max values for all days-present inputs
                    document.querySelectorAll('.days-present').forEach(input => {
                        input.max = this.value;
                        if (parseInt(input.value) > parseInt(this.value)) {
                            input.value = this.value;
                        }
                    });
                    calculateSettlement();
                });
            }
            
            // Initialize
            updateSettlementData();
            updateExpenseBreakdown();
            updatePerDayCalculation();
            calculateSettlement();
        });
    </script>
</body>
</html>