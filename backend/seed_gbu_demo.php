<?php
require_once 'config.php';

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Helper ID generator
    function gen_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    echo "Seeding Demo Data...\n";

    // --- EXAMPLE A: GBU „Elektro-Werkstatt / Instandhaltung“ ---
    $gbuId = gen_uuid();
    $check = $pdo->query("SELECT id FROM gbu_assessments WHERE area = 'Elektro-Werkstatt' AND activity = 'Arbeiten an elektrischen Anlagen'")->fetchColumn();
    
    if (!$check) {
        echo "Creating Example A: Elektro-Werkstatt...\n";
        
        // 1. GBU Header
        $stmt = $pdo->prepare("INSERT INTO gbu_assessments (id, area, activity, scope_type, status, created_by, created_at, review_due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $gbuId,
            'Elektro-Werkstatt',
            'Arbeiten an elektrischen Anlagen',
            'activity',
            'active',
            'system_seeder',
            date('Y-m-d H:i:s'),
            date('Y-m-d', strtotime('+1 year'))
        ]);

        // 2. Hazards
        $hazards = [
            [
                'cat' => 'electrical',
                'desc' => 'Elektrischer Schlag, Lichtbogen',
                'exposed' => 'Techniker / Hausmeister',
                'prob' => 3, 'sev' => 5 // Risk: 15 (High)
            ],
            [
                'cat' => 'fall',
                'desc' => 'Stolpern/Absturz (Leitern)',
                'exposed' => 'Techniker',
                'prob' => 3, 'sev' => 3 // Risk: 9
            ],
            [
                'cat' => 'mechanical',
                'desc' => 'Schnitt/Quetsch',
                'exposed' => 'Techniker',
                'prob' => 2, 'sev' => 3 // Risk: 6
            ],
            [
                'cat' => 'chemical',
                'desc' => 'Gefahrstoffe (Reinigungschemie, Batterien)',
                'exposed' => 'Techniker',
                'prob' => 2, 'sev' => 4 // Risk: 8
            ]
        ];

        foreach ($hazards as $h) {
            $hId = gen_uuid();
            $pdo->prepare("INSERT INTO gbu_hazards (id, gbu_id, hazard_category, hazard_description, exposed_group, probability, severity, risk_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$hId, $gbuId, $h['cat'], $h['desc'], $h['exposed'], $h['prob'], $h['sev'], $h['prob']*$h['sev']]);

            // 3. Measures (Example for Electrical)
            if ($h['cat'] === 'electrical') {
                $controls = [
                    ['level' => 'technical', 'desc' => 'FI/LS, Spannungsfreiheit prüfen, Absperrung, geeignete Messgeräte'],
                    ['level' => 'organizational', 'desc' => 'Freigabeverfahren, „Arbeiten nur durch Elektrofachkraft“, Lockout/Tagout'],
                    ['level' => 'personal', 'desc' => 'PSA (Handschuhe, Schutzbrille), Unterweisung, Qualifikation']
                ];
                foreach ($controls as $c) {
                    $cId = gen_uuid();
                    $pdo->prepare("INSERT INTO gbu_controls (id, hazard_id, control_level, description, status, due_date) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$cId, $hId, $c['level'], $c['desc'], 'done', date('Y-m-d')]);
                }
            }
        }

        // 4. Training Requirements
        // Create Template if not exists
        $tmplId = gen_uuid();
        $pdo->prepare("INSERT OR IGNORE INTO training_templates (id, title, interval_days, content_outline) VALUES (?, ?, ?, ?)")
            ->execute([$tmplId, 'Unterweisung Elektroarbeiten', 365, 'Sicherheitsregeln, PSA, Erste Hilfe bei Stromunfällen']);
        
        // Use existing ID if ignored
        $checkTmpl = $pdo->query("SELECT id FROM training_templates WHERE title = 'Unterweisung Elektroarbeiten'")->fetchColumn();
        $tmplId = $checkTmpl ?: $tmplId;

        // Add Requirement
        $pdo->prepare("INSERT INTO training_requirements (id, gbu_id, template_id, target_group, trigger) VALUES (?, ?, ?, ?, ?)")
            ->execute([gen_uuid(), $gbuId, $tmplId, 'Techniker', 'annual']);

    } else {
        echo "Example A already exists.\n";
    }

    // --- EXAMPLE B: GBU „Fluchtwege / Brandschutzorganisation“ ---
    $gbuIdB = gen_uuid();
    $checkB = $pdo->query("SELECT id FROM gbu_assessments WHERE area = 'Fluchtwege' AND activity = 'Brandschutzorganisation'")->fetchColumn();

    if (!$checkB) {
        echo "Creating Example B: Fluchtwege...\n";
        
        $stmt = $pdo->prepare("INSERT INTO gbu_assessments (id, area, activity, scope_type, status, created_by, created_at, review_due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $gbuIdB,
            'Fluchtwege',
            'Brandschutzorganisation',
            'area',
            'active',
            'system_seeder',
            date('Y-m-d H:i:s'),
            date('Y-m-d', strtotime('+1 year'))
        ]);

        // Hazards
        $hazardsB = [
            [
                'cat' => 'fire',
                'desc' => 'Blockierte Fluchtwege, Fehlende Kennzeichnung',
                'exposed' => 'Alle Mitarbeiter',
                'prob' => 3, 'sev' => 5 // Risk: 15
            ],
            [
                'cat' => 'organizational',
                'desc' => 'Unzureichende Unterweisung im Brandfall',
                'exposed' => 'Alle Mitarbeiter',
                'prob' => 4, 'sev' => 4 // Risk: 16
            ]
        ];

        foreach ($hazardsB as $h) {
            $hId = gen_uuid();
            $pdo->prepare("INSERT INTO gbu_hazards (id, gbu_id, hazard_category, hazard_description, exposed_group, probability, severity, risk_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$hId, $gbuIdB, $h['cat'], $h['desc'], $h['exposed'], $h['prob'], $h['sev'], $h['prob']*$h['sev']]);

            // Measures
            $controlsB = [];
            if ($h['cat'] === 'fire') {
                $controlsB[] = ['level' => 'organizational', 'desc' => 'Regelmäßige Begehung/Checkliste'];
                $controlsB[] = ['level' => 'technical', 'desc' => 'Kennzeichnung nach ASR A1.3'];
            }
            if ($h['cat'] === 'organizational') {
                $controlsB[] = ['level' => 'organizational', 'desc' => 'Unterweisung Brandfall/Erste Hilfe, Sammelplatz, Alarmierung'];
            }

            foreach ($controlsB as $c) {
                $cId = gen_uuid();
                $pdo->prepare("INSERT INTO gbu_controls (id, hazard_id, control_level, description, status, due_date) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$cId, $hId, $c['level'], $c['desc'], 'open', date('Y-m-d', strtotime('+1 month'))]);
            }
        }
        
        // Training Template for Fire Safety
        $tmplIdFire = gen_uuid();
        $pdo->prepare("INSERT OR IGNORE INTO training_templates (id, title, interval_days, content_outline) VALUES (?, ?, ?, ?)")
            ->execute([$tmplIdFire, 'Brandschutzunterweisung', 365, 'Verhalten im Brandfall, Fluchtwege, Feuerlöscher']);
        
        $checkTmplFire = $pdo->query("SELECT id FROM training_templates WHERE title = 'Brandschutzunterweisung'")->fetchColumn();
        $tmplIdFire = $checkTmplFire ?: $tmplIdFire;

        $pdo->prepare("INSERT INTO training_requirements (id, gbu_id, template_id, target_group, trigger) VALUES (?, ?, ?, ?, ?)")
            ->execute([gen_uuid(), $gbuIdB, $tmplIdFire, 'All Employees', 'annual']);

    } else {
        echo "Example B already exists.\n";
    }

    echo "Demo data seeding complete.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
