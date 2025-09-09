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

// Filtrlash
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'month';

// Umumiy statistikalar
$stats = [
    'total_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'")->fetchColumn(),
    'total_revenue' => $conn->query("SELECT SUM(total_amount) FROM orders WHERE status = 'delivered' AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59'")->fetchColumn(),
    'avg_order_value' => $conn->query("SELECT AVG(total_amount) FROM orders WHERE status = 'delivered' AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59'")->fetchColumn(),
    'conversion_rate' => 0 // Bu yerda conversion rate hisoblash logikasi
];

// Kunlik sotuvlar
$daily_sales = $conn->query("
    SELECT DATE(created_at) as date, 
           COUNT(*) as orders_count,
           SUM(total_amount) as revenue
    FROM orders 
    WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll(PDO::FETCH_ASSOC);

// Top mahsulotlar
$top_products = $conn->query("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Kategoriyalar bo'yicha sotuvlar
$sales_by_category = $conn->query("
    SELECT c.name, COUNT(o.id) as orders_count, SUM(o.total_amount) as revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY c.id
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-chart-bar"></i> Аналитика и отчеты</h2>
    </div>

    <!-- Filtrlar -->
    <div class="filters-card">
        <h3>Фильтры</h3>
        <form method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Начальная дата</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="form-group">
                    <label>Конечная дата</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="form-group">
                    <label>Период</label>
                    <select name="period">
                        <option value="day" <?php echo $period == 'day' ? 'selected' : ''; ?>>День</option>
                        <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>Неделя</option>
                        <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Месяц</option>
                        <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>Год</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Применить</button>
                    <button type="button" class="btn btn-secondary" onclick="exportReport()">Экспорт</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Statistikalar -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['total_orders']; ?></h3>
                <p>Всего заказов</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-ruble-sign"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?> руб.</h3>
                <p>Общий доход</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['avg_order_value'], 0, ',', ' '); ?> руб.</h3>
                <p>Средний чек</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['conversion_rate'], 2); ?>%</h3>
                <p>Конверсия</p>
            </div>
        </div>
    </div>

    <!-- Grafiklar -->
    <div class="charts-row">
        <div class="chart-card">
            <h3>Динамика продаж</h3>
            <canvas id="salesChart" height="300"></canvas>
        </div>
        
        <div class="chart-card">
            <h3>Продажи по категориям</h3>
            <canvas id="categoryChart" height="300"></canvas>
        </div>
    </div>

    <!-- Jadval hisobotlari -->
    <div class="reports-row">
        <div class="report-card">
            <h3>Топ товары</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th>Продано</th>
                            <th>Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><?php echo $product['name']; ?></td>
                            <td><?php echo $product['total_sold']; ?> шт.</td>
                            <td><?php echo number_format($product['revenue'], 0, ',', ' '); ?> руб.</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="report-card">
            <h3>Продажи по категориям</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Категория</th>
                            <th>Заказы</th>
                            <th>Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_by_category as $category): ?>
                        <tr>
                            <td><?php echo $category['name']; ?></td>
                            <td><?php echo $category['orders_count']; ?></td>
                            <td><?php echo number_format($category['revenue'], 0, ',', ' '); ?> руб.</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sotuvlar grafigi
const salesChart = new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($daily_sales, 'date')); ?>,
        datasets: [{
            label: 'Выручка',
            data: <?php echo json_encode(array_column($daily_sales, 'revenue')); ?>,
            borderColor: '#c8a97e',
            backgroundColor: 'rgba(200, 169, 126, 0.1)',
            fill: true
        }]
    }
});

// Kategoriyalar grafigi
const categoryChart = new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($sales_by_category, 'name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($sales_by_category, 'revenue')); ?>,
            backgroundColor: [
                '#c8a97e', '#a38a6d', '#8e7b5f', '#796c51',
                '#645d43', '#4f4e35', '#3a3f27', '#253019'
            ]
        }]
    }
});

function exportReport() {
    // Eksport funksiyasi
    alert('Функция экспорта в разработке');
}
</script>

<?php include 'includes/footer.php'; ?>	
