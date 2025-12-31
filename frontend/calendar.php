<?php
require_once '../backend/auth.php';
require_once '../backend/csrf_helper.php';
requireLogin();
$csrf_token = generateCsrfToken();
$userRole = $_SESSION['user']['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .fc-event { cursor: pointer; }
    </style>
</head>
<body class="bg-gray-100 font-sans">

<?php if ($userRole === 'staff'): ?>
    <!-- Staff Layout -->
    <header class="bg-blue-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <a href="staff_portal.php" class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h1 class="text-xl font-bold tracking-wider">FM App <span class="text-blue-300 font-normal">| Staff Portal</span></h1>
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="staff_portal.php" class="text-blue-200 hover:text-white font-medium mr-2">Dashboard</a>
                <span class="text-white font-bold underline mr-2">Calendar</span>
                
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Calendar</h1>
            <button onclick="openCreateModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Event
            </button>
        </div>

        <div class="bg-white p-6 rounded-lg shadow h-[800px]">
            <div id="calendar"></div>
        </div>
    </main>

<?php else: ?>
    <!-- Admin/Manager/User Layout (Sidebar) -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Calendar</h1>
                    <button onclick="openCreateModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Event
                    </button>
                </div>

                <div class="bg-white p-6 rounded-lg shadow h-[800px]">
                    <div id="calendar"></div>
                </div>
            </main>
        </div>
    </div>
<?php endif; ?>

    <!-- Create Event Modal -->
    <div id="create-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Add New Event</h3>
                <form id="create-event-form" class="mt-4 text-left">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                        <input type="text" id="event-title" name="title" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea id="event-description" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Start</label>
                            <input type="datetime-local" id="event-start" name="start" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">End</label>
                            <input type="datetime-local" id="event-end" name="end" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>

                    <?php if ($userRole === 'admin' || $userRole === 'manager'): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Visibility</label>
                        <select id="event-visibility" name="visibility_type" onchange="toggleVisibilityTarget()" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="public">Public (All Users)</option>
                            <option value="private">Private (Only Me)</option>
                            <option value="role">Specific Role</option>
                            <option value="user">Specific User ID</option>
                        </select>
                    </div>
                    <div id="visibility-target-container" class="mb-4 hidden">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Target (Role or User ID)</label>
                        <input type="text" id="event-visibility-target" name="visibility_target" placeholder="e.g. staff or 5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center justify-end mt-6">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-auto shadow-sm hover:bg-gray-400 focus:outline-none mr-2">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-500 text-white text-base font-medium rounded-md w-auto shadow-sm hover:bg-indigo-700 focus:outline-none">
                            Save Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div id="details-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="details-title" class="text-xl leading-6 font-bold text-gray-900 mb-2">Event Title</h3>
                <p id="details-time" class="text-sm text-gray-500 mb-4"></p>
                <div id="details-desc" class="text-gray-700 mb-4 bg-gray-50 p-3 rounded"></div>
                
                <div class="flex justify-end space-x-2">
                    <button id="delete-event-btn" onclick="deleteEvent()" class="hidden px-4 py-2 bg-red-500 text-white text-sm font-medium rounded-md hover:bg-red-700">
                        Delete
                    </button>
                    <button onclick="closeDetailsModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let calendar;
        let selectedEventId = null;

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: '../backend/calendar.php',
                selectable: true,
                editable: false, // For now
                select: function(info) {
                    openCreateModal(info.startStr, info.endStr);
                },
                eventClick: function(info) {
                    showEventDetails(info.event);
                }
            });
            calendar.render();
        });

        function openCreateModal(startStr, endStr) {
            document.getElementById('create-modal').classList.remove('hidden');
            if (startStr) {
                // Convert to datetime-local format (YYYY-MM-DDTHH:MM)
                let start = new Date(startStr);
                start.setMinutes(start.getMinutes() - start.getTimezoneOffset());
                document.getElementById('event-start').value = start.toISOString().slice(0, 16);
            }
            if (endStr) {
                 let end = new Date(endStr);
                 // FullCalendar end date is exclusive for all-day events, so subtract 1 minute or keep as is?
                 // Usually for day selection it returns next day 00:00.
                 // Let's just set it.
                 end.setMinutes(end.getMinutes() - end.getTimezoneOffset());
                 document.getElementById('event-end').value = end.toISOString().slice(0, 16);
            }
        }

        function closeCreateModal() {
            document.getElementById('create-modal').classList.add('hidden');
            document.getElementById('create-event-form').reset();
        }

        function toggleVisibilityTarget() {
            const type = document.getElementById('event-visibility').value;
            const container = document.getElementById('visibility-target-container');
            if (type === 'role' || type === 'user') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        document.getElementById('create-event-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'create',
                title: document.getElementById('event-title').value,
                description: document.getElementById('event-description').value,
                start: document.getElementById('event-start').value,
                end: document.getElementById('event-end').value,
                visibility_type: document.getElementById('event-visibility') ? document.getElementById('event-visibility').value : 'private',
                visibility_target: document.getElementById('event-visibility-target') ? document.getElementById('event-visibility-target').value : ''
            };

            fetch('../backend/calendar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeCreateModal();
                    calendar.refetchEvents();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });

        function showEventDetails(event) {
            selectedEventId = event.id;
            document.getElementById('details-title').innerText = event.title;
            
            let timeStr = event.start.toLocaleString();
            if (event.end) {
                timeStr += ' - ' + event.end.toLocaleString();
            }
            document.getElementById('details-time').innerText = timeStr;
            
            const props = event.extendedProps;
            let desc = props.description || '';
            
            // Add extra info for tasks
            if (props.type === 'maintenance' || props.type === 'daily_task') {
                desc += `<br><strong>Status:</strong> ${props.status}`;
                if (props.priority) desc += `<br><strong>Priority:</strong> ${props.priority}`;
                document.getElementById('delete-event-btn').classList.add('hidden'); // Cannot delete tasks here
            } else {
                // Check if user can delete (creator or admin)
                // We rely on backend to reject, but UI wise we can show button if it's "event" type
                // Ideally we check permissions. For now, show it for all "event" types, backend will block.
                 document.getElementById('delete-event-btn').classList.remove('hidden');
            }

            document.getElementById('details-desc').innerHTML = desc;
            document.getElementById('details-modal').classList.remove('hidden');
        }

        function closeDetailsModal() {
            document.getElementById('details-modal').classList.add('hidden');
            selectedEventId = null;
        }

        function deleteEvent() {
            if (!selectedEventId || !confirm('Are you sure you want to delete this event?')) return;

            fetch('../backend/calendar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: selectedEventId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDetailsModal();
                    calendar.getEventById(selectedEventId).remove();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
    </script>
</body>
</html>