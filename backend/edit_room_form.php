<?php
require_once 'structure_logic.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Error: ID missing";
    exit;
}

$room = getRoom($id);
if (!$room) {
    echo "Error: Room not found";
    exit;
}
?>

<form id="room-<?php echo $room['id']; ?>"
      hx-post="../backend/update_room.php" 
      hx-target="this" 
      hx-swap="outerHTML"
      class="border rounded p-2 bg-yellow-50 text-sm">
    <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
    <input type="hidden" name="floor_id" value="<?php echo $room['floor_id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    
    <div class="grid grid-cols-2 gap-2 mb-2">
        <input type="text" name="name" value="<?php echo htmlspecialchars($room['name']); ?>" required class="shadow border rounded py-1 px-2 w-full">
        <select name="type" class="shadow border rounded py-1 px-2 w-full bg-white">
            <?php
            $types = ['Office', 'Meeting Room', 'Utility', 'Common Area'];
            foreach ($types as $type) {
                $selected = $room['type'] === $type ? 'selected' : '';
                echo "<option value='$type' $selected>$type</option>";
            }
            ?>
        </select>
    </div>
    <div class="grid grid-cols-2 gap-2 mb-2">
        <input type="number" name="capacity" value="<?php echo htmlspecialchars($room['capacity']); ?>" placeholder="Capacity" class="shadow border rounded py-1 px-2 w-full">
        <input type="number" name="area" value="<?php echo htmlspecialchars($room['area']); ?>" placeholder="Area (m²)" step="0.01" class="shadow border rounded py-1 px-2 w-full">
    </div>
    
    <div class="flex justify-end gap-2">
        <button type="button" 
                hx-get="../backend/update_room.php?id=<?php echo $room['id']; ?>&cancel=true" 
                hx-target="closest form" 
                hx-swap="outerHTML"
                class="text-gray-500 hover:text-gray-700 text-xs font-bold py-1 px-2">
            Cancel
        </button>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1 px-2 rounded">
            Save
        </button>
    </div>
</form>
