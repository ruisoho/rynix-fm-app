<?php
require_once 'db.php';

echo "Updating schema...\n";

// Add visibility_type column
try {
    $pdo->exec("ALTER TABLE events ADD COLUMN visibility_type TEXT DEFAULT 'private'");
    echo "Added visibility_type column.\n";
} catch (PDOException $e) {
    echo "visibility_type column likely already exists or error: " . $e->getMessage() . "\n";
}

// Add visibility_target column
try {
    $pdo->exec("ALTER TABLE events ADD COLUMN visibility_target TEXT DEFAULT ''");
    echo "Added visibility_target column.\n";
} catch (PDOException $e) {
    echo "visibility_target column likely already exists or error: " . $e->getMessage() . "\n";
}

echo "Schema update complete.\n";
?>
