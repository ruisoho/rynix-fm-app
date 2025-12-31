<?php
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE providers ADD COLUMN customer_number VARCHAR(50)");
    echo "Added customer_number\n";
} catch (PDOException $e) {
    echo "customer_number error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE providers ADD COLUMN hourly_rate DECIMAL(10, 2)");
    echo "Added hourly_rate\n";
} catch (PDOException $e) {
    echo "hourly_rate error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE providers ADD COLUMN status VARCHAR(20) DEFAULT 'Active'");
    echo "Added status\n";
} catch (PDOException $e) {
    echo "status error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE providers ADD COLUMN specialization TEXT");
    echo "Added specialization\n";
} catch (PDOException $e) {
    echo "specialization error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE providers ADD COLUMN notes TEXT");
    echo "Added notes\n";
} catch (PDOException $e) {
    echo "notes error: " . $e->getMessage() . "\n";
}

echo "Schema update completed.\n";
?>