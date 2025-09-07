<?php
// SQLite sozlamalari
define('DB_PATH', __DIR__ . '/../db/elita_sham.db');
define('SITE_URL', 'http://localhost:8000/');

// Sessionni boshlash
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
