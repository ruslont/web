<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Statistikalar
$stats = [
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_revenue' => $conn->query("SELECT SUM(total_amount) FROM orders WHERE status = 'delivered'")->fetchColumn(),
    'total_products' => $conn->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'pending_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn()
];

// Oxirgi buyurtmalar
$recent_orders = $conn->query("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Sotuvlar statistikasi
$sales_stats = $conn->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders_count,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE created_at >= date('now', '-30 days')
    GROUP BY DATE(created_at)
    ORDER BY date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Elita Sham</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Панель управления</h1>
        </div>
        
        <?php include 'includes/nav.php'; ?>
        
        <div class="admin-content">
            <!-- Statistikalar -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?= $stats['total_orders'] ?></h3>
                        <p>Всего заказов</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_revenue'] ?? 0, 0, ',', ' ') ?> руб.</h3>
                        <p>Общий доход</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛍️</div>
                    <div class="stat-info">
                        <h3><?= $stats['total_products'] ?></h3>
                        <p>Товаров</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?= $stats['total_users'] ?></h3>
                        <p>Пользователей</p>
                    </div>
                </div>
            </div>

            <!-- Grafiklar -->
            <div class="charts-row">
                <div class="chart-container">
                    <h3>Продажи за последние 30 дней</h3>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3>Статусы заказов</h3>
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>

            <!-- Oxirgi buyurtmalar -->
            <div class="recent-orders">
                <h3>Последние заказы</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Клиент</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><?= $order['order_number'] ?></td>
                            <td><?= $order['customer_name'] ?></td>
                            <td><?= number_format($order['total_amount'], 0, ',', ' ') ?> руб.</td>
                            <td><span class="status-badge status-<?= $order['status'] ?>"><?= $order['status'] ?></span></td>
                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Sotuvlar grafigi
    const salesChart = new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($sales_stats, 'date')) ?>,
            datasets: [{
                label: 'Доход',
                data: <?= json_encode(array_column($sales_stats, 'revenue')) ?>,
                borderColor: '#c8a97e',
                tension: 0.1
            }]
        }
    });
    </script>
</body>
</html>
