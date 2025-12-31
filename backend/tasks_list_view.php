<?php
if (!isset($tasks)) {
    if (function_exists('getDailyTasks')) {
        $tasks = getDailyTasks();
    } else {
        $tasks = [];
    }
}
?>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No daily tasks found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <tr hx-get="../backend/tasks.php?action=get_task_details&id=<?php echo $task['id']; ?>"
                            hx-target="#edit-modal-container"
                            hx-swap="innerHTML"
                            class="hover:bg-gray-50 cursor-pointer transition-colors">
                            
                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
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
                            </td>

                            <!-- Task Title & Description -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($task['title']); ?></div>
                                <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($task['description']); ?></div>
                                <?php if(!empty($task['creator_name'])): ?>
                                    <div class="text-xs text-gray-400 mt-1">Created by: <?php echo htmlspecialchars($task['creator_name']); ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Location -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($task['facility_name']); ?></div>
                                <?php if($task['floor_name'] || $task['room_name']): ?>
                                    <div class="text-xs">
                                        <?php 
                                        echo htmlspecialchars($task['floor_name'] ?? ''); 
                                        if($task['floor_name'] && $task['room_name']) echo ' > ';
                                        echo htmlspecialchars($task['room_name'] ?? ''); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Assigned To -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                if (!empty($task['provider_name'])) {
                                    echo '<div class="flex items-center"><i class="fas fa-user-hard-hat mr-2 text-gray-400"></i>' . htmlspecialchars($task['provider_name']) . '</div>';
                                } elseif (!empty($task['assigned_to'])) {
                                    echo '<div class="flex items-center"><i class="fas fa-user mr-2 text-gray-400"></i>' . htmlspecialchars($task['assigned_to']) . '</div>';
                                } else {
                                    echo '<span class="text-gray-400 italic">Unassigned</span>';
                                }
                                ?>
                            </td>

                            <!-- Due Date -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $task['due_date'] ? htmlspecialchars($task['due_date']) : '-'; ?>
                            </td>

                            <!-- Priority -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if(isset($task['priority'])): ?>
                                    <span class="text-sm font-semibold flex items-center 
                                        <?php 
                                            echo match($task['priority']) {
                                                'Critical' => 'text-red-600',
                                                'High' => 'text-red-500',
                                                'Medium' => 'text-yellow-600',
                                                'Low' => 'text-green-600',
                                                default => 'text-gray-500'
                                            };
                                        ?>">
                                        <i class="fas fa-circle text-[8px] mr-2"></i>
                                        <?php echo htmlspecialchars($task['priority']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
