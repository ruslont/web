<?php
require_once 'database.php';

// Xavfsizlik funksiyasi - ma'lumotlarni tozalash
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// OTP yuborish funksiyasi
function sendOTP($phone, $method = 'telegram') {
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time();
    $_SESSION['otp_phone'] = $phone;
    
    $message = "Ваш код подтверждения для Elita Sham: " . $otp;
    
    if ($method === 'sms') {
        // SMS yuborish logikasi
        $url = "https://sms.ru/sms/send?api_id=" . SMS_API_KEY . "&to=" . $phone . "&msg=" . urlencode($message);
        file_get_contents($url);
    } elseif ($method === 'whatsapp') {
        // WhatsApp yuborish logikasi (third-party API orqali)
    } else {
        // Telegram yuborish (sukut bo'yicha)
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage?chat_id=" . TELEGRAM_CHAT_ID . "&text=" . urlencode($message);
        file_get_contents($url);
    }
    
    return true;
}

// OTPni tekshirish
function verifyOTP($enteredOtp) {
    if (isset($_SESSION['otp']) && 
        isset($_SESSION['otp_time']) && 
        $_SESSION['otp'] == $enteredOtp && 
        (time() - $_SESSION['otp_time']) < 300) { // 5 daqiqa
        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);
        return true;
    }
    return false;
}

// Foydalanuvchini ro'yxatdan o'tkazish
function registerUser($phone, $name = '') {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Foydalanuvchi mavjudligini tekshirish
    $query = "SELECT id FROM users WHERE phone = :phone";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return false; // Foydalanuvchi allaqachon mavjud
    }
    
    // Yangi foydalanuvchi yaratish
    $query = "INSERT INTO users (phone, name, created_at) VALUES (:phone, :name, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':name', $name);
    
    if ($stmt->execute()) {
        return $conn->lastInsertId();
    }
    
    return false;
}

// Buyurtma raqamini yaratish
function generateOrderNumber() {
    return 'ORD' . strtoupper(substr(uniqid(), -6));
}

// Yandex.Delivery narxini hisoblash
function calculateDelivery($address) {
    // Yandex.Delivery API ga so'rov yuborish
    $url = "https://api.delivery.yandex.ru/delivery/calculate";
    
    $data = [
        'sender_id' => 'your_sender_id',
        'from' => ['latitude' => 55.7558, 'longitude' => 37.6173], // Moskva
        'to' => $address,
        'items' => [['quantity' => 1, 'size' => ['length' => 0.1, 'width' => 0.1, 'height' => 0.1]]]
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\nAuthorization: Bearer " . YANDEX_DELIVERY_API_KEY,
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return json_decode($result, true);
}
?>
