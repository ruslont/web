#!/bin/bash

echo "Elita Sham loyihasini o'rnatish..."

# Papkalarni yaratish
mkdir -p web/{includes,assets/{css,js,images},admin,db}

# Config faylini yaratish
cat > web/includes/config.php << 'EOL'
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
EOL

echo "Loyiha strukturasi yaratildi!"
echo "Endi MySQL da ma'lumotlar bazasini yarating"
echo "Va quyidagini ishga tushiring: php -S localhost:8000"
