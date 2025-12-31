<?php
require_once '../backend/auth.php';
// Ensure user is logged in
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
    <title>Legal Updates - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <style>
        .diff-added { background-color: #d1fae5; color: #065f46; }
        .diff-removed { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-sync-alt mr-2 text-blue-600"></i> Legal Change Management
                    </h1>
                    <div class="flex items-center space-x-4">
                        <button id="check-btn" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex items-center"
                                onclick="runCheck()">
                            <i class="fas fa-search mr-2"></i> Check Sources Now
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Area -->
            <main class="flex-1 overflow-y-auto p-6">
                
                <!-- Status Bar -->
                <div id="status-area" class="mb-6 hidden">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700" id="status-text">
                                    Checking official sources (bundesgesetzblatt.de, gesetze-im-internet.de)...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Updates List -->
                <div id="updates-container" class="space-y-6">
                    <!-- Loaded via JS -->
                    <div class="text-center text-gray-500 py-10">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>
                        Loading pending updates...
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        // Initial Load
        document.addEventListener('DOMContentLoaded', loadUpdates);

        function loadUpdates() {
            fetch('../backend/legal_watchers.php?action=list_updates')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('updates-container');
                    if (data.updates.length === 0) {
                        container.innerHTML = `
                            <div class="text-center py-12 bg-white rounded-lg shadow">
                                <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900">All caught up!</h3>
                                <p class="text-gray-500 mt-2">No pending legal updates found from monitored sources.</p>
                            </div>
                        `;
                        return;
                    }

                    let html = '';
                    data.updates.forEach(u => {
                        html += `
                            <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200" id="update-${u.id}">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            ${u.abbreviation} - ${u.change_type.toUpperCase()}
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            Detected: ${u.detected_at} | Source: <a href="${u.source_url}" target="_blank" class="text-blue-600 hover:underline">${new URL(u.source_url).hostname}</a>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                        Review Required
                                    </span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- AI Summary -->
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2">
                                                <i class="fas fa-robot text-purple-600 mr-1"></i> AI Impact Analysis
                                            </h4>
                                            <div class="bg-purple-50 rounded p-4 text-sm text-gray-800 border border-purple-100">
                                                <p class="mb-2"><strong>Summary:</strong> ${u.summary}</p>
                                                <p class="mb-2"><strong>Affected Sections:</strong> ${u.affected_sections}</p>
                                                <p><strong>Suggestion:</strong> ${u.suggested_obligations}</p>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-col justify-center items-start space-y-4">
                                            <div class="text-sm text-gray-600">
                                                <p class="mb-2"><i class="fas fa-exclamation-triangle text-orange-500 mr-2"></i> <strong>Action Required:</strong></p>
                                                <ul class="list-disc pl-5 space-y-1">
                                                    <li>Verify change against official text.</li>
                                                    <li>Update internal obligation database.</li>
                                                    <li>Notify relevant facility managers.</li>
                                                </ul>
                                            </div>
                                            <div class="flex space-x-3 w-full">
                                                <button onclick="processUpdate(${u.id}, 'approve')" 
                                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none">
                                                    <i class="fas fa-check mr-2"></i> Approve & Merge
                                                </button>
                                                <button onclick="processUpdate(${u.id}, 'dismiss')" 
                                                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none">
                                                    <i class="fas fa-times mr-2"></i> Dismiss
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                });
        }

        function runCheck() {
            const btn = document.getElementById('check-btn');
            const statusArea = document.getElementById('status-area');
            const statusText = document.getElementById('status-text');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Checking...';
            statusArea.classList.remove('hidden');

            fetch('../backend/legal_watchers.php?action=check_updates')
                .then(r => r.json())
                .then(data => {
                    if (data.new_updates > 0) {
                        statusText.innerHTML = `Success! Found ${data.new_updates} new update(s). Refreshing list...`;
                        setTimeout(() => {
                            statusArea.classList.add('hidden');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-search mr-2"></i> Check Sources Now';
                            loadUpdates();
                        }, 2000);
                    } else {
                        statusText.innerHTML = 'Check complete. No new updates found.';
                        setTimeout(() => {
                            statusArea.classList.add('hidden');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-search mr-2"></i> Check Sources Now';
                        }, 3000);
                    }
                })
                .catch(err => {
                    statusText.innerHTML = 'Error checking updates. See console.';
                    console.error(err);
                    btn.disabled = false;
                });
        }

        function processUpdate(id, decision) {
            if (!confirm(`Are you sure you want to ${decision} this update?`)) return;

            const formData = new FormData();
            formData.append('action', 'process_update');
            formData.append('update_id', id);
            formData.append('decision', decision);

            fetch('../backend/legal_watchers.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Remove the card
                    const card = document.getElementById(`update-${id}`);
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 500);
                    
                    // Reload if empty
                    const container = document.getElementById('updates-container');
                    if (container.children.length <= 1) loadUpdates();
                }
            });
        }
    </script>
</body>
</html>
