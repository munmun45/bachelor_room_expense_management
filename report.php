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
        .bottom-nav {
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        .add-expense-btn {
            transform: translateY(-50%);
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.5);
        }
        .chart-container {
            height: 200px;
            position: relative;
        }
        .bar {
            position: absolute;
            bottom: 0;
            width: 12%;
            background-color: #3b82f6;
            border-radius: 6px 6px 0 0;
            transition: height 0.3s ease;
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
                    <p class="text-xs text-gray-500">Expense Reports</p>
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
        <!-- Report Period Selector with Filters -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <div class="flex flex-col space-y-3">
                <h2 class="text-lg font-semibold">Monthly Overview</h2>
                
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
                        <p class="text-sm text-gray-500">Total Expenses</p>
                        <p class="text-xl font-bold"><i class="fas fa-rupee-sign"></i>1,250.00</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Your Share</p>
                        <p class="text-xl font-bold text-blue-600"><i class="fas fa-rupee-sign"></i>312.50</p>
                    </div>
                </div>
                <div class="h-2 w-full bg-gray-200 rounded-full">
                    <div class="h-2 bg-blue-600 rounded-full" style="width: 65%"></div>
                </div>
            </div>
        </div>

        

        <!-- Settlement Summary (compact design) -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3">Settlement Summary</h2>
            
            <!-- Compact card layout -->
            <div class="space-y-2 mb-4">
                <!-- Member 1 -->
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2.5">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-2">
                            <span class="text-green-600 font-bold">A</span>
                        </div>
                        <span class="font-medium">Alice</span>
                    </div>
                    <div class="text-green-600 font-semibold">+₹250.00</div>
                </div>
                
                <!-- Member 2 -->
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2.5">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-2">
                            <span class="text-red-600 font-bold">B</span>
                        </div>
                        <span class="font-medium">Bob</span>
                    </div>
                    <div class="text-red-600 font-semibold">-₹150.00</div>
                </div>
                
                <!-- Member 3 -->
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2.5">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-2">
                            <span class="text-red-600 font-bold">C</span>
                        </div>
                        <span class="font-medium">Charlie</span>
                    </div>
                    <div class="text-red-600 font-semibold">-₹100.00</div>
                </div>
                
                <!-- Member 4 -->
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2.5">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-2">
                            <span class="text-green-600 font-bold">D</span>
                        </div>
                        <span class="font-medium">David</span>
                    </div>
                    <div class="text-green-600 font-semibold">+₹50.00</div>
                </div>
                
                <!-- Member 5 -->
                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2.5">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-2">
                            <span class="text-red-600 font-bold">E</span>
                        </div>
                        <span class="font-medium">Eva</span>
                    </div>
                    <div class="text-red-600 font-semibold">-₹50.00</div>
                </div>
            </div>

            <!-- Who pays whom section (compact) -->
            <h3 class="font-semibold mb-2">Who Pays Whom</h3>
            <div class="bg-blue-50 rounded-lg p-3">
                <div class="space-y-2">
                    <!-- Payment 1 -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mr-1">
                                <span class="text-red-600 text-xs font-bold">B</span>
                            </div>
                            <span class="text-sm">pays</span>
                            <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center mx-1">
                                <span class="text-green-600 text-xs font-bold">A</span>
                            </div>
                        </div>
                        <span class="font-semibold text-sm">₹150.00</span>
                    </div>
                    
                    <!-- Payment 2 -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mr-1">
                                <span class="text-red-600 text-xs font-bold">C</span>
                            </div>
                            <span class="text-sm">pays</span>
                            <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center mx-1">
                                <span class="text-green-600 text-xs font-bold">A</span>
                            </div>
                        </div>
                        <span class="font-semibold text-sm">₹100.00</span>
                    </div>
                    
                    <!-- Payment 3 -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center mr-1">
                                <span class="text-red-600 text-xs font-bold">E</span>
                            </div>
                            <span class="text-sm">pays</span>
                            <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center mx-1">
                                <span class="text-green-600 text-xs font-bold">D</span>
                            </div>
                        </div>
                        <span class="font-semibold text-sm">₹50.00</span>
                    </div>
                </div>
            </div>
            
            <!-- Settlement Date -->
            <div class="mt-3 text-right text-sm text-gray-500">
                Settlement Date: <span class="font-medium">June 30, 2023</span>
            </div>
        </div>



        <!-- Expense Chart -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3">Monthly Breakdown</h2>
            <div class="chart-container">
                <!-- Bars for the chart -->
                <div class="bar" style="left: 5%; height: 60%;"></div>
                <div class="bar" style="left: 20%; height: 80%;"></div>
                <div class="bar" style="left: 35%; height: 40%;"></div>
                <div class="bar" style="left: 50%; height: 90%;"></div>
                <div class="bar" style="left: 65%; height: 70%;"></div>
                <div class="bar" style="left: 80%; height: 50%;"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-2">
                <span>Jan</span>
                <span>Feb</span>
                <span>Mar</span>
                <span>Apr</span>
                <span>May</span>
                <span>Jun</span>
            </div>
        </div>








        <!-- Export Options -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-4">
            <h2 class="text-lg font-semibold mb-3">Export Report</h2>
            <div class="flex space-x-2">
                <button class="flex-1 py-2 bg-blue-600 text-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </button>
                <button class="flex-1 py-2 bg-green-600 text-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-excel mr-2"></i> Excel
                </button>
            </div>
        </div>
    </main>

    <!-- No bottom navigation bar as requested -->
    
    <!-- JavaScript for Month Filter -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default month and year to current month and year
            const currentDate = new Date();
            const currentMonth = (currentDate.getMonth() + 1).toString().padStart(2, '0'); // +1 because getMonth() returns 0-11
            const currentYear = currentDate.getFullYear().toString();
            
            // Set the default selected options for settlement filters
            const settlementMonth = document.getElementById('settlement-month');
            const settlementYear = document.getElementById('settlement-year');
            
            if (settlementMonth && settlementYear) {
                settlementMonth.value = currentMonth;
                settlementYear.value = currentYear;
                
                // Add event listeners for settlement filters
                settlementMonth.addEventListener('change', updateSettlementData);
                settlementYear.addEventListener('change', updateSettlementData);
            }
            
            // Function to update settlement data based on selected month and year
            function updateSettlementData() {
                if (!settlementMonth || !settlementYear) return;
                
                const selectedMonth = settlementMonth.value;
                const selectedYear = settlementYear.value;
                
                // Demo: Update settlement date display
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                                   'July', 'August', 'September', 'October', 'November', 'December'];
                const monthIndex = parseInt(selectedMonth) - 1;
                const lastDay = new Date(parseInt(selectedYear), monthIndex + 1, 0).getDate();
                
                const settlementDateEl = document.querySelector('.mt-3.text-right.text-sm.text-gray-500 span');
                if (settlementDateEl) {
                    settlementDateEl.textContent = `${monthNames[monthIndex]} ${lastDay}, ${selectedYear}`;
                }
                
                // In a real app, you would fetch data for the selected month/year here
                // and update the settlement summary accordingly
            }
            
            // Initialize with current date data
            if (settlementMonth && settlementYear) {
                updateSettlementData();
            }
            
            // Existing code for other filters
            const monthFilter = document.getElementById('month-filter');
            const yearFilter = document.getElementById('year-filter');
            if (!monthFilter || !yearFilter) {
                // Required filter elements not present; skip script
                return;
            }
            const transactions = document.querySelectorAll('.recent-transactions .flex.items-center.justify-between');
            
            // Add class to transaction container for easier selection
            const transactionContainer = document.querySelector('.recent-transactions');
            if (!transactionContainer) {
                // Add class to the container if it doesn't have one
                document.querySelector('.space-y-4')?.classList.add('recent-transactions');
            }
            
            // Use the already defined currentDate, currentMonth and currentYear variables
            
            // Set the default selected options to current month and year
            monthFilter.value = currentMonth;
            
            // Check if the current year exists in the options
            let yearExists = false;
            for (let i = 0; i < yearFilter.options.length; i++) {
                if (yearFilter.options[i].value === currentYear) {
                    yearExists = true;
                    break;
                }
            }
            
            // If current year doesn't exist in options, add it
            if (!yearExists && currentYear !== 'all') {
                const newOption = document.createElement('option');
                newOption.value = currentYear;
                newOption.textContent = currentYear;
                yearFilter.appendChild(newOption);
            }
            
            yearFilter.value = currentYear;
            
            // Call filterTransactions to apply both filters
            filterTransactions();
            
            // Function to filter transactions based on month and year
            function filterTransactions() {
                const selectedMonth = monthFilter.value;
                const selectedYear = yearFilter.value;
                
                // Get all transaction date elements
                const dateElements = document.querySelectorAll('.recent-transactions .text-xs.text-gray-500');
                
                dateElements.forEach((dateEl, index) => {
                    const transaction = dateEl.closest('.flex.items-center.justify-between');
                    const dateText = dateEl.textContent;
                    
                    // Extract month and year from date (format: 'Jun 15, 2023')
                    const dateParts = dateText.split(' ');
                    const monthName = dateParts[0];
                    const year = dateParts[2];
                    
                    // Convert month name to number
                    const monthMap = {
                        'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04',
                        'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08',
                        'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
                    };
                    
                    const monthNumber = monthMap[monthName];
                    
                    // Show/hide based on filter
                    const monthMatch = selectedMonth === 'all' || monthNumber === selectedMonth;
                    const yearMatch = selectedYear === 'all' || year === selectedYear;
                    
                    if (monthMatch && yearMatch) {
                        transaction.style.display = 'flex';
                    } else {
                        transaction.style.display = 'none';
                    }
                });
                
                // Update total expenses and share based on visible transactions
                updateTotals();
            }
            
            // Add event listeners to both filters
            monthFilter.addEventListener('change', filterTransactions);
            yearFilter.addEventListener('change', filterTransactions);
            
            function updateTotals() {
                // This function would calculate new totals based on visible transactions
                // For demo purposes, we'll just show different values for different months and years
                const selectedMonth = monthFilter.value;
                const selectedYear = yearFilter.value;
                const totalExpenseEl = document.querySelector('.text-xl.font-bold');
                const yourShareEl = document.querySelector('.text-xl.font-bold.text-blue-600');
                
                // Demo values for different months
                const monthlyData = {
                    'all': { total: '₹4,250.00', share: '₹1,062.50' },
                    '01': { total: '₹3,850.00', share: '₹962.50' },
                    '02': { total: '₹4,120.00', share: '₹1,030.00' },
                    '03': { total: '₹3,640.00', share: '₹910.00' },
                    '04': { total: '₹4,580.00', share: '₹1,145.00' },
                    '05': { total: '₹3,920.00', share: '₹980.00' },
                    '06': { total: '₹4,250.00', share: '₹1,062.50' },
                    '07': { total: '₹3,780.00', share: '₹945.00' },
                    '08': { total: '₹4,350.00', share: '₹1,087.50' },
                    '09': { total: '₹3,980.00', share: '₹995.00' },
                    '10': { total: '₹4,150.00', share: '₹1,037.50' },
                    '11': { total: '₹3,890.00', share: '₹972.50' },
                    '12': { total: '₹4,420.00', share: '₹1,105.00' }
                };
                
                // Demo values for different years (multipliers)
                const yearMultiplier = {
                    'all': 1,
                    '2023': 0.9,
                    '2024': 1.0,
                    '2025': 1.1
                };
                
                // Update the displayed values
                if (totalExpenseEl && yourShareEl && monthlyData[selectedMonth]) {
                    let total = monthlyData[selectedMonth].total;
                    let share = monthlyData[selectedMonth].share;
                    
                    // Apply year multiplier if not 'all'
                    if (selectedYear !== 'all' && yearMultiplier[selectedYear]) {
                        // Extract numeric value, apply multiplier, and format back
                        let totalValue = parseFloat(total.replace('₹', '').replace(',', ''));
                        let shareValue = parseFloat(share.replace('₹', '').replace(',', ''));
                        
                        totalValue = totalValue * yearMultiplier[selectedYear];
                        shareValue = shareValue * yearMultiplier[selectedYear];
                        
                        total = '₹' + totalValue.toFixed(2);
                        share = '₹' + shareValue.toFixed(2);
                    }
                    
                    totalExpenseEl.innerHTML = total.replace('₹', '<i class="fas fa-rupee-sign"></i>');
                    yourShareEl.innerHTML = share.replace('₹', '<i class="fas fa-rupee-sign"></i>');
                }
            }
        });
    </script>
</body>
</html>