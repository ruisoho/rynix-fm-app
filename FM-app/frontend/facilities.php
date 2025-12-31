<?php
require_once '../backend/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities - FM App</title>
    <link rel="stylesheet" href="style.css">
    <script src="../assets/htmx.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Facilities Management</h1>
                <button onclick="document.getElementById('add-modal').classList.remove('hidden')" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New Facility
                </button>
            </div>
            
            <!-- Facilities List -->
            
            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <form class="grid grid-cols-1 md:grid-cols-4 gap-4" 
                      hx-get="../backend/index.php" 
                      hx-target="#facilities-list" 
                      hx-trigger="keyup delay:500ms from:input, change">
                    
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-1">Search</label>
                        <input type="text" name="search" placeholder="Search by name, manager, or address..." 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Type</label>
                        <select name="type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                            <option value="">All Types</option>
                            <option value="Office">Office</option>
                            <option value="Warehouse">Warehouse</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Retail">Retail</option>
                            <option value="Residential">Residential</option>
                            <option value="School">School</option>
                            <option value="Mixed Use">Mixed Use</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Status</label>
                        <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                        </select>
                    </div>
                </form>
            </div>

            <div id="facilities-list" 
                 hx-get="../backend/index.php" 
                 hx-trigger="load" 
                 class="space-y-4">
                <div class="text-center text-gray-500 py-8">Loading facilities...</div>
            </div>
        </div>

        <!-- Add Facility Modal -->
        <div id="add-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Add New Facility
                    </h3>
                    <button onclick="document.getElementById('add-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form hx-post="../backend/index.php" 
                      hx-target="#facilities-list" 
                      hx-swap="innerHTML"
                      hx-on::after-request="if(event.detail.successful) { document.getElementById('add-modal').classList.add('hidden'); this.reset(); }">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- Name -->
                        <div class="col-span-1">
                            <label class="block text-gray-700 text-sm font-bold mb-1">Facility Name *</label>
                            <input type="text" name="name" required placeholder="Enter facility name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <!-- Type -->
                        <div class="col-span-1">
                            <label class="block text-gray-700 text-sm font-bold mb-1">Facility Type *</label>
                            <select name="type" required class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                <option value="Office">Office</option>
                                <option value="Warehouse">Warehouse</option>
                                <option value="Manufacturing">Manufacturing</option>
                                <option value="Retail">Retail</option>
                                <option value="Residential">Residential</option>
                                <option value="School">School</option>
                                <option value="Mixed Use">Mixed Use</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-1">Address</label>
                        <input type="text" name="address" placeholder="Enter facility address" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- Area -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Total Area (m²)</label>
                            <input type="number" name="area" step="0.01" placeholder="Enter area" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <!-- Year -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Construction Year</label>
                            <input type="number" name="construction_year" placeholder="Enter year" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <!-- Status -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Status</label>
                            <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Under Maintenance">Under Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- Employees -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Employees</label>
                            <input type="number" name="employees" placeholder="Count" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <!-- Op Hours -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Op. Hours</label>
                            <input type="text" name="op_hours" placeholder="e.g. 9-5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <!-- Hazard Level -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Hazard Level</label>
                            <select name="hazard_level" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- Manager -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Facility Manager</label>
                            <input type="text" name="manager_name" placeholder="Enter manager name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <!-- Manager Contact -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Manager Contact</label>
                            <input type="text" name="manager_contact" placeholder="Email or phone number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-1">Description</label>
                        <textarea name="description" rows="3" placeholder="Additional notes or description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden')" class="mr-4 text-gray-600 hover:text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Save Facility
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
    <script src="script.js"></script>
</body>
</html>