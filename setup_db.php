<?php
// setup_db.php
// Run this script from the browser or CLI to import database.sql into MySQL.

// Try to load .env if present
function load_env($path) {
    $vars = [];
    if (!file_exists($path)) return $vars;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2) + [1 => '']);
        if ($k !== '') $vars[$k] = $v;
    }
    return $vars;
}

$env = load_env(__DIR__ . '/.env');
$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbUser = $env['DB_USER'] ?? 'root';
$dbPass = $env['DB_PASS'] ?? '';
$sqlFile = __DIR__ . '/database.sql';

if (!file_exists($sqlFile)) {
    echo "database.sql not found at: $sqlFile";
    exit;
}

$mysqli = new mysqli($dbHost, $dbUser, $dbPass);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit;
}

$sql = file_get_contents($sqlFile);

if ($mysqli->multi_query($sql)) {
    do {
        if ($res = $mysqli->store_result()) {
            $res->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    echo "Database import completed successfully.";
} else {
    echo "Database import failed: " . $mysqli->error;
}

$mysqli->close();

?>
