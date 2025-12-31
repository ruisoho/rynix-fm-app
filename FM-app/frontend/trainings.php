<?php
require_once '../backend/auth.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Training - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm z-10 px-6 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Safety Instructions / Trainings</h1>
                <div class="space-x-3">
                    <button onclick="openTemplateModal()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-50">
                        <i class="fas fa-file-alt mr-2"></i> Templates
                    </button>
                    <button onclick="openSessionModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-calendar-plus mr-2"></i> Schedule Session
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 space-y-6">
                
                <!-- Dashboard & Stats -->
                <section class="mb-8">
                    <h2 class="text-lg font-bold text-gray-800 mb-3">Training Dashboard</h2>
                    
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-red-50 p-4 rounded border-l-4 border-red-500 shadow-sm">
                            <div class="text-sm text-red-700 font-bold uppercase">Overdue</div>
                            <div class="text-3xl font-bold text-red-800" id="stat-overdue">-</div>
                            <div class="text-xs text-red-600 mt-1">Immediate action required</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded border-l-4 border-yellow-500 shadow-sm">
                            <div class="text-sm text-yellow-700 font-bold uppercase">Due 30 Days</div>
                            <div class="text-3xl font-bold text-yellow-800" id="stat-due-30">-</div>
                            <div class="text-xs text-yellow-600 mt-1">Plan sessions now</div>
                        </div>
                        <div class="bg-blue-50 p-4 rounded border-l-4 border-blue-500 shadow-sm">
                            <div class="text-sm text-blue-700 font-bold uppercase">Due 60 Days</div>
                            <div class="text-3xl font-bold text-blue-800" id="stat-due-60">-</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded border-l-4 border-green-500 shadow-sm">
                            <div class="text-sm text-green-700 font-bold uppercase">Due 90 Days</div>
                            <div class="text-3xl font-bold text-green-800" id="stat-due-90">-</div>
                        </div>
                    </div>

                    <!-- Upcoming Sessions -->
                    <h3 class="text-md font-bold text-gray-700 mb-2">Upcoming Sessions</h3>
                    <div id="upcoming-sessions-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Loaded via JS -->
                    </div>
                </section>

                <!-- Training Matrix / Due -->
                <section>
                    <h2 class="text-lg font-bold text-gray-800 mb-3">Required Trainings (From Active GBUs)</h2>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Training Topic</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target Group</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source (GBU)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Interval</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody id="matrix-list" class="bg-white divide-y divide-gray-200">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Compliance Overview -->
                <section>
                    <h2 class="text-lg font-bold text-gray-800 mb-3">Employee Compliance Status</h2>
                    
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="bg-white p-4 rounded shadow">
                            <div class="text-sm text-gray-500">Overall Compliance</div>
                            <div class="text-2xl font-bold text-gray-800" id="stat-compliance">-%</div>
                        </div>
                        <div class="bg-white p-4 rounded shadow">
                            <div class="text-sm text-gray-500">Pending Requirements</div>
                            <div class="text-2xl font-bold text-orange-600" id="stat-pending">-</div>
                        </div>
                        <div class="bg-white p-4 rounded shadow">
                            <div class="text-sm text-gray-500">Overdue</div>
                            <div class="text-2xl font-bold text-red-600" id="stat-overdue">-</div>
                        </div>
                        <div class="bg-white p-4 rounded shadow">
                            <div class="text-sm text-gray-500">Employees Tracked</div>
                            <div class="text-2xl font-bold text-blue-600" id="stat-employees">-</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Reqs</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overdue</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody id="compliance-list" class="bg-white divide-y divide-gray-200">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Upcoming Sessions -->
                <section>
                    <h2 class="text-lg font-bold text-gray-800 mb-3">Scheduled Sessions</h2>
                    <div id="sessions-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Loaded via JS -->
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Template Modal -->
    <div id="template-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-bold mb-4">Manage Training Templates</h3>
            <div id="template-list-view">
                <ul id="template-ul" class="mb-4 space-y-2 max-h-60 overflow-y-auto border p-2 rounded"></ul>
                <button onclick="showTemplateForm()" class="w-full py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">+ Create New Template</button>
                <button onclick="closeTemplateModal()" class="w-full mt-2 py-2 text-gray-500">Close</button>
            </div>
            
            <form id="template-form" class="hidden" onsubmit="saveTemplate(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="title" required class="mt-1 block w-full rounded border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Standard Interval (Days)</label>
                        <input type="number" name="interval_days" placeholder="365" class="mt-1 block w-full rounded border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Content Outline</label>
                        <textarea name="content_outline" rows="3" required class="mt-1 block w-full rounded border p-2"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="hideTemplateForm()" class="px-4 py-2 text-gray-600">Back</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Session Modal -->
    <div id="session-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-bold mb-4">Schedule Training Session</h3>
            <form id="session-form" onsubmit="createSession(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Training Topic</label>
                        <select id="session-template-select" name="template_id" required class="mt-1 block w-full rounded border p-2"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" required class="mt-1 block w-full rounded border p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <input type="text" name="location" required class="mt-1 block w-full rounded border p-2">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeSessionModal()" class="px-4 py-2 text-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div id="attendance-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-xl font-bold mb-4">Manage Attendance</h3>
            <div id="attendance-list" class="space-y-2 mb-4 max-h-60 overflow-y-auto">
                <!-- User list with checkboxes -->
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="document.getElementById('attendance-modal').classList.add('hidden')" class="px-4 py-2 text-gray-600">Close</button>
                <button onclick="saveAttendance()" class="px-4 py-2 bg-green-600 text-white rounded">Save & Sign</button>
            </div>
        </div>
    </div>

    <script>
        let currentSessionId = null;

        function openAttendanceModal(sessionId) {
            currentSessionId = sessionId;
            document.getElementById('attendance-modal').classList.remove('hidden');
            loadAttendanceList();
        }

        async function loadAttendanceList() {
            // Mock list of employees for now (ideally fetch specific target group)
            const res = await fetch('../backend/gbu.php?action=get_users');
            const users = await res.json();
            const container = document.getElementById('attendance-list');
            container.innerHTML = users.map(u => `
                <div class="flex items-center justify-between p-2 border rounded hover:bg-gray-50">
                    <div>
                        <div class="font-bold text-sm">${u.full_name || u.username}</div>
                        <div class="text-xs text-gray-500">${u.role}</div>
                    </div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" class="attendance-check rounded text-blue-600" value="${u.id}">
                        <span class="text-sm">Present</span>
                    </label>
                </div>
            `).join('');
        }

        async function saveAttendance() {
            const checks = document.querySelectorAll('.attendance-check:checked');
            for (const chk of checks) {
                await fetch('../backend/trainings.php?action=log_attendance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        session_id: currentSessionId,
                        person_id: chk.value,
                        attended: 1,
                        signed_at: new Date().toISOString()
                    })
                });
            }
            alert('Attendance saved!');
            document.getElementById('attendance-modal').classList.add('hidden');
            loadDashboard(); // Refresh stats
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadDashboard();
            loadMatrix();
            // loadSessions(); // Now part of dashboard
            loadTemplatesForSelect();
        });

        async function loadDashboard() {
            try {
                const res = await fetch('../backend/trainings.php?action=dashboard_stats');
                const data = await res.json();
                
                // Stats
                document.getElementById('stat-overdue').textContent = data.stats.overdue;
                document.getElementById('stat-due-30').textContent = data.stats.due_30;
                document.getElementById('stat-due-60').textContent = data.stats.due_60;
                document.getElementById('stat-due-90').textContent = data.stats.due_90;

                // Upcoming Sessions
                const sessionContainer = document.getElementById('upcoming-sessions-list');
                if (data.sessions.length === 0) {
                    sessionContainer.innerHTML = '<div class="col-span-full text-gray-500 italic">No upcoming sessions scheduled.</div>';
                } else {
                    sessionContainer.innerHTML = data.sessions.map(s => `
                        <div class="bg-white rounded shadow p-4 border-l-4 border-blue-500">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-bold uppercase text-gray-500">Session</span>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">${s.status}</span>
                            </div>
                            <h3 class="font-bold text-md mb-1">${s.template_title}</h3>
                            <div class="text-sm text-gray-600 space-y-1 mb-3">
                                <p><i class="far fa-calendar mr-2"></i> ${new Date(s.scheduled_at).toLocaleString()}</p>
                                <p><i class="fas fa-map-marker-alt mr-2"></i> ${s.location}</p>
                                <p><i class="fas fa-user-tie mr-2"></i> ${s.trainer_name || 'Assigned Trainer'}</p>
                            </div>
                            <button onclick="openAttendanceModal('${s.id}')" class="w-full text-center border border-blue-600 text-blue-600 rounded py-1 hover:bg-blue-50 text-xs">
                                Manage Attendance
                            </button>
                        </div>
                    `).join('');
                }

            } catch(e) { console.error(e); }
        }

        async function loadMatrix() {
            const res = await fetch('../backend/trainings.php?action=matrix');
            const data = await res.json();
            const tbody = document.getElementById('matrix-list');
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No active training requirements found.</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(row => `
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${row.title}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${row.target_group}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${row.activity} (${row.area})</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${row.interval_days ? row.interval_days + ' days' : 'Event-based'}</td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <button onclick="openSessionModal('${row.template_id}')" class="text-blue-600 hover:text-blue-900">Plan Session</button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Removed loadCompliance as table was removed
        // Removed loadSessions as it's merged into loadDashboard

        // --- Template Mgmt ---
        function openTemplateModal() {
            document.getElementById('template-modal').classList.remove('hidden');
            fetchTemplatesList();
        }
        function closeTemplateModal() { document.getElementById('template-modal').classList.add('hidden'); }
        
        async function fetchTemplatesList() {
            const res = await fetch('../backend/trainings.php?action=list_templates');
            const data = await res.json();
            document.getElementById('template-ul').innerHTML = data.map(t => 
                `<li class="border-b last:border-0 p-2 text-sm">${t.title} (${t.interval_days || 'n/a'}d)</li>`
            ).join('');
        }
        
        function showTemplateForm() {
            document.getElementById('template-list-view').classList.add('hidden');
            document.getElementById('template-form').classList.remove('hidden');
        }
        function hideTemplateForm() {
            document.getElementById('template-form').classList.add('hidden');
            document.getElementById('template-list-view').classList.remove('hidden');
        }

        async function saveTemplate(e) {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            await fetch('../backend/trainings.php?action=save_template', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            e.target.reset();
            hideTemplateForm();
            fetchTemplatesList();
            loadTemplatesForSelect(); // Refresh dropdown
        }

        // --- Session Mgmt ---
        async function loadTemplatesForSelect() {
            const res = await fetch('../backend/trainings.php?action=list_templates');
            const data = await res.json();
            const select = document.getElementById('session-template-select');
            select.innerHTML = data.map(t => `<option value="${t.id}">${t.title}</option>`).join('');
        }

        function openSessionModal(preselectId = null) {
            document.getElementById('session-modal').classList.remove('hidden');
            if(preselectId) document.getElementById('session-template-select').value = preselectId;
        }
        function closeSessionModal() { document.getElementById('session-modal').classList.add('hidden'); }

        async function createSession(e) {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            await fetch('../backend/trainings.php?action=create_session', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            closeSessionModal();
            loadSessions();
        }

    </script>
</body>
</html>
