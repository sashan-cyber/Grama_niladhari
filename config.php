<?php
// ── Database Configuration ───────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // ← change to your MySQL username
define('DB_PASS', '');           // ← change to your MySQL password
define('DB_NAME', 'resident_database');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
         <h2>Database Connection Failed</h2>
         <p>' . htmlspecialchars($conn->connect_error) . '</p>
         <p>Please check your credentials in <code>config.php</code>.</p>
         </div>');
}

$conn->set_charset('utf8mb4');
?>
