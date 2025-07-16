<?php
// config.php
date_default_timezone_set('America/Chicago');

// Pull in from Docker environment, falling back to sensible defaults:
$host     = getenv('DB_HOST') ?: 'db';                 // service name in compose
$user     = getenv('DB_USER') ?: 'timeclockuser';      // as set in your compose
$password = getenv('DB_PASS') ?: 'timeclockpass';      // as set in your compose
$dbname   = getenv('DB_NAME') ?: 'timeclock';          // as set in your compose

// If you prefer hard‑coding, just do:
// $host = 'db';
// $user = 'timeclockuser';
// $password = 'timeclockpass';
// $dbname = 'timeclock';

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>