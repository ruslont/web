<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: catalog.php');
    exit;
}

$product_id = intval($_GET['id']);

$db = new Database();
$conn = $db->getConnection();

// Mahsulot ma'lumotlarini olish
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = :id AND p.in_stock > 0";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: catalog.php');
    exit;
}

// O'xshash mahsulotlarni olish
$similar_query = "SELECT * FROM products 
                  WHERE category_id = :category_id 
                  AND id != :id 
                  AND in_stock > 0 
                  ORDER BY RAND() 
                  LIMIT 4";
$similar_stmt = $conn->prepare($similar_query);
$similar_stmt->bindParam(':category_id', $product['category_id'], PDO::PARAM_INT);
$similar_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
$similar_stmt->execute();
$similar_products = $similar_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="product-page">
        <div class="container">
            <div class="breadcrumbs">
                <a href="index.php">Главная</a> / 
                <a href="catalog.php">Каталог</a> / 
                <a href="catalog.php?category=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a> / 
                <span><?php echo $product['name']; ?></span>
            </div>

            <div class="product-detail">
                <div class="product-image">
                    <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                </div>

                <div class="product-info">
                    <h1><?php echo $product['name']; ?></h1>
                    <p class="product-category"><?php echo $product['category_name']; ?></p>
                    <p class="product-description"><?php echo $product['description']; ?></p>

                    <div class="product-specs">
                        <div class="spec">
                            <span class="spec-label">Время горения:</span>
                            <span class="spec-value"><?php echo $product['burn_time']; ?> часов</span>
                        </div>
                        <div class="spec">
                            <span class="spec-label">Тип воска:</span>
                            <span class="spec-value"><?php echo $product['wax_type']; ?></span>
                        </div>
                        <div class="spec">
                            <span class="spec-label">Аромат:</span>
                            <span class="spec-value"><?php echo $product['fragrance_notes']; ?></span>
                        </div>
                        <div class="spec">
                            <span class="spec-label">Вес:</span>
                            <span class="spec-value"><?php echo $product['weight']; ?> кг</span>
                        </div>
                        <div class="spec">
                            <span class="spec-label">В наличии:</span>
                            <span class="spec-value"><?php echo $product['in_stock']; ?> шт.</span>
                        </div>
                    </div>

                    <div class="product-price">
                        <span class="price"><?php echo number_format($product['price'], 0, ',', ' '); ?> руб.</span>
                    </div>

                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button class="quantity-btn decrease">-</button>
                            <input type="number" class="quantity-input" value="1" min="1" max="<?php echo $product['in_stock']; ?>">
                            <button class="quantity-btn increase">+</button>
                        </div>
                        <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                            Добавить в корзину
                        </button>
                    </div>
                </div>
            </div>

            <?php if (count($similar_products) > 0): ?>
            <section class="similar-products">
                <h2>Похожие товары</h2>
                <div class="products-grid">
                    <?php foreach ($similar_products as $similar_product): ?>
                    <div class="product-card">
                        <img src="assets/images/<?php echo $similar_product['image']; ?>" alt="<?php echo $similar_product['name']; ?>">
                        <div class="product-info">
                            <h3><?php echo $similar_product['name']; ?></h3>
                            <p class="price"><?php echo number_format($similar_product['price'], 0, ',', ' '); ?> руб.</p>
                            <button class="btn add-to-cart" data-product-id="<?php echo $similar_product['id']; ?>">В корзину</button>
                            <a href="product.php?id=<?php echo $similar_product['id']; ?>" class="btn btn-outline">Подробнее</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>
