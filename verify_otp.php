<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

$otp = sanitizeInput($_POST['otp']);

if (empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Введите код подтверждения']);
    exit;
}

if (verifyOTP($otp)) {
    // Foydalanuvchini ro'yxatdan o'tkazish yoki tizimga kirish
    $phone = $_SESSION['otp_phone'];
    $user_id = registerUser($phone);
    
    if ($user_id) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_phone'] = $phone;
        unset($_SESSION['otp_phone']);
        
        echo json_encode(['success' => true, 'message' => 'Успешная авторизация']);
    } else {
        // Foydalanuvchi allaqachon mavjud, shunchaki kirish
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT id FROM users WHERE phone = :phone";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_phone'] = $phone;
            unset($_SESSION['otp_phone']);
            
            echo json_encode(['success' => true, 'message' => 'Успешная авторизация']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка авторизации']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный код подтверждения']);
}
?>
