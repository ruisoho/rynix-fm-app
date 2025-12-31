<?php
require_once 'db.php';

echo "Updating schema for Document Archive Module...\n";

try {
    // 1. Collections Table (Folders)
    $pdo->exec("CREATE TABLE IF NOT EXISTS collections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        parent_id INTEGER,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES collections(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "Created collections table.\n";

    // 2. Collection Items Table (Many-to-Many: Documents in Folders)
    $pdo->exec("CREATE TABLE IF NOT EXISTS collection_items (
        collection_id INTEGER NOT NULL,
        document_id INTEGER NOT NULL,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (collection_id, document_id),
        FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
        FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
    )");
    echo "Created collection_items table.\n";

    // 3. Ensure Documents and Document Links exist (from previous task)
    // Just in case, re-run safe IF NOT EXISTS
    $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL,
        file_path TEXT NOT NULL,
        mime_type TEXT,
        file_size INTEGER,
        uploaded_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS document_links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        document_id INTEGER NOT NULL,
        owner_type TEXT NOT NULL,
        owner_id INTEGER NOT NULL,
        link_role TEXT DEFAULT 'attachment',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
    )");
    
    echo "Verified base document tables.\n";

} catch (PDOException $e) {
    echo "Schema Update Error: " . $e->getMessage() . "\n";
}
?>
