<?php
require_once 'facilities.php';
require_once 'auth.php';

// Ensure session is started and user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
requireLogin();

// Allow CORS if necessary
header("Access-Control-Allow-Origin: *");

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<div class='text-red-500'>Invalid request: ID missing.</div>";
    exit;
}

$facility = getFacility($id);

if (!$facility) {
    echo "<div class='text-red-500'>Facility not found.</div>";
    exit;
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="border-b pb-4">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($facility['name']); ?></h2>
                <span class="inline-block mt-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                    <?php echo htmlspecialchars($facility['type']); ?>
                </span>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo $facility['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($facility['status']); ?>
                </span>
                <button hx-get="../backend/edit_form.php?id=<?php echo $facility['id']; ?>"
                        hx-target="#detail-modal-content"
                        class="text-blue-600 hover:text-blue-800 text-sm font-semibold underline">
                    Edit Facility
                </button>
            </div>
        </div>
        <p class="mt-2 text-gray-600 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <?php echo htmlspecialchars($facility['address']); ?>
        </p>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('overview')" 
                    id="tab-overview"
                    class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Overview
            </button>
            <button onclick="switchTab('structure')" 
                    id="tab-structure"
                    class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Building Structure
            </button>
        </nav>
    </div>

    <!-- Overview Content -->
    <div id="content-overview" class="block">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Physical Details -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-900 mb-3 border-b pb-2">Physical Specs</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Area:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($facility['area']); ?> m²</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Built Year:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($facility['construction_year']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Operational Details -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-900 mb-3 border-b pb-2">Operations</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Employees:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($facility['employees']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Op. Hours:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($facility['op_hours']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Hazard Level:</span>
                        <span class="font-medium <?php 
                            echo match($facility['hazard_level']) {
                                'High', 'Critical' => 'text-red-600',
                                'Medium' => 'text-orange-600',
                                default => 'text-green-600'
                            };
                        ?>"><?php echo htmlspecialchars($facility['hazard_level']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management -->
        <div class="bg-gray-50 p-4 rounded-lg mt-6">
            <h3 class="font-semibold text-gray-900 mb-3 border-b pb-2">Management</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="block text-gray-600 text-sm">Facility Manager</span>
                    <span class="font-medium"><?php echo htmlspecialchars($facility['manager_name']); ?></span>
                </div>
                <div>
                    <span class="block text-gray-600 text-sm">Contact Info</span>
                    <span class="font-medium text-blue-600"><?php echo htmlspecialchars($facility['manager_contact']); ?></span>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="mt-6">
            <h3 class="font-semibold text-gray-900 mb-2">Description</h3>
            <div class="p-4 bg-white border rounded text-gray-700">
                <?php echo nl2br(htmlspecialchars($facility['description'])); ?>
            </div>
        </div>
    </div>

    <!-- Structure Content -->
    <div id="content-structure" class="hidden">
        <?php 
        $facility_id = $facility['id'];
        include 'structure_view.php'; 
        ?>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all contents
    document.getElementById('content-overview').classList.add('hidden');
    document.getElementById('content-structure').classList.add('hidden');
    
    // Reset all tab styles
    document.getElementById('tab-overview').classList.remove('border-blue-500', 'text-blue-600');
    document.getElementById('tab-overview').classList.add('border-transparent', 'text-gray-500');
    
    document.getElementById('tab-structure').classList.remove('border-blue-500', 'text-blue-600');
    document.getElementById('tab-structure').classList.add('border-transparent', 'text-gray-500');
    
    // Show selected content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Highlight selected tab
    document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab-' + tabName).classList.add('border-blue-500', 'text-blue-600');
}
</script>
