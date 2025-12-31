<?php
if (!isset($providers)) {
    if (function_exists('getProviders')) {
        $providers = getProviders();
    } else {
        $providers = [];
    }
}
?>

<div class="overflow-x-auto bg-white rounded-lg shadow">
    <table class="min-w-full leading-normal">
        <thead>
            <tr>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Name / Status
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Service Type
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Contact
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Contact Person
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Rate
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php if (empty($providers)): ?>
                <tr>
                    <td colspan="5" class="px-5 py-5 bg-white text-sm text-center text-gray-500">
                        No providers found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($providers as $provider): ?>
                    <tr hx-get="../backend/providers.php?action=get_provider_details&id=<?php echo $provider['id']; ?>"
                        hx-target="#edit-modal-container"
                        hx-swap="innerHTML"
                        class="hover:bg-gray-50 cursor-pointer transition-colors duration-150">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="ml-3">
                                    <p class="text-gray-900 font-semibold whitespace-no-wrap">
                                        <?php echo htmlspecialchars($provider['name']); ?>
                                    </p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo ($provider['status'] ?? 'Active') === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($provider['status'] ?? 'Active'); ?>
                                        </span>
                                        <?php if (!empty($provider['customer_number'])): ?>
                                            <span class="text-xs text-gray-500">#<?php echo htmlspecialchars($provider['customer_number']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <?php if ($provider['service_type']): ?>
                                <span class="inline-block bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($provider['service_type']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php if ($provider['email']): ?>
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-envelope text-gray-400 mr-2 w-4"></i>
                                        <?php echo htmlspecialchars($provider['email']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($provider['phone']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone text-gray-400 mr-2 w-4"></i>
                                        <?php echo htmlspecialchars($provider['phone']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$provider['email'] && !$provider['phone']): ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($provider['contact_person'] ?? '-'); ?>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                            <?php echo !empty($provider['hourly_rate']) ? '€' . number_format($provider['hourly_rate'], 2) . ' / hr' : '-'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>