<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'elita_sham');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost:8000/');
define('TELEGRAM_BOT_TOKEN', 'test_token');
define('TELEGRAM_CHAT_ID', 'test_chat_id');
define('SMS_API_KEY', 'test_sms_key');
define('YANDEX_DELIVERY_API_KEY', 'test_yandex_key');
if (session_status() == PHP_SESSION_NONE) { session_start(); }
?>
