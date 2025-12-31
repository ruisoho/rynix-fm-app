<?php
require_once 'structure_logic.php';
require_once 'auth.php';
require_once 'csrf_helper.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) exit('ID missing');

$floor = getFloor($id);
if (!$floor) exit('Floor not found');
?>

<form hx-post="../backend/update_floor.php" 
      hx-target="#structure-container" 
      hx-swap="outerHTML"
      class="bg-gray-100 p-3 rounded-t-lg border border-b-0 flex gap-4 items-center">
    
    <input type="hidden" name="id" value="<?php echo $floor['id']; ?>">
    <input type="hidden" name="facility_id" value="<?php echo $floor['facility_id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    
    <div class="flex-grow flex gap-2 items-center">
        <label class="text-xs font-bold text-gray-600">Lvl</label>
        <input type="number" name="level_number" value="<?php echo htmlspecialchars($floor['level_number']); ?>" 
               class="shadow border rounded py-1 px-2 text-sm w-16" required>
        
        <label class="text-xs font-bold text-gray-600 ml-2">Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($floor['name']); ?>" 
               class="shadow border rounded py-1 px-2 text-sm w-full max-w-xs" required>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-1 px-3 rounded">
            Save
        </button>
        <button hx-get="../backend/structure_view.php?facility_id=<?php echo $floor['facility_id']; ?>&active_floor_id=<?php echo $floor['id']; ?>"
                hx-target="#structure-container"
                hx-swap="outerHTML"
                type="button" 
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-xs font-bold py-1 px-3 rounded">
            Cancel
        </button>
    </div>
</form>