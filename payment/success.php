<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// To'lov ma'lumotlarini olish
$payment_id = $_GET['payment_id'] ?? '';
$order_id = $_GET['order_id'] ?? '';

if (empty($payment_id) && empty($order_id)) {
    header('Location: ../index.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Buyurtma ma'lumotlarini olish
if (!empty($order_id)) {
    $order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch(PDO::FETCH_ASSOC);
} else {
    $order = $conn->query("SELECT * FROM orders WHERE payment_id = '$payment_id'")->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    header('Location: ../index.php');
    exit;
}

// To'lov statusini yangilash
$conn->query("UPDATE orders SET payment_status = 'completed', status = 'processing' WHERE id = {$order['id']}");

// Xabarnoma yuborish
NotificationSystem::sendOrderNotification($order['id'], 'payment_success');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата успешна - Elita Sham</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="payment-page">
        <div class="container">
            <div class="payment-success">
                <div class="success-icon">✅</div>
                <h1>Оплата успешно завершена!</h1>
                <p>Спасибо за ваш заказ. Мы уже начали его обработку.</p>
                
                <div class="order-details">
                    <h2>Детали заказа</h2>
                    <div class="detail-item">
                        <span>Номер заказа:</span>
                        <strong><?php echo $order['order_number']; ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Сумма:</span>
                        <strong><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> руб.</strong>
                    </div>
                    <div class="detail-item">
                        <span>Статус:</span>
                        <span class="status-badge status-processing">В обработке</span>
                    </div>
                </div>

                <div class="success-actions">
                    <a href="../track.php?order=<?php echo $order['order_number']; ?>" class="btn btn-primary">
                        Отследить заказ
                    </a>
                    <a href="../catalog.php" class="btn btn-outline">
                        Продолжить покупки
                    </a>
                </div>

                <div class="support-info">
                    <p>Если у вас есть вопросы, свяжитесь с нами:</p>
                    <p>📞 +7 (999) 999-99-99</p>
                    <p>📧 info@elita-sham.ru</p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
