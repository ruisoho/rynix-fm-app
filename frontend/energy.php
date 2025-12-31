<?php
require_once '../backend/auth.php';
requireLogin();
require_once '../backend/csrf_helper.php';
require_once '../backend/energy.php';

// Get facilities for dropdown (for filter)
$facilities = getFacilitiesDropdownEnergy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Monitoring - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Energy Monitoring</h1>
                    <button onclick="document.getElementById('add-modal').classList.remove('hidden')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New Meter
                    </button>
                </div>

                <!-- Main Tabs Navigation -->
                <div class="mb-6 border-b border-gray-300">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button onclick="switchMainTab('overview')" id="tab-main-overview" 
                                class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            Overview
                        </button>
                        <button onclick="switchMainTab('meters')" id="tab-main-meters" 
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            Meters
                        </button>
                    </nav>
                </div>

                <!-- Overview Tab Content -->
                <div id="view-overview" class="block space-y-6">
                    
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Electricity Card -->
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Electricity (This Month)</p>
                                    <h3 class="text-2xl font-bold text-gray-800 mt-1" id="card-electricity-value">--</h3>
                                </div>
                                <div class="p-2 bg-yellow-100 rounded-full text-yellow-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Gas Card -->
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Gas (This Month)</p>
                                    <h3 class="text-2xl font-bold text-gray-800 mt-1" id="card-gas-value">--</h3>
                                </div>
                                <div class="p-2 bg-orange-100 rounded-full text-orange-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Heating Card -->
                        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Heating (This Month)</p>
                                    <h3 class="text-2xl font-bold text-gray-800 mt-1" id="card-heating-value">--</h3>
                                </div>
                                <div class="p-2 bg-red-100 rounded-full text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Consumption Table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Energy Consumption Summary</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utility</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Today</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">This Week</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">This Month</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">3 Months</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">6 Months</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">1 Year</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="summary-table-body">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Trends Chart -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex flex-col md:flex-row justify-between items-center border-b border-gray-200 mb-6 pb-4 gap-4">
                             <h3 class="text-lg font-medium text-gray-900">Energy Usage Trends</h3>
                            
                            <!-- Period Filter -->
                            <div class="flex bg-gray-100 p-1 rounded-md">
                                <button onclick="updatePeriod('weekly')" id="period-weekly" class="px-3 py-1 text-xs font-medium rounded-md text-gray-500 hover:bg-white hover:shadow-sm">Weekly</button>
                                <button onclick="updatePeriod('monthly')" id="period-monthly" class="px-3 py-1 text-xs font-medium rounded-md bg-white text-gray-800 shadow-sm">Monthly</button>
                                <button onclick="updatePeriod('3_monthly')" id="period-3_monthly" class="px-3 py-1 text-xs font-medium rounded-md text-gray-500 hover:bg-white hover:shadow-sm">3 Months</button>
                                <button onclick="updatePeriod('6_monthly')" id="period-6_monthly" class="px-3 py-1 text-xs font-medium rounded-md text-gray-500 hover:bg-white hover:shadow-sm">6 Months</button>
                                <button onclick="updatePeriod('yearly')" id="period-yearly" class="px-3 py-1 text-xs font-medium rounded-md text-gray-500 hover:bg-white hover:shadow-sm">Yearly</button>
                            </div>
                        </div>

                        <!-- Single Unified Chart -->
                        <div class="relative h-[400px] w-full">
                            <canvas id="overviewChart"></canvas>
                        </div>
                        
                        <!-- Utility Toggles (Moved below chart) -->
                        <div class="flex flex-wrap justify-center gap-2 mt-4">
                            <button onclick="toggleUtility('Electricity')" id="btn-toggle-Electricity" class="px-4 py-2 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200 hover:bg-yellow-200 transition-colors shadow-sm ring-2 ring-yellow-400">
                                Electricity
                            </button>
                            <button onclick="toggleUtility('Gas')" id="btn-toggle-Gas" class="px-4 py-2 text-sm font-medium rounded-full bg-orange-100 text-orange-800 border border-orange-200 hover:bg-orange-200 transition-colors shadow-sm ring-2 ring-orange-400">
                                Gas
                            </button>
                            <button onclick="toggleUtility('Water')" id="btn-toggle-Water" class="px-4 py-2 text-sm font-medium rounded-full bg-blue-100 text-blue-800 border border-blue-200 hover:bg-blue-200 transition-colors shadow-sm ring-2 ring-blue-400">
                                Water
                            </button>
                            <button onclick="toggleUtility('Heating')" id="btn-toggle-Heating" class="px-4 py-2 text-sm font-medium rounded-full bg-red-100 text-red-800 border border-red-200 hover:bg-red-200 transition-colors shadow-sm ring-2 ring-red-400">
                                Heating
                            </button>
                        </div>
                    </div>

                    <!-- Pie Charts Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Usage by Type -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Energy Usage by Type</h3>
                            <div class="relative h-[300px] w-full">
                                <canvas id="typeDistributionChart"></canvas>
                            </div>
                        </div>

                        <!-- Usage by Facility -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Energy Consumption by Facility</h3>
                            <div class="relative h-[300px] w-full">
                                <canvas id="facilityDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Meters Tab Content -->
                <div id="view-meters" class="hidden space-y-6">
                    <!-- Filters -->
                    <div class="bg-white p-4 rounded-lg shadow">
                        <form class="grid grid-cols-1 md:grid-cols-4 gap-4" 
                              hx-get="../backend/energy.php" 
                              hx-target="#energy-list" 
                              hx-trigger="keyup delay:500ms from:input, change">
                            
                            <!-- Search -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-1">Search</label>
                                <input type="text" name="search" placeholder="Meter Name or Serial..." 
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>

                            <!-- Facility Filter -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-1">Facility</label>
                                <select name="facility_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                    <option value="">All Facilities</option>
                                    <?php foreach ($facilities as $facility): ?>
                                        <option value="<?php echo $facility['id']; ?>">
                                            <?php echo htmlspecialchars($facility['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Type Filter -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-1">Meter Type</label>
                                <select name="type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                    <option value="">All Types</option>
                                    <option value="Electricity">Electricity</option>
                                    <option value="Gas">Gas</option>
                                    <option value="Water">Water</option>
                                    <option value="Heating">Heating</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    <!-- Energy Meters List -->
                    <div id="energy-list">
                        <?php 
                        $meters = getMeters();
                        include '../backend/energy_list_view.php'; 
                        ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Meter Modal -->
    <div id="add-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Add New Meter</h3>
                <form hx-post="../backend/energy.php" hx-target="#energy-list" 
                      hx-on::after-request="if(event.detail.elt === this && event.detail.successful) { document.getElementById('add-modal').classList.add('hidden'); this.reset(); }">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="add_meter">
                    
                    <div class="mt-2 text-left">
                        <label class="block text-sm font-medium text-gray-700">Facility</label>
                        <select name="facility_id" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select Facility</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility['id']; ?>"><?php echo htmlspecialchars($facility['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-sm font-medium text-gray-700">Meter Name</label>
                        <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" id="meter_type_select" required onchange="updateUnit()" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select Type</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Gas">Gas</option>
                            <option value="Water">Water</option>
                            <option value="Heating">Heating</option>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-sm font-medium text-gray-700">Unit</label>
                        <select name="unit" id="meter_unit_select" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select Unit</option>
                            <option value="kWh">kWh</option>
                            <option value="m³">m³</option>
                            <option value="MWh">MWh</option>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-sm font-medium text-gray-700">Serial Number</label>
                        <input type="text" name="serial_number" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <input type="text" name="location" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div class="items-center px-4 py-3">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Add Meter
                        </button>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div id="edit-modal-content" class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <!-- Content loaded via HTMX -->
        </div>
    </div>

    <!-- Readings Modal -->
    <div id="readings-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div id="readings-modal-content" class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <!-- Content loaded via HTMX -->
        </div>
    </div>

    <script>
        function updateUnit() {
            const typeSelect = document.getElementById('meter_type_select');
            const unitSelect = document.getElementById('meter_unit_select');
            const type = typeSelect.value;
            
            if (type === 'Electricity') {
                unitSelect.value = 'kWh';
            } else if (type === 'Gas' || type === 'Water') {
                unitSelect.value = 'm³';
            } else if (type === 'Heating') {
                unitSelect.value = 'MWh';
            }
        }

        // --- Main Tab Switching ---
        function switchMainTab(tabName) {
            // Update Tab Buttons
            const tabs = ['overview', 'meters'];
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-main-${t}`);
                if (t === tabName) {
                    btn.classList.add('border-blue-500', 'text-blue-600');
                    btn.classList.remove('border-transparent', 'text-gray-500');
                } else {
                    btn.classList.remove('border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                }
            });

            // Toggle Content
            if (tabName === 'overview') {
                document.getElementById('view-overview').classList.remove('hidden');
                document.getElementById('view-meters').classList.add('hidden');
                // Resize chart if needed
                if (mainChart) mainChart.resize();
            } else {
                document.getElementById('view-overview').classList.add('hidden');
                document.getElementById('view-meters').classList.remove('hidden');
            }
        }

        // --- Chart & Data Logic ---
        let mainChart = null;
        let typeChart = null;
        let facilityChart = null;
        let currentPeriod = 'monthly';
        let activeUtilities = {
            'Electricity': true,
            'Gas': true,
            'Water': true,
            'Heating': true
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            fetchDataAndRender();
            fetchSummary();
            fetchDistribution();
        });

        function updatePeriod(period) {
            currentPeriod = period;
            
            // Update buttons UI
            const periods = ['weekly', 'monthly', '3_monthly', '6_monthly', 'yearly'];
            periods.forEach(p => {
                const btn = document.getElementById(`period-${p}`);
                if (p === period) {
                    btn.className = "px-3 py-1 text-xs font-medium rounded-md bg-white text-gray-800 shadow-sm transition-all";
                } else {
                    btn.className = "px-3 py-1 text-xs font-medium rounded-md text-gray-500 hover:bg-white hover:shadow-sm transition-all";
                }
            });

            fetchDataAndRender();
            // Optional: Update distribution charts if we want them to react to the period filter too
            // Convert period codes to human readable for backend if needed, or backend handles it?
            // Backend expects: 'Today', 'This Week', 'This Month', '3 Months', '6 Months', '1 Year'
            // Frontend 'period' var is: 'weekly', 'monthly', '3_monthly', '6_monthly', 'yearly'
            
            let backendPeriod = 'This Month';
            switch(period) {
                case 'weekly': backendPeriod = 'This Week'; break;
                case 'monthly': backendPeriod = 'This Month'; break;
                case '3_monthly': backendPeriod = '3 Months'; break;
                case '6_monthly': backendPeriod = '6 Months'; break;
                case 'yearly': backendPeriod = '1 Year'; break;
            }
            fetchDistribution(backendPeriod);
        }

        function toggleUtility(type) {
            activeUtilities[type] = !activeUtilities[type];
            
            // Update Button UI
            const btn = document.getElementById(`btn-toggle-${type}`);
            if (activeUtilities[type]) {
                btn.classList.remove('opacity-50', 'grayscale');
            } else {
                btn.classList.add('opacity-50', 'grayscale');
            }

            // Update Chart visibility
            if (mainChart) {
                const datasetIndex = mainChart.data.datasets.findIndex(d => d.label.includes(type));
                if (datasetIndex !== -1) {
                    mainChart.setDatasetVisibility(datasetIndex, activeUtilities[type]);
                    mainChart.update();
                }
            }
        }

        function fetchDataAndRender() {
            fetch(`../backend/energy.php?action=get_stats&period=${currentPeriod}`)
                .then(response => response.json())
                .then(data => {
                    renderOverviewChart(data);
                })
                .catch(err => console.error('Error fetching stats:', err));
        }

        function fetchSummary() {
            fetch('../backend/energy.php?action=get_summary')
                .then(response => response.json())
                .then(data => {
                    updateSummaryCards(data);
                    updateSummaryTable(data);
                })
                .catch(err => console.error('Error fetching summary:', err));
        }

        function fetchDistribution(period = 'This Month') {
            fetch(`../backend/energy.php?action=get_distribution&period=${period}`)
                .then(response => response.json())
                .then(data => {
                    renderDistributionCharts(data);
                })
                .catch(err => console.error('Error fetching distribution:', err));
        }

        function updateSummaryCards(data) {
            // Data structure: { 'This Month': { 'Electricity': 123, ... }, ... }
            const periodData = data['This Month'];
            if (periodData) {
                document.getElementById('card-electricity-value').textContent = formatValue(periodData['Electricity'], 'kWh');
                document.getElementById('card-gas-value').textContent = formatValue(periodData['Gas'], 'm³');
                document.getElementById('card-heating-value').textContent = formatValue(periodData['Heating'], 'MWh');
            }
        }

        function updateSummaryTable(data) {
            const tbody = document.getElementById('summary-table-body');
            tbody.innerHTML = '';

            const types = ['Electricity', 'Gas', 'Water', 'Heating'];
            const units = {'Electricity': 'kWh', 'Gas': 'm³', 'Water': 'm³', 'Heating': 'MWh'};
            const periodsMap = {
                'Today': 'Today',
                'This Week': 'This Week',
                'This Month': 'This Month',
                '3 Months': '3 Months',
                '6 Months': '6 Months',
                '1 Year': '1 Year'
            };

            // We need to pivot: Row = Type, Cols = Periods
            types.forEach(type => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                
                let html = `<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${type}</td>`;
                
                Object.keys(periodsMap).forEach(pKey => {
                    const val = data[pKey] ? data[pKey][type] : 0;
                    html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatValue(val, units[type])}</td>`;
                });

                tr.innerHTML = html;
                tbody.appendChild(tr);
            });
        }

        function renderDistributionCharts(data) {
            // Render Type Distribution
            const ctxType = document.getElementById('typeDistributionChart').getContext('2d');
            if (typeChart) typeChart.destroy();
            
            const typeLabels = Object.keys(data.by_type);
            const typeValues = Object.values(data.by_type);
            const typeColors = {
                'Electricity': '#FBBF24', // Yellow
                'Gas': '#F97316', // Orange
                'Water': '#3B82F6', // Blue
                'Heating': '#DC2626' // Red
            };
            const bgColors = typeLabels.map(l => typeColors[l] || '#CBD5E1');

            typeChart = new Chart(ctxType, {
                type: 'doughnut',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        data: typeValues,
                        backgroundColor: bgColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });

            // Render Facility Distribution
            const ctxFacility = document.getElementById('facilityDistributionChart').getContext('2d');
            if (facilityChart) facilityChart.destroy();

            const facilityLabels = Object.keys(data.by_facility);
            const facilityValues = Object.values(data.by_facility);
            // Generate random colors or use a palette
            const palette = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1'];
            const facilityColors = facilityLabels.map((_, i) => palette[i % palette.length]);

            facilityChart = new Chart(ctxFacility, {
                type: 'pie',
                data: {
                    labels: facilityLabels,
                    datasets: [{
                        data: facilityValues,
                        backgroundColor: facilityColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        }

        function formatValue(val, unit) {
            if (val === undefined || val === null) return '0 ' + unit;
            return parseFloat(val).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 1}) + ' ' + unit;
        }

        function renderOverviewChart(data) {
            const ctx = document.getElementById('overviewChart').getContext('2d');
            
            if (mainChart) {
                mainChart.destroy();
            }

            // Define datasets config
            const datasetsConfig = [
                { type: 'Electricity', color: '#FBBF24', bgColor: 'rgba(251, 191, 36, 0.1)' },
                { type: 'Gas', color: '#F97316', bgColor: 'rgba(249, 115, 22, 0.1)' },
                { type: 'Water', color: '#3B82F6', bgColor: 'rgba(59, 130, 246, 0.1)' },
                { type: 'Heating', color: '#DC2626', bgColor: 'rgba(220, 38, 38, 0.1)' }
            ];

            const datasets = datasetsConfig.map(cfg => ({
                label: cfg.type,
                data: data.datasets[cfg.type] || [], // Ensure data exists
                borderColor: cfg.color,
                backgroundColor: cfg.bgColor,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                hidden: !activeUtilities[cfg.type] // Initial state based on toggles
            }));

            mainChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            onClick: (e, legendItem, legend) => {
                                // Sync with our custom buttons
                                const type = legendItem.text;
                                toggleUtility(type); 
                                // Note: toggleUtility calls update(), so we don't need to do default behavior
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }
    </script>
</body>
</html>
