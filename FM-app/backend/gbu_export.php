<?php
require_once 'config.php';
require_once 'auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

$gbuId = $_GET['id'] ?? '';
if (empty($gbuId)) {
    die("Invalid GBU ID");
}

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Fetch Main GBU Details
    $stmt = $pdo->prepare("SELECT * FROM gbu_assessments WHERE id = ?");
    $stmt->execute([$gbuId]);
    $gbu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gbu) {
        die("GBU not found");
    }

    // 2. Fetch Hazards & Controls
    // We fetch all hazards, then for each hazard we fetch controls.
    // Optimally we could do one join, but nested loop in PHP is fine for PDF export of one entity.
    $stmtHazards = $pdo->prepare("SELECT * FROM gbu_hazards WHERE gbu_id = ? ORDER BY id ASC");
    $stmtHazards->execute([$gbuId]);
    $hazards = $stmtHazards->fetchAll(PDO::FETCH_ASSOC);

    foreach ($hazards as &$hazard) {
        $stmtControls = $pdo->prepare("SELECT * FROM gbu_controls WHERE hazard_id = ? ORDER BY id ASC");
        $stmtControls->execute([$hazard['id']]);
        $hazard['controls'] = $stmtControls->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($hazard); // break reference

    // 3. Fetch History (for Approvals)
    $stmtHistory = $pdo->prepare("SELECT h.*, u.full_name, u.username 
                                 FROM gbu_history h 
                                 LEFT JOIN users u ON h.changed_by = u.id 
                                 WHERE h.gbu_id = ? 
                                 ORDER BY h.changed_at DESC");
    $stmtHistory->execute([$gbuId]);
    $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    // Find latest approval
    $approvedBy = "N/A";
    $approvedAt = "N/A";
    foreach ($history as $log) {
        if ($log['action_type'] === 'approve' || $log['action_type'] === 'status_change' && strpos($log['change_details'], 'active') !== false) {
            $approvedBy = $log['full_name'] ?: $log['username'];
            $approvedAt = date('d.m.Y H:i', strtotime($log['changed_at']));
            break;
        }
    }

    // 4. Fetch Documents
    $stmtDocs = $pdo->prepare("SELECT * FROM gbu_documents WHERE gbu_id = ? ORDER BY uploaded_at DESC");
    $stmtDocs->execute([$gbuId]);
    $documents = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// --- HTML OUTPUT ---
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>GBU Export - <?php echo htmlspecialchars($gbu['activity']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #333;
            max-width: 210mm; /* A4 width */
            margin: 0 auto;
            padding: 20px;
        }
        @media print {
            body { margin: 0; padding: 10px; max-width: none; }
            .no-print { display: none; }
            a { text-decoration: none; color: #000; }
            .page-break { page-break-before: always; }
        }
        h1 { font-size: 18pt; margin-bottom: 5px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        h2 { font-size: 14pt; margin-top: 20px; margin-bottom: 10px; background: #eee; padding: 5px; border-left: 5px solid #666; }
        h3 { font-size: 12pt; margin-top: 15px; margin-bottom: 5px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; page-break-inside: avoid; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f9f9f9; font-weight: bold; }
        
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .meta-item { border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .meta-label { font-weight: bold; display: block; font-size: 0.9em; color: #666; }
        
        .risk-high { background-color: #ffebeb; color: #c00; font-weight: bold; }
        .risk-med { background-color: #fff8e1; color: #b70; }
        .risk-low { background-color: #f0fff4; color: #080; }

        .footer { margin-top: 50px; font-size: 0.8em; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right;">
        <button onclick="window.print()" class="btn">🖨️ Als PDF speichern / Drucken</button>
    </div>

    <h1>Gefährdungsbeurteilung (GBU)</h1>
    <p style="margin-top:0; color:#666;">gemäß § 5 ArbSchG / § 3 BetrSichV</p>

    <div class="meta-grid">
        <div class="meta-item">
            <span class="meta-label">Bereich / Gebäude</span>
            <?php echo htmlspecialchars($gbu['area']); ?>
        </div>
        <div class="meta-item">
            <span class="meta-label">Tätigkeit / Arbeitsmittel</span>
            <?php echo htmlspecialchars($gbu['activity']); ?>
        </div>
        <div class="meta-item">
            <span class="meta-label">Status</span>
            <?php echo strtoupper(htmlspecialchars($gbu['status'])); ?>
        </div>
        <div class="meta-item">
            <span class="meta-label">Erstellt am</span>
            <?php echo date('d.m.Y', strtotime($gbu['created_at'])); ?>
        </div>
        <div class="meta-item">
            <span class="meta-label">Letzte Freigabe durch</span>
            <?php echo htmlspecialchars($approvedBy); ?>
        </div>
        <div class="meta-item">
            <span class="meta-label">Freigabedatum</span>
            <?php echo $approvedAt; ?>
        </div>
        <div class="meta-item">
            <span class="meta-label">Nächste Überprüfung</span>
            <?php echo !empty($gbu['review_due_date']) ? date('d.m.Y', strtotime($gbu['review_due_date'])) : '-'; ?>
        </div>
        <div class="meta-item">
            <span class="meta-label">Dokumenten-ID</span>
            <?php echo htmlspecialchars($gbu['id']); ?>
        </div>
    </div>

    <h2>1. Gefährdungen & Risiken</h2>
    
    <?php if (empty($hazards)): ?>
        <p>Keine Gefährdungen dokumentiert.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 25%">Gefährdungsart</th>
                    <th style="width: 40%">Beschreibung</th>
                    <th style="width: 15%">Risiko (Vorher)</th>
                    <th style="width: 15%">Risiko (Nachher)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hazards as $idx => $h): 
                    $riskClass = $h['risk_score'] >= 15 ? 'risk-high' : ($h['risk_score'] >= 8 ? 'risk-med' : 'risk-low');
                ?>
                <tr>
                    <td><?php echo $idx + 1; ?></td>
                    <td><?php echo htmlspecialchars($h['hazard_type']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($h['description'])); ?></td>
                    <td class="<?php echo $riskClass; ?>">
                        Score: <?php echo $h['risk_score']; ?>
                    </td>
                    <td>
                        <!-- Residual risk calculation would go here if stored, assuming simplified for now -->
                        -
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>2. Maßnahmen (TOP-Prinzip)</h2>
    
    <?php 
    $hasControls = false;
    foreach ($hazards as $h) {
        if (!empty($h['controls'])) {
            $hasControls = true;
            break;
        }
    }
    ?>

    <?php if (!$hasControls): ?>
        <p>Keine Maßnahmen dokumentiert.</p>
    <?php else: ?>
        <?php foreach ($hazards as $idx => $h): ?>
            <?php if (!empty($h['controls'])): ?>
                <h3>Zu Gefährdung #<?php echo $idx + 1; ?>: <?php echo htmlspecialchars($h['hazard_type']); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 10%">Typ</th>
                            <th style="width: 40%">Maßnahme</th>
                            <th style="width: 15%">Verantwortlich</th>
                            <th style="width: 15%">Fälligkeit</th>
                            <th style="width: 10%">Status</th>
                            <th style="width: 10%">Wirksamkeit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($h['controls'] as $c): ?>
                        <tr>
                            <td><b><?php echo strtoupper(htmlspecialchars($c['type'])); ?></b></td>
                            <td><?php echo nl2br(htmlspecialchars($c['description'])); ?></td>
                            <td><?php echo htmlspecialchars($c['responsible_user_id'] ?: 'Nicht zugewiesen'); ?></td> <!-- Should resolve user name if time permits -->
                            <td><?php echo !empty($c['due_date']) ? date('d.m.Y', strtotime($c['due_date'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($c['status']); ?></td>
                            <td><?php echo !empty($c['effectiveness_check_date']) ? 'Prüfung: '.date('d.m.Y', strtotime($c['effectiveness_check_date'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($documents)): ?>
        <h3>Referenzierte Dokumente / Nachweise</h3>
        <ul>
            <?php foreach ($documents as $d): ?>
                <li>
                    <b><?php echo htmlspecialchars($d['filename']); ?></b> 
                    (<?php echo strtoupper($d['doc_type']); ?>) - 
                    <small>Hochgeladen am <?php echo date('d.m.Y', strtotime($d['uploaded_at'])); ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="page-break"></div>

    <h2>3. Freigabe & Bestätigung</h2>
    <p>Hiermit wird bestätigt, dass die Gefährdungsbeurteilung vollständig und korrekt durchgeführt wurde. Die festgelegten Maßnahmen sind geeignet, um die Sicherheit und Gesundheit der Beschäftigten zu gewährleisten.</p>

    <div style="margin-top: 50px; display: flex; justify-content: space-between;">
        <div style="width: 45%; border-top: 1px solid #000; padding-top: 10px;">
            <p>Ort, Datum</p>
        </div>
        <div style="width: 45%; border-top: 1px solid #000; padding-top: 10px;">
            <p>Unterschrift Verantwortlicher (<?php echo htmlspecialchars($approvedBy); ?>)</p>
        </div>
    </div>

    <div class="footer">
        Generiert durch FM-App am <?php echo date('d.m.Y H:i'); ?> | Seite <span class="page-number"></span>
    </div>

    <script>
        // Optional: Auto-print
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
