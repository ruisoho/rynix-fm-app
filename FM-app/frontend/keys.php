<?php
require_once '../backend/auth.php';
requireLogin();
require_once '../backend/keys.php';

// Initial fetch for dropdowns
$facilities = getFacilitiesDropdown();
$users = getUsersDropdown();
$locks = $pdo->query("SELECT l.*, f.name as facility_name FROM locks l JOIN facilities f ON l.facility_id = f.id ORDER BY l.name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Key Management - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Key Management</h1>
                    
                    <div class="space-x-2">
                        <button onclick="document.getElementById('add-key-modal').classList.remove('hidden')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                            Add Key
                        </button>
                        <button onclick="document.getElementById('issue-key-modal').classList.remove('hidden')" 
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
                            Issue Key
                        </button>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="mb-6 border-b border-gray-300">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button onclick="switchTab('inventory')" id="tab-inventory" 
                                class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            Inventory
                        </button>
                        <button onclick="switchTab('locks')" id="tab-locks" 
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            Locks
                        </button>
                        <button onclick="switchTab('transactions')" id="tab-transactions" 
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            History / Issues
                        </button>
                        <button onclick="switchTab('access')" id="tab-access" 
                                class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                            Access Matrix
                        </button>
                        <!-- Placeholders for other tabs -->
                        <button onclick="switchTab('reports')" id="tab-reports" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">Reports</button>
                        <button onclick="switchTab('audit')" id="tab-audit" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">Audit Logs</button>
                    </nav>
                </div>

                <!-- Inventory Tab -->
                <div id="view-inventory" class="block space-y-4">
                    <!-- Filters -->
                    <div class="bg-white p-4 rounded-lg shadow mb-4">
                        <form class="grid grid-cols-1 md:grid-cols-4 gap-4" 
                              hx-get="../backend/keys.php" 
                              hx-vals='{"action": "list_keys"}'
                              hx-target="#inventory-list" 
                              hx-trigger="keyup delay:500ms from:input, change">
                            <input type="hidden" name="action" value="list_keys">
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-1">Search</label>
                                <input type="text" name="search" placeholder="Name or Serial..." 
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-1">Facility</label>
                                <select name="facility_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                    <option value="">All Facilities</option>
                                    <?php foreach ($facilities as $f): ?>
                                        <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-1">Status</label>
                                <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                    <option value="">All Statuses</option>
                                    <option value="Available">Available</option>
                                    <option value="Issued">Issued</option>
                                    <option value="Lost">Lost</option>
                                    <option value="Broken">Broken</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    <div id="inventory-list" hx-get="../backend/keys.php?action=list_keys" hx-trigger="load, refreshKeys from:body">
                        <!-- Content loaded via HTMX -->
                        <div class="text-center p-4">Loading keys...</div>
                    </div>
                </div>

                <!-- Locks Tab -->
                <div id="view-locks" class="hidden space-y-4">
                    <div class="flex justify-end mb-4">
                        <button onclick="document.getElementById('add-lock-modal').classList.remove('hidden')" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow">
                            Add Lock
                        </button>
                    </div>
                    <div id="locks-list" hx-get="../backend/keys.php?action=list_locks" hx-trigger="load, refreshLocks from:body">
                        <div class="text-center p-4">Loading locks...</div>
                    </div>
                </div>

                <!-- Transactions Tab -->
                <div id="view-transactions" class="hidden space-y-4">
                     <div class="bg-white p-4 rounded-lg shadow mb-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium">History & Issues</h3>
                            <div class="flex space-x-2">
                                <!-- Quick Filters -->
                                <button onclick="filterTransactions('Active')" class="px-3 py-1 text-xs font-medium rounded bg-yellow-100 text-yellow-800">Active Issues</button>
                                <button onclick="filterTransactions('Overdue')" class="px-3 py-1 text-xs font-medium rounded bg-red-100 text-red-800">Overdue</button>
                                <button onclick="filterTransactions('')" class="px-3 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">All</button>
                            </div>
                        </div>
                    </div>

                    <div id="transactions-list" hx-get="../backend/keys.php?action=list_transactions" hx-trigger="load, refreshTransactions from:body">
                         <div class="text-center p-4">Loading history...</div>
                    </div>
                </div>

                <!-- Access Matrix Tab -->
                <div id="view-access" class="hidden space-y-4">
                    <div class="flex justify-end mb-4">
                        <button onclick="document.getElementById('grant-access-modal').classList.remove('hidden')" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow">
                            Grant Access
                        </button>
                    </div>

                    <div id="access-list" hx-get="../backend/keys.php?action=list_access" hx-trigger="load, refreshAccess from:body">
                         <div class="text-center p-4">Loading access matrix...</div>
                    </div>
                </div>

                <!-- Other Tabs (Placeholders) -->
                <div id="view-reports" class="hidden">
                    <div class="p-8 text-center text-gray-500">Reports Module Coming Soon</div>
                </div>
                <div id="view-audit" class="hidden">
                    <div class="p-8 text-center text-gray-500">Audit Logs Coming Soon</div>
                </div>

            </main>
        </div>
    </div>

    <!-- Add Key Modal -->
    <div id="add-key-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-10 mx-auto p-6 border w-[500px] shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">New Key</h3>
                <button onclick="document.getElementById('add-key-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form hx-post="../backend/keys.php" hx-swap="none" 
                  hx-on::after-request="if(event.detail.successful) { document.getElementById('add-key-modal').classList.add('hidden'); this.reset(); }">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add_key">
                
                <!-- Facility (Required by DB) -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Facility *</label>
                    <select name="facility_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Key Number -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Key Number *</label>
                    <input type="text" name="serial_number" required placeholder="e.g. 001, M-01" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                </div>

                <!-- Lock Number -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Lock Number</label>
                    <div id="lock-options-container" hx-get="../backend/keys.php?action=get_lock_options" hx-trigger="refreshLocks from:body">
                        <?php if (empty($locks)): ?>
                            <div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-500">
                                No locks available. Please create locks first in the "Locks" tab.
                            </div>
                        <?php else: ?>
                            <select name="lock_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                                <option value="">Select a lock...</option>
                                <?php foreach ($locks as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name'] . ' (' . ($l['facility_name'] ?? 'N/A') . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Number of the lock cylinder this key opens</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Name -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Name</label>
                    <input type="text" name="name" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                </div>

                <!-- Keys in Bundle -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Keys in Bundle</label>
                    <input type="number" name="keys_in_bundle" value="1" min="1" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                    <p class="text-xs text-gray-500 mt-1">For key bundles with multiple keys</p>
                </div>

                <!-- Master Key -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold">Master Key</label>
                        <p class="text-xs text-gray-500">Opens all locks in this facility</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_master" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Access Restriction (Roles) -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Access Restriction (Roles)</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="allowed_roles[]" value="Admin" class="form-checkbox h-4 w-4 text-blue-600">
                            <span class="ml-2 text-gray-700">Admin</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="allowed_roles[]" value="Staff" class="form-checkbox h-4 w-4 text-blue-600">
                            <span class="ml-2 text-gray-700">Staff</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="allowed_roles[]" value="Provider" class="form-checkbox h-4 w-4 text-blue-600">
                            <span class="ml-2 text-gray-700">Provider</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">If no role is selected, the key is available to all.</p>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="document.getElementById('add-key-modal').classList.add('hidden')" class="px-4 py-2 bg-white text-gray-700 rounded-md border border-gray-300 hover:bg-gray-50 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 font-medium">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Key Modal -->
    <div id="edit-key-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-10 mx-auto p-6 border w-[500px] shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Edit Key</h3>
                <button onclick="document.getElementById('edit-key-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form hx-post="../backend/keys.php" hx-swap="none" 
                  hx-on::after-request="if(event.detail.successful) { document.getElementById('edit-key-modal').classList.add('hidden'); }">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="update_key">
                <input type="hidden" name="id" id="edit-key-id">
                
                <!-- Facility -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Facility *</label>
                    <select name="facility_id" id="edit-key-facility" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Key Number -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Key Number *</label>
                    <input type="text" name="serial_number" id="edit-key-serial" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                </div>

                <!-- Name -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Name</label>
                    <input type="text" name="name" id="edit-key-name" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                </div>
                
                <!-- Status -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Status</label>
                    <select name="status" id="edit-key-status" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                        <option value="Available">Available</option>
                        <option value="Issued">Issued</option>
                        <option value="Lost">Lost</option>
                        <option value="Broken">Broken</option>
                    </select>
                </div>

                <!-- Keys in Bundle -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Keys in Bundle</label>
                    <input type="number" name="keys_in_bundle" id="edit-key-bundle" min="1" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                </div>

                <!-- Master Key -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold">Master Key</label>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_master" id="edit-key-master" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Access Restriction (Roles) -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Access Restriction (Roles)</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="allowed_roles[]" value="Admin" class="form-checkbox h-4 w-4 text-blue-600 edit-key-role">
                            <span class="ml-2 text-gray-700">Admin</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="allowed_roles[]" value="Staff" class="form-checkbox h-4 w-4 text-blue-600 edit-key-role">
                            <span class="ml-2 text-gray-700">Staff</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="allowed_roles[]" value="Provider" class="form-checkbox h-4 w-4 text-blue-600 edit-key-role">
                            <span class="ml-2 text-gray-700">Provider</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="document.getElementById('edit-key-modal').classList.add('hidden')" class="px-4 py-2 bg-white text-gray-700 rounded-md border border-gray-300 hover:bg-gray-50 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Lock Modal -->
    <div id="add-lock-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">New Lock</h3>
                <button onclick="document.getElementById('add-lock-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form hx-post="../backend/keys.php" 
                  hx-on::after-request="if(event.detail.successful) { document.getElementById('add-lock-modal').classList.add('hidden'); this.reset(); location.reload(); }">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add_lock">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Facility *</label>
                    <select name="facility_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Lock Name / Number *</label>
                    <input type="text" name="name" required placeholder="e.g. Z-001, Cylinder 45" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Location</label>
                    <input type="text" name="location" placeholder="e.g. Room 101, Main Entrance" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 p-2 border">
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="document.getElementById('add-lock-modal').classList.add('hidden')" class="px-4 py-2 bg-white text-gray-700 rounded-md border border-gray-300 hover:bg-gray-50 font-medium">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Issue Key Modal -->
    <div id="issue-key-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Issue Key</h3>
            <form hx-post="../backend/keys.php" 
                  hx-on::after-request="if(event.detail.successful) { document.getElementById('issue-key-modal').classList.add('hidden'); this.reset(); }">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="issue_key">
                
                <!-- Dynamic Key Search/Select could be better here, but for now simple input ID or dropdown -->
                <!-- Let's assume we use a simple select populated by HTMX or just manual ID entry for MVP if list is long -->
                <!-- Or better: Populate with Available Keys -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Key (Available)</label>
                    <select name="key_id" required class="shadow border rounded w-full py-2 px-3 text-gray-700"
                            hx-get="../backend/keys.php?action=get_key_options&status=Available" 
                            hx-trigger="load, refreshKeys from:body">
                        <?php 
                        // Initial load (fallback)
                        $availKeys = $pdo->query("SELECT id, name, serial_number FROM keys WHERE status = 'Available' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($availKeys as $k): ?>
                            <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['name'] . ' (' . $k['serial_number'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Recipient Type</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="recipient_type" value="user" checked onchange="toggleRecipientType('user')" class="form-radio text-blue-600">
                            <span class="ml-2 text-gray-700">Registered User</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="recipient_type" value="guest" onchange="toggleRecipientType('guest')" class="form-radio text-blue-600">
                            <span class="ml-2 text-gray-700">Guest / External</span>
                        </label>
                    </div>
                </div>

                <div id="recipient-user" class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Issue To (User)</label>
                    <select name="user_id" id="input-user-id" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="recipient-guest" class="mb-4 hidden">
                    <div class="mb-2">
                        <label class="block text-gray-700 text-sm font-bold mb-1">Guest Name *</label>
                        <input type="text" name="guest_name" id="input-guest-name" disabled class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="Full Name">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Contact Info</label>
                        <input type="text" name="guest_contact" disabled class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="Phone or Email">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Expected Return</label>
                    <input type="datetime-local" name="expected_return_at" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('issue-key-modal').classList.add('hidden')" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Issue</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Grant Access Modal -->
    <div id="grant-access-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Grant Access</h3>
            <form hx-post="../backend/keys.php" 
                  hx-on::after-request="if(event.detail.successful) { document.getElementById('grant-access-modal').classList.add('hidden'); this.reset(); }">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="grant_access">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">User</label>
                    <select name="user_id" required class="shadow border rounded w-full py-2 px-3 text-gray-700">
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Key</label>
                    <select name="key_id" required class="shadow border rounded w-full py-2 px-3 text-gray-700"
                            hx-get="../backend/keys.php?action=get_key_options" 
                            hx-trigger="load, refreshKeys from:body">
                        <?php 
                        $allKeys = $pdo->query("SELECT id, name, serial_number FROM keys ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($allKeys as $k): ?>
                            <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['name'] . ' (' . $k['serial_number'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('grant-access-modal').classList.add('hidden')" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Grant</button>
                </div>
            </form>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qr-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center">
        <div class="relative p-8 border w-80 shadow-lg rounded-md bg-white text-center">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="qr-title">Key QR Code</h3>
            <div id="qrcode" class="flex justify-center mb-4"></div>
            <button onclick="document.getElementById('qr-modal').classList.add('hidden')" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 w-full">Close</button>
        </div>
    </div>

    <script>
        // Tab Switching
        function switchTab(tabName) {
            const tabs = ['inventory', 'transactions', 'access', 'reports', 'audit'];
            
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-${t}`);
                const view = document.getElementById(`view-${t}`);
                
                if (t === tabName) {
                    btn.classList.add('border-blue-500', 'text-blue-600');
                    btn.classList.remove('border-transparent', 'text-gray-500');
                    view.classList.remove('hidden');
                } else {
                    btn.classList.remove('border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                    view.classList.add('hidden');
                }
            });
        }

        // QR Code Generation
        function showQRCode(code, name) {
            document.getElementById('qr-title').textContent = name;
            const container = document.getElementById('qrcode');
            container.innerHTML = ''; // Clear previous
            
            new QRCode(container, {
                text: code,
                width: 150,
                height: 150
            });
            
            document.getElementById('qr-modal').classList.remove('hidden');
        }

        // Helper for Issue Modal
        function openIssueModal(keyId, keyName) {
            const modal = document.getElementById('issue-key-modal');
            const select = modal.querySelector('select[name="key_id"]');
            
            // Try to select the key if it exists in the list (it should)
            select.value = keyId;
            
            modal.classList.remove('hidden');
        }

        function filterTransactions(status) {
            // Trigger HTMX request with filter
            // This is a bit manual, ideally we'd bind to a form, but let's just reload the div with param
            const url = `../backend/keys.php?action=list_transactions&status=${status}`;
            htmx.ajax('GET', url, {target: '#transactions-list'});
        }

        function toggleRecipientType(type) {
            const userDiv = document.getElementById('recipient-user');
            const guestDiv = document.getElementById('recipient-guest');
            const userInput = document.getElementById('input-user-id');
            const guestNameInput = document.getElementById('input-guest-name');
            const guestContactInput = document.getElementById('input-guest-contact');

            if (type === 'user') {
                userDiv.classList.remove('hidden');
                guestDiv.classList.add('hidden');
                
                userInput.disabled = false;
                userInput.required = true;
                
                guestNameInput.disabled = true;
                guestNameInput.required = false;
                if (guestContactInput) guestContactInput.disabled = true;
            } else {
                userDiv.classList.add('hidden');
                guestDiv.classList.remove('hidden');
                
                userInput.disabled = true;
                userInput.required = false;
                
                guestNameInput.disabled = false;
                guestNameInput.required = true;
                if (guestContactInput) guestContactInput.disabled = false;
            }
        }

        async function openEditKeyModal(id) {
            try {
                const response = await fetch(`../backend/keys.php?action=get_key&id=${id}`);
                if (!response.ok) throw new Error('Failed to fetch key');
                
                const key = await response.json();
                
                document.getElementById('edit-key-id').value = key.id;
                document.getElementById('edit-key-facility').value = key.facility_id;
                document.getElementById('edit-key-serial').value = key.serial_number;
                document.getElementById('edit-key-name').value = key.name;
                document.getElementById('edit-key-status').value = key.status;
                document.getElementById('edit-key-bundle').value = key.keys_in_bundle;
                document.getElementById('edit-key-master').checked = key.is_master == 1;
                
                // Reset roles
                document.querySelectorAll('.edit-key-role').forEach(cb => cb.checked = false);
                
                if (key.allowed_roles) {
                    const roles = key.allowed_roles.split(',');
                    roles.forEach(role => {
                        const cb = document.querySelector(`.edit-key-role[value="${role.trim()}"]`);
                        if (cb) cb.checked = true;
                    });
                }
                
                document.getElementById('edit-key-modal').classList.remove('hidden');
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load key details');
            }
        }
    </script>
</body>
</html>