<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = intval($_GET['order_id']);

$db = new Database();
$conn = $db->getConnection();

// Buyurtma ma'lumotlarini olish
$query = "SELECT o.*, 
                 GROUP_CONCAT(p.name SEPARATOR ', ') as products,
                 COUNT(oi.id) as items_count
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id
          LEFT JOIN products p ON oi.product_id = p.id
          WHERE o.id = :id AND (o.user_id = :user_id OR :user_id IS NULL)
          GROUP BY o.id";
          
$stmt = $conn->prepare($query);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$stmt->bindParam(':id', $order_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="order-success-page">
        <div class="container">
            <div class="success-content">
                <div class="success-icon">✅</div>
                <h1>Заказ успешно оформлен!</h1>
                <p>Спасибо за ваш заказ. Мы свяжемся с вами в ближайшее время.</p>
                
                <div class="order-details">
                    <h2>Детали заказа</h2>
                    
                    <div class="detail-row">
                        <span class="detail-label">Номер заказа:</span>
                        <span class="detail-value"><?php echo $order['order_number']; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Дата заказа:</span>
                        <span class="detail-value"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Имя:</span>
                        <span class="detail-value"><?php echo $order['customer_name']; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Телефон:</span>
                        <span class="detail-value"><?php echo $order['customer_phone']; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Адрес доставки:</span>
                        <span class="detail-value"><?php echo $order['delivery_address']; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Способ оплаты:</span>
                        <span class="detail-value"><?php echo $order['payment_method']; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Общая сумма:</span>
                        <span class="detail-value"><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> руб.</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Статус:</span>
                        <span class="detail-value status-<?php echo $order['status']; ?>">
                            <?php 
                            $status_labels = [
                                'pending' => 'Ожидание обработки',
                                'processing' => 'В обработке',
                                'shipped' => 'Отправлен',
                                'delivered' => 'Доставлен',
                                'cancelled' => 'Отменен'
                            ];
                            echo $status_labels[$order['status']]; 
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="success-actions">
                    <a href="track.php?order=<?php echo $order['order_number']; ?>" class="btn btn-primary">
                        Отследить заказ
                    </a>
                    <a href="catalog.php" class="btn btn-outline">
                        Продолжить покупки
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
