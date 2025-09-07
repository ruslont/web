<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Filtrlash parametrlari
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Ma'lumotlar bazasiga ulanish
$db = new Database();
$conn = $db->getConnection();

// Kategoriyalarni olish
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Mahsulotlarni olish
$where_conditions = ["p.in_stock > 0"];
$params = [];

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Jami mahsulotlar soni
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $per_page);

// Mahsulotlarni olish
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   $where_clause 
                   ORDER BY p.created_at DESC 
                   LIMIT :limit OFFSET :offset";

$products_stmt = $conn->prepare($products_query);
foreach ($params as $key => $value) {
    $products_stmt->bindValue($key, $value);
}
$products_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$products_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="catalog-page">
        <div class="container">
            <h1>Каталог свечей</h1>
            
            <div class="catalog-content">
                <!-- Filtrlar paneli -->
                <aside class="filters-sidebar">
                    <h3>Категории</h3>
                    <ul class="categories-list">
                        <li class="<?php echo $category_id == 0 ? 'active' : ''; ?>">
                            <a href="catalog.php">Все категории</a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                        <li class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                            <a href="catalog.php?category=<?php echo $category['id']; ?>">
                                <?php echo $category['name']; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>

                <!-- Mahsulotlar -->
                <section class="products-section">
                    <?php if (count($products) > 0): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            <div class="product-info">
                                <h3><?php echo $product['name']; ?></h3>
                                <p class="product-category"><?php echo $product['category_name']; ?></p>
                                <p class="product-description"><?php echo substr($product['description'], 0, 100); ?>...</p>
                                <div class="product-meta">
                                    <span class="burn-time">⏱ <?php echo $product['burn_time']; ?> часов</span>
                                    <span class="wax-type">🕯 <?php echo $product['wax_type']; ?></span>
                                </div>
                                <div class="product-footer">
                                    <p class="price"><?php echo number_format($product['price'], 0, ',', ' '); ?> руб.</p>
                                    <button class="btn add-to-cart" data-product-id="<?php echo $product['id']; ?>">В корзину</button>
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">Подробнее</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginatsiya -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="catalog.php?category=<?php echo $category_id; ?>&page=<?php echo $page - 1; ?>" class="page-link">← Назад</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="catalog.php?category=<?php echo $category_id; ?>&page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="catalog.php?category=<?php echo $category_id; ?>&page=<?php echo $page + 1; ?>" class="page-link">Вперед →</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="no-products">
                        <h3>Товары не найдены</h3>
                        <p>Попробуйте изменить параметры фильтрации</p>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>
