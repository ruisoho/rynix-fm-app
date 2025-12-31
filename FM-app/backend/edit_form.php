<?php
require_once 'facilities.php';
require_once 'csrf_helper.php';
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

<form hx-post="../backend/update_facility.php" 
      class="bg-white p-6 rounded-lg w-full max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Edit Facility</h2>
    
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo $facility['id']; ?>">
    
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
            Facility Name
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
               id="name" name="name" type="text" value="<?php echo htmlspecialchars($facility['name']); ?>" required>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="type">
                Facility Type
            </label>
            <select name="type" required class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                <?php
                $types = ['Office', 'Warehouse', 'Manufacturing', 'Retail', 'Residential', 'School', 'Mixed Use'];
                foreach ($types as $type) {
                    $selected = $facility['type'] === $type ? 'selected' : '';
                    echo "<option value='$type' $selected>$type</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                Status
            </label>
            <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                <?php
                $statuses = ['Active', 'Inactive', 'Under Maintenance'];
                foreach ($statuses as $status) {
                    $selected = $facility['status'] === $status ? 'selected' : '';
                    echo "<option value='$status' $selected>$status</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="address">
            Address
        </label>
        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
               id="address" name="address" type="text" value="<?php echo htmlspecialchars($facility['address']); ?>">
    </div>

    <div class="grid grid-cols-3 gap-4 mb-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="area">
                Total Area (m²)
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                   id="area" name="area" type="number" step="0.01" value="<?php echo htmlspecialchars($facility['area']); ?>">
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="construction_year">
                Built Year
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                   id="construction_year" name="construction_year" type="number" value="<?php echo htmlspecialchars($facility['construction_year']); ?>">
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="employees">
                Employees
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                   id="employees" name="employees" type="number" value="<?php echo htmlspecialchars($facility['employees']); ?>">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="op_hours">
                Operating Hours
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                   id="op_hours" name="op_hours" type="text" value="<?php echo htmlspecialchars($facility['op_hours']); ?>">
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="hazard_level">
                Hazard Level
            </label>
            <select name="hazard_level" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-white">
                <?php
                $levels = ['Low', 'Medium', 'High', 'Critical'];
                foreach ($levels as $level) {
                    $selected = $facility['hazard_level'] === $level ? 'selected' : '';
                    echo "<option value='$level' $selected>$level</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="manager_name">
                Manager Name
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                   id="manager_name" name="manager_name" type="text" value="<?php echo htmlspecialchars($facility['manager_name']); ?>">
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="manager_contact">
                Manager Contact
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                   id="manager_contact" name="manager_contact" type="text" value="<?php echo htmlspecialchars($facility['manager_contact']); ?>">
        </div>
    </div>

    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
            Description
        </label>
        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                  id="description" name="description" rows="3"><?php echo htmlspecialchars($facility['description']); ?></textarea>
    </div>

    <div class="flex items-center justify-between mt-6">
        <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition-colors" type="submit">
            Save Changes
        </button>
        <button type="button" 
                onclick="document.getElementById('edit-modal').classList.add('hidden')"
                class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800 px-4 py-2 rounded hover:bg-gray-100">
            Cancel
        </button>
    </div>
</form>
