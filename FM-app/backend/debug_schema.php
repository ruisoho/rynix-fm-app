<?php
$logFile = __DIR__ . '/debug_log.txt';
function logMsg($msg) {
    global $logFile;
    file_put_contents($logFile, $msg . "\n", FILE_APPEND);
}

logMsg("Starting debug_schema.php at " . date('Y-m-d H:i:s'));

$dbPath = __DIR__ . '/../database.sqlite';
logMsg("Connecting to DB at: $dbPath");

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logMsg("Connected.");
    
    // Check columns
    $stmt = $pdo->query("PRAGMA table_info(events)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    
    logMsg("Current columns: " . implode(', ', $columns));
    
    if (!in_array('visibility_type', $columns)) {
        logMsg("Adding visibility_type...");
        $pdo->exec("ALTER TABLE events ADD COLUMN visibility_type TEXT DEFAULT 'private'");
        logMsg("Added visibility_type.");
    } else {
        logMsg("visibility_type already exists.");
    }
    
    if (!in_array('visibility_target', $columns)) {
        logMsg("Adding visibility_target...");
        $pdo->exec("ALTER TABLE events ADD COLUMN visibility_target TEXT DEFAULT ''");
        logMsg("Added visibility_target.");
    } else {
        logMsg("visibility_target already exists.");
    }
    
    logMsg("Done.");
    
} catch (Exception $e) {
    logMsg("Error: " . $e->getMessage());
}
?>
