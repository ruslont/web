<?php
// API sozlamalarini boshqarish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_api_settings'])) {
    $settings = [
        'YOO_KASSA_SHOP_ID' => $_POST['yoo_kassa_shop_id'],
        'YOO_KASSA_SECRET_KEY' => $_POST['yoo_kassa_secret_key'],
        'CDEK_API_LOGIN' => $_POST['cdek_api_login'],
        'CDEK_API_PASSWORD' => $_POST['cdek_api_password'],
        'SMS_AERO_EMAIL' => $_POST['sms_aero_email'],
        'SMS_AERO_API_KEY' => $_POST['sms_aero_api_key'],
        'WHATSAPP_BUSINESS_API_KEY' => $_POST['whatsapp_api_key'],
        'TELEGRAM_BOT_TOKEN' => $_POST['telegram_bot_token']
    ];
    
    $db = new Database();
    $conn = $db->getConnection();
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("REPLACE INTO api_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
}
?>

<div class="api-settings">
    <h2>Настройки API</h2>
    
    <form method="POST">
        <div class="form-section">
            <h3>ЮKassa</h3>
            <input type="text" name="yoo_kassa_shop_id" placeholder="Shop ID" value="<?= YOO_KASSA_SHOP_ID ?>">
            <input type="text" name="yoo_kassa_secret_key" placeholder="Secret Key" value="<?= YOO_KASSA_SECRET_KEY ?>">
        </div>
        
        <div class="form-section">
            <h3>CDEK</h3>
            <input type="text" name="cdek_api_login" placeholder="API Login" value="<?= CDEK_API_LOGIN ?>">
            <input type="password" name="cdek_api_password" placeholder="API Password" value="<?= CDEK_API_PASSWORD ?>">
        </div>
        
        <div class="form-section">
            <h3>SMS Aero</h3>
            <input type="email" name="sms_aero_email" placeholder="Email" value="<?= SMS_AERO_EMAIL ?>">
            <input type="text" name="sms_aero_api_key" placeholder="API Key" value="<?= SMS_AERO_API_KEY ?>">
        </div>
        
        <div class="form-section">
            <h3>WhatsApp Business</h3>
            <input type="text" name="whatsapp_api_key" placeholder="API Key" value="<?= WHATSAPP_BUSINESS_API_KEY ?>">
        </div>
        
        <div class="form-section">
            <h3>Telegram</h3>
            <input type="text" name="telegram_bot_token" placeholder="Bot Token" value="<?= TELEGRAM_BOT_TOKEN ?>">
        </div>
        
        <button type="submit" name="save_api_settings" class="btn btn-primary">💾 Сохранить настройки</button>
    </form>
</div>
