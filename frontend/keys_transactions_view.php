<?php if (empty($transactions)): ?>
    <div class="p-4 text-center text-gray-500">No transaction history found.</div>
<?php else: ?>
    <div class="bg-white shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued To</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued By</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued At</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Return</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Returned At</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($transactions as $trx): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($trx['key_name']); ?>
                        <span class="text-xs text-gray-500 block"><?php echo htmlspecialchars($trx['serial_number']); ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($trx['issued_to_name']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($trx['issued_by_name']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M d, H:i', strtotime($trx['issued_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo $trx['expected_return_at'] ? date('M d, H:i', strtotime($trx['expected_return_at'])) : '-'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo $trx['returned_at'] ? date('M d, H:i', strtotime($trx['returned_at'])) : '-'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        $statusClass = match($trx['status']) {
                            'Active' => 'bg-yellow-100 text-yellow-800',
                            'Returned' => 'bg-green-100 text-green-800',
                            'Overdue' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($trx['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <?php if ($trx['status'] === 'Active' || $trx['status'] === 'Overdue'): ?>
                            <button hx-post="../backend/keys.php" 
                                    hx-vals='{"action": "return_key", "transaction_id": <?php echo $trx['id']; ?>}'
                                    hx-confirm="Confirm return of this key?"
                                    class="text-green-600 hover:text-green-900">
                                Return
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>