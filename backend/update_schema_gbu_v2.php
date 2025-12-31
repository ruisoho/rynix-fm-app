<?php
require_once 'config.php';

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Updating Schema for Advanced GBU & Training features...\n";

    // 1. GBU History / Audit Trail
    $pdo->exec("CREATE TABLE IF NOT EXISTS gbu_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        gbu_id TEXT NOT NULL REFERENCES gbu_assessments(id) ON DELETE CASCADE,
        changed_by TEXT NOT NULL,
        changed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        action_type TEXT NOT NULL, -- create|update|approve|trigger_review
        change_details TEXT        -- JSON or text description of what changed
    )");

    // 2. GBU Documents / Proofs
    $pdo->exec("CREATE TABLE IF NOT EXISTS gbu_documents (
        id TEXT PRIMARY KEY,
        gbu_id TEXT NOT NULL REFERENCES gbu_assessments(id) ON DELETE CASCADE,
        filename TEXT NOT NULL,
        file_path TEXT NOT NULL,
        uploaded_by TEXT NOT NULL,
        uploaded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        doc_type TEXT -- proof|manual|photo
    )");

    // 3. Training Compliance (Per Person Tracking)
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_compliance (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL,
        requirement_id TEXT NOT NULL REFERENCES training_requirements(id),
        status TEXT NOT NULL DEFAULT 'pending', -- pending|completed|overdue
        due_date TEXT NOT NULL,
        completed_at TEXT,
        session_id TEXT REFERENCES training_sessions(id)
    )");

    // 4. Update GBU Assessments table to ensure review_due_date exists (it was in CREATE but good to be safe)
    // SQLite doesn't support IF NOT EXISTS for columns in ALTER TABLE easily, skipping as it was in original create script.

    echo "Schema updated successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
