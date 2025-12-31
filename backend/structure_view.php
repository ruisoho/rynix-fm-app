<?php
require_once 'structure_logic.php';
require_once 'csrf_helper.php';

// Ensure $facility_id is available
if (!isset($facility_id) && isset($_GET['facility_id'])) {
    $facility_id = $_GET['facility_id'];
}

$floors = getFloors($facility_id);

// Determine active floor
// If $active_floor_id is not set (from add_room/floor scripts), default to first floor
if (!isset($active_floor_id)) {
    $active_floor_id = !empty($floors) ? $floors[0]['id'] : null;
}
?>

<div id="structure-container" class="mt-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-800">Building Structure</h3>
    </div>

    <!-- Tabs and Add Floor Button -->
    <div class="border-b border-gray-200 flex items-center justify-between mb-4">
        <div class="flex overflow-x-auto space-x-1" id="floor-tabs">
            <?php foreach ($floors as $floor): ?>
                <button onclick="openFloor(<?php echo $floor['id']; ?>)" 
                        id="floor-tab-<?php echo $floor['id']; ?>"
                        class="floor-tab py-2 px-4 border-b-2 font-medium text-sm whitespace-nowrap transition-colors duration-150
                        <?php echo $floor['id'] == $active_floor_id ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    <?php echo htmlspecialchars($floor['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <button onclick="document.getElementById('add-floor-form-global').classList.toggle('hidden')" 
                class="ml-4 bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-3 rounded shadow whitespace-nowrap flex items-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Floor
        </button>
    </div>

    <!-- Add Floor Form -->
    <form id="add-floor-form-global" 
          hx-post="../backend/add_floor.php" 
          hx-target="#structure-container" 
          hx-swap="outerHTML"
          class="hidden mb-6 bg-gray-50 p-4 rounded border border-gray-200">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
        <div class="flex gap-4 items-end flex-wrap">
            <div>
                <label class="block text-gray-700 text-xs font-bold mb-1">Floor Name</label>
                <input type="text" name="name" required placeholder="e.g. Ground Floor" class="shadow border rounded py-1 px-2 text-sm w-48">
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-bold mb-1">Level #</label>
                <input type="number" name="level_number" required placeholder="0" class="shadow border rounded py-1 px-2 text-sm w-20">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-bold py-1 px-3 rounded">Save Floor</button>
        </div>
    </form>

    <!-- Floor Contents -->
    <div class="mt-4 min-h-[200px]">
        <?php if (empty($floors)): ?>
            <div class="text-center py-10 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                <p class="text-gray-500 italic">No floors added yet.</p>
                <p class="text-gray-400 text-sm mt-1">Click "Add Floor" to get started.</p>
            </div>
        <?php else: ?>
            <?php foreach ($floors as $floor): ?>
                <div id="floor-content-<?php echo $floor['id']; ?>" 
                     class="floor-content <?php echo $floor['id'] == $active_floor_id ? 'block' : 'hidden'; ?>">
                    
                    <!-- Floor Header -->
                    <div id="floor-header-<?php echo $floor['id']; ?>" class="bg-gray-50 px-4 py-3 rounded-t-lg border border-b-0 flex justify-between items-center">
                        <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                            <span class="bg-gray-200 text-gray-700 py-1 px-2 rounded text-xs mr-2">Lvl <?php echo $floor['level_number']; ?></span>
                            <?php echo htmlspecialchars($floor['name']); ?>
                        </h4>
                        
                        <div class="flex items-center gap-2">
                            <!-- Edit Floor -->
                            <button hx-get="../backend/edit_floor_form.php?id=<?php echo $floor['id']; ?>"
                                    hx-target="#floor-header-<?php echo $floor['id']; ?>"
                                    hx-swap="outerHTML"
                                    class="text-gray-400 hover:text-blue-600 p-1 rounded hover:bg-gray-100 transition-colors" title="Edit Floor">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            
                            <!-- Delete Floor -->
                            <button hx-delete="../backend/delete_floor.php?id=<?php echo $floor['id']; ?>"
                                    hx-confirm="Are you sure? This will delete the floor and ALL rooms in it."
                                    hx-target="#structure-container"
                                    hx-swap="outerHTML"
                                    class="text-gray-400 hover:text-red-600 p-1 rounded hover:bg-gray-100 transition-colors mr-2" title="Delete Floor">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>

                            <!-- Add Room Button -->
                            <button onclick="document.getElementById('add-room-form-<?php echo $floor['id']; ?>').classList.toggle('hidden')" 
                                    class="text-blue-600 hover:text-blue-800 text-sm font-bold flex items-center bg-white px-3 py-1 rounded border shadow-sm hover:shadow">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Room
                            </button>
                        </div>
                    </div>

                    <!-- Add Room Form -->
                    <form id="add-room-form-<?php echo $floor['id']; ?>" 
                          hx-post="../backend/add_room.php" 
                          hx-target="#structure-container" 
                          hx-swap="outerHTML"
                          class="hidden bg-blue-50 p-4 border-x border-b mb-4">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="floor_id" value="<?php echo $floor['id']; ?>">
                        <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Room Name</label>
                                <input type="text" name="name" required placeholder="e.g. Conf Room A" class="w-full shadow border rounded py-1 px-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Type</label>
                                <select name="type" class="w-full shadow border rounded py-1 px-2 text-sm bg-white">
                                    <option value="Office">Office</option>
                                    <option value="Meeting Room">Meeting Room</option>
                                    <option value="Utility">Utility</option>
                                    <option value="Common Area">Common Area</option>
                                    <option value="Restroom">Restroom</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Capacity</label>
                                <input type="number" name="capacity" placeholder="0" class="w-full shadow border rounded py-1 px-2 text-sm">
                            </div>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-bold py-1 px-2 rounded h-8">Add Room</button>
                        </div>
                    </form>

                    <!-- Rooms List -->
                    <div class="bg-white border rounded-b-lg p-4 min-h-[100px]">
                        <?php 
                        $rooms = getRooms($floor['id']);
                        if (empty($rooms)): 
                        ?>
                            <div class="text-center py-8 text-gray-400 italic">
                                No rooms on this floor yet.
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <?php foreach ($rooms as $room): ?>
                                    <div id="room-<?php echo $room['id']; ?>" class="border rounded-lg p-3 bg-white hover:shadow-md transition-shadow relative group">
                                        <div class="font-bold text-gray-700 text-lg mb-1 pr-12 truncate" title="<?php echo htmlspecialchars($room['name']); ?>">
                                            <?php echo htmlspecialchars($room['name']); ?>
                                        </div>
                                        <div class="flex justify-between items-center text-xs text-gray-500">
                                            <span class="bg-gray-100 px-2 py-0.5 rounded"><?php echo htmlspecialchars($room['type']); ?></span>
                                            <?php if ($room['capacity']): ?>
                                                <span>Cap: <?php echo $room['capacity']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="absolute top-2 right-2 flex gap-1 bg-white bg-opacity-90 rounded p-1 shadow-sm">
                                            <button hx-get="../backend/edit_room_form.php?id=<?php echo $room['id']; ?>" 
                                                    hx-target="#room-<?php echo $room['id']; ?>" 
                                                    hx-swap="outerHTML"
                                                    class="text-blue-500 hover:text-blue-700 p-1 rounded hover:bg-blue-50" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <button hx-delete="../backend/delete_room.php?id=<?php echo $room['id']; ?>" 
                                                    hx-confirm="Are you sure you want to delete this room?"
                                                    hx-headers='{"X-CSRF-Token": "<?php echo generateCsrfToken(); ?>"}'
                                                    hx-target="#room-<?php echo $room['id']; ?>" 
                                                    hx-swap="outerHTML"
                                                    class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50" title="Delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
