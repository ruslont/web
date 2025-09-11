<?php
// Xatolarni ko'rsatish
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sessionni boshlash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SITE_URL ni sozlash
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost:8000/');
}

// Database ulanishi
try {
    $db = new PDO('sqlite:' . __DIR__ . '/db/elita_sham.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Database strukturasini yaratish (agar mavjud bo'lmasa)
    initDatabase($db);
    
    // Mahsulotlarni olish
    $products = [];
    try {
        $stmt = $db->query("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.in_stock > 0 
            ORDER BY p.created_at DESC 
            LIMIT 8
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Mahsulotlarni olishda xato: " . $e->getMessage());
    }
    
    // Kategoriyalarni olish
    $categories = [];
    try {
        $stmt = $db->query("SELECT * FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Kategoriyalarni olishda xato: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    error_log("Database ulanish xatosi: " . $e->getMessage());
    $products = [];
    $categories = [];
}

// Database strukturasini yaratish funksiyasi
function initDatabase($db) {
    try {
        // Users jadvali
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone TEXT NOT NULL UNIQUE,
            name TEXT,
            email TEXT,
            password TEXT,
            role TEXT DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Kategoriyalar jadvali
        $db->exec("CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            image TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Mahsulotlar jadvali
        $db->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            category_id INTEGER,
            image TEXT,
            weight REAL,
            burn_time INTEGER,
            wax_type TEXT,
            fragrance_notes TEXT,
            in_stock INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )");
        
        // Demo ma'lumotlarni qo'shish
        addDemoData($db);
        
    } catch (PDOException $e) {
        error_log("Database yaratish xatosi: " . $e->getMessage());
    }
}

// Demo ma'lumotlar qo'shish funksiyasi
function addDemoData($db) {
    // Kategoriyalarni tekshirish
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // Demo kategoriyalar
        $categories = [
            ['Фигурные свечи', 'Эксклюзивные формы и дизайны'],
            ['Ароматические', 'Изысканные парфюмерные композиции'],
            ['Подсвечники', 'Роскошные аксессуары премиум-класса'],
            ['Подарочные наборы', 'Готовые решения для особых случаев']
        ];
        
        foreach ($categories as $category) {
            $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$category[0], $category[1]]);
        }
        
        // Demo mahsulotlar
        $products = [
            ['Роза элеганс', 'Изысканная свеча в форме розы с ароматом ванили и бергамота', 4500.00, 1, 'rose.jpg', 0.5, 40, 'Соевый воск', 'Ваниль, Бергамот, Жасмин', 15],
            ['Лотос гармонии', 'Свеча в форме лотоса с успокаивающим ароматом лаванды', 3800.00, 1, 'lotus.jpg', 0.4, 35, 'Соевый воск', 'Лаванда, Сандал, Иланг-иланг', 12],
            ['Цитрусовый бриз', 'Ароматическая свеча с освежающим цитрусовым ароматом', 3200.00, 2, 'citrus.jpg', 0.6, 50, 'Соевый воск', 'Апельсин, Лимон, Бергамот', 20],
            ['Древесный амбар', 'Свеча с теплым древесным ароматом для уютной атмосферы', 3500.00, 2, 'wood.jpg', 0.6, 55, 'Соевый воск', 'Сандал, Кедр, Пачули', 18],
            ['Хрустальный подсвечник', 'Элегантный хрустальный подсвечник для создания особой атмосферы', 6200.00, 3, 'crystal.jpg', 0.8, NULL, NULL, NULL, 8],
            ['Подарочный набор "Романтика"', 'Набор из двух свечей и подсвечника для романтического вечера', 8900.00, 4, 'romance.jpg', 1.2, NULL, NULL, NULL, 10]
        ];
        
        foreach ($products as $product) {
            $stmt = $db->prepare("INSERT INTO products (name, description, price, category_id, image, weight, burn_time, wax_type, fragrance_notes, in_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($product);
        }
        
        // Admin user qo'shish
        $stmt = $db->prepare("INSERT INTO users (phone, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['+79999999999', 'Администратор', 'admin@elita-sham.ru', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    }
}

// Narxni formatlash funksiyasi
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' руб.';
}

// URL yasash funksiyasi
function url($path = '') {
    return SITE_URL . ltrim($path, '/');
}

// Savatdagi mahsulotlar sonini hisoblash
function getCartCount() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return array_sum($_SESSION['cart']);
    }
    return 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elita Sham - Элитные Авторские Свечи</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <style>
        /* Asosiy stillar */
        :root {
            --primary-color: #c8a97e;
            --secondary-color: #2c2c2c;
            --accent-color: #d4af37;
            --light-color: #f8f5f0;
            --dark-color: #1a1a1a;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; line-height: 1.6; background-color: var(--light-color); color: var(--dark-color); }
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        
        /* Header */
        header { background-color: rgba(255, 255, 255, 0.95); box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000; }
        header .container { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .logo h1 { color: var(--primary-color); font-size: 28px; font-family: 'Georgia', serif; }
        nav ul { display: flex; list-style: none; }
        nav ul li { margin: 0 15px; }
        nav ul li a { text-decoration: none; color: var(--dark-color); font-weight: 500; transition: color 0.3s; }
        nav ul li a:hover { color: var(--primary-color); }
        .auth-buttons .btn { margin-left: 10px; }
        
        /* Buttons */
        .btn { display: inline-block; padding: 12px 24px; background-color: var(--primary-color); color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; transition: all 0.3s ease; font-weight: 500; }
        .btn:hover { background-color: #b5986b; transform: translateY(-2px); }
        .btn-primary { background-color: var(--accent-color); }
        .btn-primary:hover { background-color: #c19b2e; }
        .btn-outline { background-color: transparent; border: 2px solid var(--primary-color); color: var(--primary-color); }
        .btn-outline:hover { background-color: var(--primary-color); color: white; }
        
        /* Hero section */
        .hero { background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?php echo url('assets/images/hero-bg.jpg'); ?>') no-repeat center center/cover; height: 100vh; display: flex; align-items: center; text-align: center; color: white; }
        .hero h1 { font-family: 'Georgia', serif; font-size: 3.5rem; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .hero p { font-size: 1.3rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        
        /* Products */
        .featured-products { padding: 80px 0; background-color: white; }
        .featured-products h2 { text-align: center; font-family: 'Georgia', serif; font-size: 2.5rem; margin-bottom: 50px; color: var(--secondary-color); }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 40px; }
        .product-card { background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .product-card img { width: 100%; height: 250px; object-fit: cover; transition: transform 0.3s ease; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; }
        .product-card:hover img { transform: scale(1.05); }
        .product-info { padding: 20px; }
        .product-card h3 { font-size: 1.2rem; margin-bottom: 10px; color: var(--secondary-color); }
        .product-card .price { font-weight: bold; color: var(--primary-color); font-size: 1.3rem; margin-bottom: 15px; }
        .product-actions { display: flex; gap: 10px; justify-content: space-between; }
        
        /* Categories */
        .categories { padding: 80px 0; background-color: var(--light-color); }
        .categories h2 { text-align: center; font-family: 'Georgia', serif; font-size: 2.5rem; margin-bottom: 50px; color: var(--secondary-color); }
        .categories-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .category-card { background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.3s ease; }
        .category-card:hover { transform: translateY(-5px); }
        .category-icon { font-size: 48px; margin-bottom: 20px; }
        .category-card h3 { font-family: 'Georgia', serif; margin-bottom: 15px; font-size: 1.5rem; color: var(--secondary-color); }
        .category-card p { margin-bottom: 20px; color: #666; }
        
        /* Features */
        .features { padding: 80px 0; background: white; }
        .features .container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; }
        .feature { text-align: center; }
        .feature-icon { font-size: 48px; margin-bottom: 20px; color: var(--primary-color); }
        .feature h3 { font-family: 'Georgia', serif; margin-bottom: 15px; font-size: 1.5rem; color: var(--secondary-color); }
        .feature p { color: #666; }
        
        /* Footer */
        footer { background-color: var(--secondary-color); color: white; padding: 60px 0 0; }
        footer .container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; }
        .footer-section h3 { font-family: 'Georgia', serif; margin-bottom: 20px; font-size: 1.5rem; color: var(--primary-color); }
        .footer-section p { margin-bottom: 10px; line-height: 1.8; }
        .payment-methods { display: flex; flex-direction: column; gap: 8px; }
        .payment-methods span { padding: 5px 0; }
        .social-links { display: flex; flex-direction: column; gap: 10px; }
        .social-links a { color: white; text-decoration: none; transition: color 0.3s; }
        .social-links a:hover { color: var(--primary-color); }
        .copyright { text-align: center; padding: 25px 0; margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem; }
        
        /* Responsive design */
        @media (max-width: 768px) {
            header .container { flex-direction: column; gap: 15px; }
            nav ul { flex-wrap: wrap; justify-content: center; }
            nav ul li { margin: 5px 10px; }
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1.1rem; }
            .products-grid, .categories-grid { grid-template-columns: 1fr; }
            footer .container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="logo">
                <h1>ELITA SHAM</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo url('/'); ?>">Главная</a></li>
                    <li><a href="<?php echo url('/catalog'); ?>">Каталог</a></li>
                    <li><a href="<?php echo url('/about'); ?>">О нас</a></li>
                    <li><a href="<?php echo url('/contacts'); ?>">Контакты</a></li>
                    <li><a href="<?php echo url('/cart'); ?>" class="cart-link">Корзина (<span id="cart-count"><?php echo getCartCount(); ?></span>)</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo url('/admin'); ?>" class="btn">Личный кабинет</a>
                    <a href="<?php echo url('/logout'); ?>" class="btn">Выйти</a>
                <?php else: ?>
                    <a href="<?php echo url('/login'); ?>" class="btn">Войти</a>
                    <a href="<?php echo url('/register'); ?>" class="btn btn-outline">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero section -->
    <section class="hero">
        <div class="container">
            <h1>Элитные Авторские Свечи</h1>
            <p>Откройте для себя мир изысканных ароматов и элегантного дизайна с нашими эксклюзивными свечами ручной работы</p>
            <a href="<?php echo url('/catalog'); ?>" class="btn btn-primary">Открыть коллекцию</a>
        </div>
    </section>

    <!-- Features section -->
    <section class="features">
        <div class="container">
            <div class="feature">
                <div class="feature-icon">👑</div>
                <h3>Элитное качество</h3>
                <p>Только премиальные материалы и безупречное исполнение</p>
            </div>
            <div class="feature">
                <div class="feature-icon">✋</div>
                <h3>Ручная работа</h3>
                <p>Каждая свеча создается мастером индивидуально</p>
            </div>
            <div class="feature">
                <div class="feature-icon">🎯</div>
                <h3>VIP сервис</h3>
                <p>Персональный подход и эксклюзивное обслуживание</p>
            </div>
        </div>
    </section>

    <!-- Featured products -->
    <section class="featured-products">
        <div class="container">
            <h2>Популярные товары</h2>
            <div class="products-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <div style="width:100%; height:250px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#666; font-weight:bold;">
                            <?php echo $product['image'] ? 'Rasm: ' . $product['image'] : 'Rasm'; ?>
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price"><?php echo formatPrice($product['price']); ?></p>
                            <div class="product-actions">
                                <button class="btn add-to-cart" data-product-id="<?php echo $product['id']; ?>">В корзину</button>
                                <a href="<?php echo url('/product?id=' . $product['id']); ?>" class="btn btn-outline">Подробнее</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <h3>Магазин готовится к открытию</h3>
                        <p>Скоро здесь появятся эксклюзивные свечи ручной работы</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Categories section -->
    <section class="categories">
        <div class="container">
            <h2>Наши коллекции</h2>
            <div class="categories-grid">
                <?php if (!empty($categories)): ?>
                    <?php foreach($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-icon">🌹</div>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                        <a href="<?php echo url('/catalog?category=' . $category['id']); ?>" class="btn btn-outline">Смотреть</a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <h3>Коллекции готовятся</h3>
                        <p>Скоро здесь появятся наши эксклюзивные коллекции</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-section">
                <h3>ELITA SHAM</h3>
                <p>Эксклюзивные авторские свечи премиум-класса. Создаём атмосферу роскоши и уюта в вашем доме.</p>
            </div>
            <div class="footer-section">
                <h3>Контакты</h3>
                <p>📞 +7 (999) 999-99-99</p>
                <p>📧 info@elita-sham.ru</p>
                <p>📍 Москва, Россия</p>
            </div>
            <div class="footer-section">
                <h3>Способы оплаты</h3>
                <div class="payment-methods">
                    <span>💳 Visa</span>
                    <span>💳 MasterCard</span>
                    <span>💳 МИР</span>
                    <span>📱 СБП</span>
                </div>
            </div>
            <div class="footer-section">
                <h3>Мы в соцсетях</h3>
                <div class="social-links">
                    <a href="#">📸 Instagram</a>
                    <a href="#">🎵 TikTok</a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>© 2024 ELITA SHAM. Все права защищены.</p>
        </div>
    </footer>

    
<link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">

</body>
</html>
