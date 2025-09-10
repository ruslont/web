<?php
session_start(); // ✅ SESSION boshida ishga tushirildi
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Foydalanuvchi tizimga kirganligini tekshirish
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Savat bo'sh bo'lsa, qayta yo'naltirish
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Foydalanuvchi ma'lumotlarini olish
$user_query = "SELECT * FROM users WHERE id = :id";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Savatdagi mahsulotlarni olish
$cart = $_SESSION['cart'];
$cart_items = [];
$total_amount = 0;

$product_ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));

$query = "SELECT * FROM products WHERE id IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->execute($product_ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    // ✅ quantity strukturasiga moslash
    $quantity = isset($cart[$product['id']]['quantity']) 
        ? $cart[$product['id']]['quantity'] 
        : $cart[$product['id']];

    $item_total = $product['price'] * $quantity;
    $total_amount += $item_total;

    $cart_items[] = [
        'product' => $product,
        'quantity' => $quantity,
        'total' => $item_total
    ];
}

// Yetkazib berish manzilini saqlash
$delivery_address = '';
if (isset($_POST['delivery_address'])) {
    $delivery_address = sanitizeInput($_POST['delivery_address']);
    $_SESSION['delivery_address'] = $delivery_address;
} elseif (isset($_SESSION['delivery_address'])) {
    $delivery_address = $_SESSION['delivery_address'];
}

// Yetkazib berish narxini hisoblash
$delivery_cost = 0;
if (!empty($delivery_address)) {
    // ✅ Agar calculateDelivery mavjud bo'lmasa xato chiqmasligi uchun
    if (function_exists('calculateDelivery')) {
        $delivery_data = calculateDelivery($delivery_address);
        if ($delivery_data && isset($delivery_data['cost'])) {
            $delivery_cost = $delivery_data['cost'];
        }
    }
}

$final_amount = $total_amount + $delivery_cost;

// Buyurtmani joylash
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $delivery_method = sanitizeInput($_POST['delivery_method']);
    $payment_method = sanitizeInput($_POST['payment_method']);
    $comments = sanitizeInput($_POST['comments']);

    // Validatsiya
    if (empty($name)) $errors[] = 'Введите имя';
    if (empty($phone)) $errors[] = 'Введите телефон';
    if (empty($address)) $errors[] = 'Введите адрес доставки';

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Buyurtma raqamini yaratish
            $order_number = generateOrderNumber();

            // Buyurtmani yaratish
            $order_query = "INSERT INTO orders (order_number, user_id, customer_name, customer_phone, customer_email, 
                            delivery_address, total_amount, delivery_method, payment_method, comments)
                            VALUES (:order_number, :user_id, :name, :phone, :email, :address, :total_amount, 
                            :delivery_method, :payment_method, :comments)";

            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bindParam(':order_number', $order_number);
            $order_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $order_stmt->bindParam(':name', $name);
            $order_stmt->bindParam(':phone', $phone);
            $order_stmt->bindParam(':email', $email);
            $order_stmt->bindParam(':address', $address);
            $order_stmt->bindValue(':total_amount', $final_amount); // ✅ float qiymat
            $order_stmt->bindParam(':delivery_method', $delivery_method);
            $order_stmt->bindParam(':payment_method', $payment_method);
            $order_stmt->bindParam(':comments', $comments);

            if ($order_stmt->execute()) {
                $order_id = $conn->lastInsertId();

                // Buyurtma elementlarini qo'shish
                foreach ($cart_items as $item) {
                    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price)
                                   VALUES (:order_id, :product_id, :quantity, :price)";

                    $item_stmt = $conn->prepare($item_query);
                    $item_stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
                    $item_stmt->bindParam(':product_id', $item['product']['id'], PDO::PARAM_INT);
                    $item_stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                    $item_stmt->bindParam(':price', $item['product']['price']);
                    $item_stmt->execute();

                    // Mahsulot miqdorini kamaytirish
                    $update_query = "UPDATE products SET in_stock = in_stock - :quantity WHERE id = :id";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':id', $item['product']['id'], PDO::PARAM_INT);
                    $update_stmt->execute();
                }

                $conn->commit();

                // Savatni tozalash
// unset($_SESSION['cart']);
// unset($_SESSION['delivery_address']);

                // Buyurtma haqida xabar yuborish
                $message = "Новый заказ #$order_number\nИмя: $name\nТелефон: $phone\nСумма: $final_amount руб.";
                // sendTelegramMessage($message);

                // Foydalanuvchini buyurtma sahifasiga yo'naltirish
                header("Location: order_success.php?order_id=$order_id");
                exit;
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = 'Ошибка при оформлении заказа: ' . $e->getMessage();
        }
    }
}
?>
