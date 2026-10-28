<?php
require_once __DIR__ . '/config.php';

// Make mysqli RETURN errors instead of throwing (PHP 8.1+ throws by default).
// This is what lets the pages echo raw SQL errors back to the attacker,
// enabling classic error-based SQL injection. Intentional (LAB).
mysqli_report(MYSQLI_REPORT_OFF);

// Intentionally verbose: connection errors are shown to the user (lab).
$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$conn) {
    die('<pre style="padding:2rem;font-family:monospace">DB connection failed: '
        . mysqli_connect_error() . '</pre>');
}
mysqli_set_charset($conn, 'utf8mb4');
