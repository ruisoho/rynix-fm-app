<?php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (php_sapi_name() === 'cli' && empty($action)) {
    $action = 'check_updates';
}


// --- CONFIGURATION ---
// Fetch sources dynamically from the database
$stmtSources = $pdo->query("SELECT id, abbreviation, link_online FROM legal_laws WHERE link_online IS NOT NULL AND link_online != ''");
$sources = [];
while ($row = $stmtSources->fetch(PDO::FETCH_ASSOC)) {
    $sources[] = [
        'id' => 'source_' . strtolower($row['abbreviation']),
        'law_abbreviation' => $row['abbreviation'],
        'url' => $row['link_online'],
        'type' => 'html_hash',
    ];
}

// Fallback if DB is empty (should not happen after import)
if (empty($sources)) {
    $sources = [
        [
            'id' => 'source_arbschg',
            'law_abbreviation' => 'ArbSchG',
            'url' => 'https://www.gesetze-im-internet.de/arbschg/BJNR124610996.html',
            'type' => 'html_hash',
        ]
    ];
}

// --- HELPER FUNCTIONS ---

function fetchUrlHead($url) {
    // Attempt to get headers (Last-Modified, ETag)
    // Suppress errors and set timeout
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $headers = @get_headers($url, 1, $context);
    return $headers;
}

function simulateAIAnalysis($updateId, $lawAbbr, $changeType) {
    global $pdo;
    
    // Deterministic "AI" response for demo
    if ($lawAbbr === 'ArbSchG') {
        $summary = "Amendment to § 3 (Basic Obligations). The update clarifies the employer's duty to consider psychological stress in risk assessments.";
        $affected = "§ 3, § 5";
        $suggested = "Update Risk Assessment (Psychological Stress)";
    } elseif ($lawAbbr === 'BetrSichV') {
        $summary = "New requirement in § 14 (Inspection). Elevator systems must now be inspected every 24 months by an Approved Inspection Body (ZÜS).";
        $affected = "§ 14, § 15";
        $suggested = "Schedule ZÜS Inspection for Elevators";
    } else {
        $summary = "Automated review of {$lawAbbr}: New amendment or technical correction detected. Please review specific changes in the official text.";
        $affected = "General Provisions / Annexes";
        $suggested = "Check relevance for current facility operations";
    }

    $stmt = $pdo->prepare("INSERT INTO law_update_analysis (update_id, summary, affected_sections, suggested_obligations) VALUES (?, ?, ?, ?)");
    $stmt->execute([$updateId, $summary, $affected, $suggested]);
}

// --- ACTIONS ---

if ($action === 'check_updates') {
    $results = [];
    $newUpdates = 0;

    foreach ($sources as $source) {
        // 1. Find Law ID
        $stmtLaw = $pdo->prepare("SELECT id FROM legal_laws WHERE abbreviation = ?");
        $stmtLaw->execute([$source['law_abbreviation']]);
        $lawId = $stmtLaw->fetchColumn();

        if (!$lawId) {
            $results[] = ['source' => $source['law_abbreviation'], 'status' => 'error', 'msg' => 'Law not found in DB'];
            continue;
        }

        // 2. "Check" (Simulated for reliability in this env, but logic is sound)
        // In reality: Fetch URL -> Hash Content -> Compare with stored hash
        // Here: We will Randomly "Detect" an update if none exists for today.
        
        // Check if we already have an update for this law today
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM law_updates WHERE law_id = ? AND date(detected_at) = date('now')");
        $stmtCheck->execute([$lawId]);
        $alreadyDetected = $stmtCheck->fetchColumn() > 0;

        if (!$alreadyDetected) {
            // SIMULATION: 20% chance to find an update for demo purposes
            $detected = (rand(1, 100) <= 20); 
            
            if ($detected) {
                $changeType = 'amendment';
                
                // Insert Update
                $stmtInsert = $pdo->prepare("INSERT INTO law_updates (law_id, source_url, change_type) VALUES (?, ?, ?)");
                $stmtInsert->execute([$lawId, $source['url'], $changeType]);
                $updateId = $pdo->lastInsertId();

                // Trigger AI Analysis
                simulateAIAnalysis($updateId, $source['law_abbreviation'], $changeType);

                $results[] = ['source' => $source['law_abbreviation'], 'status' => 'update_found', 'update_id' => $updateId];
                $newUpdates++;
            } else {
                $results[] = ['source' => $source['law_abbreviation'], 'status' => 'no_change'];
            }
        } else {
            $results[] = ['source' => $source['law_abbreviation'], 'status' => 'already_checked'];
        }
    }

    echo json_encode(['success' => true, 'new_updates' => $newUpdates, 'details' => $results]);
    exit;
}

if ($action === 'list_updates') {
    // Fetch pending updates
    $sql = "SELECT u.*, l.abbreviation, l.full_name, a.summary, a.affected_sections, a.suggested_obligations, a.id as analysis_id 
            FROM law_updates u 
            JOIN legal_laws l ON u.law_id = l.id 
            LEFT JOIN law_update_analysis a ON u.id = a.update_id 
            WHERE u.processed = 0 
            ORDER BY u.detected_at DESC";
    
    $stmt = $pdo->query($sql);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'updates' => $updates]);
    exit;
}

if ($action === 'process_update') {
    $updateId = $_POST['update_id'];
    $decision = $_POST['decision']; // 'approve' or 'dismiss'
    
    if ($decision === 'dismiss') {
        $stmt = $pdo->prepare("UPDATE law_updates SET processed = 1 WHERE id = ?");
        $stmt->execute([$updateId]);
        echo json_encode(['success' => true, 'msg' => 'Update dismissed']);
    } elseif ($decision === 'approve') {
        // Here we would actually update the law text or obligations
        // For now, we just mark as processed and maybe add a "todo" or log it.
        // In a full system, this would open an editor to merge changes.
        
        $stmt = $pdo->prepare("UPDATE law_updates SET processed = 1 WHERE id = ?");
        $stmt->execute([$updateId]);
        
        // Also update analysis status
        $stmtAnalysis = $pdo->prepare("UPDATE law_update_analysis SET reviewed = 1, reviewed_by = ? WHERE update_id = ?");
        $stmtAnalysis->execute([$_SESSION['username'] ?? 'Admin', $updateId]);

        echo json_encode(['success' => true, 'msg' => 'Update approved and merged (simulated)']);
    }
    exit;
}
?>