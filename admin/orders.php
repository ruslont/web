<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Foydalanuvchi admin ekanligini tekshirish
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Xatoliklar va muvaffaqiyat xabarlari
$errors = [];
$success = '';

// Buyurtma holatini yangilash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = sanitizeInput($_POST['status']);
    $tracking_number = sanitizeInput($_POST['tracking_number']);
    
    $query = "UPDATE orders SET status = :status, tracking_number = :tracking_number WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':tracking_number', $tracking_number);
    $stmt->bindParam(':id', $order_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $success = 'Статус заказа успешно обновлен';
        
        // Buyurtma egasiga xabar yuborish
        $order_query = "SELECT o.*, u.phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = :id";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bindParam(':id', $order_id, PDO::PARAM_INT);
        $order_stmt->execute();
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $status_labels = [
                'pending' => 'Ожидание обработки',
                'processing' => 'В обработке',
                'shipped' => 'Отправлен',
                'delivered' => 'Доставлен',
                'cancelled' => 'Отменен'
            ];
            
            $message = "Статус вашего заказа #{$order['order_number']} изменен на: {$status_labels[$status]}";
            
            if ($status == 'shipped' && !empty($tracking_number)) {
                $message .= "\nТрек-номер для отслеживания: $tracking_number";
            }
            
            // Telegram xabarini yuborish
            // sendTelegramMessage($message, $order['phone']);
        }
    } else {
        $errors[] = 'Ошибка при обновлении статуса заказа';
    }
}

// Buyurtmalarni olish
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$where_conditions = [];
$params = [];

if (!empty($status_filter) && $status_filter != 'all') {
    $where_conditions[] = "o.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(o.order_number LIKE :search OR o.customer_name LIKE :search OR o.customer_phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Jami buyurtmalar soni
$count_query = "SELECT COUNT(*) as total FROM orders o $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_orders / $per_page);

// Buyurtmalarni olish
$orders_query = "SELECT o.*, u.name as user_name, 
                        COUNT(oi.id) as items_count,
                        SUM(oi.quantity * oi.price) as items_total
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 LEFT JOIN order_items oi ON o.id = oi.order_id 
                 $where_clause 
                 GROUP BY o.id 
                 ORDER BY o.created_at DESC 
                 LIMIT :limit OFFSET :offset";

$orders_stmt = $conn->prepare($orders_query);
foreach ($params as $key => $value) {
    $orders_stmt->bindValue($key, $value);
}
$orders_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$orders_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Tahrirlash uchun buyurtma
$edit_order = null;
if (isset($_GET['view'])) {
    $order_id = intval($_GET['view']);
    $order_query = "SELECT o.*, u.name as user_name, u.phone as user_phone 
                    FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    WHERE o.id = :id";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bindParam(':id', $order_id, PDO::PARAM_INT);
    $order_stmt->execute();
    $edit_order = $order_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($edit_order) {
        // Buyurtma elementlarini olish
        $items_query = "SELECT oi.*, p.name as product_name, p.image as product_image 
                        FROM order_items oi 
                        LEFT JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = :order_id";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
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
    <title>Управление заказами - Elita Sham</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Управление заказами</h1>
        </div>
        
        <?php include 'includes/nav.php'; ?>
        
        <div class="admin-content">
            <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['view']) && $edit_order): ?>
            <!-- Buyurtma batafsil ko'rinishi -->
            <div class="order-detail-view">
                <div class="view-header">
                    <h2>Заказ #<?php echo $edit_order['order_number']; ?></h2>
                    <a href="orders.php" class="btn btn-outline">← Назад к списку</a>
                </div>
                
                <div class="order-info">
                    <div class="info-section">
                        <h3>Информация о заказе</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Статус:</span>
                                <span class="info-value">
                                    <span class="status-badge status-<?php echo $edit_order['status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'pending' => 'Ожидание',
                                            'processing' => 'В обработке',
                                            'shipped' => 'Отправлен',
                                            'delivered' => 'Доставлен',
                                            'cancelled' => 'Отменен'
                                        ];
                                        echo $status_labels[$edit_order['status']]; 
                                        ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Дата заказа:</span>
                                <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($edit_order['created_at'])); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Общая сумма:</span>
                                <span class="info-value"><?php echo number_format($edit_order['total_amount'], 0, ',', ' '); ?> руб.</span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Способ оплаты:</span>
                                <span class="info-value"><?php echo $edit_order['payment_method']; ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Способ доставки:</span>
                                <span class="info-value"><?php echo $edit_order['delivery_method']; ?></span>
                            </div>
                            
                            <?php if ($edit_order['tracking_number']): ?>
                            <div class="info-item">
                                <span class="info-label">Трек-номер:</span>
                                <span class="info-value"><?php echo $edit_order['tracking_number']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h3>Информация о клиенте</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">ФИО:</span>
                                <span class="info-value"><?php echo $edit_order['customer_name']; ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Телефон:</span>
                                <span class="info-value"><?php echo $edit_order['customer_phone']; ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo $edit_order['customer_email'] ?: 'Не указан'; ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Адрес доставки:</span>
                                <span class="info-value"><?php echo $edit_order['delivery_address']; ?></span>
                            </div>
                            
                            <?php if ($edit_order['user_id']): ?>
                            <div class="info-item">
                                <span class="info-label">Зарегистрированный пользователь:</span>
                                <span class="info-value">Да (ID: <?php echo $edit_order['user_id']; ?>)</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($edit_order['comments'])): ?>
                    <div class="info-section">
                        <h3>Комментарий к заказу</h3>
                        <p><?php echo $edit_order['comments']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="order-items-section">
                    <h3>Состав заказа</h3>
                    
                    <div class="items-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Товар</th>
                                    <th>Цена</th>
                                    <th>Количество</th>
                                    <th>Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="item-info">
                                            <?php if ($item['product_image']): ?>
                                            <img src="../assets/images/<?php echo $item['product_image']; ?>" alt="<?php echo $item['product_name']; ?>" class="item-thumb">
                                            <?php endif; ?>
                                            <span><?php echo $item['product_name']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['price'], 0, ',', ' '); ?> руб.</td>
                                    <td><?php echo $item['quantity']; ?> шт.</td>
                                    <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> руб.</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right">Итого:</td>
                                    <td><strong><?php echo number_format($edit_order['total_amount'], 0, ',', ' '); ?> руб.</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <div class="order-actions">
                    <h3>Изменение статуса заказа</h3>
                    
                    <form method="POST" class="status-form">
                        <input type="hidden" name="order_id" value="<?php echo $edit_order['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Статус заказа</label>
                                <select id="status" name="status" required>
                                    <option value="pending" <?php echo $edit_order['status'] == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                    <option value="processing" <?php echo $edit_order['status'] == 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                    <option value="shipped" <?php echo $edit_order['status'] == 'shipped' ? 'selected' : ''; ?>>Отправлен</option>
                                    <option value="delivered" <?php echo $edit_order['status'] == 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                                    <option value="cancelled" <?php echo $edit_order['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="tracking_number">Трек-номер</label>
                                <input type="text" id="tracking_number" name="tracking_number" 
                                       value="<?php echo $edit_order['tracking_number'] ?: ''; ?>" 
                                       placeholder="Введите трек-номер">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="update_status" class="btn btn-primary">Обновить статус</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Filtrlar va qidiruv -->
            <div class="filters">
                <form method="GET" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Поиск по номеру, имени или телефону" 
                                   value="<?php echo $search; ?>">
                        </div>
                        
                        <div class="form-group">
                            <select name="status">
                                <option value="all">Все статусы</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Отправлен</option>
                                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn">Применить фильтры</button>
                            <a href="orders.php" class="btn btn-outline">Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Buyurtmalar jadvali -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Номер заказа</th>
                            <th>Клиент</th>
                            <th>Телефон</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата заказа</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
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
                            <td>
                                <div class="action-buttons">
                                    <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-view">👁️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">Заказы не найдены</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Paginatsiya -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="orders.php?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>" class="page-link">← Назад</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="orders.php?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="orders.php?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>" class="page-link">Вперед →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
``` 
