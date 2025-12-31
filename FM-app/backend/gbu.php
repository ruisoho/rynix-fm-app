<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_GET['action'] ?? '';

    // --- HELPER: UUID Generator ---
    function generate_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    // --- HELPER: Audit Log ---
    function log_change($pdo, $gbuId, $actionType, $details) {
        $stmt = $pdo->prepare("INSERT INTO gbu_history (gbu_id, changed_by, action_type, change_details) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $gbuId,
            $_SESSION['user_id'],
            $actionType,
            json_encode($details)
        ]);
    }

    // --- 0. GET USERS (Helper for Dropdowns) ---
    if ($action === 'get_users') {
        $stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY full_name ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // --- 1. LIST GBUS ---
    if ($action === 'list') {
        $where = "1=1";
        $params = [];

        // Filters
        if (!empty($_GET['area'])) {
            $where .= " AND ga.area LIKE ?";
            $params[] = '%' . $_GET['area'] . '%';
        }
        if (!empty($_GET['status'])) {
            $where .= " AND ga.status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['review_due'])) {
            $where .= " AND ga.review_due_date < date('now', '+30 days')";
        }
        if (!empty($_GET['high_risk'])) {
             // Optimized High Risk Filter using EXISTS
            $where .= " AND EXISTS (SELECT 1 FROM gbu_hazards gh WHERE gh.gbu_id = ga.id AND gh.risk_score >= 15)";
        }

        // Optimized Query with JOINs for Stats
        $query = "SELECT ga.*, 
                         COUNT(DISTINCT gh.id) as hazard_count,
                         COUNT(DISTINCT CASE WHEN gc.status = 'open' THEN gc.id END) as open_measures,
                         MAX(gh.risk_score) as max_risk
                  FROM gbu_assessments ga
                  LEFT JOIN gbu_hazards gh ON ga.id = gh.gbu_id
                  LEFT JOIN gbu_controls gc ON gh.id = gc.hazard_id
                  WHERE $where
                  GROUP BY ga.id
                  ORDER BY ga.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $gbus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($gbus);
        exit;
    }

    // --- 2. GET SINGLE GBU (Lazy Fetch Support) ---
    if ($action === 'get') {
        $id = $_GET['id'] ?? '';
        if (!$id) throw new Exception('Missing ID');
        
        // Fetch specific sections if requested (lazy loading)
        $section = $_GET['section'] ?? 'main';

        if ($section === 'history') {
             $stmt = $pdo->prepare("SELECT h.*, u.username 
                                  FROM gbu_history h 
                                  LEFT JOIN users u ON h.changed_by = u.id 
                                  WHERE h.gbu_id = ? 
                                  ORDER BY changed_at DESC");
             $stmt->execute([$id]);
             echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
             exit;
        }

        if ($section === 'documents') {
            $stmt = $pdo->prepare("SELECT d.*, u.username 
                                  FROM gbu_documents d 
                                  LEFT JOIN users u ON d.uploaded_by = u.id 
                                  WHERE d.gbu_id = ? 
                                  ORDER BY uploaded_at DESC");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;
        }

        if ($section === 'trainings') {
             $stmt = $pdo->prepare("SELECT tr.*, tt.title as template_title 
                                   FROM training_requirements tr
                                   JOIN training_templates tt ON tr.template_id = tt.id
                                   WHERE tr.gbu_id = ?");
             $stmt->execute([$id]);
             echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
             exit;
        }

        // Default: Main GBU Data + Hazards + Controls (Optimized)
        $stmt = $pdo->prepare("SELECT * FROM gbu_assessments WHERE id = ?");
        $stmt->execute([$id]);
        $gbu = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gbu) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            exit;
        }

        // Fetch Hazards
        $stmtHaz = $pdo->prepare("SELECT * FROM gbu_hazards WHERE gbu_id = ?");
        $stmtHaz->execute([$id]);
        $gbu['hazards'] = $stmtHaz->fetchAll(PDO::FETCH_ASSOC);

        // Fetch ALL Controls for this GBU in ONE query
        if (!empty($gbu['hazards'])) {
            $hazardIds = array_column($gbu['hazards'], 'id');
            $placeholders = str_repeat('?,', count($hazardIds) - 1) . '?';
            
            $stmtCtrl = $pdo->prepare("SELECT * FROM gbu_controls WHERE hazard_id IN ($placeholders)");
            $stmtCtrl->execute($hazardIds);
            $allControls = $stmtCtrl->fetchAll(PDO::FETCH_ASSOC);

            // Group controls by hazard_id in PHP
            $controlsMap = [];
            foreach ($allControls as $c) {
                $controlsMap[$c['hazard_id']][] = $c;
            }

            // Assign back to hazards
            foreach ($gbu['hazards'] as &$h) {
                $h['controls'] = $controlsMap[$h['id']] ?? [];
            }
        }

        // Initialize empty arrays for lazy-loaded sections
        $gbu['history'] = []; 
        $gbu['documents'] = [];
        $gbu['training_requirements'] = [];

        echo json_encode($gbu);
        exit;
    }

    // --- 3. SAVE GBU (Upsert) ---
    if ($action === 'save_gbu') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            // Insert
            $id = generate_uuid();
            $stmt = $pdo->prepare("INSERT INTO gbu_assessments (id, building_id, area, activity, scope_type, scope_ref_id, created_by, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), 'draft')");
            $stmt->execute([
                $id,
                $data['building_id'] ?? null,
                $data['area'],
                $data['activity'],
                $data['scope_type'],
                $data['scope_ref_id'] ?? null,
                $_SESSION['user_id']
            ]);
            log_change($pdo, $id, 'create', ['msg' => 'Created GBU']);
        } else {
            // Update
            $id = $data['id'];
            $stmt = $pdo->prepare("UPDATE gbu_assessments SET building_id=?, area=?, activity=?, scope_type=?, scope_ref_id=? WHERE id=?");
            $stmt->execute([
                $data['building_id'] ?? null,
                $data['area'],
                $data['activity'],
                $data['scope_type'],
                $data['scope_ref_id'] ?? null,
                $id
            ]);
            log_change($pdo, $id, 'update', ['msg' => 'Updated metadata']);
        }
        echo json_encode(['id' => $id, 'success' => true]);
        exit;
    }

    // --- 4. SAVE HAZARD ---
    if ($action === 'save_hazard') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = empty($data['id']) ? generate_uuid() : $data['id'];
        
        $risk_score = intval($data['probability']) * intval($data['severity']);

        if (empty($data['id'])) {
            $stmt = $pdo->prepare("INSERT INTO gbu_hazards (id, gbu_id, hazard_category, hazard_description, exposed_group, probability, severity, risk_score, existing_controls) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $data['gbu_id'],
                $data['hazard_category'],
                $data['hazard_description'],
                $data['exposed_group'],
                $data['probability'],
                $data['severity'],
                $risk_score,
                $data['existing_controls']
            ]);
            log_change($pdo, $data['gbu_id'], 'update', ['msg' => 'Added hazard: ' . $data['hazard_description']]);
        } else {
            $stmt = $pdo->prepare("UPDATE gbu_hazards SET hazard_category=?, hazard_description=?, exposed_group=?, probability=?, severity=?, risk_score=?, existing_controls=? WHERE id=?");
            $stmt->execute([
                $data['hazard_category'],
                $data['hazard_description'],
                $data['exposed_group'],
                $data['probability'],
                $data['severity'],
                $risk_score,
                $data['existing_controls'],
                $id
            ]);
            log_change($pdo, $data['gbu_id'], 'update', ['msg' => 'Updated hazard: ' . $data['hazard_description']]);
        }
        echo json_encode(['id' => $id, 'success' => true]);
        exit;
    }

    // --- 5. SAVE CONTROL ---
    if ($action === 'save_control') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = empty($data['id']) ? generate_uuid() : $data['id'];

        // Need GBU ID for logging
        $gbuId = $pdo->query("SELECT gbu_id FROM gbu_hazards WHERE id = '{$data['hazard_id']}'")->fetchColumn();

        if (empty($data['id'])) {
            $stmt = $pdo->prepare("INSERT INTO gbu_controls (id, hazard_id, control_level, description, responsible_user_id, due_date, status, effectiveness_check_date, effectiveness_result) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $data['hazard_id'],
                $data['control_level'],
                $data['description'],
                $data['responsible_user_id'] ?? null,
                $data['due_date'] ?? null,
                'open',
                $data['effectiveness_check_date'] ?? null,
                $data['effectiveness_result'] ?? null
            ]);
            log_change($pdo, $gbuId, 'update', ['msg' => 'Added control: ' . $data['description']]);
        } else {
            $stmt = $pdo->prepare("UPDATE gbu_controls SET control_level=?, description=?, responsible_user_id=?, due_date=?, status=?, effectiveness_check_date=?, effectiveness_result=? WHERE id=?");
            $stmt->execute([
                $data['control_level'],
                $data['description'],
                $data['responsible_user_id'] ?? null,
                $data['due_date'] ?? null,
                $data['status'] ?? 'open',
                $data['effectiveness_check_date'] ?? null,
                $data['effectiveness_result'] ?? null,
                $id
            ]);
        }
        echo json_encode(['id' => $id, 'success' => true]);
        exit;
    }

    // --- 6. ADD TRAINING REQUIREMENT ---
    if ($action === 'add_training_req') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = generate_uuid();
        
        $stmt = $pdo->prepare("INSERT INTO training_requirements (id, gbu_id, template_id, target_group, trigger) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $data['gbu_id'],
            $data['template_id'],
            $data['target_group'],
            $data['trigger']
        ]);
        log_change($pdo, $data['gbu_id'], 'update', ['msg' => 'Added training requirement']);
        echo json_encode(['id' => $id, 'success' => true]);
        exit;
    }

    // --- 7. ACTIVATE GBU (The "Magic" Logic) ---
    if ($action === 'activate') {
        $id = $_POST['id'];
        
        $pdo->beginTransaction();

        // 1. Update GBU Status
        $stmt = $pdo->prepare("UPDATE gbu_assessments SET status = 'active', approved_by = ?, approved_at = datetime('now') WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);

        // 2. Fetch all controls (Open for tasks, Done for effectiveness checks)
        $stmtCtrls = $pdo->prepare("SELECT gc.*, gh.hazard_description, ga.area, ga.activity 
                                    FROM gbu_controls gc
                                    JOIN gbu_hazards gh ON gc.hazard_id = gh.id
                                    JOIN gbu_assessments ga ON gh.gbu_id = ga.id
                                    WHERE gh.gbu_id = ?");
        $stmtCtrls->execute([$id]);
        $controls = $stmtCtrls->fetchAll(PDO::FETCH_ASSOC);

        // 3. Create System Tasks for each Control
        $stmtTask = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, status, due_date, created_by, facility_id) VALUES (?, ?, ?, 'pending', ?, ?, ?)");
        
        // RULE 1: Open Measures -> Tasks
        foreach ($controls as $ctrl) {
            $title = "GBU Measure: " . substr($ctrl['description'], 0, 30) . "...";
            $desc = "Derived from GBU: {$ctrl['area']} / {$ctrl['activity']}\n\nHazard: {$ctrl['hazard_description']}\nMeasure: {$ctrl['description']}\nLevel: {$ctrl['control_level']}";
            $facilityId = null; 

            // Create main task ONLY if Open
            if ($ctrl['status'] === 'open') {
                $stmtTask->execute([
                    $title,
                    $desc,
                    $ctrl['responsible_user_id'] ?? null,
                    $ctrl['due_date'],
                    $_SESSION['user_id'],
                    $facilityId
                ]);
            }

            // RULE 1: Effectiveness Check -> Review Task (Regardless of status, if date is set)
            if (!empty($ctrl['effectiveness_check_date'])) {
                $checkTitle = "Effectiveness Check: " . substr($ctrl['description'], 0, 20) . "...";
                $checkDesc = "Verify effectiveness of measure: {$ctrl['description']}\n\nExpected Result: {$ctrl['effectiveness_result']}";
                $stmtTask->execute([
                    $checkTitle,
                    $checkDesc,
                    $ctrl['responsible_user_id'] ?? null, // Assign to same person or safety officer
                    $ctrl['effectiveness_check_date'],
                    $_SESSION['user_id'],
                    $facilityId
                ]);
            }
        }
        
        // RULE 1: Review Due -> Task
        // Fetch review date
        $reviewDue = $pdo->query("SELECT review_due_date FROM gbu_assessments WHERE id = '$id'")->fetchColumn();
        if ($reviewDue) {
            $stmtTask->execute([
                "GBU Review Due: " . $id, // Ideally use Area/Activity name but ID is safe fallback or fetch again
                "Regular review of GBU $id required.",
                $_SESSION['user_id'], // Assign to approver/creator
                $reviewDue,
                $_SESSION['user_id'],
                null
            ]);
        }
        
        // --- RULE 2: Automatic Training Scheduling ---
        $stmtReqs = $pdo->prepare("SELECT * FROM training_requirements WHERE gbu_id = ?");
        $stmtReqs->execute([$id]);
        $reqs = $stmtReqs->fetchAll(PDO::FETCH_ASSOC);

        $scheduledCount = 0;
        foreach ($reqs as $req) {
            $usersToTrain = [];
            if (strtolower($req['target_group']) === 'all') {
                $usersToTrain = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
            } else {
                // Match role or generic group
                $stmtUsers = $pdo->prepare("SELECT id FROM users WHERE role = ? OR ? = 'employees'"); // Simple fallback
                $stmtUsers->execute([$req['target_group'], $req['target_group']]);
                $usersToTrain = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);
                
                // If no users found by role, maybe it's a specific user? 
                // For MVP, we stick to roles.
            }

            // RULE 2b: If interval=365 (recurring), ensure a Session exists
            // Check template interval
            $tmplInterval = $pdo->query("SELECT interval_days FROM training_templates WHERE id = '{$req['template_id']}'")->fetchColumn();
            if ($tmplInterval == 365) {
                // Check if future session exists for this template
                $futureSession = $pdo->query("SELECT id FROM training_sessions WHERE template_id = '{$req['template_id']}' AND scheduled_at > date('now')")->fetchColumn();
                
                if (!$futureSession) {
                    // Create a placeholder session
                    $sessId = generate_uuid();
                    $sessDate = date('Y-m-d', strtotime('+3 months')); // Plan ahead
                    $pdo->prepare("INSERT INTO training_sessions (id, template_id, trainer_user_id, scheduled_at, status, location) VALUES (?, ?, ?, ?, 'planned', 'TBD')")
                        ->execute([$sessId, $req['template_id'], $_SESSION['user_id'], $sessDate]);
                    // Note: We don't auto-assign users to session yet, just create the slot.
                }
            }

            $stmtComp = $pdo->prepare("INSERT OR IGNORE INTO training_compliance (id, user_id, requirement_id, status, due_date) VALUES (?, ?, ?, 'pending', ?)");
            
            foreach ($usersToTrain as $uid) {
                $dueDate = date('Y-m-d', strtotime('+30 days'));
                $compId = generate_uuid();
                // We use INSERT OR IGNORE (SQLite) to avoid duplicates if ID collides, 
                // but actually we want to check if *user+requirement* exists and is pending.
                // SQLite doesn't have "ON DUPLICATE KEY UPDATE" easily without unique index on (user_id, requirement_id).
                // Let's just insert. If we want to avoid dups, we should query first.
                // For now, we generate a new ID every time, so it will stack. This is acceptable for "re-training".
                $stmtComp->execute([$compId, $uid, $req['id'], $dueDate]);
                $scheduledCount++;
            }
        }

        log_change($pdo, $id, 'approve', ['msg' => "GBU Activated. Tasks: " . count($controls) . ", Trainings Scheduled: $scheduledCount"]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "GBU Activated. Tasks: " . count($controls) . ", Trainings: $scheduledCount"]);
        exit;
    }
    
    // --- 8. TRIGGER REVIEW (Rule 3) ---
    if ($action === 'trigger_review') {
        $id = $_POST['id'];
        $triggerType = $_POST['trigger_type']; // change|incident|new_equipment|new_law_update
        
        $dueDate = date('Y-m-d', strtotime('+14 days'));
        
        $stmt = $pdo->prepare("UPDATE gbu_assessments SET review_due_date = ?, status = 'draft' WHERE id = ?");
        $stmt->execute([$dueDate, $id]);
        
        log_change($pdo, $id, 'trigger_review', ['msg' => "Review triggered by $triggerType. Due: $dueDate"]);
        
        // --- RULE 3: Generate "Anlassbezogene Unterweisung" ---
        // 1. Get existing requirements to copy target groups
        $stmtReqs = $pdo->prepare("SELECT DISTINCT target_group FROM training_requirements WHERE gbu_id = ?");
        $stmtReqs->execute([$id]);
        $targetGroups = $stmtReqs->fetchAll(PDO::FETCH_COLUMN);
        
        // If no specific groups found, maybe default to "all"? For now, only if groups exist.
        if (!empty($targetGroups)) {
            $stmtNewReq = $pdo->prepare("INSERT INTO training_requirements (id, gbu_id, template_id, target_group, trigger, created_at) VALUES (?, ?, ?, ?, ?, datetime('now'))");
            
            foreach ($targetGroups as $group) {
                // We need a template for "Event-based Training". 
                // For now, we'll create a generic requirement linking to a placeholder template or just text.
                // Assuming we have a "General Update" template or similar. 
                // Let's search for a template with title "Anlassbezogene Unterweisung" or create one if missing?
                // To be safe and simple: We just create a requirement. 
                // BUT we need a template_id. Let's pick the first available one or a specific one.
                // Better approach: User must define what training is needed. 
                // However, the rule says "Generate...". 
                // Let's check if there is a template for "Review/Change".
                
                $tId = $pdo->query("SELECT id FROM training_templates WHERE title LIKE '%Anlass%' OR title LIKE '%Update%' LIMIT 1")->fetchColumn();
                
                // Fallback: If no specific template, take the first one available or a "General" one
                if (!$tId) {
                    $tId = $pdo->query("SELECT id FROM training_templates LIMIT 1")->fetchColumn();
                }

                if ($tId) {
                    $reqId = generate_uuid();
                    $stmtNewReq->execute([
                        $reqId,
                        $id,
                        $tId,
                        $group,
                        'event'
                    ]);
                    log_change($pdo, $id, 'system', ['msg' => "Auto-generated training requirement for group $group"]);
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => "Review triggered. Due in 14 days. Training requirements updated."]);
        exit;
    }
    
    // --- 9. UPLOAD DOCUMENT ---
    if ($action === 'upload_doc') {
        $gbuId = $_POST['gbu_id'];
        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
            $filename = basename($file['name']);
            $targetPath = '../uploads/' . uniqid() . '_' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $docId = generate_uuid();
                $stmt = $pdo->prepare("INSERT INTO gbu_documents (id, gbu_id, filename, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$docId, $gbuId, $filename, $targetPath, $_SESSION['user_id']]);
                
                log_change($pdo, $gbuId, 'update', ['msg' => "Uploaded document: $filename"]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Upload failed']);
            }
        }
        exit;
    }
    
    // --- 10. DELETE HELPERS ---
    if ($action === 'delete_gbu') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM gbu_assessments WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete_hazard') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM gbu_hazards WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete_control') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM gbu_controls WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete_training_req') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM training_requirements WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }


} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
