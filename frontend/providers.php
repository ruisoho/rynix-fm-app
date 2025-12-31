<?php
require_once '../backend/auth.php';
require_once '../backend/csrf_helper.php';
requireLogin();
$csrf_token = generateCsrfToken();
require_once '../backend/providers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Providers - FM App</title>
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
                    <h1 class="text-3xl font-bold text-gray-800">Provider Management</h1>
                    <button onclick="document.getElementById('add-modal').classList.remove('hidden')" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Provider
                    </button>
                </div>

                <!-- Filters -->
                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <form class="grid grid-cols-1 md:grid-cols-3 gap-4" 
                          hx-get="../backend/providers.php" 
                          hx-target="#providers-list" 
                          hx-trigger="keyup delay:500ms from:input, change">
                        
                        <!-- Search -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Search</label>
                            <input type="text" name="search" placeholder="Name, Contact, Email, Phone..." 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <!-- Service Type Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Service Type</label>
                            <select name="service_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                <option value="">All Service Types</option>
                                <option value="Plumbing">Plumbing</option>
                                <option value="Electrical">Electrical</option>
                                <option value="Cleaning">Cleaning</option>
                                <option value="HVAC">HVAC</option>
                                <option value="General">General</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">Status</label>
                            <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                                <option value="">All Statuses</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Providers List -->
                <div id="providers-list">
                    <?php 
                    $providers = getProviders();
                    include '../backend/providers_list_view.php'; 
                    ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Edit Modal Container -->
    <div id="edit-modal-container"></div>

    <!-- Add Provider Modal -->
    <div id="add-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Add Provider</h3>
                <form hx-post="../backend/providers.php" hx-target="#providers-list" hx-swap="innerHTML" 
                      hx-on::after-request="if(event.detail.elt === this && event.detail.successful) { document.getElementById('add-modal').classList.add('hidden'); this.reset(); }">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mt-2 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Provider Name *</label>
                            <input type="text" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mt-2 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Customer Number</label>
                            <input type="text" name="customer_number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Service Type *</label>
                        <select name="service_type" required 
                                onchange="this.value === 'other' ? document.getElementById('custom_service_type_container').classList.remove('hidden') : document.getElementById('custom_service_type_container').classList.add('hidden')"
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="Plumbing">Plumbing</option>
                            <option value="Electrical">Electrical</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="HVAC">HVAC</option>
                            <option value="General">General</option>
                            <option value="other">Other (Specify)</option>
                        </select>
                        <div id="custom_service_type_container" class="hidden mt-2">
                            <input type="text" name="custom_service_type" placeholder="Enter custom service type" 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="mt-2 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Contact Person</label>
                            <input type="text" name="contact_person" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mt-2 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                            <input type="text" name="phone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
                        <input type="text" name="address" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="mt-2 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Hourly Rate (€)</label>
                            <input type="number" step="0.01" name="hourly_rate" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mt-2 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                            <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Specialization</label>
                        <textarea name="specialization" placeholder="Areas of expertise and specialization" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Notes</label>
                        <textarea name="notes" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>

                    <div class="items-center px-4 py-3">
                        <button type="submit" class="px-4 py-2 bg-indigo-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            Save Provider
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