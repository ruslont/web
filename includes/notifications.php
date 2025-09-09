<?php
class NotificationSystem {
    public static function sendSMS($phone, $message) {
        if (!SMS_AERO_API_KEY) return false;
        
        $url = "https://gate.smsaero.ru/v2/sms/send";
        $data = [
            'number' => $phone,
            'text' => $message,
            'sign' => 'ELITA SHAM'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, SMS_AERO_EMAIL . ':' . SMS_AERO_API_KEY);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true)['success'] ?? false;
    }
    
    public static function sendWhatsApp($phone, $message) {
        if (!WHATSAPP_BUSINESS_API_KEY) return false;
        
        // WhatsApp Business API integrasiyasi
        $url = "https://graph.facebook.com/v13.0/me/messages";
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WHATSAPP_BUSINESS_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true)['messages'] ?? false;
    }
    
    public static function sendTelegram($chatId, $message) {
        if (!TELEGRAM_BOT_TOKEN) return false;
        
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true)['ok'] ?? false;
    }
    
    public static function sendOrderNotification($orderId, $type) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $order = $conn->query("SELECT * FROM orders WHERE id = $orderId")->fetch(PDO::FETCH_ASSOC);
        $user = $conn->query("SELECT * FROM users WHERE id = {$order['user_id']}")->fetch(PDO::FETCH_ASSOC);
        
        $messages = [
            'new_order' => "Новый заказ #{$order['order_number']} на сумму {$order['total_amount']} руб.",
            'status_changed' => "Статус заказа #{$order['order_number']} изменен на: {$order['status']}",
            'delivery_sent' => "Ваш заказ #{$order['order_number']} отправлен. Трек: {$order['tracking_number']}"
        ];
        
        $message = $messages[$type] ?? '';
        
        // Hammaga xabar yuborish
        self::sendSMS($user['phone'], $message);
        self::sendWhatsApp($user['phone'], $message);
        self::sendTelegram($user['phone'], $message);
    }
}
?>
