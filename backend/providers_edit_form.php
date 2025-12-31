<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="edit-modal">
    <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Provider</h3>
            <form hx-post="../backend/providers.php" hx-target="#providers-list" hx-swap="innerHTML"
                  hx-on::after-request="if(event.detail.elt === this && event.detail.successful) document.getElementById('edit-modal').remove();">
                
                <input type="hidden" name="action" value="update_provider">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $provider['id']; ?>">

                <div class="grid grid-cols-2 gap-4">
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Provider Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($provider['name']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Customer Number</label>
                        <input type="text" name="customer_number" value="<?php echo htmlspecialchars($provider['customer_number'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Service Type *</label>
                    <?php 
                    $standard_types = ['Plumbing', 'Electrical', 'Cleaning', 'HVAC', 'General'];
                    $current_type = $provider['service_type'];
                    $is_custom = !in_array($current_type, $standard_types) && $current_type !== '';
                    $select_value = $is_custom ? 'other' : $current_type;
                    ?>
                    <select name="service_type" required 
                            onchange="this.value === 'other' ? document.getElementById('edit_custom_service_type_container_<?php echo $provider['id']; ?>').classList.remove('hidden') : document.getElementById('edit_custom_service_type_container_<?php echo $provider['id']; ?>').classList.add('hidden')"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="Plumbing" <?php echo $select_value == 'Plumbing' ? 'selected' : ''; ?>>Plumbing</option>
                        <option value="Electrical" <?php echo $select_value == 'Electrical' ? 'selected' : ''; ?>>Electrical</option>
                        <option value="Cleaning" <?php echo $select_value == 'Cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                        <option value="HVAC" <?php echo $select_value == 'HVAC' ? 'selected' : ''; ?>>HVAC</option>
                        <option value="General" <?php echo $select_value == 'General' ? 'selected' : ''; ?>>General</option>
                        <option value="other" <?php echo $select_value == 'other' ? 'selected' : ''; ?>>Other (Specify)</option>
                    </select>
                    <div id="edit_custom_service_type_container_<?php echo $provider['id']; ?>" class="<?php echo $is_custom ? '' : 'hidden'; ?> mt-2">
                        <input type="text" name="custom_service_type" value="<?php echo $is_custom ? htmlspecialchars($current_type) : ''; ?>" placeholder="Enter custom service type" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Contact Person</label>
                        <input type="text" name="contact_person" value="<?php echo htmlspecialchars($provider['contact_person']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($provider['phone']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($provider['email']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($provider['address']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Hourly Rate (€)</label>
                        <input type="number" step="0.01" name="hourly_rate" value="<?php echo htmlspecialchars($provider['hourly_rate'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mt-2 text-left">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <?php $status = $provider['status'] ?? 'Active'; ?>
                            <option value="Active" <?php echo $status == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $status == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Specialization</label>
                    <textarea name="specialization" placeholder="Areas of expertise and specialization" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($provider['specialization'] ?? ''); ?></textarea>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Notes</label>
                    <textarea name="notes" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($provider['notes'] ?? ''); ?></textarea>
                </div>

                <div class="flex justify-end space-x-2 px-4 py-3 bg-gray-50 rounded-b-md">
                    <button type="button" onclick="document.getElementById('edit-modal').remove()" class="px-3 py-1 bg-gray-300 text-gray-700 text-sm font-medium rounded hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                    <button type="button" 
                            hx-post="../backend/providers.php" 
                            hx-target="#providers-list" 
                            hx-swap="innerHTML"
                            hx-vals='{"action": "delete_provider", "id": "<?php echo $provider['id']; ?>", "csrf_token": "<?php echo generateCsrfToken(); ?>"}'
                            hx-confirm="Are you sure you want to delete this provider?"
                            hx-on::after-request="if(event.detail.successful) document.getElementById('edit-modal').remove();"
                            class="px-3 py-1 bg-red-500 text-white text-sm font-medium rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Delete
                    </button>
                    <button type="submit" class="px-3 py-1 bg-indigo-500 text-white text-sm font-medium rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>