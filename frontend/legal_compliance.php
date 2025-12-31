<?php
require_once '../backend/auth.php';
require_once '../backend/csrf_helper.php';
requireLogin();

$csrf_token = generateCsrfToken();

// Fetch data for Task Modal
$facilities = $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$providers = $pdo->query("SELECT id, name FROM providers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal Compliance (Rechtskataster) - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script>
        function openObligationModal(id) {
            document.getElementById('modal-content').innerHTML = '<div class="p-10 text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></div>';
            document.getElementById('obligation-modal').classList.remove('hidden');
            
            fetch('../backend/legal.php?action=get_obligation&id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modal-content').innerHTML = html;
                });
        }
        
        function closeModal() {
            document.getElementById('obligation-modal').classList.add('hidden');
        }

        function openLawTextModal(id) {
            document.getElementById('law-text-content').innerHTML = '<div class="p-20 text-center"><i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i><br><span class="text-gray-500 mt-2 block">Loading Law Text...</span></div>';
            document.getElementById('law-text-modal').classList.remove('hidden');
            
            fetch('../backend/legal.php?action=get_law_text&id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('law-text-content').innerHTML = html;
                });
        }

        function closeLawTextModal() {
            document.getElementById('law-text-modal').classList.add('hidden');
        }

        function openCreateTaskModal(title, description, deadline) {
            closeModal();
            document.getElementById('add-task-modal').classList.remove('hidden');
            
            // Pre-fill form
            const form = document.getElementById('create-task-form');
            form.querySelector('input[name="title"]').value = "Compliance: " + title;
            form.querySelector('textarea[name="description"]').value = description + "\n\nFrequency/Deadline: " + deadline;
        }

        function closeTaskModal() {
            document.getElementById('add-task-modal').classList.add('hidden');
            document.getElementById('create-task-form').reset();
        }
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Law Text Modal -->
    <div id="law-text-modal" class="fixed inset-0 z-50 hidden overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeLawTextModal()"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl h-[85vh] flex flex-col">
                    <div id="law-text-content" class="h-full flex flex-col">
                        <!-- Content loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Legal Operating System</h1>
                        <p class="text-xs text-gray-500 mt-1">Deterministic Facility Management Compliance (Germany)</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded">DE / FM Standards</span>
                    </div>
                </div>
            </header>

            <!-- Filters / Toolbar -->
            <div class="bg-white border-b border-gray-200 px-6 py-4">
                <form id="filter-form" class="flex flex-col md:flex-row gap-4 items-center" 
                      hx-get="../backend/legal.php?action=list_laws" 
                      hx-target="#laws-grid" 
                      hx-trigger="change from:select, keyup delay:300ms from:input, submit">
                    
                    <!-- Search -->
                    <div class="relative flex-grow w-full md:w-auto">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" placeholder="Search laws, abbreviations, keywords..." 
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out">
                    </div>

                    <!-- Layer Filter -->
                    <div class="w-full md:w-48">
                        <select name="layer" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="all">All Layers</option>
                            <option value="A">Layer A: Public Law</option>
                            <option value="B">Layer B: Fire Safety</option>
                            <option value="C">Layer C: Occ Safety</option>
                            <option value="D">Layer D: Liability</option>
                        </select>
                    </div>

                    <!-- Category Filter (Dynamic) -->
                    <div class="w-full md:w-56">
                        <select name="tag" hx-get="../backend/legal.php?action=get_tags" hx-trigger="load" hx-swap="innerHTML"
                                class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="all">Loading categories...</option>
                        </select>
                    </div>

                    <!-- Reset Button -->
                    <button type="button" onclick="document.getElementById('filter-form').reset(); htmx.trigger('#filter-form', 'submit')" 
                            class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 whitespace-nowrap">
                        <i class="fas fa-undo mr-1"></i> Reset
                    </button>
                </form>
            </div>

            <!-- Content Body -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                <div id="laws-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
                     hx-get="../backend/legal.php?action=list_laws" hx-trigger="load">
                    <!-- Cards loaded via HTMX -->
                    <div class="col-span-full text-center py-20 text-gray-400">
                        <i class="fas fa-circle-notch fa-spin text-3xl mb-3"></i><br>
                        Loading Legal Database...
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Obligation Detail Modal -->
    <div id="obligation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center">
        <div class="relative p-0 border w-full max-w-2xl shadow-lg rounded-lg bg-white" id="modal-content">
            <!-- Content loaded via JS/AJAX -->
        </div>
    </div>

    <!-- Add Task Modal -->
    <div id="add-task-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Create Compliance Task</h3>
                <form id="create-task-form" hx-post="../backend/tasks.php" hx-swap="none" 
                      hx-on::after-request="if(event.detail.successful) { closeTaskModal(); alert('Task created successfully!'); }">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Facility</label>
                        <select name="facility_id" required 
                                hx-get="../backend/tasks.php?action=get_floors" 
                                hx-target="#floor_select" 
                                hx-trigger="change"
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Select Facility</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility['id']; ?>"><?php echo htmlspecialchars($facility['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Floor</label>
                        <select id="floor_select" name="floor_id" 
                                hx-get="../backend/tasks.php?action=get_rooms" 
                                hx-target="#room_select" 
                                hx-trigger="change"
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Select Facility First</option>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Room</label>
                        <select id="room_select" name="room_id" 
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Select Floor First</option>
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
                        <textarea name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="4"></textarea>
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Assigned To</label>
                        <input type="text" name="assigned_to" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Due Date</label>
                        <input type="date" name="due_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Priority</label>
                        <select name="priority" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>

                    <div class="mt-2 text-left">
                         <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="Pending" selected>Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div class="items-center px-4 py-3">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Create Task
                        </button>
                        <button type="button" onclick="closeTaskModal()" class="mt-3 px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
