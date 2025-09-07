<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Foydalanuvchi admin ekanligini tekshirish
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Umumiy statistikalar
$db = new Database();
$conn = $db->getConnection();

// Buyurtmalar soni
$query = "SELECT COUNT(*) as total_orders FROM orders";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

// Mahsulotlar soni
$query = "SELECT COUNT(*) as total_products FROM products";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// Foydalanuvchilar soni
$query = "SELECT COUNT(*) as total_users FROM users";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Jami daromad
$query = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'delivered'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// So'nggi buyurtmalar
$query = "SELECT o.*, u.name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Elita Sham</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Панель управления</h1>
            <p>Добро пожаловать, администратор</p>
        </div>
        
        <?php include 'includes/nav.php'; ?>
        
        <div class="admin-content">
            <h2>Общая статистика</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $total_orders; ?></h3>
                    <p>Всего заказов</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Товаров</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Пользователей</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format($total_revenue, 0, ',', ' '); ?> руб.</h3>
                    <p>Общий доход</p>
                </div>
            </div>
            
            <h2>Последние заказы</h2>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Номер заказа</th>
                        <th>Клиент</th>
                        <th>Телефон</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_orders as $order): ?>
                    <tr>
                        <td><a href="orders.php?action=view&id=<?php echo $order['id']; ?>"><?php echo $order['order_number']; ?></a></td>
                        <td><?php echo $order['customer_name']; ?></td>
                        <td><?php echo $order['customer_phone']; ?></td>
                        <td><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> руб.</td>
                        <td>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php 
                                $status_labels = [
                                    'pending' => 'Ожидание',
                                    'processing' => 'В обработке',
                                    'shipped' => 'Отправлен',
                                    'delivered' => 'Доставлен',
                                    'cancelled' => 'Отменен'
                                ];
                                echo $status_labels[$order['status']]; 
                                ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
