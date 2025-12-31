<?php
require_once '../backend/auth.php';
requireLogin();
require_once '../backend/facilities.php';
$facilities = getFacilities();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FM App</title>
    <link rel="stylesheet" href="style.css">
    <script src="../assets/htmx.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard</h1>
            
            <!-- Welcome Card -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Welcome, <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>!</h2>
                <p class="text-gray-600 mt-2">Here's what's happening in your facilities today.</p>
            </div>

            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                <form class="flex flex-wrap gap-4 items-end">
                    <div class="w-full md:w-1/3">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Filter by Facility</label>
                        <select name="facility_id" 
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                hx-get="../backend/dashboard_content.php" 
                                hx-target="#dashboard-content" 
                                hx-trigger="change">
                            <option value="">All Facilities</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility['id']; ?>">
                                    <?php echo htmlspecialchars($facility['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <div id="dashboard-content">
                <?php include '../backend/dashboard_content.php'; ?>
            </div>
        </div>
    </main>
</body>
</html>