<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Savatdagi mahsulotlarni olish
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cart_items = [];
$total_amount = 0;

if (!empty($cart)) {
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
}

// Mahsulotni savatdan o'chirish
if (isset($_POST['remove_from_cart'])) {
    $product_id = intval($_POST['product_id']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        header('Location: cart.php');
        exit;
    }
}

// Miqdorni yangilash
if (isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = max(1, intval($_POST['quantity']));
    
    // Mahsulot mavjudligini tekshirish
    $query = "SELECT in_stock FROM products WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $quantity <= $product['in_stock']) {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    header('Location: cart.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="cart-page">
        <div class="container">
            <h1>Корзина покупок</h1>
            
            <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h2>Ваша корзина пуста</h2>
                <p>Перейдите в каталог, чтобы добавить товары</p>
                <a href="catalog.php" class="btn btn-primary">Перейти в каталог</a>
            </div>
            <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-product-id="<?php echo $item['product']['id']; ?>">
                        <img src="assets/images/<?php echo $item['product']['image']; ?>" alt="<?php echo $item['product']['name']; ?>">
                        
                        <div class="item-details">
                            <h3><?php echo $item['product']['name']; ?></h3>
                            <p class="item-description"><?php echo substr($item['product']['description'], 0, 100); ?>...</p>
                        </div>
                        
                        <div class="item-price">
                            <span class="price"><?php echo number_format($item['product']['price'], 0, ',', ' '); ?> руб.</span>
                        </div>
                        
                        <div class="item-quantity">
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                <button type="button" class="quantity-btn decrease">-</button>
                                <input type="number" name="quantity" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['product']['in_stock']; ?>">
                                <button type="button" class="quantity-btn increase">+</button>
                                <button type="submit" name="update_quantity" class="btn-update" style="display:none">Обновить</button>
                            </form>
                        </div>
                        
                        <div class="item-total">
                            <span class="total-price"><?php echo number_format($item['total'], 0, ',', ' '); ?> руб.</span>
                        </div>
                        
                        <div class="item-actions">
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                <button type="submit" name="remove_from_cart" class="btn-remove">×</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Итого</h3>
                        
                        <div class="summary-row">
                            <span>Товары (<?php echo count($cart_items); ?>):</span>
                            <span><?php echo number_format($total_amount, 0, ',', ' '); ?> руб.</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Доставка:</span>
                            <span>Рассчитывается при оформлении</span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row total">
                            <span>Общая сумма:</span>
                            <span><?php echo number_format($total_amount, 0, ',', ' '); ?> руб.</span>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-primary btn-checkout">Оформить заказ</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/cart.js"></script>
</body>
</html>
