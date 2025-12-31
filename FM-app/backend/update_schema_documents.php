<?php
require_once 'db.php';

echo "Updating schema for Universal Document System...\n";

try {
    // 1. Documents Table
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
    echo "Created documents table.\n";

    // 2. Document Links Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS document_links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        document_id INTEGER NOT NULL,
        owner_type TEXT NOT NULL, -- 'maintenance', 'task', 'invoice', 'project', 'obligation', etc.
        owner_id INTEGER NOT NULL,
        link_role TEXT DEFAULT 'attachment', -- 'invoice', 'evidence', 'contract', 'plan', 'protocol'
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
    )");
    echo "Created document_links table.\n";

    // 3. Invoices Table (First-class entity)
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_number TEXT NOT NULL,
        supplier_name TEXT,
        invoice_date DATE,
        due_date DATE,
        amount REAL,
        currency TEXT DEFAULT 'EUR',
        status TEXT DEFAULT 'Pending', -- Pending, Paid, Overdue
        description TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "Created invoices table.\n";
    
    // 4. Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "Created uploads directory.\n";
    }

} catch (PDOException $e) {
    echo "Schema Update Error: " . $e->getMessage() . "\n";
}
?>
