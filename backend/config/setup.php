<?php

require_once __DIR__ . '/database.php';

try {
    $pdo = Database::getInstance();
    echo "Connected to PostgreSQL database instance safely.\n";

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);
    echo "Database setup successfully finished! Schema tables loaded cleanly.\n";
} catch (PDOException $e) {
    echo "Database Setup Error: " . $e->getMessage() . "\n";
    exit(1);
}

// docker-compose exec backend php /var/www/html/backend/config/setup.php
