<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Корзина - " . SITE_NAME;

// Header ni include qilish
$header_path = __DIR__ . '/includes/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    echo "<!DOCTYPE html><html><head><title>$page_title</title></head><body>";
    echo "<header><div class='container'><h1>" . SITE_NAME . "</h1></div></header>";
    echo "<main class='container'>";
}

// Savatdagi mahsulotlar
$cart_items = [];
$total_amount = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $db = getDBConnection();
    if ($db) {
        try {
            $product_ids = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            
            $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as $product) {
                $quantity = $_SESSION['cart'][$product['id']]['quantity']
