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
    function generate_uuid_t() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    // --- 1. LIST TEMPLATES ---
    if ($action === 'dashboard_stats') {
        $stats = [
            'due_30' => $pdo->query("SELECT COUNT(*) FROM training_compliance WHERE status != 'completed' AND due_date BETWEEN date('now') AND date('now', '+30 days')")->fetchColumn(),
            'due_60' => $pdo->query("SELECT COUNT(*) FROM training_compliance WHERE status != 'completed' AND due_date BETWEEN date('now', '+31 days') AND date('now', '+60 days')")->fetchColumn(),
            'due_90' => $pdo->query("SELECT COUNT(*) FROM training_compliance WHERE status != 'completed' AND due_date BETWEEN date('now', '+61 days') AND date('now', '+90 days')")->fetchColumn(),
            'overdue' => $pdo->query("SELECT COUNT(*) FROM training_compliance WHERE status != 'completed' AND due_date < date('now')")->fetchColumn()
        ];
        
        $sessions = $pdo->query("SELECT ts.*, tt.title as template_title, u.username as trainer_name 
                                 FROM training_sessions ts 
                                 JOIN training_templates tt ON ts.template_id = tt.id
                                 LEFT JOIN users u ON ts.trainer_user_id = u.id
                                 WHERE ts.scheduled_at >= date('now')
                                 ORDER BY ts.scheduled_at ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                                 
        echo json_encode(['stats' => $stats, 'sessions' => $sessions]);
        exit;
    }

    // --- 1. LIST TEMPLATES ---
    if ($action === 'list_templates') {
        $stmt = $pdo->query("SELECT * FROM training_templates ORDER BY title ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // --- 2. SAVE TEMPLATE ---
    if ($action === 'save_template') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = empty($data['id']) ? generate_uuid_t() : $data['id'];

        if (empty($data['id'])) {
            $stmt = $pdo->prepare("INSERT INTO training_templates (id, title, interval_days, content_outline, requires_quiz) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $data['title'],
                $data['interval_days'] ?? null,
                $data['content_outline'],
                $data['requires_quiz'] ?? 0
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE training_templates SET title=?, interval_days=?, content_outline=?, requires_quiz=? WHERE id=?");
            $stmt->execute([
                $data['title'],
                $data['interval_days'] ?? null,
                $data['content_outline'],
                $data['requires_quiz'] ?? 0,
                $id
            ]);
        }
        echo json_encode(['id' => $id, 'success' => true]);
        exit;
    }

    // --- 3. LIST SESSIONS ---
    if ($action === 'list_sessions') {
        $stmt = $pdo->query("SELECT ts.*, tt.title as template_title, u.username as trainer_name 
                             FROM training_sessions ts 
                             JOIN training_templates tt ON ts.template_id = tt.id
                             LEFT JOIN users u ON ts.trainer_user_id = u.id
                             ORDER BY scheduled_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // --- 4. CREATE SESSION ---
    if ($action === 'create_session') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = generate_uuid_t();

        $stmt = $pdo->prepare("INSERT INTO training_sessions (id, template_id, scheduled_at, trainer_user_id, location, status) VALUES (?, ?, ?, ?, ?, 'planned')");
        $stmt->execute([
            $id,
            $data['template_id'],
            $data['scheduled_at'],
            $_SESSION['user_id'], // Default to current user as trainer for now
            $data['location']
        ]);
        echo json_encode(['id' => $id, 'success' => true]);
        exit;
    }

    // --- 5. LOG ATTENDANCE ---
    if ($action === 'log_attendance') {
        $data = json_decode(file_get_contents('php://input'), true);
        // data: { session_id, person_id, attended, signed_at }
        
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO training_attendance (session_id, person_id, attended, signed_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['session_id'],
            $data['person_id'],
            $data['attended'] ? 1 : 0,
            $data['signed_at'] // e.g. '2023-10-27 10:00:00'
        ]);
        
        // Also update Compliance Status if attended
        if ($data['attended']) {
             // Find the template for this session
             $stmtTmpl = $pdo->prepare("SELECT template_id FROM training_sessions WHERE id = ?");
             $stmtTmpl->execute([$data['session_id']]);
             $tmplId = $stmtTmpl->fetchColumn();
             
             if ($tmplId) {
                 // Find relevant compliance record
                 // We look for a compliance record for this user and a requirement that uses this template
                 // This is a simplification. A user might have multiple requirements for the same template (rare).
                 $stmtComp = $pdo->prepare("
                    UPDATE training_compliance 
                    SET status = 'completed', completed_at = ?, session_id = ?
                    WHERE user_id = ? 
                    AND requirement_id IN (SELECT id FROM training_requirements WHERE template_id = ?)
                 ");
                 $stmtComp->execute([
                     $data['signed_at'],
                     $data['session_id'],
                     $data['person_id'],
                     $tmplId
                 ]);
             }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
    }
    
    // --- 6. GET MATRIX (Who needs what?) ---
    if ($action === 'matrix') {
        $stmt = $pdo->query("
            SELECT tr.*, tt.title, tt.interval_days, ga.area, ga.activity
            FROM training_requirements tr
            JOIN training_templates tt ON tr.template_id = tt.id
            JOIN gbu_assessments ga ON tr.gbu_id = ga.id
            WHERE tr.active = 1 AND ga.status = 'active'
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // --- 7. RULE 2: CALCULATE COMPLIANCE (Run periodically or on demand) ---
    if ($action === 'calc_compliance') {
        // 1. Get all active requirements
        $stmtReqs = $pdo->query("
            SELECT tr.*, tt.interval_days 
            FROM training_requirements tr
            JOIN training_templates tt ON tr.template_id = tt.id
            JOIN gbu_assessments ga ON tr.gbu_id = ga.id
            WHERE ga.status = 'active' 
            -- AND tr.trigger = 'regular' (We should process event triggers too, but usually they are one-off)
        ");
        $reqs = $stmtReqs->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($reqs as $req) {
            // 2. Identify target users
            // Simplification: Match user 'role' or 'department' to target_group.
            // If target_group is 'All', select all.
            // If target_group matches a role, select those.
            
            $users = [];
            if (strtolower($req['target_group']) === 'all') {
                $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
            } else {
                // Try to match role
                $stmtU = $pdo->prepare("SELECT id FROM users WHERE role = ? OR ? = ''"); 
                // Logic: if target_group matches role.
                // NOTE: This is a loose match. Real world needs better group management.
                $stmtU->execute([$req['target_group'], $req['target_group']]);
                $users = $stmtU->fetchAll(PDO::FETCH_COLUMN);
            }

            foreach ($users as $userId) {
                // 3. Check/Create Compliance Record
                $stmtCheck = $pdo->prepare("SELECT * FROM training_compliance WHERE user_id = ? AND requirement_id = ?");
                $stmtCheck->execute([$userId, $req['id']]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (!$existing) {
                    // Create new
                    $due = date('Y-m-d', strtotime('+30 days')); // Default grace period for new reqs
                    $cid = generate_uuid_t();
                    $stmtIns = $pdo->prepare("INSERT INTO training_compliance (id, user_id, requirement_id, status, due_date) VALUES (?, ?, ?, 'pending', ?)");
                    $stmtIns->execute([$cid, $userId, $req['id'], $due]);
                    $count++;
                } else {
                    // Update if recurring
                    if ($existing['status'] === 'completed' && $req['interval_days'] > 0) {
                        $last = strtotime($existing['completed_at']);
                        $next = strtotime("+{$req['interval_days']} days", $last);
                        if (time() > $next) {
                            // Overdue / New Cycle
                            // We don't overwrite the old record usually in strict audit systems, we create a new one.
                            // But for this simple schema, let's reset status to pending?
                            // OR better: Create a NEW record for the new cycle?
                            // User wants "compliance instance".
                            // Let's UPDATE for simplicity but strict compliance needs history.
                            // We will update and set status to 'pending' if it's time to renew.
                            
                            // Check if we are within renewal window (e.g. 30 days before)
                            if (time() > ($next - 30*24*3600)) {
                                $stmtUpd = $pdo->prepare("UPDATE training_compliance SET status = 'pending', due_date = ? WHERE id = ?");
                                $stmtUpd->execute([date('Y-m-d', $next), $existing['id']]);
                                $count++;
                            }
                        }
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'processed' => count($reqs), 'updated' => $count]);
        exit;
    }

    // --- 8. DASHBOARD STATS ---
    if ($action === 'dashboard_stats') {
        $userId = $_SESSION['user_id'];
        $isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'; // Basic check
        
        $stats = [
            'due_30' => 0,
            'due_60' => 0,
            'overdue' => 0
        ];
        
        // Base query
        $sql = "SELECT due_date, status FROM training_compliance WHERE status != 'completed'";
        $params = [];
        
        if (!$isAdmin) {
             // Users see only their own stats? Or maybe managers see all?
             // Let's show PERSONAL stats for now.
             $sql .= " AND user_id = ?";
             $params[] = $userId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $due = strtotime($row['due_date']);
            $now = time();
            $diff = $due - $now;
            $days = $diff / (60*60*24);

            if ($days < 0) $stats['overdue']++;
            elseif ($days <= 30) $stats['due_30']++;
            elseif ($days <= 60) $stats['due_60']++;
        }
        
        echo json_encode($stats);
        exit;
    }

    // --- 9. DUE TRAININGS LIST ---
    if ($action === 'due_trainings') {
        $userId = $_SESSION['user_id'];
        
        // Return list for the current user
        $sql = "SELECT tc.*, tt.title, ga.area 
                FROM training_compliance tc
                JOIN training_requirements tr ON tc.requirement_id = tr.id
                JOIN training_templates tt ON tr.template_id = tt.id
                JOIN gbu_assessments ga ON tr.gbu_id = ga.id
                WHERE tc.user_id = ? AND tc.status != 'completed'
                ORDER BY tc.due_date ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }


    // --- 10. COMPLIANCE OVERVIEW (Admin/Manager View) ---
    if ($action === 'compliance_overview') {
        $sql = "SELECT 
                    u.id, u.full_name, u.role,
                    COUNT(tc.id) as total_reqs,
                    SUM(CASE WHEN tc.status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN tc.status != 'completed' AND tc.due_date < date('now') THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN tc.status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM users u
                LEFT JOIN training_compliance tc ON u.id = tc.user_id
                GROUP BY u.id
                ORDER BY overdue DESC, pending DESC";
        
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
