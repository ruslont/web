<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Каталог - " . SITE_NAME;

// Header ni include qilish
$header_path = __DIR__ . '/includes/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    echo "<!DOCTYPE html><html><head><title>$page_title</title></head><body>";
    echo "<header><div class='container'><h1>" . SITE_NAME . "</h1></div></header>";
    echo "<main class='container'>";
}

// Database ulanishi
$db = getDBConnection();
$products = [];
$categories = [];

if ($db) {
    try {
        // Filtr parametrlari
        $category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = 12;
        $offset = ($page - 1) * $per_page;
        
        // Kategoriyalarni olish
        $categories_stmt = $db->query("SELECT * FROM categories ORDER BY name");
        $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mahsulotlarni olish
        $where = "WHERE p.in_stock > 0";
        $params = [];
        
        if ($category_id > 0) {
            $where .= " AND p.category_id = :category_id";
            $params[':category_id'] = $category_id;
        }
        
        $products_query = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          $where 
                          ORDER BY p.created_at DESC 
                          LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($products_query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        displayError("Database xatosi", $e->getMessage());
    }
} else {
    displayError("Database ulanmadi");
}
?>

<h1>Каталог свечей</h1>

<div style="display: grid; grid-template-columns: 250px 1fr; gap: 30px; margin-top: 20px;">
    <!-- Sidebar -->
    <aside>
        <h3>Категории</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin: 10px 0;">
                <a href="<?php echo url('/catalog'); ?>" style="text-decoration: none; color: <?php echo $category_id == 0 ? 'var(--primary-color)' : 'var(--dark-color)'; ?>; font-weight: <?php echo $category_id == 0 ? 'bold' : 'normal'; ?>;">
                    Все категории
                </a>
            </li>
            <?php foreach ($categories as $category): ?>
            <li style="margin: 10px 0;">
                <a href="<?php echo url('/catalog?category=' . $category['id']); ?>" style="text-decoration: none; color: <?php echo $category_id == $category['id'] ? 'var(--primary-color)' : 'var(--dark-color)'; ?>; font-weight: <?php echo $category_id == $category['id'] ? 'bold' : 'normal'; ?>;">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <!-- Mahsulotlar -->
    <div>
        <?php if (!empty($products)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                <?php foreach ($products as $product): ?>
                    <div style="background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="height: 200px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <?php echo displayImage($product['image'], $product['name'], "product-image"); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p style="color: #666; margin: 10px 0;"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p style="font-weight: bold; color: var(--primary-color); font-size: 1.2em; margin: 10px 0;">
                            <?php echo formatPrice($product['price']); ?>
                        </p>
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button class="btn add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                В корзину
                            </button>
                            <a href="<?php echo url('/product?id=' . $product['id']); ?>" class="btn btn-outline">
                                Подробнее
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px;">
                <h3>Товары не найдены</h3>
                <p>В выбранной категории пока нет товаров</p>
                <a href="<?php echo url('/catalog'); ?>" class="btn btn-primary">Вернуться в каталог</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Footer ni include qilish
$footer_path = __DIR__ . '/includes/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    echo "</main></body></html>";
}
?>
