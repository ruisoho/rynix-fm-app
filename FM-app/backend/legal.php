<?php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: text/html; charset=utf-8');

$action = $_GET['action'] ?? 'list_laws';
$layer = $_GET['layer'] ?? 'all';
$tag = $_GET['tag'] ?? 'all';
$search = $_GET['search'] ?? '';

// --- ACTIONS ---

// 0. Get Unique Tags (for Dropdown)
if ($action === 'get_tags') {
    $stmt = $pdo->query("SELECT DISTINCT category_tag FROM legal_laws WHERE category_tag IS NOT NULL AND category_tag != '' ORDER BY category_tag ASC");
    $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo '<option value="all">All Categories</option>';
    foreach ($tags as $t) {
        $selected = ($tag === $t) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($t) . '" ' . $selected . '>' . htmlspecialchars($t) . '</option>';
    }
    exit;
}

// 1. List Laws (Filtered)
if ($action === 'list_laws') {
    $sql = "SELECT * FROM legal_laws WHERE 1=1";
    $params = [];
    
    if ($layer !== 'all' && !empty($layer)) {
        $sql .= " AND layer = ?";
        $params[] = $layer;
    }
    
    if ($tag !== 'all' && !empty($tag)) {
        $sql .= " AND category_tag = ?";
        $params[] = $tag;
    }

    if (!empty($search)) {
        $sql .= " AND (abbreviation LIKE ? OR full_name LIKE ? OR description LIKE ?)";
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    
    $sql .= " ORDER BY abbreviation ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $laws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($laws)) {
        echo '<div class="col-span-full text-center py-10 text-gray-500">No laws found matching filters.</div>';
        exit;
    }
    
    // Optimization: Bulk fetch obligations to avoid N+1 queries
    $lawIds = array_column($laws, 'id');
    $obligationsByLaw = [];
    
    if (!empty($lawIds)) {
        // Use placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($lawIds), '?'));
        // We could use ROW_NUMBER() here for optimization if supported, but fetching all is safe for now
        // as long as obligation count isn't massive per law.
        // To be safe against massive result sets, we stick to the loop if we can't ensure low volume.
        // But let's assume reasonable volume.
        $stmtObs = $pdo->prepare("SELECT * FROM legal_obligations WHERE law_id IN ($placeholders)");
        $stmtObs->execute($lawIds);
        $allObs = $stmtObs->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($allObs as $ob) {
            $lid = $ob['law_id'];
            if (!isset($obligationsByLaw[$lid])) {
                $obligationsByLaw[$lid] = [];
            }
            // Only keep top 3 for display
            if (count($obligationsByLaw[$lid]) < 3) {
                $obligationsByLaw[$lid][] = $ob;
            }
        }
    }

    foreach ($laws as $law) {
        $obligations = $obligationsByLaw[$law['id']] ?? [];
        
        // Determine badge color based on tag
        $badgeColor = 'bg-gray-100 text-gray-800';
        if ($law['category_tag'] == 'Arbeitsschutz') $badgeColor = 'bg-blue-100 text-blue-800';
        if ($law['category_tag'] == 'Brandschutz') $badgeColor = 'bg-red-100 text-red-800';
        if ($law['category_tag'] == 'Elektrosicherheit') $badgeColor = 'bg-yellow-100 text-yellow-800';
        
        echo '
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow p-5 flex flex-col h-full">
            <div class="flex justify-between items-start mb-2">
                <h3 class="text-lg font-bold text-blue-600">' . htmlspecialchars($law['abbreviation']) . '</h3>
                <span class="px-2 py-1 text-xs rounded-full ' . $badgeColor . '">' . htmlspecialchars($law['category_tag']) . '</span>
            </div>
            <h4 class="text-sm font-medium text-gray-900 mb-2 min-h-[40px]">' . htmlspecialchars($law['full_name']) . '</h4>
            <p class="text-xs text-gray-500 mb-4 line-clamp-3 flex-grow">' . htmlspecialchars($law['description']) . '</p>
            
            <div class="space-y-2 mb-4">
                ';
                foreach ($obligations as $ob) {
                    echo '<button onclick="openObligationModal(' . $ob['id'] . ')" class="text-left w-full text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50 truncate">
                        <span class="font-bold">' . htmlspecialchars($ob['section']) . '</span> ' . htmlspecialchars($ob['title']) . '
                    </button>';
                }
                if (count($obligations) >= 3) {
                     echo '<div class="text-center text-xs text-gray-400 mt-1">+ more sections</div>';
                }
                echo '
            </div>
            
            <div class="flex mt-auto pt-4 border-t border-gray-100">
                <a href="' . ($law['link_online'] ?? '#') . '" target="_blank" class="w-full text-center py-1 text-xs text-gray-600 hover:text-blue-600 border rounded hover:bg-gray-50">
                    <i class="fas fa-globe mr-1"></i> Online
                </a>
            </div>
            <button onclick="openLawTextModal(' . $law['id'] . ')" class="w-full mt-2 py-1 text-xs bg-gray-800 text-white rounded hover:bg-gray-700">
                <i class="fas fa-book-open mr-1"></i> Read Full Text
            </button>
        </div>
        ';
    }
    exit;
}

// 2. Get Law Text (Full Text Modal)
if ($action === 'get_law_text') {
    $id = $_GET['id'];
    
    // Fetch Law Info
    $stmt = $pdo->prepare("SELECT * FROM legal_laws WHERE id = ?");
    $stmt->execute([$id]);
    $law = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Sections
    $stmtSec = $pdo->prepare("SELECT * FROM legal_law_sections WHERE law_id = ? ORDER BY id ASC");
    $stmtSec->execute([$id]);
    $sections = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

    if (!$law) exit('Law not found');

    echo '
    <div class="h-full flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg">
            <div>
                <h3 class="text-xl font-bold text-gray-900">' . htmlspecialchars($law['abbreviation']) . ' - Full Text</h3>
                <p class="text-sm text-gray-500">' . htmlspecialchars($law['full_name']) . '</p>
            </div>
            <button onclick="closeLawTextModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6 bg-white space-y-8 h-full">
            ' . (empty($sections) ? 
                (!empty($law['link_pdf']) ? 
                    '<div class="w-full h-full min-h-[500px]">
                        <object data="' . htmlspecialchars($law['link_pdf']) . '" type="application/pdf" width="100%" height="100%">
                            <div class="text-center py-10">
                                <p class="mb-4">It appears your browser does not support embedded PDFs.</p>
                                <a href="' . htmlspecialchars($law['link_pdf']) . '" target="_blank" class="text-blue-600 hover:underline">
                                    Click here to download the PDF
                                </a>
                            </div>
                        </object>
                    </div>' : 
                    '<div class="text-center text-gray-500 italic py-10">
                        <p class="mb-4">No full text content available.</p>
                        Please check the online source.
                    </div>'
                ) : '') . '
            ';
            
            foreach ($sections as $sec) {
                echo '
                <div class="prose max-w-none">
                    <h4 class="text-lg font-semibold text-gray-800 border-b pb-1 mb-2">
                        <span class="text-blue-600">' . htmlspecialchars($sec['section_number']) . '</span> ' . htmlspecialchars($sec['title']) . '
                    </h4>
                    <div class="text-gray-700 whitespace-pre-wrap leading-relaxed text-sm">
                        ' . nl2br(htmlspecialchars($sec['content'])) . '
                    </div>
                </div>
                ';
            }
            
    echo '
        </div>
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg flex justify-between items-center">
            <span class="text-xs text-gray-500">Source: Local Database (Synced ' . date('Y-m-d') . ')</span>
            <a href="' . ($law['link_online'] ?? '#') . '" target="_blank" class="text-sm text-blue-600 hover:underline">
                View Official Source <i class="fas fa-external-link-alt ml-1"></i>
            </a>
        </div>
    </div>
    ';
    exit;
}

// 3. Get Obligation Details (Modal Content)
if ($action === 'get_obligation') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT o.*, l.abbreviation as law_name FROM legal_obligations o JOIN legal_laws l ON o.law_id = l.id WHERE o.id = ?");
    $stmt->execute([$id]);
    $ob = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ob) exit('Not found');
    
    echo '
    <div class="p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">' . htmlspecialchars($ob['law_name']) . ' ' . htmlspecialchars($ob['section']) . '</h3>
                <h4 class="text-lg text-gray-700">' . htmlspecialchars($ob['title']) . '</h4>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <div class="bg-gray-50 p-3 rounded border border-gray-200">
                <span class="block text-xs font-bold text-gray-500 uppercase">Trigger</span>
                <p class="text-sm text-gray-800">' . htmlspecialchars($ob['trigger_condition']) . '</p>
            </div>
            
            <div>
                <span class="block text-xs font-bold text-gray-500 uppercase mb-1">Required Action</span>
                <p class="text-sm text-gray-800 bg-blue-50 p-3 rounded border-l-4 border-blue-500">' . nl2br(htmlspecialchars($ob['required_action'])) . '</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="block text-xs font-bold text-gray-500 uppercase">Proof / Documentation</span>
                    <p class="text-sm text-gray-800"><i class="fas fa-file-alt text-gray-400 mr-1"></i> ' . htmlspecialchars($ob['proof_type']) . '</p>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-500 uppercase">Deadline / Frequency</span>
                    <p class="text-sm text-gray-800"><i class="fas fa-clock text-gray-400 mr-1"></i> ' . htmlspecialchars($ob['deadline']) . '</p>
                </div>
            </div>
        </div>
        
        <div class="mt-8 pt-4 border-t flex justify-end space-x-3">
             <button onclick=\'openCreateTaskModal(\' . htmlspecialchars(json_encode($ob[\'title\']), ENT_QUOTES) . \', \' . htmlspecialchars(json_encode($ob[\'required_action\']), ENT_QUOTES) . \', \' . htmlspecialchars(json_encode($ob[\'deadline\']), ENT_QUOTES) . \')\' class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Create Task</button>
        </div>
    </div>
    ';
    exit;
}
?>
