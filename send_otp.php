<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

$phone = sanitizeInput($_POST['phone']);
$method = sanitizeInput($_POST['method']);

// Telefon raqamini tekshirish
if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Введите номер телефона']);
    exit;
}

// OTP yuborish
if (sendOTP($phone, $method)) {
    echo json_encode(['success' => true, 'message' => 'Код подтверждения отправлен']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при отправке кода']);
}
?>
