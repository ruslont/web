<?php
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
    $quantity = $cart[$product['id']];
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
    // Yandex.Delivery API dan narxni olish
    $delivery_data = calculateDelivery($delivery_address);
    if ($delivery_data && isset($delivery_data['cost'])) {
        $delivery_cost = $delivery_data['cost'];
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
            $order_stmt->bindParam(':user_id', $user_id);
            $order_stmt->bindParam(':name', $name);
            $order_stmt->bindParam(':phone', $phone);
            $order_stmt->bindParam(':email', $email);
            $order_stmt->bindParam(':address', $address);
            $order_stmt->bindParam(':total_amount', $final_amount);
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
                    $item_stmt->bindParam(':order_id', $order_id);
                    $item_stmt->bindParam(':product_id', $item['product']['id']);
                    $item_stmt->bindParam(':quantity', $item['quantity']);
                    $item_stmt->bindParam(':price', $item['product']['price']);
                    $item_stmt->execute();
                    
                    // Mahsulot miqdorini kamaytirish
                    $update_query = "UPDATE products SET in_stock = in_stock - :quantity WHERE id = :id";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':quantity', $item['quantity']);
                    $update_stmt->bindParam(':id', $item['product']['id']);
                    $update_stmt->execute();
                }
                
                $conn->commit();
                
                // Savatni tozalash
                unset($_SESSION['cart']);
                unset($_SESSION['delivery_address']);
                
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

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="checkout-page">
        <div class="container">
            <h1>Оформление заказа</h1>
            
            <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="checkout-content">
                <div class="checkout-form">
                    <form method="POST" id="order-form">
                        <div class="form-section">
                            <h2>Контактная информация</h2>
                            
                            <div class="form-group">
                                <label for="name">ФИО *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo isset($_POST['name']) ? $_POST['name'] : ($user['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Телефон *</label>
                                <input type="tel" id="phone" name="phone" required 
                                       value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? $_POST['email'] : ($user['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h2>Доставка</h2>
                            
                            <div class="form-group">
                                <label for="delivery_method">Способ доставки</label>
                                <select id="delivery_method" name="delivery_method" required>
                                    <option value="courier">Курьерская доставка</option>
                                    <option value="pickup">Самовывоз</option>
                                    <option value="post">Почта России</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Адрес доставки *</label>
                                <textarea id="address" name="address" rows="3" required><?php echo isset($_POST['address']) ? $_POST['address'] : $delivery_address; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="comments">Комментарий к заказу</label>
                                <textarea id="comments" name="comments" rows="3"><?php echo isset($_POST['comments']) ? $_POST['comments'] : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h2>Оплата</h2>
                            
                            <div class="form-group">
                                <label for="payment_method">Способ оплаты</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="card">Банковская карта</option>
                                    <option value="cash">Наличными при получении</option>
                                    <option value="online">Онлайн-оплата</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="order-summary">
                    <div class="summary-card">
                        <h3>Ваш заказ</h3>
                        
                        <div class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <span class="item-name"><?php echo $item['product']['name']; ?> × <?php echo $item['quantity']; ?></span>
                                <span class="item-price"><?php echo number_format($item['total'], 0, ',', ' '); ?> руб.</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row">
                            <span>Товары:</span>
                            <span><?php echo number_format($total_amount, 0, ',', ' '); ?> руб.</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Доставка:</span>
                            <span><?php echo number_format($delivery_cost, 0, ',', ' '); ?> руб.</span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row total">
                            <span>Итого:</span>
                            <span><?php echo number_format($final_amount, 0, ',', ' '); ?> руб.</span>
                        </div>
                        
                        <button type="submit" form="order-form" name="place_order" class="btn btn-primary btn-place-order">
                            Оформить заказ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/checkout.js"></script>
</body>
</html>
