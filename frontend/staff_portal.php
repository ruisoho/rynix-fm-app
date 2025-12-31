<?php
require_once '../backend/auth.php';
requireLogin();

// Note: requireLogin() in backend/auth.php handles the redirection 
// ensuring only Staff can access this page and Staff cannot access other pages.

require_once '../backend/tasks.php';

// Try to find tasks assigned to this user
$searchTerm = $_SESSION['user']['full_name'] ?? $_SESSION['user']['username'];
$assignedTasks = getDailyTasks(['assigned_to' => $searchTerm]);

// Find tickets created by this user
$createdTickets = getDailyTasks(['created_by' => $_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    
    <!-- Staff Header -->
    <header class="bg-blue-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <h1 class="text-xl font-bold tracking-wider">FM App <span class="text-blue-300 font-normal">| Staff Portal</span></h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="calendar.php" class="text-blue-200 hover:text-white font-medium mr-2">Calendar</a>
                
                <!-- Notification Bell -->
                <div class="relative">
                    <button onclick="document.getElementById('staff-notification-panel').classList.toggle('hidden'); if(!document.getElementById('staff-notification-panel').classList.contains('hidden')) htmx.trigger('#staff-notification-list', 'reload')" 
                            class="text-blue-200 hover:text-white focus:outline-none relative p-1">
                        <i class="fas fa-bell text-xl"></i>
                        <!-- Badge -->
                        <div id="staff-notification-badge" hx-get="../backend/notifications.php?action=count" hx-trigger="load, every 60s"></div>
                    </button>

                    <!-- Notification Panel (Popover) -->
                    <div id="staff-notification-panel" class="hidden absolute right-0 mt-2 w-80 bg-white shadow-xl rounded-lg overflow-hidden border border-gray-200 z-50 text-gray-800">
                         <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                            <button onclick="document.getElementById('staff-notification-panel').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div id="staff-notification-list" class="notification-container" hx-get="../backend/notifications.php?action=list" hx-trigger="load, reload">
                            <div class="p-4 text-center text-gray-500">Loading...</div>
                        </div>
                    </div>
                </div>

                <span class="text-sm">Welcome, <strong><?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']); ?></strong></span>
                <button hx-post="../backend/auth.php" 
                        hx-vals='{"action": "logout", "csrf_token": "<?php echo $csrf_token; ?>"}'
                        class="bg-blue-700 hover:bg-blue-600 px-3 py-1 rounded text-sm transition-colors text-white focus:outline-none">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">My Dashboard</h2>
                <p class="text-gray-600">Access your assigned tasks and work orders here.</p>
            </div>
            <button onclick="openTicketModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 shadow-md flex items-center transition-transform transform hover:scale-105">
                <i class="fas fa-plus-circle mr-2 text-lg"></i>
                <span class="font-semibold">Create Ticket</span>
            </button>
        </div>

        <!-- Tasks Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Assigned Tasks -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-100 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-blue-800">My Assigned Tasks</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"><?php echo count($assignedTasks); ?> Active</span>
                </div>
                <div class="p-6 h-96 overflow-y-auto">
                    <?php if (empty($assignedTasks)): ?>
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-clipboard-check text-4xl mb-3 text-gray-300"></i>
                            <p>No tasks assigned to you currently.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($assignedTasks as $task): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($task['title']); ?></h4>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php 
                                            echo match($task['status']) {
                                                'Completed' => 'bg-green-100 text-green-800',
                                                'In Progress' => 'bg-blue-100 text-blue-800',
                                                'Pending' => 'bg-yellow-100 text-yellow-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>">
                                            <?php echo htmlspecialchars($task['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($task['description']); ?></p>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <i class="far fa-building mr-1"></i> <?php echo htmlspecialchars($task['facility_name'] ?? 'N/A'); ?>
                                        <?php if(!empty($task['floor_name'])): ?>
                                            <span class="mx-1">></span> <?php echo htmlspecialchars($task['floor_name']); ?>
                                        <?php endif; ?>
                                        <?php if(!empty($task['room_name'])): ?>
                                            <span class="mx-1">></span> <?php echo htmlspecialchars($task['room_name']); ?>
                                        <?php endif; ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-exclamation-circle mr-1 <?php echo $task['priority'] === 'High' ? 'text-red-500' : ($task['priority'] === 'Medium' ? 'text-yellow-500' : 'text-green-500'); ?>"></i> <?php echo htmlspecialchars($task['priority'] ?? 'Medium'); ?>
                                        <span class="mx-2">•</span>
                                        <i class="far fa-calendar-alt mr-1"></i> Due: <?php echo htmlspecialchars($task['due_date'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Created Tickets -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-green-50 px-6 py-4 border-b border-green-100 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-green-800">My Created Tickets</h3>
                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"><?php echo count($createdTickets); ?> Total</span>
                </div>
                <div class="p-6 h-96 overflow-y-auto">
                    <?php if (empty($createdTickets)): ?>
                        <div class="text-center text-gray-500 py-8">
                            <i class="fas fa-ticket-alt text-4xl mb-3 text-gray-300"></i>
                            <p>You haven't created any tickets yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($createdTickets as $ticket): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors relative">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($ticket['title']); ?></h4>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php 
                                            echo match($ticket['status']) {
                                                'Completed' => 'bg-green-100 text-green-800',
                                                'In Progress' => 'bg-blue-100 text-blue-800',
                                                'Pending' => 'bg-yellow-100 text-yellow-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>">
                                            <?php echo htmlspecialchars($ticket['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($ticket['description']); ?></p>
                                    <div class="flex items-center text-xs text-gray-500 mb-1">
                                        <i class="far fa-building mr-1"></i> <?php echo htmlspecialchars($ticket['facility_name'] ?? 'N/A'); ?>
                                        <?php if(!empty($ticket['floor_name'])): ?>
                                            <span class="mx-1">></span> <?php echo htmlspecialchars($ticket['floor_name']); ?>
                                        <?php endif; ?>
                                        <?php if(!empty($ticket['room_name'])): ?>
                                            <span class="mx-1">></span> <?php echo htmlspecialchars($ticket['room_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center text-xs text-gray-500">
                                         <i class="fas fa-clock mr-1"></i> Created: <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                         <span class="mx-2">•</span>
                                         <i class="fas fa-exclamation-circle mr-1 <?php echo $ticket['priority'] === 'High' ? 'text-red-500' : ($ticket['priority'] === 'Medium' ? 'text-yellow-500' : 'text-green-500'); ?>"></i> <?php echo htmlspecialchars($ticket['priority'] ?? 'Medium'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </main>

    <!-- Create Ticket Modal -->
    <div id="ticket-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 md:w-[500px] shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-ticket-alt mr-2 text-blue-600"></i> Create New Ticket
                </h3>
                <form id="ticket-form" class="space-y-4">
                    <input type="hidden" name="action" value="create_ticket">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title / Issue *</label>
                        <input type="text" name="title" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border" placeholder="e.g. Broken AC in Lobby">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Facility *</label>
                        <select name="facility_id" id="facility-select" required 
                                hx-get="../backend/tasks.php?action=get_floors" 
                                hx-target="#ticket_floor_select" 
                                hx-trigger="change"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Select Facility</option>
                            <?php 
                            $facilities = getFacilitiesDropdownTasks();
                            foreach($facilities as $f): ?>
                                <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Floor</label>
                        <select name="floor_id" id="ticket_floor_select"
                                hx-get="../backend/tasks.php?action=get_rooms" 
                                hx-target="#ticket_room_select" 
                                hx-trigger="change"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Select Facility First</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                        <select name="room_id" id="ticket_room_select"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">Select Floor First</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <select name="priority" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border" placeholder="Describe the issue in detail..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeTicketModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition-colors">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors flex items-center">
                            <i class="fas fa-paper-plane mr-2"></i> Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openTicketModal() {
            document.getElementById('ticket-modal').classList.remove('hidden');
        }

        function closeTicketModal() {
            document.getElementById('ticket-modal').classList.add('hidden');
            document.getElementById('ticket-form').reset();
            // Reset dynamic dropdowns
            document.getElementById('ticket_floor_select').innerHTML = '<option value="">Select Facility First</option>';
            document.getElementById('ticket_room_select').innerHTML = '<option value="">Select Floor First</option>';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('ticket-modal');
            if (event.target == modal) {
                closeTicketModal();
            }
        }

        document.getElementById('ticket-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';

            const formData = new FormData(this);

            try {
                const response = await fetch('../backend/tasks.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    alert('Ticket created successfully!');
                    closeTicketModal();
                    window.location.reload(); // Refresh to show new task if assigned to self (or just to clear state)
                } else {
                    alert('Failed to create ticket. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please check your connection.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    </script>

</body>
</html>
