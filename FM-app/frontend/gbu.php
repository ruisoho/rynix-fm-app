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
    <title>Gefährdungsbeurteilung (GBU) - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../assets/htmx.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-800">Risk Assessment (GBU)</h1>
                    <div class="flex items-center space-x-4">
                        <button onclick="openCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i> New Assessment
                        </button>
                    </div>
                </div>
                <!-- Filter Bar -->
                <div class="border-t px-6 py-3 flex flex-wrap gap-4 items-center bg-gray-50">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="filter-search" placeholder="Search area or activity..." 
                            class="pl-10 pr-4 py-2 border rounded-lg text-sm focus:outline-none focus:border-blue-500 w-64"
                            onkeyup="applyFilters()">
                    </div>
                    
                    <select id="filter-status" onchange="applyFilters()" class="border rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500 bg-white">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="review_pending">Review Pending</option>
                        <option value="archived">Archived</option>
                    </select>
                    
                    <label class="flex items-center space-x-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-200 px-2 py-1 rounded transition">
                        <input type="checkbox" id="filter-high-risk" onchange="applyFilters()" class="rounded text-blue-600 focus:ring-blue-500">
                        <span>High Risk Only</span>
                    </label>

                    <label class="flex items-center space-x-2 text-sm text-gray-700 cursor-pointer hover:bg-gray-200 px-2 py-1 rounded transition">
                        <input type="checkbox" id="filter-review-due" onchange="applyFilters()" class="rounded text-orange-600 focus:ring-orange-500">
                        <span>Review Due Soon</span>
                    </label>
                </div>
            </header>

            <!-- GBU List -->
            <main class="flex-1 overflow-y-auto p-6">
                <div id="gbu-list" class="space-y-4">
                    <!-- Cards will be loaded here -->
                    <div class="text-center py-10 text-gray-500">
                        <i class="fas fa-spinner fa-spin text-3xl mb-3"></i><br>Loading Assessments...
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create GBU Modal -->
    <div id="create-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-xl font-bold mb-4">New Risk Assessment</h3>
            <form id="create-form" onsubmit="createGBU(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Area / Location</label>
                        <input type="text" name="area" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2" placeholder="e.g., Workshop, Lab 2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Activity / Process</label>
                        <input type="text" name="activity" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2" placeholder="e.g., Electrical Maintenance">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Scope Type</label>
                        <select name="scope_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                            <option value="area">Area</option>
                            <option value="activity">Activity</option>
                            <option value="asset">Asset / Machine</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create Draft</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load GBUs on start
        document.addEventListener('DOMContentLoaded', loadGBUs);

        async function loadGBUs() {
            const area = document.getElementById('filter-search')?.value || '';
            const status = document.getElementById('filter-status')?.value || '';
            const highRisk = document.getElementById('filter-high-risk')?.checked ? 1 : '';
            const reviewDue = document.getElementById('filter-review-due')?.checked ? 1 : '';

            const params = new URLSearchParams({
                action: 'list',
                area: area,
                status: status,
                high_risk: highRisk,
                review_due: reviewDue
            });

            try {
                const res = await fetch(`../backend/gbu.php?${params}`);
                const data = await res.json();
                renderGBUs(data);
            } catch (e) {
                console.error(e);
            }
        }
        
        function applyFilters() {
            loadGBUs();
        }

        function renderGBUs(gbus) {
            const container = document.getElementById('gbu-list');
            if (gbus.length === 0) {
                container.innerHTML = '<div class="text-center py-10 text-gray-400">No assessments found. Create one to get started.</div>';
                return;
            }

            container.innerHTML = gbus.map(gbu => `
                <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer border-l-4 ${getStatusColor(gbu.status)} flex items-center justify-between p-4" onclick="window.location.href='gbu_edit.php?id=${gbu.id}'">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-1">
                            <h3 class="text-lg font-bold text-gray-900">${gbu.activity}</h3>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 bg-gray-100 px-2 py-0.5 rounded">${gbu.scope_type}</span>
                        </div>
                        <p class="text-gray-600 text-sm">${gbu.area}</p>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        <!-- Actions -->
                        <button onclick="event.stopPropagation(); window.open('../backend/gbu_export.php?id=${gbu.id}', '_blank')" class="text-gray-400 hover:text-blue-600 p-2" title="Export PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>

                         <!-- Stats -->
                         <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <div title="Hazards" class="flex items-center"><i class="fas fa-exclamation-triangle mr-1 text-gray-400"></i> ${gbu.hazard_count || 0}</div>
                            <div title="Open Measures" class="flex items-center"><i class="fas fa-clipboard-check mr-1 text-gray-400"></i> ${gbu.open_measures || 0}</div>
                         </div>

                        <!-- Status & Meta -->
                        <div class="text-right">
                             <span class="px-2 py-1 rounded-full text-xs font-semibold ${getStatusBadge(gbu.status)}">
                                ${gbu.status}
                            </span>
                            <div class="text-xs text-gray-400 mt-1">
                                Updated: ${new Date(gbu.created_at).toLocaleDateString()}
                            </div>
                        </div>
                        
                        <div class="text-gray-400">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function getStatusColor(status) {
            if (status === 'active') return 'border-green-500';
            if (status === 'archived') return 'border-gray-500';
            return 'border-yellow-500'; // draft
        }

        function getStatusBadge(status) {
            if (status === 'active') return 'bg-green-100 text-green-800';
            if (status === 'archived') return 'bg-gray-100 text-gray-800';
            return 'bg-yellow-100 text-yellow-800';
        }

        function openCreateModal() {
            document.getElementById('create-modal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('create-modal').classList.add('hidden');
        }

        async function createGBU(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('../backend/gbu.php?action=save_gbu', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    window.location.href = `gbu_edit.php?id=${result.id}`;
                }
            } catch (err) {
                alert('Error creating GBU');
            }
        }
    </script>
</body>
</html>
