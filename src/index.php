<?php
// Central Application Router Gateway — Isolation Mode
session_start();

// 1. Mandatory Security Check: Enforce global HTTPS alignment
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// 2. Render Header Template Shell
require_once __DIR__ . '/views/templates/header.php';

// 3. Temporary Placeholder Context Block
// (This empty spacer guarantees your CSS layout wrappers stay open cleanly on mobile viewports)
echo '<div style="min-height: 50vh;"></div>';

// 4. Render Footer Template Shell
require_once __DIR__ . '/views/templates/footer.php';
?>
