<?php
require_once 'config.php';

try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adding Performance Indexes...\n";

    // GBU Assessments
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_gbu_area ON gbu_assessments(area)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_gbu_status ON gbu_assessments(status)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_gbu_review_due ON gbu_assessments(review_due_date)");

    // Hazards
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_hazards_gbu_id ON gbu_hazards(gbu_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_hazards_risk ON gbu_hazards(risk_score)");

    // Controls
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_controls_hazard_id ON gbu_controls(hazard_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_controls_status ON gbu_controls(status)");

    // History & Docs
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_history_gbu_id ON gbu_history(gbu_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_documents_gbu_id ON gbu_documents(gbu_id)");
    
    // Training Requirements
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_train_req_gbu_id ON training_requirements(gbu_id)");

    echo "Indexes added successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
