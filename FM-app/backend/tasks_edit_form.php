<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="edit-modal">
    <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Daily Task</h3>
            <form hx-post="../backend/tasks.php" hx-target="#tasks-list" hx-swap="innerHTML"
                  hx-on::after-request="if(event.detail.elt === this && event.detail.successful) document.getElementById('edit-modal').remove();">
                
                <input type="hidden" name="action" value="update_task">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Facility</label>
                    <select name="facility_id" required 
                            hx-get="../backend/tasks.php?action=get_floors" 
                            hx-target="#edit_floor_select" 
                            hx-trigger="change"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Select Facility</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?php echo $facility['id']; ?>" <?php echo $facility['id'] == $task['facility_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($facility['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Floor</label>
                    <select id="edit_floor_select" name="floor_id" 
                            hx-get="../backend/tasks.php?action=get_rooms" 
                            hx-target="#edit_room_select" 
                            hx-trigger="change"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Select Floor</option>
                        <?php foreach ($floors as $floor): ?>
                            <option value="<?php echo $floor['id']; ?>" <?php echo $floor['id'] == $task['floor_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($floor['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Room</label>
                    <select id="edit_room_select" name="room_id" 
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Select Room</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" <?php echo $room['id'] == $task['room_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($room['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Assigned To</label>
                    <?php 
                    $is_provider = !empty($task['provider_id']);
                    $is_manual = empty($task['provider_id']) && !empty($task['assigned_to']);
                    $assign_value = $is_provider ? 'p_' . $task['provider_id'] : ($is_manual ? 'custom' : '');
                    ?>
                    <select name="assignee_select" 
                            onchange="this.value === 'custom' ? document.getElementById('edit_custom_assignee_container').classList.remove('hidden') : document.getElementById('edit_custom_assignee_container').classList.add('hidden')"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Unassigned</option>
                        <optgroup label="Providers">
                            <?php foreach ($providers as $provider): ?>
                                <option value="p_<?php echo $provider['id']; ?>" <?php echo ($is_provider && (string)$task['provider_id'] === (string)$provider['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($provider['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <option value="custom" <?php echo $is_manual ? 'selected' : ''; ?>>Special Person (Manual Entry)</option>
                    </select>
                    
                    <div id="edit_custom_assignee_container" class="<?php echo $is_manual ? '' : 'hidden'; ?> mt-2">
                        <input type="text" name="custom_assignee" value="<?php echo htmlspecialchars($task['assigned_to'] ?? ''); ?>" placeholder="Enter person's name" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
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
                    <label class="block text-gray-700 text-sm font-bold mb-2">Due Date</label>
                    <input type="date" name="due_date" value="<?php echo htmlspecialchars($task['due_date']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mt-2 text-left">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Priority</label>
                    <select name="priority" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="Low" <?php echo ($task['priority'] ?? 'Medium') === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($task['priority'] ?? 'Medium') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($task['priority'] ?? 'Medium') === 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Critical" <?php echo ($task['priority'] ?? 'Medium') === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                    </select>
                </div>
                
                <div class="mt-2 text-left">
                     <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                    <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="Pending" <?php echo $task['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo $task['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $task['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-2 px-4 py-3 mt-4">
                    <button type="button" 
                            hx-post="../backend/tasks.php" 
                            hx-target="#tasks-list" 
                            hx-swap="innerHTML"
                            hx-vals='{"action": "delete_task", "id": "<?php echo $task['id']; ?>", "csrf_token": "<?php echo generateCsrfToken(); ?>"}'
                            hx-confirm="Are you sure you want to delete this task?"
                            hx-on::after-request="if(event.detail.successful) document.getElementById('edit-modal').remove();"
                            class="px-3 py-1 bg-red-500 text-white text-sm font-medium rounded shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        Delete
                    </button>
                    <button type="button" onclick="document.getElementById('edit-modal').remove()" class="px-3 py-1 bg-gray-300 text-gray-700 text-sm font-medium rounded shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-3 py-1 bg-blue-500 text-white text-sm font-medium rounded shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
