<?php
// Programmatic Database Migration and Schema Initialization Script
require_once __DIR__ . '/database.php';

try {
    echo "<h2>Starting Camagru Database Provisioning Sequence...</h2>";

    // 1. Drop stale architecture layout maps in reverse dependency order
    $pdo->exec("DROP TABLE IF EXISTS likes CASCADE;");
    $pdo->exec("DROP TABLE IF EXISTS comments CASCADE;");
    $pdo->exec("DROP TABLE IF EXISTS snapshots CASCADE;");
    $pdo->exec("DROP TABLE IF EXISTS users CASCADE;");
    echo "✔ Cleaned out existing system database table records.<br>";

    // 2. Read and ingest your master structural SQL blueprint file
    $schemaPath = __DIR__ . '/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new Exception("Master structural mapping source 'schema.sql' is missing.");
    }
    
    $sqlSchemaCode = file_get_contents($schemaPath);

    // 3. Compile and execute the full schema layout block
    $pdo->exec($sqlSchemaCode);
    echo "✔ Successfully compiled and verified all core database tables architecture.<br>";

    echo "<h3 style='color: #4caf50;'>🎉 Deployment Completed! Your Camagru relational database tables are now active and ready.</h3>";

} catch (Exception $e) {
    // Graceful exception intercept management loop
    die("<h3 style='color:#f44336;'>Database Migration Aborted: " . htmlspecialchars($e->getMessage()) . "</h3>");
}
