<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="detail-modal">
    <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <!-- Header -->
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($task['title']); ?></h3>
            <button onclick="document.getElementById('detail-modal').remove()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
            <!-- Status & Priority Badges -->
            <div class="flex space-x-3">
                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                    <?php 
                        echo match($task['status']) {
                            'Completed' => 'bg-green-100 text-green-800',
                            'In Progress' => 'bg-blue-100 text-blue-800',
                            'Pending' => 'bg-yellow-100 text-yellow-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    ?>">
                    <?php echo htmlspecialchars($task['status']); ?>
                </span>
                
                <?php if(isset($task['priority'])): ?>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full flex items-center
                        <?php 
                            echo match($task['priority']) {
                                'Critical' => 'bg-red-100 text-red-800',
                                'High' => 'bg-red-50 text-red-600',
                                'Medium' => 'bg-yellow-100 text-yellow-800',
                                'Low' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?>">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        <?php echo htmlspecialchars($task['priority']); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Description</label>
                <p class="text-gray-800 mt-1 bg-gray-50 p-3 rounded text-sm">
                    <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                </p>
            </div>

            <!-- Grid Layout for Details -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Location -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Location</label>
                    <p class="text-sm font-medium text-gray-900 mt-1">
                        <i class="far fa-building mr-1 text-gray-400"></i>
                        <?php echo htmlspecialchars($task['facility_name']); ?>
                    </p>
                    <?php if($task['floor_name'] || $task['room_name']): ?>
                        <p class="text-xs text-gray-600 ml-5">
                            <?php 
                            echo htmlspecialchars($task['floor_name'] ?? ''); 
                            if($task['floor_name'] && $task['room_name']) echo ' > ';
                            echo htmlspecialchars($task['room_name'] ?? ''); 
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Assigned To -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Assigned To</label>
                    <p class="text-sm font-medium text-gray-900 mt-1">
                        <?php 
                        if (!empty($task['provider_name'])) {
                            echo '<i class="fas fa-user-hard-hat mr-1 text-gray-400"></i>' . htmlspecialchars($task['provider_name']);
                        } elseif (!empty($task['assigned_to'])) {
                            echo '<i class="fas fa-user mr-1 text-gray-400"></i>' . htmlspecialchars($task['assigned_to']);
                        } else {
                            echo '<span class="text-gray-400 italic">Unassigned</span>';
                        }
                        ?>
                    </p>
                </div>

                <!-- Due Date -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Due Date</label>
                    <p class="text-sm font-medium text-gray-900 mt-1">
                        <i class="far fa-calendar-alt mr-1 text-gray-400"></i>
                        <?php echo $task['due_date'] ? htmlspecialchars($task['due_date']) : 'No due date'; ?>
                    </p>
                </div>

                <!-- Creator info -->
                <?php if(!empty($task['creator_name'])): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Created By</label>
                    <p class="text-sm font-medium text-gray-900 mt-1">
                        <i class="fas fa-user-edit mr-1 text-gray-400"></i>
                        <?php echo htmlspecialchars($task['creator_name']); ?>
                    </p>
                    <p class="text-xs text-gray-500 ml-5">
                        <?php echo date('M j, Y H:i', strtotime($task['created_at'])); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="mt-6 flex justify-end space-x-3 pt-4 border-t">
            <button onclick="document.getElementById('detail-modal').remove()" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                Close
            </button>
            <button hx-get="../backend/tasks.php?action=get_task_form&id=<?php echo $task['id']; ?>" 
                    hx-target="#edit-modal-container" 
                    hx-swap="innerHTML"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center">
                <i class="fas fa-edit mr-2"></i> Edit Task
            </button>
        </div>
    </div>
</div>