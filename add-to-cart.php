<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? "";
$price = $_POST['price'] ?? 0;

if ($id && $name && $price) {
    if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = [
            'name' => $name,
            'price' => (float)$price,
            'quantity' => 1
        ];
    } else {
        $_SESSION['cart'][$id]['quantity']++;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Товар добавлен в корзину"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Неверные данные"
    ]);
}
