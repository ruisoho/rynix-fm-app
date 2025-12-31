<?php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

// --- Helper Functions ---

function getDocuments($ownerType, $ownerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT d.*, dl.link_role, u.username as uploader_name
        FROM documents d
        JOIN document_links dl ON d.id = dl.document_id
        LEFT JOIN users u ON d.uploaded_by = u.id
        WHERE dl.owner_type = ? AND dl.owner_id = ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$ownerType, $ownerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- API Logic ---

// 1. List Documents
if ($action === 'list') {
    $ownerType = $_GET['owner_type'] ?? '';
    $ownerId = $_GET['owner_id'] ?? '';

    if (!$ownerType || !$ownerId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing owner_type or owner_id']);
        exit;
    }

    $documents = getDocuments($ownerType, $ownerId);
    echo json_encode($documents);
    exit;
}

// 2. Upload Document
if ($action === 'upload') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    $ownerType = $_POST['owner_type'] ?? '';
    $ownerId = $_POST['owner_id'] ?? '';
    $linkRole = $_POST['link_role'] ?? 'attachment';

    if (!$ownerType || !$ownerId || !isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields or file']);
        exit;
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(500);
        echo json_encode(['error' => 'File upload error: ' . $file['error']]);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('doc_') . '.' . $extension;
    $filePath = $uploadDir . $filename;
    $relativePath = '../uploads/' . $filename; // Store relative path for frontend access

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        try {
            $pdo->beginTransaction();

            // Insert into documents
            $stmt = $pdo->prepare("INSERT INTO documents (filename, file_path, mime_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$file['name'], $relativePath, $file['type'], $file['size'], $userId]);
            $documentId = $pdo->lastInsertId();

            // Link to owner
            $stmt = $pdo->prepare("INSERT INTO document_links (document_id, owner_type, owner_id, link_role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$documentId, $ownerType, $ownerId, $linkRole]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'File uploaded successfully']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            unlink($filePath); // Delete file if DB insert fails
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move uploaded file']);
    }
    exit;
}

// 3. Delete Document Link (Unlink)
if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    $documentId = $_POST['document_id'] ?? '';
    $ownerType = $_POST['owner_type'] ?? '';
    $ownerId = $_POST['owner_id'] ?? '';

    if (!$documentId || !$ownerType || !$ownerId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        // Only delete the link
        $stmt = $pdo->prepare("DELETE FROM document_links WHERE document_id = ? AND owner_type = ? AND owner_id = ?");
        $stmt->execute([$documentId, $ownerType, $ownerId]);
        
        // Optional: Check if document is orphaned and delete file/record if so.
        // For now, we keep the file record as per "Store each file once" philosophy, 
        // though typically you'd want garbage collection for true orphans.
        // But user said "Store each file once... Represent 'folders' via document_links".
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

?>
