<?php
require_once '../backend/auth.php';
requireLogin();
require_once '../backend/facilities.php';

// Allow CORS if necessary
// header("Access-Control-Allow-Origin: *");

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Details</title>
    <link rel="stylesheet" href="style.css">
    <script src="../assets/htmx.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-8">
        <div class="w-full bg-white shadow-lg min-h-full p-6 md:p-10 rounded-lg">
            <div class="mb-6 border-b pb-4 flex justify-between items-center">
                <a href="index.php" class="text-blue-600 hover:text-blue-800 flex items-center font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
                
                <!-- Primary Edit Button -->
                <button hx-get="../backend/edit_form.php?id=<?php echo $id; ?>"
                        hx-target="#edit-modal-content"
                        onclick="document.getElementById('edit-modal').classList.remove('hidden')"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Facility
                </button>
            </div>

            <div class="space-y-6">
                <!-- Header -->
                <div class="border-b pb-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($facility['name']); ?></h1>
                            <span class="inline-block mt-2 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                <?php echo htmlspecialchars($facility['type']); ?>
                            </span>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo $facility['status'] === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo htmlspecialchars($facility['status']); ?>
                            </span>
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
                                    <span class="text-gray-600">Construction Year:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($facility['construction_year']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Hazard Level:</span>
                                    <span class="font-medium <?php echo $facility['hazard_level'] === 'High' || $facility['hazard_level'] === 'Critical' ? 'text-red-600' : 'text-gray-900'; ?>">
                                        <?php echo htmlspecialchars($facility['hazard_level']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Operational Details -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-900 mb-3 border-b pb-2">Operational</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Employees:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($facility['employees']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Op. Hours:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($facility['op_hours']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Management -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-900 mb-3 border-b pb-2">Management</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Manager:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($facility['manager_name']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Contact:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($facility['manager_contact']); ?></span>
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
                </div>

                <!-- Structure Content -->
                <div id="content-structure" class="hidden">
                    <?php 
                    $facility_id = $facility['id'];
                    include '../backend/structure_view.php'; 
                    ?>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="edit-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
            <div class="relative mx-auto p-5 w-full max-w-2xl">
                <div id="edit-modal-content" class="bg-white rounded-lg shadow-xl">
                    <!-- Content loaded via HTMX -->
                </div>
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

        function openFloor(floorId) {
            // Hide all contents
            document.querySelectorAll('.floor-content').forEach(el => {
                el.classList.remove('block');
                el.classList.add('hidden');
            });
            
            // Reset tabs
            document.querySelectorAll('.floor-tab').forEach(el => {
                el.classList.remove('border-blue-500', 'text-blue-600');
                el.classList.add('border-transparent', 'text-gray-500');
            });

            // Show active content
            const content = document.getElementById('floor-content-' + floorId);
            if(content) {
                content.classList.remove('hidden');
                content.classList.add('block');
            }

            // Highlight active tab
            const tab = document.getElementById('floor-tab-' + floorId);
            if(tab) {
                tab.classList.remove('border-transparent', 'text-gray-500');
                tab.classList.add('border-blue-500', 'text-blue-600');
            }
        }
        </script>
    </main>
</body>
</html>