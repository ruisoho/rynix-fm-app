<?php
if (!isset($logs)) {
    // Fallback if logs are not set
    if (function_exists('getMaintenanceLogs')) {
        $logs = getMaintenanceLogs();
    } else {
        $logs = [];
    }
}
?>

<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <table class="min-w-full leading-normal">
        <thead>
            <tr>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Facility
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Task
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Provider
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Status
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Priority
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Date
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Cost
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                        No maintenance logs found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr hx-get="../backend/maintenance.php?action=get_task_form&id=<?php echo $log['id']; ?>"
                        hx-target="#edit-modal-container"
                        hx-swap="innerHTML"
                        class="cursor-pointer hover:bg-gray-50 transition duration-150">
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($log['facility_name']); ?></p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap font-bold"><?php echo htmlspecialchars($log['title']); ?></p>
                            <p class="text-gray-600 text-xs"><?php echo htmlspecialchars($log['description']); ?></p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap"><?php echo !empty($log['provider_name']) ? htmlspecialchars($log['provider_name']) : '-'; ?></p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <span class="relative inline-block px-3 py-1 font-semibold leading-tight 
                                <?php 
                                    echo match($log['status']) {
                                        'Open' => 'text-green-900',
                                        'In Progress' => 'text-yellow-900',
                                        'Closed' => 'text-red-900',
                                        default => 'text-gray-900'
                                    };
                                ?>">
                                <span aria-hidden class="absolute inset-0 opacity-50 rounded-full 
                                    <?php 
                                        echo match($log['status']) {
                                            'Open' => 'bg-green-200',
                                            'In Progress' => 'bg-yellow-200',
                                            'Closed' => 'bg-red-200',
                                            default => 'bg-gray-200'
                                        };
                                    ?>"></span>
                                <span class="relative"><?php echo htmlspecialchars($log['status']); ?></span>
                            </span>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                             <span class="
                                <?php 
                                    echo match($log['priority']) {
                                        'High' => 'text-red-600 font-bold',
                                        'Medium' => 'text-yellow-600',
                                        'Low' => 'text-green-600',
                                        default => 'text-gray-600'
                                    };
                                ?>">
                                <?php echo htmlspecialchars($log['priority']); ?>
                            </span>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap">
                                <?php echo $log['scheduled_date'] ? htmlspecialchars($log['scheduled_date']) : 'N/A'; ?>
                            </p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap">
                                <?php echo $log['cost'] ? '€' . number_format($log['cost'], 2) : '-'; ?>
                            </p>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
