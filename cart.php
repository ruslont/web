<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        table.cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table.cart-table th, table.cart-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        table.cart-table th {
            background: #f8f8f8;
        }
        .cart-actions {
            margin-top: 20px;
        }
        .cart-actions a button {
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-continue { background: #777; color: #fff; }
        .btn-checkout { background: #28a745; color: #fff; }
        .btn-delete { color: red; text-decoration: none; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="container">
    <h1>🛒 Ваша корзина</h1>

    <?php if (empty($cart)): ?>
        <p>Ваша корзина пуста.</p>
        <a href="catalog.php">← Перейти в каталог</a>
    <?php else: ?>
        <table class="cart-table">
            <tr>
                <th>Товар</th>
                <th>Цена</th>
                <th>Количество</th>
                <th>Итого</th>
                <th>Удалить</th>
            </tr>
            <?php foreach ($cart as $id => $item): 
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= number_format($item['price'], 0, '.', ' ') ?> сум</td>
                <td><?= (int)$item['quantity'] ?></td>
                <td><?= number_format($subtotal, 0, '.', ' ') ?> сум</td>
                <td>
                    <a class="btn-delete" href="remove-from-cart.php?id=<?= $id ?>">❌</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>Общая сумма: <?= number_format($total, 0, '.', ' ') ?> сум</h2>

        <div class="cart-actions">
            <a href="catalog.php"><button class="btn-continue">Продолжить покупки</button></a>
            <a href="checkout.php"><button class="btn-checkout">Оформить заказ</button></a>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
