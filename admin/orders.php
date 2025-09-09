<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/api/CDEK.php';
require_once '../includes/api/YandexDelivery.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// CDEK integratsiyasi
$cdek = new CDEK(CDEK_API_LOGIN, CDEK_API_PASSWORD);
$yandex_delivery = new YandexDelivery(YANDEX_DELIVERY_API_KEY);

// Buyurtma statusini yangilash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        $tracking_number = $_POST['tracking_number'];
        
        // Statusni yangilash
        $stmt = $conn->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?");
        $stmt->execute([$new_status, $tracking_number, $order_id]);
        
        // Xabar yuborish
        sendOrderStatusNotification($order_id, $new_status, $tracking_number);
    }
    
    // Yetkazib berish xizmatiga yuborish
    if (isset($_POST['send_to_delivery'])) {
        $order_id = $_POST['order_id'];
        $service = $_POST['delivery_service'];
        
        $order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch(PDO::FETCH_ASSOC);
        $order_items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetchAll(PDO::FETCH_ASSOC);
        
        if ($service === 'cdek') {
            $result = $cdek->createOrder([
                'number' => $order['order_number'],
                'recipient' => [
                    'name' => $order['customer_name'],
                    'phones' => [['number' => $order['customer_phone']]]
                ],
                'delivery_address' => $order['delivery_address']
            ]);
            
            if ($result['uuid']) {
                $conn->prepare("UPDATE orders SET tracking_number = ?, status = 'processing' WHERE id = ?")
                    ->execute([$result['uuid'], $order_id]);
            }
        }
    }
}
?>

<!-- Buyurtmalar boshqaruvi interfeysi -->
<div class="orders-management">
    <h2>Управление заказами</h2>
    
    <!-- Filtrlar -->
    <div class="filters">
        <select id="statusFilter">
            <option value="">Все статусы</option>
            <option value="pending">Новые</option>
            <option value="processing">В обработке</option>
            <option value="shipped">Отправлены</option>
            <option value="delivered">Доставлены</option>
        </select>
        
        <input type="date" id="dateFilter">
        
        <button class="btn" onclick="filterOrders()">Применить</button>
    </div>

    <!-- Buyurtmalar jadvali -->
    <table class="table">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Клиент</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Трек-номер</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['order_number'] ?></td>
                <td><?= $order['customer_name'] ?></td>
                <td><?= number_format($order['total_amount'], 0, ',', ' ') ?> руб.</td>
                <td>
                    <select class="status-select" data-order-id="<?= $order['id'] ?>">
                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Новый</option>
                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                        <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                        <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="tracking-input" 
                           value="<?= $order['tracking_number'] ?>" 
                           placeholder="Трек-номер"
                           data-order-id="<?= $order['id'] ?>">
                </td>
                <td>
                    <button class="btn btn-sm" onclick="updateStatus(<?= $order['id'] ?>)">💾</button>
                    <button class="btn btn-sm" onclick="showOrderDetails(<?= $order['id'] ?>)">👁️</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
