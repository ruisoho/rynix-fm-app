<div class="mt-3 text-center">
    <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Meter</h3>
    <form hx-post="../backend/energy.php" hx-target="#energy-list" 
          hx-on::after-request="if(event.detail.elt === this && event.detail.successful) { document.getElementById('edit-modal').classList.add('hidden'); }">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="update_meter">
        <input type="hidden" name="id" value="<?php echo $meter['id']; ?>">
        
        <div class="mt-2 text-left">
            <label class="block text-sm font-medium text-gray-700">Facility</label>
            <select name="facility_id" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">Select Facility</option>
                <?php foreach ($facilities as $facility): ?>
                    <option value="<?php echo $facility['id']; ?>" <?php echo $meter['facility_id'] == $facility['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($facility['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mt-2 text-left">
            <label class="block text-sm font-medium text-gray-700">Meter Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($meter['name']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div class="mt-2 text-left">
            <label class="block text-sm font-medium text-gray-700">Type</label>
            <select name="type" id="edit_meter_type_select" required onchange="updateEditUnit()" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="Electricity" <?php echo $meter['type'] == 'Electricity' ? 'selected' : ''; ?>>Electricity</option>
                <option value="Gas" <?php echo $meter['type'] == 'Gas' ? 'selected' : ''; ?>>Gas</option>
                <option value="Water" <?php echo $meter['type'] == 'Water' ? 'selected' : ''; ?>>Water</option>
                <option value="Heating" <?php echo $meter['type'] == 'Heating' ? 'selected' : ''; ?>>Heating</option>
            </select>
        </div>

        <div class="mt-2 text-left">
            <label class="block text-sm font-medium text-gray-700">Unit</label>
            <select name="unit" id="edit_meter_unit_select" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="kWh" <?php echo $meter['unit'] == 'kWh' ? 'selected' : ''; ?>>kWh</option>
                <option value="m³" <?php echo $meter['unit'] == 'm³' ? 'selected' : ''; ?>>m³</option>
                <option value="MWh" <?php echo $meter['unit'] == 'MWh' ? 'selected' : ''; ?>>MWh</option>
            </select>
        </div>

        <div class="mt-2 text-left">
            <label class="block text-sm font-medium text-gray-700">Serial Number</label>
            <input type="text" name="serial_number" value="<?php echo htmlspecialchars($meter['serial_number']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div class="mt-2 text-left">
            <label class="block text-sm font-medium text-gray-700">Location</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($meter['location']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div class="items-center px-4 py-3">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                Update Meter
            </button>
        </div>
        <div class="items-center px-4 py-3">
            <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                Cancel
            </button>
        </div>
    </form>
    <script>
        function updateEditUnit() {
            const typeSelect = document.getElementById('edit_meter_type_select');
            const unitSelect = document.getElementById('edit_meter_unit_select');
            const type = typeSelect.value;
            
            if (type === 'Electricity') {
                unitSelect.value = 'kWh';
            } else if (type === 'Gas' || type === 'Water') {
                unitSelect.value = 'm³';
            } else if (type === 'Heating') {
                unitSelect.value = 'MWh';
            }
        }
    </script>
</div>
