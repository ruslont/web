<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Mahsulot ID sini tekshirish
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/catalog');
    exit;
}

$product_id = intval($_GET['id']);
$db = getDBConnection();
$product = null;
$similar_products = [];

if ($db) {
    try {
        // Mahsulot ma'lumotlarini olish
        $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             WHERE p.id = :id AND p.in_stock > 0");
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $page_title = $product['name'] . " - " . SITE_NAME;
            
            // O'xshash mahsulotlarni olish (RANDOM o'rniga created_at dan foydalanish)
            $similar_stmt = $db->prepare("SELECT * FROM products 
                                         WHERE category_id = :category_id 
                                         AND id != :id 
                                         AND in_stock > 0 
                                         ORDER BY created_at DESC 
                                         LIMIT 4");
            $similar_stmt->bindParam(':category_id', $product['category_id'], PDO::PARAM_INT);
            $similar_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $similar_stmt->execute();
            $similar_products = $similar_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } catch (PDOException $e) {
        displayError("Database xatosi", $e->getMessage());
    }
}

// Agar mahsulot topilmasa, katalogga yo'naltirish
if (!$product) {
    redirect('/catalog');
    exit;
}

// Header ni include qilish
$header_path = __DIR__ . '/includes/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    echo "<!DOCTYPE html><html><head><title>$page_title</title></head><body>";
    echo "<header><div class='container'><h1>" . SITE_NAME . "</h1></div></header>";
    echo "<main class='container'>";
}
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px;">
    <!-- Mahsulot rasmi -->
    <div>
        <div style="background: #f0f0f0; border-radius: 10px; padding: 20px; text-align: center; height: 400px; display: flex; align-items: center; justify-content: center;">
            <?php echo displayImage($product['image'], $product['name'], "product-image-large"); ?>
        </div>
    </div>

    <!-- Mahsulot ma'lumotlari -->
    <div>
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <p style="color: #666; margin: 10px 0;"><?php echo htmlspecialchars($product['category_name']); ?></p>
        
        <div style="font-size: 1.5em; font-weight: bold; color: var(--primary-color); margin: 20px 0;">
            <?php echo formatPrice($product['price']); ?>
        </div>

        <div style="margin: 20px 0;">
            <h3>Описание</h3>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>

        <!-- Texnik xususiyatlar -->
        <div style="margin: 20px 0;">
            <h3>Характеристики</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <?php if ($product['weight']): ?>
                <div><strong>Вес:</strong> <?php echo $product['weight']; ?> кг</div>
                <?php endif; ?>
                
                <?php if ($product['burn_time']): ?>
                <div><strong>Время горения:</strong> <?php echo $product['burn_time']; ?> часов</div>
                <?php endif; ?>
                
                <?php if ($product['wax_type']): ?>
                <div><strong>Тип воска:</strong> <?php echo htmlspecialchars($product['wax_type']); ?></div>
                <?php endif; ?>
                
                <?php if ($product['fragrance_notes']): ?>
                <div><strong>Аромат:</strong> <?php echo htmlspecialchars($product['fragrance_notes']); ?></div>
                <?php endif; ?>
                
                <div><strong>В наличии:</strong> <?php echo $product['in_stock']; ?> шт.</div>
            </div>
        </div>

        <!-- Buyurtma qismi -->
        <div style="margin: 30px 0;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 4px;">
                    <button type="button" class="quantity-btn decrease" style="padding: 10px 15px; border: none; background: #f0f0f0; cursor: pointer;">-</button>
                    <input type="number" class="quantity-input" value="1" min="1" max="<?php echo $product['in_stock']; ?>" style="width: 60px; text-align: center; border: none; padding: 10px;">
                    <button type="button" class="quantity-btn increase" style="padding: 10px 15px; border: none; background: #f0f0f0; cursor: pointer;">+</button>
                </div>
                <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>" style="padding: 12px 30px;">
                    Добавить в корзину
                </button>
            </div>
        </div>
    </div>
</div>

<!-- O'xshash mahsulotlar -->
<?php if (!empty($similar_products)): ?>
<div style="margin-top: 50px;">
    <h2>Похожие товары</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <?php foreach ($similar_products as $similar): ?>
            <div style="background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="height: 200px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                    <?php echo displayImage($similar['image'], $similar['name']); ?>
                </div>
                <h3><?php echo htmlspecialchars($similar['name']); ?></h3>
                <p style="font-weight: bold; color: var(--primary-color); margin: 10px 0;">
                    <?php echo formatPrice($similar['price']); ?>
                </p>
                <a href="<?php echo url('/product?id=' . $similar['id']); ?>" class="btn btn-outline" style="display: block; text-align: center;">
                    Подробнее
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Miqdor tugmalari
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.querySelector('.quantity-input');
    const decreaseBtn = document.querySelector('.quantity-btn.decrease');
    const increaseBtn = document.querySelector('.quantity-btn.increase');
    
    decreaseBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1) {
            quantityInput.value = value - 1;
        }
    });
    
    increaseBtn.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        const max = parseInt(quantityInput.max);
        if (value < max) {
            quantityInput.value = value + 1;
        }
    });
    
    // Savatga qo'shish
    document.querySelector('.add-to-cart').addEventListener('click', function() {
        const productId = this.dataset.productId;
        const quantity = parseInt(quantityInput.value);
        addToCart(productId, quantity);
    });
});
</script>

<?php
// Footer ni include qilish
$footer_path = __DIR__ . '/includes/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
} else {
    echo "</main></body></html>";
}
?>
