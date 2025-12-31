<?php
require_once '../backend/auth.php';
requireLogin();
require_once '../backend/csrf_helper.php';
require_once '../backend/maintenance.php';

// Get facilities for dropdown
$facilities = getFacilitiesDropdown();
// Get providers for dropdown
$providers = getProvidersDropdown();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Maintenance Management</h1>
                    <button onclick="document.getElementById('add-modal').classList.remove('hidden')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Maintenance Task
                    </button>
                </div>

                <!-- Filters -->
                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <form class="grid grid-cols-1 md:grid-cols-4 gap-4" 
                          hx-get="../backend/maintenance.php" 
                          hx-target="#maintenance-list" 
                          hx-trigger="keyup delay:500ms from:input, change">
                        
                        <!-- Search -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Search</label>
                            <input type="text" name="search" placeholder="Title or Description..." 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <!-- Facility Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Facility</label>
                            <select name="facility_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                <option value="">All Facilities</option>
                                <?php foreach ($facilities as $facility): ?>
                                    <option value="<?php echo $facility['id']; ?>"><?php echo htmlspecialchars($facility['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Priority Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Priority</label>
                            <select name="priority" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                <option value="">All Priorities</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Status</label>
                            <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                <option value="">All Statuses</option>
                                <option value="Open">Open</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Maintenance List -->
                <div id="maintenance-list">
                    <?php 
                    $logs = getMaintenanceLogs();
                    include '../backend/maintenance_list_view.php'; 
                    ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Edit Modal Container -->
    <div id="edit-modal-container"></div>

    <!-- Add Task Modal -->
    <div id="add-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Add Maintenance Task</h3>
                <form hx-post="../backend/maintenance.php" hx-target="#maintenance-list" hx-swap="innerHTML" 
                      hx-on::after-request="if(event.detail.elt === this && event.detail.successful) { document.getElementById('add-modal').classList.add('hidden'); this.reset(); }">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Facility</label>
                        <select name="facility_id" required class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Select Facility</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility['id']; ?>"><?php echo htmlspecialchars($facility['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Provider</label>
                        <select name="provider_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Select Provider (Optional)</option>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?php echo $provider['id']; ?>"><?php echo htmlspecialchars($provider['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                        <input type="text" name="title" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Priority</label>
                        <select name="priority" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Cost (€)</label>
                        <input type="number" step="0.01" name="cost" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Recurrence</label>
                        <select name="recurrence" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="None">None</option>
                            <option value="1 Month">1 Month</option>
                            <option value="3 Months">3 Months</option>
                            <option value="6 Months">6 Months</option>
                            <option value="1 Year">1 Year</option>
                            <option value="2 Years">2 Years</option>
                            <option value="4 Years">4 Years</option>
                            <option value="5 Years">5 Years</option>
                        </select>
                    </div>

                    <div class="items-center px-4 py-3">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Create Task
                        </button>
                        <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden')" class="mt-3 px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
