<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$payment_id = $_GET['payment_id'] ?? '';
$error_message = $_GET['message'] ?? 'Произошла ошибка при оплате';

$db = new Database();
$conn = $db->getConnection();

// Agar payment_id bo'lsa, buyurtma ma'lumotlarini olish
if (!empty($payment_id)) {
    $order = $conn->query("SELECT * FROM orders WHERE payment_id = '$payment_id'")->fetch(PDO::FETCH_ASSOC);
    if ($order) {
        // To'lov statusini yangilash
        $conn->query("UPDATE orders SET payment_status = 'failed' WHERE id = {$order['id']}");
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка оплаты - Elita Sham</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="payment-page">
        <div class="container">
            <div class="payment-error">
                <div class="error-icon">❌</div>
                <h1>Ошибка оплаты</h1>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                
                <div class="error-details">
                    <p>Пожалуйста, попробуйте еще раз или выберите другой способ оплаты.</p>
                    
                    <?php if (isset($order)): ?>
                    <div class="order-info">
                        <p>Заказ: <strong><?php echo $order['order_number']; ?></strong></p>
                        <p>Сумма: <strong><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> руб.</strong></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="error-actions">
                    <?php if (isset($order)): ?>
                    <a href="../checkout.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                        Попробовать снова
                    </a>
                    <?php endif; ?>
                    
                    <a href="../cart.php" class="btn btn-outline">
                        Вернуться в корзину
                    </a>
                    
                    <a href="../catalog.php" class="btn btn-secondary">
                        Продолжить покупки
                    </a>
                </div>

                <div class="support-info">
                    <p>Если проблема повторяется, свяжитесь с нами:</p>
                    <p>📞 +7 (999) 999-99-99</p>
                    <p>📧 support@elita-sham.ru</p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
