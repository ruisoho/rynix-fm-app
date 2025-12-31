<?php
require_once 'db.php';

// Read JSON file
$jsonFile = __DIR__ . '/../uploads/germanlaws.json';
if (!file_exists($jsonFile)) {
    die("Error: JSON file not found at $jsonFile\n");
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (!$data) {
    die("Error: Failed to decode JSON.\n");
}

echo "Starting import of " . count($data['laws']) . " laws...\n";

// Helper to map category to Layer
function mapCategoryToLayer($category) {
    $map = [
        'workplace_safety' => 'C', // Occ Safety
        'workplace_regulations' => 'C', // Occ Safety
        'industrial_safety' => 'C', // Occ Safety
        'accident_prevention' => 'C', // Occ Safety
        'building_safety' => 'A', // Core Public Law
        'environmental' => 'A', // Core Public Law (or E)
        'fire_safety' => 'B', // Fire Safety
        'electrical' => 'C', // Occ Safety (or E)
        'technical_rules' => 'C', // Technical Rules
        'biological_agents' => 'C', // Biological Agents
        'hazardous_substances' => 'C', // Hazardous Substances
        'guidelines' => 'C', // Guidelines
    ];
    return $map[$category] ?? 'C'; // Default to C
}

// Helper to map category to Tag
function mapCategoryToTag($category) {
    $map = [
        'workplace_safety' => 'Arbeitsschutz',
        'workplace_regulations' => 'Arbeitsstätten',
        'industrial_safety' => 'Betriebssicherheit',
        'accident_prevention' => 'Unfallverhütung',
        'building_safety' => 'Gebäudesicherheit',
        'environmental' => 'Umweltschutz',
        'fire_safety' => 'Brandschutz',
        'electrical' => 'Elektrosicherheit',
        'technical_rules' => 'Technische Regeln',
        'biological_agents' => 'Biostoffe',
        'hazardous_substances' => 'Gefahrstoffe',
        'guidelines' => 'Leitlinien',
    ];
    return $map[$category] ?? ucfirst($category);
}

try {
    $pdo->beginTransaction();

    foreach ($data['laws'] as $law) {
        // 1. Insert Law
        $layer = mapCategoryToLayer($law['category']);
        $tag = mapCategoryToTag($law['category']);
        
        // Robust Abbreviation Logic
        $abbreviation = $law['abbreviation'] ?? $law['name'] ?? strtoupper($law['id']);
        
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM legal_laws WHERE abbreviation = ?");
        $stmt->execute([$abbreviation]);
        $existingId = $stmt->fetchColumn();

        // Prepare Description with Relevance to FM
        $lawDescription = $law['scope'] ?? $law['description'] ?? '';
        if (isset($law['relevance_to_fm']) && !empty($law['relevance_to_fm'])) {
            $lawDescription .= "\n\nFM-Relevanz: " . $law['relevance_to_fm'];
        }

        if ($existingId) {
            echo "Updating existing law: {$abbreviation}\n";
            $stmtUpdate = $pdo->prepare("UPDATE legal_laws SET full_name = ?, description = ?, layer = ?, category_tag = ?, link_online = ?, link_pdf = ? WHERE id = ?");
            $stmtUpdate->execute([
                $law['full_name'], 
                $lawDescription, 
                $layer, 
                $tag, 
                $law['official_source'] ?? '', 
                $law['pdf_download'] ?? '', 
                $existingId
            ]);
            $lawId = $existingId;
            
            // Clear existing obligations to avoid duplicates/stale data
            $pdo->prepare("DELETE FROM legal_obligations WHERE law_id = ?")->execute([$lawId]);
        } else {
            echo "Inserting new law: {$abbreviation}\n";
            $stmtInsert = $pdo->prepare("INSERT INTO legal_laws (abbreviation, full_name, description, layer, category_tag, link_online, link_pdf) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([
                $abbreviation, 
                $law['full_name'], 
                $lawDescription, 
                $layer, 
                $tag, 
                $law['official_source'] ?? '', 
                $law['pdf_download'] ?? ''
            ]);
            $lawId = $pdo->lastInsertId();
        }

        // 2. Insert Obligations (from key_provisions)
        if (isset($law['key_provisions']) && is_array($law['key_provisions'])) {
            foreach ($law['key_provisions'] as $key => $provision) {
                // Parse Section and Title
                // Format: "§ 3 - Grundpflichten des Arbeitgebers"
                $rawTitle = $provision['title'] ?? $key;
                $parts = explode(' - ', $rawTitle, 2);
                $section = $parts[0] ?? '';
                $title = $parts[1] ?? $rawTitle;

                // Description
                $description = $provision['summary'] ?? '';
                
                // Append URL to description
                if (isset($provision['url']) && !empty($provision['url'])) {
                    $description .= "\n\nQuelle: " . $provision['url'];
                }

                // Required Action (from key_points)
                $requiredAction = '';
                if (isset($provision['key_points']) && is_array($provision['key_points'])) {
                    $requiredAction = implode("\n- ", $provision['key_points']);
                    if ($requiredAction) $requiredAction = "- " . $requiredAction;
                } else {
                    $requiredAction = $description; // Fallback
                }

                // Defaults for missing fields
                $triggerCondition = "Gilt für alle Arbeitgeber/Betreiber";
                $proofType = "Dokumentation"; 
                $deadline = "Fortlaufend"; 
                $riskLevel = "medium";

                $stmtOb = $pdo->prepare("INSERT INTO legal_obligations (law_id, section, title, description, trigger_condition, required_action, proof_type, deadline, risk_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtOb->execute([
                    $lawId,
                    $section,
                    $title,
                    $description,
                    $triggerCondition,
                    $requiredAction,
                    $proofType,
                    $deadline,
                    $riskLevel
                ]);
            }
        }

        // 3. Insert Full Text Sections (if available)
        // Clear existing sections first
        $pdo->prepare("DELETE FROM legal_law_sections WHERE law_id = ?")->execute([$lawId]);

        if (isset($law['full_text']) && is_array($law['full_text'])) {
            $stmtSec = $pdo->prepare("INSERT INTO legal_law_sections (law_id, section_number, title, content) VALUES (?, ?, ?, ?)");
            foreach ($law['full_text'] as $sec) {
                $stmtSec->execute([
                    $lawId,
                    $sec['section'] ?? '',
                    $sec['title'] ?? '',
                    $sec['content'] ?? ''
                ]);
            }
        }
    }
    
    $pdo->commit();
    echo "Import completed successfully.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error during import: " . $e->getMessage() . "\n");
}
?>
