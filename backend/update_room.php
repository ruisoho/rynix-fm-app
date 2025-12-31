<?php
require_once 'structure_logic.php';
require_once 'auth.php';
require_once 'csrf_helper.php';

requireLogin();

// Handle Cancel request (render original view)
if (isset($_GET['cancel']) && $_GET['cancel'] == 'true') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $room = getRoom($id);
        renderRoom($room);
    }
    exit;
}

// Handle Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $capacity = $_POST['capacity'] ?? 0;
    $area = $_POST['area'] ?? 0;

    if ($id) {
        updateRoom($id, $name, $type, $capacity, $area);
        $room = getRoom($id);
        renderRoom($room);
    } else {
        echo "Error: ID missing";
    }
}

function renderRoom($room) {
?>
    <div id="room-<?php echo $room['id']; ?>" class="border rounded p-2 bg-gray-50 text-sm hover:bg-gray-100 relative group">
        <div class="font-bold text-gray-700 pr-16"><?php echo htmlspecialchars($room['name']); ?></div>
        <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($room['type']); ?></div>
        <?php if ($room['capacity']): ?>
            <div class="text-gray-400 text-xs mt-1">Cap: <?php echo $room['capacity']; ?></div>
        <?php endif; ?>
        
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
<?php
}
?>
