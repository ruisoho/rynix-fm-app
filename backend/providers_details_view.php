<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="details-modal">
    <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-2xl leading-6 font-bold text-gray-900"><?php echo htmlspecialchars($provider['name']); ?></h3>
                <button onclick="document.getElementById('details-modal').remove()" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Service Type</p>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($provider['service_type']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Status</p>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo ($provider['status'] ?? 'Active') === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo htmlspecialchars($provider['status'] ?? 'Active'); ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Hourly Rate</p>
                        <p class="text-lg text-gray-900"><?php echo !empty($provider['hourly_rate']) ? '€' . number_format($provider['hourly_rate'], 2) . ' / hr' : '-'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Customer Number</p>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($provider['customer_number'] ?? '-'); ?></p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <h4 class="text-md font-semibold text-gray-700 border-b pb-1 mb-2">Contact Information</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Contact Person</p>
                            <p class="text-gray-900"><?php echo htmlspecialchars($provider['contact_person'] ?? '-'); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Phone</p>
                            <p class="text-gray-900"><?php echo htmlspecialchars($provider['phone'] ?? '-'); ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-gray-500">Email</p>
                            <p class="text-gray-900"><?php echo htmlspecialchars($provider['email'] ?? '-'); ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-gray-500">Address</p>
                            <p class="text-gray-900"><?php echo htmlspecialchars($provider['address'] ?? '-'); ?></p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($provider['specialization'])): ?>
                <div>
                    <h4 class="text-md font-semibold text-gray-700 border-b pb-1 mb-2">Specialization</h4>
                    <p class="text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($provider['specialization'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($provider['notes'])): ?>
                <div>
                    <h4 class="text-md font-semibold text-gray-700 border-b pb-1 mb-2">Notes</h4>
                    <p class="text-sm text-gray-900 bg-yellow-50 p-3 rounded border border-yellow-100"><?php echo nl2br(htmlspecialchars($provider['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-8 flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="document.getElementById('details-modal').remove()" 
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Close
                </button>
                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                <button type="button" 
                        hx-get="../backend/providers.php?action=get_provider_form&id=<?php echo $provider['id']; ?>"
                        hx-target="#edit-modal-container"
                        hx-swap="innerHTML"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    Edit Provider
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
