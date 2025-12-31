<?php
require_once '../backend/auth.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: gbu.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit GBU - FM App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="../assets/htmx.min.js"></script>
    <style>
        .hazard-card { transition: all 0.2s; }
        .hazard-card:hover { transform: translateY(-2px); }
        .tab-active { border-bottom: 2px solid #2563eb; color: #2563eb; }
        .tab-inactive { border-bottom: 2px solid transparent; color: #6b7280; }
        .tab-inactive:hover { color: #374151; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b px-6 py-4 flex justify-between items-center sticky top-0 z-20">
        <div class="flex items-center space-x-4">
            <a href="gbu.php" class="text-gray-500 hover:text-gray-800"><i class="fas fa-arrow-left"></i> Back</a>
            <div>
                <h1 id="gbu-title" class="text-xl font-bold text-gray-900">Loading...</h1>
                <p id="gbu-subtitle" class="text-sm text-gray-500"></p>
            </div>
            <span id="gbu-status" class="px-3 py-1 rounded-full text-xs font-bold uppercase"></span>
        </div>
        <div class="flex items-center space-x-3">
            <a href="../backend/gbu_export.php?id=<?php echo $id; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 px-3 py-2 text-sm font-medium border border-blue-200 rounded bg-blue-50">
                <i class="fas fa-file-pdf mr-1"></i> Export PDF
            </a>
            <button id="btn-trigger-review" onclick="openReviewModal()" class="hidden text-orange-600 hover:text-orange-800 px-3 py-2 text-sm font-medium border border-orange-200 rounded bg-orange-50">
                <i class="fas fa-sync-alt mr-1"></i> Trigger Review
            </button>
            <button id="btn-activate" onclick="activateGBU()" class="hidden bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 shadow-sm text-sm font-medium">
                <i class="fas fa-check-circle mr-2"></i> Release & Activate
            </button>
        </div>
    </header>

    <!-- Tabs -->
    <div class="bg-white border-b px-6">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('hazards')" id="tab-hazards" class="tab-active py-4 px-1 font-medium text-sm flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i> Hazards & Risks
            </button>
            <button onclick="switchTab('measures')" id="tab-measures" class="tab-inactive py-4 px-1 font-medium text-sm flex items-center">
                <i class="fas fa-clipboard-check mr-2"></i> Measures (TOP)
            </button>
            <button onclick="switchTab('trainings')" id="tab-trainings" class="tab-inactive py-4 px-1 font-medium text-sm flex items-center">
                <i class="fas fa-graduation-cap mr-2"></i> Trainings
            </button>
            <button onclick="switchTab('documents')" id="tab-documents" class="tab-inactive py-4 px-1 font-medium text-sm flex items-center">
                <i class="fas fa-file-alt mr-2"></i> Documents / Proofs
            </button>
            <button onclick="switchTab('history')" id="tab-history" class="tab-inactive py-4 px-1 font-medium text-sm flex items-center">
                <i class="fas fa-history mr-2"></i> History
            </button>
        </nav>
    </div>

    <div class="flex flex-1 overflow-hidden relative">
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-8 space-y-8">
            
            <!-- Tab: Hazards -->
            <section id="content-hazards" class="block">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Risk Matrix</h2>
                    <button onclick="openHazardModal()" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 shadow-sm">
                        + Add Hazard
                    </button>
                </div>
                <div id="hazards-container" class="space-y-6">
                    <!-- Hazards injected here -->
                </div>
            </section>

            <!-- Tab: Measures (Consolidated View) -->
            <section id="content-measures" class="hidden">
                 <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Action Plan (Measures)</h2>
                    <button onclick="alert('Add measures via Hazards tab')" class="text-sm text-gray-500 border px-3 py-1 rounded hover:bg-gray-50">
                        Manage via Hazards
                    </button>
                </div>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                         <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hazard</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Measure</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody id="measures-list" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </section>

            <!-- Tab: Trainings -->
            <section id="content-trainings" class="hidden">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Derived Training Requirements</h2>
                    <button onclick="openTrainingModal()" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 shadow-sm">
                        + Add Requirement
                    </button>
                </div>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Template</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target Group</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trigger</th>
                                <th class="px-6 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody id="training-list" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </section>

            <!-- Tab: Documents -->
            <section id="content-documents" class="hidden">
                 <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Legal Proofs & Attachments</h2>
                    <button onclick="openUploadModal()" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 shadow-sm">
                        + Upload Document
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="documents-container">
                    <!-- Docs here -->
                </div>
            </section>

             <!-- Tab: History -->
            <section id="content-history" class="hidden">
                 <h2 class="text-lg font-semibold text-gray-800 mb-4">Audit Log (Change History)</h2>
                 <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                            </tr>
                        </thead>
                        <tbody id="history-list" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>

    <!-- Modals (Hazard, Control, Training are same as before, adding Upload & Review) -->
    
    <!-- Hazard Modal -->
    <div id="hazard-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold mb-4">Edit Hazard</h3>
            <form id="hazard-form" onsubmit="saveHazard(event)">
                <input type="hidden" name="id">
                <input type="hidden" name="gbu_id" value="<?php echo $id; ?>">
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="hazard_category" class="mt-1 block w-full rounded border-gray-300 border p-2">
                            <option value="electrical">Electrical (Schlag, Lichtbogen)</option>
                            <option value="mechanical">Mechanical (Schnitt, Quetsch)</option>
                            <option value="fall">Fall / Absturz</option>
                            <option value="fire">Fire / Explosion</option>
                            <option value="chemical">Chemicals / Hazardous Substances</option>
                            <option value="ergonomic">Ergonomics</option>
                            <option value="psych">Psychological Stress</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="hazard_description" rows="2" class="mt-1 block w-full rounded border-gray-300 border p-2" required></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Exposed Group</label>
                        <input type="text" name="exposed_group" class="mt-1 block w-full rounded border-gray-300 border p-2" placeholder="e.g. Technicians, Visitors">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Probability (1-5)</label>
                            <input type="number" name="probability" min="1" max="5" class="mt-1 block w-full rounded border-gray-300 border p-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Severity (1-5)</label>
                            <input type="number" name="severity" min="1" max="5" class="mt-1 block w-full rounded border-gray-300 border p-2" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Existing Controls</label>
                        <textarea name="existing_controls" rows="2" class="mt-1 block w-full rounded border-gray-300 border p-2"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeHazardModal()" class="px-4 py-2 text-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Hazard</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Control Modal -->
    <div id="control-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-bold mb-4">Add/Edit Measure (Control)</h3>
            <form id="control-form" onsubmit="saveControl(event)">
                <input type="hidden" name="id">
                <input type="hidden" name="hazard_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type (TOP Principle)</label>
                        <select name="control_level" class="mt-1 block w-full rounded border-gray-300 border p-2">
                            <option value="technical">Technical (T)</option>
                            <option value="organizational">Organizational (O)</option>
                            <option value="personal">Personal (P)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Measure Description</label>
                        <textarea name="description" rows="3" class="mt-1 block w-full rounded border-gray-300 border p-2" required></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Responsible Person</label>
                            <select name="responsible_user_id" id="control-responsible" class="mt-1 block w-full rounded border-gray-300 border p-2">
                                <option value="">-- Select User --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" name="due_date" class="mt-1 block w-full rounded border-gray-300 border p-2">
                        </div>
                    </div>

                    <div class="border-t pt-4 mt-4">
                        <h4 class="text-sm font-bold text-gray-800 mb-2">Effectiveness Check (Wirksamkeitskontrolle)</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Check Date</label>
                                <input type="date" name="effectiveness_check_date" class="mt-1 block w-full rounded border-gray-300 border p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Result / Status</label>
                                <input type="text" name="effectiveness_result" class="mt-1 block w-full rounded border-gray-300 border p-2" placeholder="e.g. Effective, Needs Revision">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeControlModal()" class="px-4 py-2 text-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Save Measure</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Training Modal -->
    <div id="training-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-bold mb-4">Add Training Requirement</h3>
            <form id="training-form" onsubmit="addTrainingReq(event)">
                <input type="hidden" name="gbu_id" value="<?php echo $id; ?>">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Training Template</label>
                        <select id="template-select" name="template_id" class="mt-1 block w-full rounded border-gray-300 border p-2" required>
                            <!-- Populated via JS -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Target Group</label>
                        <input type="text" name="target_group" class="mt-1 block w-full rounded border-gray-300 border p-2" placeholder="e.g. Electricians" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Trigger / Interval</label>
                        <select name="trigger" class="mt-1 block w-full rounded border-gray-300 border p-2">
                            <option value="annual">Annual (Recurring)</option>
                            <option value="onboarding">Onboarding (Once)</option>
                            <option value="task_based">Task Based</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeTrainingModal()" class="px-4 py-2 text-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add Requirement</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
             <h3 class="text-lg font-bold mb-4">Upload Document</h3>
             <form id="upload-form" onsubmit="uploadDoc(event)">
                 <input type="hidden" name="gbu_id" value="<?php echo $id; ?>">
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-medium text-gray-700">File (PDF, Img)</label>
                         <input type="file" name="file" required class="mt-1 block w-full border border-gray-300 rounded p-2">
                     </div>
                 </div>
                 <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="px-4 py-2 text-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Upload</button>
                </div>
             </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="review-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
             <h3 class="text-lg font-bold mb-4">Trigger Event-Based Review</h3>
             <p class="text-sm text-gray-600 mb-4">This will set the status to 'draft', set a due date (14 days), and generate event-based training requirements.</p>
             <form id="review-form" onsubmit="triggerReview(event)">
                 <input type="hidden" name="id" value="<?php echo $id; ?>">
                 <div class="space-y-4">
                     <div>
                         <label class="block text-sm font-medium text-gray-700">Trigger Reason</label>
                         <select name="trigger_type" class="mt-1 block w-full rounded border-gray-300 border p-2">
                            <option value="change">Significant Change</option>
                            <option value="incident">Incident / Accident</option>
                            <option value="new_equipment">New Equipment</option>
                            <option value="new_law_update">Legal Update</option>
                        </select>
                     </div>
                 </div>
                 <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('review-modal').classList.add('hidden')" class="px-4 py-2 text-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">Trigger Review</button>
                </div>
             </form>
        </div>
    </div>

    <script>
        const GBU_ID = '<?php echo $id; ?>';
        let currentGBU = null;
        
        // Lazy Loading Flags
        let loadedTabs = {
            trainings: false,
            documents: false,
            history: false
        };

        document.addEventListener('DOMContentLoaded', () => {
            loadGBU();
            loadTemplates();
            loadUsers();
        });

        // --- TAB SWITCHING ---
        async function switchTab(tabName) {
            ['hazards', 'measures', 'trainings', 'documents', 'history'].forEach(t => {
                document.getElementById(`content-${t}`).classList.add('hidden');
                document.getElementById(`tab-${t}`).classList.remove('tab-active');
                document.getElementById(`tab-${t}`).classList.add('tab-inactive');
            });
            document.getElementById(`content-${tabName}`).classList.remove('hidden');
            document.getElementById(`tab-${tabName}`).classList.add('tab-active');
            document.getElementById(`tab-${tabName}`).classList.remove('tab-inactive');

            // Lazy Load Logic
            if (loadedTabs[tabName] === false) {
                await loadTabContent(tabName);
                loadedTabs[tabName] = true;
            }
        }

        async function loadGBU() {
            try {
                const res = await fetch(`../backend/gbu.php?action=get&id=${GBU_ID}`);
                if (!res.ok) throw new Error('Failed to load');
                currentGBU = await res.json();
                renderHeader();
                renderHazards();
                renderMeasures();
                
                // Initialize empty arrays for other sections
                currentGBU.training_requirements = currentGBU.training_requirements || [];
                currentGBU.documents = currentGBU.documents || [];
                currentGBU.history = currentGBU.history || [];
            } catch (e) {
                console.error(e);
                alert('Error loading GBU data');
            }
        }

        async function loadTabContent(section) {
            const containerMap = {
                'trainings': 'training-list',
                'documents': 'documents-container',
                'history': 'history-list'
            };
            const containerId = containerMap[section];
            if(containerId) document.getElementById(containerId).innerHTML = '<div class="p-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

            try {
                const res = await fetch(`../backend/gbu.php?action=get&id=${GBU_ID}&section=${section}`);
                const data = await res.json();

                if (section === 'trainings') {
                    currentGBU.training_requirements = data;
                    renderTrainingReqs();
                } else if (section === 'documents') {
                    currentGBU.documents = data;
                    renderDocuments();
                } else if (section === 'history') {
                    currentGBU.history = data;
                    renderHistory();
                }
            } catch(e) {
                console.error("Lazy load failed", e);
            }
        }

        async function loadTemplates() {
            try {
                const res = await fetch('../backend/trainings.php?action=list_templates');
                const templates = await res.json();
                const select = document.getElementById('template-select');
                select.innerHTML = templates.map(t => `<option value="${t.id}">${t.title}</option>`).join('');
            } catch(e) {}
        }

        function renderHeader() {
            document.getElementById('gbu-title').textContent = currentGBU.activity;
            document.getElementById('gbu-subtitle').textContent = `${currentGBU.area} (${currentGBU.scope_type})`;
            
            const statusEl = document.getElementById('gbu-status');
            statusEl.textContent = currentGBU.status;
            statusEl.className = `px-3 py-1 rounded-full text-xs font-bold uppercase ${currentGBU.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`;

            const btnActivate = document.getElementById('btn-activate');
            const btnTrigger = document.getElementById('btn-trigger-review');

            if (currentGBU.status === 'draft') {
                btnActivate.classList.remove('hidden');
                btnTrigger.classList.add('hidden');
            } else {
                btnActivate.classList.add('hidden');
                btnTrigger.classList.remove('hidden');
            }
        }

        function renderHazards() {
            const container = document.getElementById('hazards-container');
            container.innerHTML = currentGBU.hazards.map(h => `
                <div class="bg-white border rounded-lg p-6 hazard-card shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center space-x-3">
                            <span class="text-2xl">${getCategoryIcon(h.hazard_category)}</span>
                            <div>
                                <h4 class="font-bold text-gray-900">${h.hazard_description}</h4>
                                <span class="text-xs text-gray-500">Exposed: ${h.exposed_group}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500 uppercase">Risk Score</div>
                            <div class="font-bold text-lg ${getRiskColor(h.risk_score)}">${h.risk_score}</div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded p-3 mb-4 text-sm text-gray-700">
                        <strong>Existing Controls:</strong> ${h.existing_controls || 'None'}
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-xs font-semibold text-gray-500 uppercase border-b pb-1">
                            <span>Required Measures (TOP)</span>
                            <button onclick="openControlModal('${h.id}')" class="text-blue-600 hover:text-blue-800">+ Add Measure</button>
                        </div>
                        ${h.controls.map(c => `
                            <div class="flex justify-between items-center text-sm py-1 border-b border-gray-100 last:border-0">
                                <div class="flex items-center space-x-2">
                                    <span class="w-6 font-bold ${getTOPColor(c.control_level)}">${c.control_level.charAt(0).toUpperCase()}</span>
                                    <span>${c.description}</span>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded ${c.status === 'open' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100'}">${c.status}</span>
                            </div>
                        `).join('')}
                        ${h.controls.length === 0 ? '<div class="text-xs text-gray-400 italic">No measures defined yet.</div>' : ''}
                    </div>
                </div>
            `).join('');
        }

        function renderMeasures() {
            // Flatten all controls from all hazards
            const allControls = [];
            currentGBU.hazards.forEach(h => {
                h.controls.forEach(c => {
                    allControls.push({...c, hazard_desc: h.hazard_description});
                });
            });

            const tbody = document.getElementById('measures-list');
            if (allControls.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No measures yet.</td></tr>';
                return;
            }

            tbody.innerHTML = allControls.map(c => `
                <tr>
                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate" title="${c.hazard_desc}">${c.hazard_desc}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">${c.description}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="font-bold ${getTOPColor(c.control_level)}">${c.control_level.toUpperCase()}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${c.due_date || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 rounded text-xs ${c.status === 'open' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'}">${c.status}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="editControl('${c.id}')" class="text-blue-600 hover:text-blue-900 mr-2">Edit</button>
                    </td>
                </tr>
            `).join('');
        }

        function renderTrainingReqs() {
            const tbody = document.getElementById('training-list');
            if (!currentGBU.training_requirements.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No training requirements.</td></tr>';
                return;
            }
            tbody.innerHTML = currentGBU.training_requirements.map(r => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${r.template_title}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${r.target_group}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${r.trigger}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="deleteTrainingReq('${r.id}')" class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        function renderDocuments() {
            const container = document.getElementById('documents-container');
            if (!currentGBU.documents || !currentGBU.documents.length) {
                container.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400">No documents uploaded.</div>';
                return;
            }
            container.innerHTML = currentGBU.documents.map(d => `
                <div class="bg-white border rounded p-4 flex items-center justify-between">
                    <div class="flex items-center overflow-hidden">
                        <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                        <div class="truncate">
                            <a href="${d.file_path}" target="_blank" class="text-blue-600 hover:underline font-medium truncate block">${d.filename}</a>
                            <span class="text-xs text-gray-500">${new Date(d.uploaded_at).toLocaleDateString()} by ${d.username || 'Unknown'}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function renderHistory() {
             const tbody = document.getElementById('history-list');
             if (!currentGBU.history || !currentGBU.history.length) {
                 tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No history yet.</td></tr>';
                 return;
             }
             tbody.innerHTML = currentGBU.history.map(h => {
                 let details = '';
                 try {
                     const json = JSON.parse(h.change_details);
                     details = json.msg || h.change_details;
                 } catch(e) { details = h.change_details; }

                 return `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(h.changed_at).toLocaleString()}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${h.username || 'System'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 uppercase tracking-wide text-xs">${h.action_type}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">${details}</td>
                    </tr>
                 `;
             }).join('');
        }

        // --- Helpers ---
        function getCategoryIcon(cat) {
            const map = {
                electrical: '⚡', mechanical: '⚙️', fall: '🪜', fire: '🔥', 
                chemical: '🧪', ergonomic: '💺', psych: '🧠', other: '❓'
            };
            return map[cat] || '⚠️';
        }

        function getRiskColor(score) {
            if (score >= 15) return 'text-red-600';
            if (score >= 8) return 'text-orange-500';
            return 'text-green-600';
        }

        function getTOPColor(level) {
            if (level === 'technical') return 'text-red-600';
            if (level === 'organizational') return 'text-blue-600';
            return 'text-green-600';
        }

        // --- Actions ---
        function openHazardModal() {
            document.getElementById('hazard-form').reset();
            document.querySelector('#hazard-form [name="id"]').value = '';
            document.getElementById('hazard-modal').classList.remove('hidden');
        }
        function closeHazardModal() { document.getElementById('hazard-modal').classList.add('hidden'); }

        function openControlModal(hazardId) {
            document.getElementById('control-form').reset();
            document.querySelector('#control-form [name="id"]').value = '';
            document.querySelector('#control-form [name="hazard_id"]').value = hazardId;
            document.getElementById('control-modal').classList.remove('hidden');
        }
        function closeControlModal() { document.getElementById('control-modal').classList.add('hidden'); }

        function openTrainingModal() { document.getElementById('training-modal').classList.remove('hidden'); }
        function closeTrainingModal() { document.getElementById('training-modal').classList.add('hidden'); }

        function openUploadModal() { document.getElementById('upload-modal').classList.remove('hidden'); }
        
        function openReviewModal() { document.getElementById('review-modal').classList.remove('hidden'); }

        async function postForm(url, form) {
             const formData = new FormData(form);
             let body;
             // If upload, send FormData. If json, send JSON.
             if (url.includes('upload_doc')) {
                 body = formData;
             } else {
                 body = JSON.stringify(Object.fromEntries(formData.entries()));
             }
             
             const headers = url.includes('upload_doc') ? {} : { 'Content-Type': 'application/json' };

             const res = await fetch(url, { method: 'POST', headers, body });
             const data = await res.json();
             if (!data.success) throw new Error(data.error || 'Failed');
             return data;
        }

        async function saveHazard(e) {
            e.preventDefault();
            await postForm('../backend/gbu.php?action=save_hazard', e.target);
            closeHazardModal();
            loadGBU();
        }

        async function saveControl(e) {
            e.preventDefault();
            await postForm('../backend/gbu.php?action=save_control', e.target);
            closeControlModal();
            loadGBU();
        }

        async function addTrainingReq(e) {
            e.preventDefault();
            await postForm('../backend/gbu.php?action=add_training_req', e.target);
            closeTrainingModal();
            loadGBU();
        }

        async function uploadDoc(e) {
            e.preventDefault();
            try {
                await postForm('../backend/gbu.php?action=upload_doc', e.target);
                document.getElementById('upload-modal').classList.add('hidden');
                loadGBU();
            } catch(err) { alert('Upload failed'); }
        }

        async function triggerReview(e) {
            e.preventDefault();
             // Since trigger_review expects POST params, and our postForm handles JSON or FormData
             // We can use FormData here but let's just stick to the pattern
             const formData = new FormData(e.target);
             try {
                const res = await fetch('../backend/gbu.php?action=trigger_review', {
                    method: 'POST',
                    body: formData // Send as form data
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    document.getElementById('review-modal').classList.add('hidden');
                    loadGBU();
                }
             } catch(err) { alert('Failed'); }
        }

        async function activateGBU() {
            if (!confirm('This will RELEASE the GBU and create Tasks for all open measures. Continue?')) return;
            const formData = new FormData();
            formData.append('id', GBU_ID);
            
            try {
                const res = await fetch('../backend/gbu.php?action=activate', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    loadGBU();
                }
            } catch (err) {
                alert('Activation failed');
            }
        }
        async function loadUsers() {
            try {
                const res = await fetch('../backend/gbu.php?action=get_users');
                const users = await res.json();
                const select = document.getElementById('control-responsible');
                select.innerHTML = '<option value="">-- Select User --</option>' + 
                    users.map(u => `<option value="${u.id}">${u.full_name || u.username}</option>`).join('');
            } catch(e) {}
        }

        function editControl(controlId) {
            let control = null;
            let hazardId = null;
            for (const h of currentGBU.hazards) {
                const c = h.controls.find(ctrl => ctrl.id === controlId);
                if (c) {
                    control = c;
                    hazardId = h.id;
                    break;
                }
            }
            if (!control) return;

            const form = document.getElementById('control-form');
            form.reset();
            form.querySelector('[name="id"]').value = control.id;
            form.querySelector('[name="hazard_id"]').value = hazardId;
            form.querySelector('[name="control_level"]').value = control.control_level;
            form.querySelector('[name="description"]').value = control.description;
            
            const respSelect = form.querySelector('[name="responsible_user_id"]');
            if (respSelect) respSelect.value = control.responsible_user_id || '';
            
            form.querySelector('[name="due_date"]').value = control.due_date || '';
            
            if (form.querySelector('[name="effectiveness_check_date"]')) {
                form.querySelector('[name="effectiveness_check_date"]').value = control.effectiveness_check_date || '';
                form.querySelector('[name="effectiveness_result"]').value = control.effectiveness_result || '';
            }
            
            document.getElementById('control-modal').classList.remove('hidden');
        }

    </script>
</body>
</html>
