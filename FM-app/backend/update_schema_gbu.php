<?php
require_once 'config.php';

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Creating GBU and Training tables...\n";

    // 1) GBU Kopf (pro Bereich/Tätigkeit)
    $pdo->exec("CREATE TABLE IF NOT EXISTS gbu_assessments (
        id TEXT PRIMARY KEY,
        building_id TEXT,
        area TEXT NOT NULL,                -- z.B. 'Werkstatt', 'Labor 2.OG', 'Halle 2'
        activity TEXT NOT NULL,            -- z.B. 'Elektro-Instandhaltung'
        scope_type TEXT NOT NULL,          -- area|activity|asset|project
        scope_ref_id TEXT,                 -- optional: asset_id / project_id
        status TEXT NOT NULL DEFAULT 'draft',   -- draft|active|archived
        version INTEGER NOT NULL DEFAULT 1,
        created_by TEXT NOT NULL,
        created_at TEXT NOT NULL,
        approved_by TEXT,
        approved_at TEXT,
        review_due_date TEXT               -- nächste turnusmäßige Überprüfung
    )");

    // 2) Gefährdungen
    $pdo->exec("CREATE TABLE IF NOT EXISTS gbu_hazards (
        id TEXT PRIMARY KEY,
        gbu_id TEXT NOT NULL REFERENCES gbu_assessments(id) ON DELETE CASCADE,
        hazard_category TEXT NOT NULL,     -- electrical|fire|falls|chemicals|ergonomics|psych|other
        hazard_description TEXT NOT NULL,
        exposed_group TEXT NOT NULL,       -- employees|contractors|students|visitors|mixed
        probability INTEGER NOT NULL,      -- 1..5
        severity INTEGER NOT NULL,         -- 1..5
        risk_score INTEGER NOT NULL,       -- stored = probability*severity
        existing_controls TEXT
    )");

    // 3) Maßnahmen (TOP: technisch/organisatorisch/persönlich)
    $pdo->exec("CREATE TABLE IF NOT EXISTS gbu_controls (
        id TEXT PRIMARY KEY,
        hazard_id TEXT NOT NULL REFERENCES gbu_hazards(id) ON DELETE CASCADE,
        control_level TEXT NOT NULL,       -- technical|organizational|personal
        description TEXT NOT NULL,
        responsible_user_id TEXT,
        due_date TEXT,
        status TEXT NOT NULL DEFAULT 'open',  -- open|in_progress|done|rejected
        effectiveness_check_date TEXT,
        effectiveness_result TEXT
    )");

    // 4) Unterweisungs-Templates (Inhalte) + Instanzen (Nachweise)
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_templates (
        id TEXT PRIMARY KEY,
        title TEXT NOT NULL,               -- 'Unterweisung Elektroarbeiten', 'Brandschutz'
        interval_days INTEGER,             -- z.B. 365, NULL = anlassbezogen
        content_outline TEXT NOT NULL,
        requires_quiz INTEGER NOT NULL DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS training_requirements (
        id TEXT PRIMARY KEY,
        gbu_id TEXT REFERENCES gbu_assessments(id) ON DELETE CASCADE,
        template_id TEXT NOT NULL REFERENCES training_templates(id),
        target_group TEXT NOT NULL,        -- role:Technician, dept:FM, contractors, etc.
        trigger TEXT NOT NULL,             -- annual|onboarding|change|incident|task_based
        active INTEGER NOT NULL DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS training_sessions (
        id TEXT PRIMARY KEY,
        template_id TEXT NOT NULL REFERENCES training_templates(id),
        scheduled_at TEXT NOT NULL,
        trainer_user_id TEXT,
        location TEXT,
        status TEXT NOT NULL DEFAULT 'planned'  -- planned|done|canceled
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS training_attendance (
        session_id TEXT NOT NULL REFERENCES training_sessions(id) ON DELETE CASCADE,
        person_id TEXT NOT NULL,
        attended INTEGER NOT NULL DEFAULT 0,
        signed_at TEXT,
        PRIMARY KEY (session_id, person_id)
    )");

    echo "Tables created successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
