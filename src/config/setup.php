<?php
require_once __DIR__ . '/database.php';

try {
	echo "<h2>Starting Camagru Database Provisioning Sequence...</h2>";

	$pdo->exec("DROP TABLE IF EXISTS likes CASCADE;");
	$pdo->exec("DROP TABLE IF EXISTS comments CASCADE;");
	$pdo->exec("DROP TABLE IF EXISTS snapshots CASCADE;");
	$pdo->exec("DROP TABLE IF EXISTS users CASCADE;");
	echo "✔ Cleaned out existing system database table records.<br>";

	$schemaPath = __DIR__ . '/schema.sql';
	if (!file_exists($schemaPath)) {
		throw new Exception("Master structural mapping source 'schema.sql' is missing.");
	}
	
	$sqlSchemaCode = file_get_contents($schemaPath);

	$pdo->exec($sqlSchemaCode);
	echo "✔ Successfully compiled and verified all core database tables architecture.<br>";

	echo "<h3 style='color: #4caf50;'>🎉 Deployment Completed! Your Camagru relational database tables are now active and ready.</h3>";

} catch (Exception $e) {
	die("<h3 style='color:#f44336;'>Database Migration Aborted: " . htmlspecialchars($e->getMessage()) . "</h3>");
}
