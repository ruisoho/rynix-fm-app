<?php
require_once 'db.php';
require_once 'auth.php';

// Helper: Format File Size
function formatSize($bytes) {
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = Math.floor(Math.log($bytes) / Math.log($k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[$i];
}

// Helper: Get Icon
function getFileIcon($mimeType) {
    if (strpos($mimeType, 'pdf') !== false) return 'fa-file-pdf text-red-500';
    if (strpos($mimeType, 'image') !== false) return 'fa-file-image text-purple-500';
    if (strpos($mimeType, 'word') !== false) return 'fa-file-word text-blue-500';
    if (strpos($mimeType, 'excel') !== false || strpos($mimeType, 'spreadsheet') !== false) return 'fa-file-excel text-green-500';
    return 'fa-file text-gray-400';
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'view';
$view = $_GET['view'] ?? 'all'; // all, maintenance, tasks, invoices, projects, laws
$collectionId = $_GET['collection_id'] ?? null;
$userId = $_SESSION['user_id'] ?? 0;

// --- ACTIONS ---

// 1. Create Folder
if ($action === 'create_folder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? 'New Folder';
    $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    
    $stmt = $pdo->prepare("INSERT INTO collections (name, parent_id, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$name, $parentId, $userId]);
    
    // Return updated tree
    $action = 'get_tree';
}

// 2. Delete Folder
if ($action === 'delete_folder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM collections WHERE id = ?");
    $stmt->execute([$id]);
    
    $action = 'get_tree';
}

// 3. Add Document to Collection
if ($action === 'add_to_collection' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $docId = $_POST['document_id'];
    $colId = $_POST['collection_id'];
    
    // Check if already exists
    $check = $pdo->prepare("SELECT 1 FROM collection_items WHERE collection_id = ? AND document_id = ?");
    $check->execute([$colId, $docId]);
    
    if (!$check->fetchColumn()) {
        $stmt = $pdo->prepare("INSERT INTO collection_items (collection_id, document_id) VALUES (?, ?)");
        $stmt->execute([$colId, $docId]);
    }
    
    echo "Added"; // Simple ack
    exit;
}

// --- VIEWS ---

// A. Get Folder Tree (Recursive)
if ($action === 'get_tree') {
    function buildTree($parentId = null) {
        global $pdo;
        $sql = "SELECT * FROM collections WHERE parent_id " . ($parentId ? "= ?" : "IS NULL") . " ORDER BY name";
        $stmt = $pdo->prepare($sql);
        if ($parentId) $stmt->execute([$parentId]);
        else $stmt->execute();
        $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<ul class="pl-4 space-y-1">';
        foreach ($folders as $folder) {
            $html .= '<li>';
            $html .= '<div class="flex items-center justify-between group hover:bg-gray-100 p-1 rounded cursor-pointer" 
                           hx-get="../backend/documents_archive.php?action=list_docs&collection_id=' . $folder['id'] . '" 
                           hx-target="#document-list-container">';
            $html .= '<span class="flex items-center"><i class="fas fa-folder text-yellow-500 mr-2"></i> ' . htmlspecialchars($folder['name']) . '</span>';
            // Delete Button (Small)
            $html .= '<button hx-post="../backend/documents_archive.php" hx-vals=\'{"action": "delete_folder", "id": ' . $folder['id'] . '}\' hx-target="#folder-tree" hx-confirm="Delete folder?" class="text-red-400 opacity-0 group-hover:opacity-100 hover:text-red-600 text-xs"><i class="fas fa-trash"></i></button>';
            $html .= '</div>';
            $html .= buildTree($folder['id']);
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
    
    echo buildTree();
    exit;
}

// B. List Documents (Grid View)
if ($action === 'list_docs' || $action === 'view') {
    $sql = "SELECT d.*, u.username FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id";
    $params = [];
    $where = [];

    if ($collectionId) {
        // Filter by Collection
        $sql .= " JOIN collection_items ci ON d.id = ci.document_id";
        $where[] = "ci.collection_id = ?";
        $params[] = $collectionId;
    } elseif ($view !== 'all') {
        // Filter by View (Owner Type)
        // Map view names to owner_types in document_links
        // maintenance, task, invoice, project, obligation
        $ownerType = '';
        switch($view) {
            case 'maintenance': $ownerType = 'maintenance'; break;
            case 'tasks': $ownerType = 'task'; break;
            case 'invoices': $ownerType = 'invoice'; break;
            case 'projects': $ownerType = 'project'; break;
            case 'laws': $ownerType = 'obligation'; break; 
        }
        
        if ($ownerType) {
            $sql .= " JOIN document_links dl ON d.id = dl.document_id";
            $where[] = "dl.owner_type = ?";
            $params[] = $ownerType;
        }
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " GROUP BY d.id ORDER BY d.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($documents)) {
        echo '<div class="col-span-full text-center py-10 text-gray-500">No documents found.</div>';
        exit;
    }

    foreach ($documents as $doc) {
        $iconClass = getFileIcon($doc['mime_type']);
        $size = round($doc['file_size'] / 1024, 1) . ' KB';
        
        echo '
        <div class="bg-white p-4 rounded-lg shadow border border-gray-200 hover:shadow-md transition-shadow relative group draggable" draggable="true" ondragstart="drag(event, ' . $doc['id'] . ')">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <i class="fas ' . $iconClass . ' text-4xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate" title="' . htmlspecialchars($doc['filename']) . '">
                        ' . htmlspecialchars($doc['filename']) . '
                    </p>
                    <p class="text-xs text-gray-500">' . $size . '</p>
                    <p class="text-xs text-gray-400 mt-1">By ' . htmlspecialchars($doc['username'] ?? 'Unknown') . '</p>
                    <p class="text-xs text-gray-400">' . date('M j, Y', strtotime($doc['created_at'])) . '</p>
                </div>
            </div>
            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <a href="' . $doc['file_path'] . '" target="_blank" class="text-blue-500 hover:text-blue-700 p-1">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
        </div>
        ';
    }
    exit;
}
?>
