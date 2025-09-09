<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/api/YooKassa.php';
require_once '../includes/api/CDEK.php';

// Webhook ni qayd qilish
function logWebhook($source, $data) {
    $log = date('Y-m-d H:i:s') . " - $source: " . json_encode($data) . "\n";
    file_put_contents('../logs/webhook.log', $log, FILE_APPEND);
}

// JSON ma'lumotlarini olish
$input = file_get_contents('php://input');
$data = json_decode($input, true);

logWebhook('INCOMING', $data);

// YooKassa webhook
if (isset($data['object']['status'])) {
    handleYooKassaWebhook($data);
}

// CDEK webhook
if (isset($data['type']) && strpos($data['type'], 'cdek') !== false) {
    handleCDEKWebhook($data);
}

// Yandex Delivery webhook
if (isset($data['event'])) {
    handleYandexWebhook($data);
}

function handleYooKassaWebhook($data) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $payment_id = $data['object']['id'];
    $status = $data['object']['status'];
    $order_id = $data['object']['metadata']['order_id'] ?? null;
    
    if ($order_id) {
        // To'lov statusini yangilash
        $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        
        if ($status === 'succeeded') {
            // Muvaffaqiyatli to'lov
            $conn->query("UPDATE orders SET status = 'processing' WHERE id = $order_id");
            NotificationSystem::sendOrderNotification($order_id, 'payment_success');
        }
        
        logWebhook('YOO_KASSA', "Order $order_id: $status");
    }
}

function handleCDEKWebhook($data) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $tracking_number = $data['uuid'] ?? '';
    $status = mapCDEKStatus($data['status'] ?? '');
    
    if ($tracking_number && $status) {
        // Buyurtma statusini yangilash
        $stmt = $conn->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE tracking_number = ?");
        $stmt->execute([$status, $tracking_number, $tracking_number]);
        
        // Xabarnoma yuborish
        $order = $conn->query("SELECT id FROM orders WHERE tracking_number = '$tracking_number'")->fetch(PDO::FETCH_ASSOC);
        if ($order) {
            NotificationSystem::sendOrderNotification($order['id'], 'status_changed');
        }
        
        logWebhook('CDEK', "Tracking $tracking_number: $status");
    }
}

function mapCDEKStatus($cdekStatus) {
    $statusMap = [
        'CREATED' => 'processing',
        'ACCEPTED' => 'processing',
        'IN_PROGRESS' => 'processing',
        'DELIVERED' => 'delivered',
        'RETURNED' => 'cancelled'
    ];
    
    return $statusMap[$cdekStatus] ?? 'processing';
}

function handleYandexWebhook($data) {
    // Yandex Delivery webhook ni qayta ishlash
    logWebhook('YANDEX', $data);
    
    // Yandex statuslarini mahalliy statuslarga o'girish
    // ...
}

// Javob qaytarish
http_response_code(200);
echo json_encode(['status' => 'success']);
