<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="edit-modal">
    <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Maintenance Task</h3>
            <form hx-post="../backend/maintenance.php" hx-target="#maintenance-list" hx-swap="innerHTML"
                  hx-on::after-request="if(event.detail.successful) document.getElementById('edit-modal').remove();">
                
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="update_task">
                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Facility</label>
                    <select name="facility_id" required class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Select Facility</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?php echo $facility['id']; ?>" <?php echo $facility['id'] == $task['facility_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($facility['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Provider</label>
                    <select name="provider_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Select Provider (Optional)</option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?php echo $provider['id']; ?>" <?php echo $provider['id'] == $task['provider_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($provider['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                    <textarea name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($task['description']); ?></textarea>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Priority</label>
                    <select name="priority" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="Low" <?php echo $task['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $task['priority'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $task['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                    <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="Open" <?php echo $task['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                        <option value="In Progress" <?php echo $task['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Closed" <?php echo $task['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Scheduled Date</label>
                    <input type="date" name="scheduled_date" value="<?php echo htmlspecialchars($task['scheduled_date']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Cost (€)</label>
                    <input type="number" step="0.01" name="cost" value="<?php echo htmlspecialchars($task['cost']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Recurrence</label>
                    <select name="recurrence" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="None" <?php echo $task['recurrence'] == 'None' ? 'selected' : ''; ?>>None</option>
                        <option value="1 Month" <?php echo $task['recurrence'] == '1 Month' ? 'selected' : ''; ?>>1 Month</option>
                        <option value="3 Months" <?php echo $task['recurrence'] == '3 Months' ? 'selected' : ''; ?>>3 Months</option>
                        <option value="6 Months" <?php echo $task['recurrence'] == '6 Months' ? 'selected' : ''; ?>>6 Months</option>
                        <option value="1 Year" <?php echo $task['recurrence'] == '1 Year' ? 'selected' : ''; ?>>1 Year</option>
                        <option value="2 Years" <?php echo $task['recurrence'] == '2 Years' ? 'selected' : ''; ?>>2 Years</option>
                        <option value="4 Years" <?php echo $task['recurrence'] == '4 Years' ? 'selected' : ''; ?>>4 Years</option>
                        <option value="5 Years" <?php echo $task['recurrence'] == '5 Years' ? 'selected' : ''; ?>>5 Years</option>
                    </select>
                </div>

                <div class="items-center px-4 py-3">
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Update Task
                    </button>
                    <button type="button" 
                            hx-post="../backend/maintenance.php" 
                            hx-target="#maintenance-list" 
                            hx-swap="innerHTML"
                            hx-vals='{"action": "delete_task", "id": "<?php echo $task['id']; ?>", "csrf_token": "<?php echo generateCsrfToken(); ?>"}'
                            hx-confirm="Are you sure you want to delete this task?"
                            hx-on::after-request="if(event.detail.successful) document.getElementById('edit-modal').remove();"
                            class="mt-3 px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Delete
                    </button>
                    <button type="button" onclick="document.getElementById('edit-modal').remove()" class="mt-3 px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Document Tab Placeholder -->
        <div class="p-4 border-t border-gray-200">
            <?php include '../frontend/components/documents_tab.php'; ?>
        </div>
    </div>
</div>
