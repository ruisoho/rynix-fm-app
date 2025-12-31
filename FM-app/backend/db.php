<?php
require_once 'config.php';

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Performance Optimization: Enable Write-Ahead Logging (WAL)
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA synchronous = NORMAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');
    
    // Create table if not exists with new schema
    $pdo->exec("CREATE TABLE IF NOT EXISTS facilities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        type TEXT NOT NULL,
        address TEXT,
        area REAL,
        construction_year INTEGER,
        status TEXT,
        employees INTEGER,
        op_hours TEXT,
        hazard_level TEXT,
        manager_name TEXT,
        manager_contact TEXT,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create floors table
    $pdo->exec("CREATE TABLE IF NOT EXISTS floors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        facility_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        level_number INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
    )");

    // Create rooms table
    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        floor_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        type TEXT,
        capacity INTEGER,
        area REAL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE
    )");

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        full_name TEXT,
        email TEXT,
        role TEXT DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create providers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS providers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        contact_person TEXT,
        email TEXT,
        phone TEXT,
        service_type TEXT,
        address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create maintenance_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        facility_id INTEGER NOT NULL,
        provider_id INTEGER,
        title TEXT NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'Open',
        priority TEXT DEFAULT 'Medium',
        recurrence TEXT DEFAULT 'None',
        cost REAL,
        scheduled_date DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
        FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL
    )");

    // Add provider_id column to maintenance_logs if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE maintenance_logs ADD COLUMN provider_id INTEGER REFERENCES providers(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // Column likely already exists
    }

    // Create daily_tasks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        facility_id INTEGER NOT NULL,
        floor_id INTEGER,
        room_id INTEGER,
        provider_id INTEGER,
        title TEXT NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'Pending',
        priority TEXT DEFAULT 'Medium',
        assigned_to TEXT,
        due_date DATE,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
        FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE SET NULL,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
        FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Add provider_id column to daily_tasks if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE daily_tasks ADD COLUMN provider_id INTEGER REFERENCES providers(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // Column likely already exists
    }

    // Add created_by column to daily_tasks if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE daily_tasks ADD COLUMN created_by INTEGER REFERENCES users(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // Column likely already exists
    }

    // Add priority column to daily_tasks if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE daily_tasks ADD COLUMN priority TEXT DEFAULT 'Medium'");
    } catch (PDOException $e) {
        // Column likely already exists
    }
    
    // Create meters table
    $pdo->exec("CREATE TABLE IF NOT EXISTS meters (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        facility_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        type TEXT NOT NULL, -- Electricity, Gas, Water, Heating
        unit TEXT NOT NULL, -- kWh, m³, MWh
        serial_number TEXT,
        location TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
    )");

    // Create meter_readings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS meter_readings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        meter_id INTEGER NOT NULL,
        reading_value REAL NOT NULL,
        reading_date DATE NOT NULL,
        recorded_by INTEGER, -- User ID
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (meter_id) REFERENCES meters(id) ON DELETE CASCADE,
        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Insert some dummy data if empty
    $count = $pdo->query("SELECT COUNT(*) FROM facilities")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO facilities (name, type, address, area, construction_year, status, employees, op_hours, hazard_level, manager_name, manager_contact, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            'Main Building', 'Office', '123 Tech Blvd', 5000.5, 2010, 'Active', 150, '9-5', 'Low', 'John Doe', 'john@example.com', 'Headquarters'
        ]);
        
        $stmt->execute([
            'Warehouse A', 'Warehouse', '456 Storage Ln', 12000, 2015, 'Active', 20, '24/7', 'Medium', 'Jane Smith', 'jane@example.com', 'Storage for raw materials'
        ]);
    }

    // Insert default admin user if no users exist
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $password, 'Administrator', 'admin@fm-app.com', 'admin']);
    }

    // --- Key Management Tables ---

    // Create keys table
    $pdo->exec("CREATE TABLE IF NOT EXISTS keys (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        facility_id INTEGER NOT NULL,
        serial_number TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        type TEXT NOT NULL, -- Physical, Key Card, Fob, Master
        status TEXT DEFAULT 'Available', -- Available, Issued, Lost, Broken
        qr_code TEXT UNIQUE,
        assigned_to INTEGER, -- User ID
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
    )");

    // --- Document Management Tables ---

    // Create documents table
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

    // Create collections table (Folders)
    $pdo->exec("CREATE TABLE IF NOT EXISTS collections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        parent_id INTEGER,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES collections(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Create collection_items table (Many-to-Many: Documents <-> Collections)
    $pdo->exec("CREATE TABLE IF NOT EXISTS collection_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        collection_id INTEGER NOT NULL,
        document_id INTEGER NOT NULL,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
        FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
        UNIQUE(collection_id, document_id)
    )");

    // Create document_links table (Polymorphic: Documents <-> Entities like Tasks, Logs)
    $pdo->exec("CREATE TABLE IF NOT EXISTS document_links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        document_id INTEGER NOT NULL,
        owner_type TEXT NOT NULL, -- 'maintenance', 'task', 'invoice', 'project', 'obligation'
        owner_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
    )");

    // Add new columns to keys table if they don't exist (for existing DBs)
    try {
        $pdo->exec("ALTER TABLE keys ADD COLUMN keys_in_bundle INTEGER DEFAULT 1");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE keys ADD COLUMN is_master INTEGER DEFAULT 0");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE keys ADD COLUMN allowed_roles TEXT");
    } catch (PDOException $e) {}

    // Create locks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS locks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        facility_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        location TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
    )");

    // Create key_locks table (Many-to-Many)
    $pdo->exec("CREATE TABLE IF NOT EXISTS key_locks (
        key_id INTEGER NOT NULL,
        lock_id INTEGER NOT NULL,
        PRIMARY KEY (key_id, lock_id),
        FOREIGN KEY (key_id) REFERENCES keys(id) ON DELETE CASCADE,
        FOREIGN KEY (lock_id) REFERENCES locks(id) ON DELETE CASCADE
    )");

    // Create notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        type TEXT DEFAULT 'info', -- info, success, warning, error
        is_read INTEGER DEFAULT 0,
        related_link TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create key_transactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS key_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key_id INTEGER NOT NULL,
        user_id INTEGER, -- Issued To (Nullable for Guests)
        guest_name TEXT, -- For non-registered users
        guest_contact TEXT,
        issued_by INTEGER NOT NULL, -- Staff who issued it
        issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expected_return_at DATETIME,
        returned_at DATETIME,
        status TEXT DEFAULT 'Active', -- Active, Returned, Overdue
        FOREIGN KEY (key_id) REFERENCES keys(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Add guest columns to key_transactions if they don't exist
    try {
        $pdo->exec("ALTER TABLE key_transactions ADD COLUMN guest_name TEXT");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE key_transactions ADD COLUMN guest_contact TEXT");
    } catch (PDOException $e) {}

    // Create key_access_matrix table
    $pdo->exec("CREATE TABLE IF NOT EXISTS key_access_matrix (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        key_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (key_id) REFERENCES keys(id) ON DELETE CASCADE,
        UNIQUE(user_id, key_id)
    )");

    // Create events table for Calendar
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME,
        visibility_type TEXT DEFAULT 'private',
        visibility_target TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )");

    // --- Performance Indexes ---
    $indexes = [
        // Daily Tasks
        "CREATE INDEX IF NOT EXISTS idx_tasks_facility ON daily_tasks(facility_id)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_floor ON daily_tasks(floor_id)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_room ON daily_tasks(room_id)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_provider ON daily_tasks(provider_id)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_creator ON daily_tasks(created_by)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_status ON daily_tasks(status)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_due_date ON daily_tasks(due_date)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_assigned ON daily_tasks(assigned_to)",
        
        // Maintenance Logs
        "CREATE INDEX IF NOT EXISTS idx_maint_facility ON maintenance_logs(facility_id)",
        "CREATE INDEX IF NOT EXISTS idx_maint_provider ON maintenance_logs(provider_id)",
        "CREATE INDEX IF NOT EXISTS idx_maint_status ON maintenance_logs(status)",
        "CREATE INDEX IF NOT EXISTS idx_maint_date ON maintenance_logs(scheduled_date)",
        
        // Keys
        "CREATE INDEX IF NOT EXISTS idx_keys_facility ON keys(facility_id)",
        "CREATE INDEX IF NOT EXISTS idx_keys_serial ON keys(serial_number)",
        
        // Key Transactions
        "CREATE INDEX IF NOT EXISTS idx_key_trans_key ON key_transactions(key_id)",
        "CREATE INDEX IF NOT EXISTS idx_key_trans_user ON key_transactions(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_key_trans_status ON key_transactions(status)",
        
        // Events
        "CREATE INDEX IF NOT EXISTS idx_events_start ON events(start_datetime)",
        
        // Legal (if tables exist)
        "CREATE INDEX IF NOT EXISTS idx_legal_laws_layer ON legal_laws(layer)",
        "CREATE INDEX IF NOT EXISTS idx_legal_obs_law ON legal_obligations(law_id)",
        "CREATE INDEX IF NOT EXISTS idx_legal_sections_law ON legal_law_sections(law_id)"
    ];

    foreach ($indexes as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Ignore if table doesn't exist yet (e.g. legal tables)
        }
    }

    // --- FTS5 Full Text Search ---
    try {
        // Create Virtual Table
        $pdo->exec("CREATE VIRTUAL TABLE IF NOT EXISTS legal_law_sections_fts USING fts5(
            title, 
            content, 
            content='legal_law_sections', 
            content_rowid='id'
        )");

        // Create Triggers to keep FTS synced
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS legal_law_sections_ai AFTER INSERT ON legal_law_sections BEGIN
            INSERT INTO legal_law_sections_fts(rowid, title, content) VALUES (new.id, new.title, new.content);
        END;");

        $pdo->exec("CREATE TRIGGER IF NOT EXISTS legal_law_sections_ad AFTER DELETE ON legal_law_sections BEGIN
            INSERT INTO legal_law_sections_fts(legal_law_sections_fts, rowid, title, content) VALUES('delete', old.id, old.title, old.content);
        END;");

        $pdo->exec("CREATE TRIGGER IF NOT EXISTS legal_law_sections_au AFTER UPDATE ON legal_law_sections BEGIN
            INSERT INTO legal_law_sections_fts(legal_law_sections_fts, rowid, title, content) VALUES('delete', old.id, old.title, old.content);
            INSERT INTO legal_law_sections_fts(rowid, title, content) VALUES (new.id, new.title, new.content);
        END;");
        
    } catch (PDOException $e) {
        // FTS5 might not be enabled on all systems, proceed without it but log/warn
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
