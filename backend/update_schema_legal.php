<?php
require_once 'db.php';

try {
    echo "Updating schema for Legal Operating System (SQLite)...\n";

    // 1. Laws Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS legal_laws (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        abbreviation TEXT NOT NULL,
        full_name TEXT NOT NULL,
        description TEXT,
        layer TEXT NOT NULL,
        category_tag TEXT,
        link_online TEXT,
        link_pdf TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created legal_laws table.\n";

    // 2. Obligations Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS legal_obligations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        law_id INTEGER NOT NULL,
        section TEXT NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        trigger_condition TEXT,
        required_action TEXT,
        proof_type TEXT,
        deadline TEXT,
        risk_level TEXT DEFAULT 'medium',
        FOREIGN KEY (law_id) REFERENCES legal_laws(id) ON DELETE CASCADE
    )");
    echo "Created legal_obligations table.\n";

    // 3. Law Updates Table (Source Watcher Results)
    $pdo->exec("CREATE TABLE IF NOT EXISTS law_updates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        law_id INTEGER NOT NULL,
        detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        source_url TEXT NOT NULL,
        change_type TEXT NOT NULL, -- amendment, new, repeal
        processed INTEGER DEFAULT 0,
        FOREIGN KEY (law_id) REFERENCES legal_laws(id) ON DELETE CASCADE
    )");
    echo "Created law_updates table.\n";

    // 4. Law Update Analysis Table (AI/Manual Summary)
    $pdo->exec("CREATE TABLE IF NOT EXISTS law_update_analysis (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        update_id INTEGER NOT NULL,
        summary TEXT NOT NULL,
        affected_sections TEXT,
        suggested_obligations TEXT,
        reviewed INTEGER DEFAULT 0,
        reviewed_by TEXT,
        FOREIGN KEY (update_id) REFERENCES law_updates(id) ON DELETE CASCADE
    )");
    echo "Created law_update_analysis table.\n";

    // 5. Law Sections Table (Full Text Storage)
    $pdo->exec("CREATE TABLE IF NOT EXISTS legal_law_sections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        law_id INTEGER NOT NULL,
        section_number TEXT, -- e.g. '§ 1'
        title TEXT,
        content TEXT,
        FOREIGN KEY (law_id) REFERENCES legal_laws(id) ON DELETE CASCADE
    )");
    echo "Created legal_law_sections table.\n";

    // Seed Data
    $check = $pdo->query("SELECT COUNT(*) FROM legal_laws");
    if ($check->fetchColumn() == 0) {
        echo "Seeding initial legal data...\n";
        
        // Layer C: Occupational Safety
        $stmt = $pdo->prepare("INSERT INTO legal_laws (abbreviation, full_name, description, layer, category_tag) VALUES (?, ?, ?, ?, ?)");
        
        $laws = [
            ['ArbSchG', 'Arbeitsschutzgesetz', 'Gesetz über die Durchführung von Maßnahmen des Arbeitsschutzes zur Verbesserung der Sicherheit und des Gesundheitsschutzes der Beschäftigten bei der Arbeit.', 'C', 'Arbeitsschutz'],
            ['BetrSichV', 'Betriebssicherheitsverordnung', 'Verordnung über Sicherheit und Gesundheitsschutz bei der Verwendung von Arbeitsmitteln.', 'C', 'Arbeitsschutz'],
            ['ArbStättV', 'Arbeitsstättenverordnung', 'Verordnung über Arbeitsstätten.', 'C', 'Arbeitsschutz'],
            ['ASR A2.2', 'Technische Regel für Arbeitsstätten - Maßnahmen gegen Brände', 'Konkretisiert die Anforderungen der Arbeitsstättenverordnung hinsichtlich des Brandschutzes.', 'B', 'Brandschutz'],
            ['DGUV V3', 'Elektrische Anlagen und Betriebsmittel', 'Unfallverhütungsvorschrift für elektrische Anlagen und Betriebsmittel.', 'C', 'Elektrosicherheit'],
        ];

        foreach ($laws as $law) {
            $stmt->execute($law);
        }
        
        // Seed some obligations for ArbSchG
        // We need to fetch the ID we just inserted. Since we inserted multiple, we search by abbreviation.
        $stmt = $pdo->prepare("SELECT id FROM legal_laws WHERE abbreviation = 'ArbSchG'");
        $stmt->execute();
        $arbSchGId = $stmt->fetchColumn();

        if ($arbSchGId) {
            $obs = [
                [$arbSchGId, '§3', 'Grundpflichten des Arbeitgebers', 'Employer must ensure safety of employees', 'Employment of staff', 'Conduct risk assessment and implement measures', 'Risk Assessment Document', 'Ongoing'],
                [$arbSchGId, '§5', 'Beurteilung der Arbeitsbedingungen', 'Assessment of working conditions', 'Employment of staff', 'Document risks associated with workplace', 'Gefährdungsbeurteilung', 'Before starting work'],
                [$arbSchGId, '§6', 'Dokumentation', 'Documentation of safety measures', '> 10 Employees (usually)', 'Keep records of risk assessments and accidents', 'Safety Logbook', 'Ongoing']
            ];
            $stmtObs = $pdo->prepare("INSERT INTO legal_obligations (law_id, section, title, description, trigger_condition, required_action, proof_type, deadline) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($obs as $ob) {
                $stmtObs->execute($ob);
            }
        }
        
        echo "Seeded initial data.\n";
    } else {
        echo "Data already exists. Skipping seed.\n";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
