<?php
// Asosiy sozlamalar
define('DB_PATH', __DIR__ . '/../db/elita_sham.db');
define('SITE_URL', 'http://localhost:8000/');

// Integratsiya sozlamalari
define('YOO_KASSA_SHOP_ID', '');
define('YOO_KASSA_SECRET_KEY', '');
define('CDEK_API_LOGIN', '');
define('CDEK_API_PASSWORD', '');
define('YANDEX_DELIVERY_API_KEY', '');
define('SMS_AERO_EMAIL', '');
define('SMS_AERO_API_KEY', '');
define('WHATSAPP_BUSINESS_API_KEY', '');
define('TELEGRAM_BOT_TOKEN', '');

// Sessionni boshlash
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// API sozlamalarini yuklash
function loadApiSettings() {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT * FROM api_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach ($settings as $key => $value) {
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// Sozlamalarni yuklash
loadApiSettings();
?>
