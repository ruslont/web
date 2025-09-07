<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$order_number = isset($_GET['order']) ? sanitizeInput($_GET['order']) : '';
$order = null;
$order_items = [];

if (!empty($order_number)) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Buyurtma ma'lumotlarini olish
    $query = "SELECT o.*, u.name as user_name, u.phone as user_phone
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.order_number = :order_number";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_number', $order_number);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Buyurtma elementlarini olish
        $items_query = "SELECT oi.*, p.name as product_name, p.image as product_image
                        FROM order_items oi
                        LEFT JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = :order_id";
        
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bindParam(':order_id', $order['id'], PDO::PARAM_INT);
        $items_stmt->execute();
        $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отслеживание заказа - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="track-order-page">
        <div class="container">
            <h1>Отслеживание заказа</h1>
            
            <div class="track-content">
                <div class="track-form">
                    <form method="GET" action="track.php">
                        <div class="form-group">
                            <label for="order-number">Номер заказа</label>
                            <input type="text" id="order-number" name="order" 
                                   value="<?php echo $order_number; ?>" 
                                   placeholder="Введите номер заказа (например, ORD123)" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Найти заказ</button>
                    </form>
                </div>
                
                <?php if (!empty($order_number)): ?>
                    <?php if ($order): ?>
                    <div class="order-status">
                        <h2>Статус заказа #<?php echo $order['order_number']; ?></h2>
                        
                        <div class="status-timeline">
                            <div class="status-step <?php echo $order['status'] == 'pending' ? 'active' : ($order['status'] != 'pending' ? 'completed' : ''); ?>">
                                <div class="step-icon">📋</div>
                                <div class="step-label">Оформлен</div>
                                <div class="step-date"><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></div>
                            </div>
                            
                            <div class="status-step <?php echo $order['status'] == 'processing' ? 'active' : ($order['status'] == 'shipped' || $order['status'] == 'delivered' ? 'completed' : ''); ?>">
                                <div class="step-icon">🔄</div>
                                <div class="step-label">В обработке</div>
                                <?php if ($order['status'] == 'processing' || $order['status'] == 'shipped' || $order['status'] == 'delivered'): ?>
                                <div class="step-date"><?php echo date('d.m.Y', strtotime($order['created_at'] . ' +1 day')); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="status-step <?php echo $order['status'] == 'shipped' ? 'active' : ($order['status'] == 'delivered' ? 'completed' : ''); ?>">
                                <div class="step-icon">🚚</div>
                                <div class="step-label">Отправлен</div>
                                <?php if ($order['status'] == 'shipped' || $order['status'] == 'delivered'): ?>
                                <div class="step-date"><?php echo date('d.m.Y', strtotime($order['created_at'] . ' +2 days')); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="status-step <?php echo $order['status'] == 'delivered' ? 'active completed' : ''; ?>">
                                <div class="step-icon">✅</div>
                                <div class="step-label">Доставлен</div>
                                <?php if ($order['status'] == 'delivered'): ?>
                                <div class="step-date"><?php echo date('d.m.Y', strtotime($order['created_at'] . ' +5 days')); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <h3>Информация о заказе</h3>
                            
                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Получатель:</span>
                                    <span class="detail-value"><?php echo $order['customer_name']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Телефон:</span>
                                    <span class="detail-value"><?php echo $order['customer_phone']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value"><?php echo $order['customer_email'] ?: 'Не указан'; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Адрес доставки:</span>
                                    <span class="detail-value"><?php echo $order['delivery_address']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Способ доставки:</span>
                                    <span class="detail-value"><?php echo $order['delivery_method']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Способ оплаты:</span>
                                    <span class="detail-value"><?php echo $order['payment_method']; ?></span>
                                </div>
                                
                                <?php if ($order['tracking_number']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Трек-номер:</span>
                                    <span class="detail-value"><?php echo $order['tracking_number']; ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Общая сумма:</span>
                                    <span class="detail-value"><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> руб.</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($order_items)): ?>
                        <div class="order-items">
                            <h3>Состав заказа</h3>
                            
                            <div class="items-list">
                                <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <img src="assets/images/<?php echo $item['product_image']; ?>" alt="<?php echo $item['product_name']; ?>">
                                    
                                    <div class="item-info">
                                        <h4><?php echo $item['product_name']; ?></h4>
                                        <p>Количество: <?php echo $item['quantity']; ?> шт.</p>
                                    </div>
                                    
                                    <div class="item-price">
                                        <?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> руб.
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="order-not-found">
                        <h2>Заказ не найден</h2>
                        <p>Проверьте правильность номера заказа или свяжитесь с нами</p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
