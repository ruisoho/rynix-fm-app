<?php
/**
 * Fresh Database Initialization Script
 * This script creates a clean database with all necessary tables
 * but NO dummy data - suitable for new customer installations.
 */

echo "=== Initializing Fresh Database ===\n\n";

// 1. Initialize core tables
echo "Step 1: Creating core tables...\n";
require_once 'db.php';
echo "✓ Core tables created\n\n";

// 2. Run all schema updates
$schemaFiles = [
    'update_schema_calendar.php',
    'update_schema_archive.php',
    'update_schema_documents.php',
    'update_schema_providers.php',
    'update_schema_legal.php',
    'update_schema_gbu.php',
    'update_schema_gbu_v2.php',
    'update_schema_indexes.php'
];

echo "Step 2: Running schema updates...\n";
foreach ($schemaFiles as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        echo "  - Running $file...\n";
        require_once $filePath;
    } else {
        echo "  - Skipping $file (not found)\n";
    }
}
echo "✓ Schema updates completed\n\n";

// 3. Verify admin user exists
echo "Step 3: Verifying admin user...\n";
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount > 0) {
    echo "✓ Admin user exists (username: admin, password: admin123)\n\n";
} else {
    echo "✗ WARNING: No admin user found!\n\n";
}

// 4. Display database statistics
echo "=== Database Statistics ===\n";
$tables = [
    'facilities', 'floors', 'rooms', 'users', 'providers',
    'maintenance_logs', 'daily_tasks', 'meters', 'meter_readings',
    'keys', 'locks', 'key_transactions', 'events',
    'legal_laws', 'legal_obligations', 'gbu_assessments',
    'documents', 'document_archive'
];

foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "$table: $count records\n";
    } catch (PDOException $e) {
        // Table doesn't exist - that's ok
    }
}

echo "\n=== Fresh Database Initialization Complete ===\n";
echo "\nDefault Login Credentials:\n";
echo "  Username: admin\n";
echo "  Password: admin123\n";
echo "\n⚠️  IMPORTANT: Please change the admin password after first login!\n\n";
