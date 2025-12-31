<div class="mt-3">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-900">Readings History</h3>
        <button onclick="document.getElementById('readings-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Meter Details</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Name:</span> 
                <span class="font-semibold"><?php echo htmlspecialchars($meter['name']); ?></span>
            </div>
            <div>
                <span class="text-gray-500">Facility:</span> 
                <span class="font-semibold"><?php echo htmlspecialchars($meter['facility_name']); ?></span>
            </div>
            <div>
                <span class="text-gray-500">Type:</span> 
                <span class="font-semibold"><?php echo htmlspecialchars($meter['type']); ?></span>
            </div>
            <div>
                <span class="text-gray-500">Unit:</span> 
                <span class="font-semibold"><?php echo htmlspecialchars($meter['unit']); ?></span>
            </div>
        </div>
    </div>

    <!-- Add Reading Form -->
    <div class="mb-6 border-b pb-6">
        <h4 class="text-md font-medium text-gray-800 mb-3">Add New Reading</h4>
        <form hx-post="../backend/energy.php" hx-target="#readings-modal-content" class="flex gap-4 items-end">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_reading">
            <input type="hidden" name="meter_id" value="<?php echo $meter['id']; ?>">
            
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="reading_date" value="<?php echo date('Y-m-d'); ?>" required 
                       class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-700 mb-1">Reading (<?php echo htmlspecialchars($meter['unit']); ?>)</label>
                <input type="number" step="0.01" name="reading_value" required placeholder="0.00"
                       class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded shadow-sm">
                Add Reading
            </button>
        </form>
    </div>

    <!-- Readings List -->
    <div class="overflow-y-auto max-h-96">
        <?php if (empty($readings)): ?>
            <p class="text-center text-gray-500 py-4">No readings recorded yet.</p>
        <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($readings as $reading): ?>
                        <tr id="reading-row-<?php echo $reading['id']; ?>" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M d, Y', strtotime($reading['reading_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo number_format($reading['reading_value'], 2); ?> <?php echo htmlspecialchars($meter['unit']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($reading['recorded_by_name'] ?? 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button hx-get="../backend/energy.php?action=get_reading_edit_form&id=<?php echo $reading['id']; ?>"
                                        hx-target="#reading-row-<?php echo $reading['id']; ?>"
                                        hx-swap="outerHTML"
                                        class="text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
