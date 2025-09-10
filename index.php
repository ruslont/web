<?php
// Xatolarni ko'rsatish
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sessionni boshlash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SITE_URL ni to'g'ri sozlash
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost:8000/');
}

define('DB_PATH', __DIR__ . '/db/elita_sham.db');

// Router - barcha so'rovlarni boshqarish
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Asosiy sahifalar
$routes = [
    '/' => 'home.php',
    '/index.php' => 'home.php',
    '/catalog' => 'catalog.php',
    '/catalog.php' => 'catalog.php',
    '/product' => 'product.php',
    '/product.php' => 'product.php',
    '/cart' => 'cart.php',
    '/cart.php' => 'cart.php',
    '/checkout' => 'checkout.php',
    '/checkout.php' => 'checkout.php',
    '/login' => 'login.php',
    '/login.php' => 'login.php',
    '/register' => 'register.php',
    '/register.php' => 'register.php',
    '/track' => 'track.php',
    '/track.php' => 'track.php',
    '/about' => 'about.php',
    '/about.php' => 'about.php',
    '/contacts' => 'contacts.php',
    '/contacts.php' => 'contacts.php',
    '/admin' => 'admin/index.php',
    '/admin/index.php' => 'admin/index.php',
    '/admin/products' => 'admin/products.php',
    '/admin/products.php' => 'admin/products.php',
    '/admin/orders' => 'admin/orders.php',
    '/admin/orders.php' => 'admin/orders.php',
    '/admin/categories' => 'admin/categories.php',
    '/admin/categories.php' => 'admin/categories.php'
];

// Router logikasi - path ni tozalash
$clean_path = rtrim($path, '/');
if ($clean_path === '') {
    $clean_path = '/';
}

// Debug ma'lumotlari
error_log("Request URI: " . $request_uri);
error_log("Clean path: " . $clean_path);
error_log("Route exists: " . (isset($routes[$clean_path]) ? 'YES' : 'NO'));

// Route'ni topish
if (isset($routes[$clean_path])) {
    $page_file = $routes[$clean_path];
    
    error_log("Trying to load: " . $page_file);
    error_log("File exists: " . (file_exists($page_file) ? 'YES' : 'NO'));
    
    // Fayl mavjudligini tekshirish
    if (file_exists($page_file)) {
        require_once $page_file;
        exit;
    } else {
        // Fayl mavjud emas
        error_log("File not found: " . $page_file);
        // Fayl yaratishga urinib ko'ramiz
        if ($page_file === 'home.php') {
            createHomePage();
            require_once 'home.php';
            exit;
        }
    }
}

// Agar route topilmasa yoki fayl mavjud bo'lmasa, 404 xatosini ko'rsatish
header("HTTP/1.0 404 Not Found");

// Home.php faylini yaratish funksiyasi
function createHomePage() {
    $home_content = '<?php
// Database ulanishi
try {
    $db = new PDO("sqlite:" . __DIR__ . "/db/elita_sham.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mahsulotlarni olish
    $products = [];
    try {
        $stmt = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.in_stock > 0 ORDER BY p.created_at DESC LIMIT 8");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Products error: " . $e->getMessage());
    }
    
    // Kategoriyalarni olish
    $categories = [];
    try {
        $stmt = $db->query("SELECT * FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Categories error: " . $e->getMessage());
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $products = [];
    $categories = [];
}

// Narxni formatlash
function formatPrice($price) {
    return number_format($price, 0, ",", " ") . " руб.";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elita Sham - Элитные Авторские Свечи</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background-color: #f8f5f0; color: #333; }
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        
        /* Header */
        header { background-color: rgba(255, 255, 255, 0.95); box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000; }
        header .container { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .logo h1 { color: #c8a97e; font-size: 28px; }
        nav ul { display: flex; list-style: none; }
        nav ul li { margin: 0 15px; }
        nav ul li a { text-decoration: none; color: #333; font-weight: 500; transition: color 0.3s; }
        nav ul li a:hover { color: #c8a97e; }
        .auth-buttons .btn { margin-left: 10px; }
        
        /* Buttons */
        .btn { display: inline-block; padding: 10px 20px; background-color: #c8a97e; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; transition: background-color 0.3s; }
        .btn:hover { background-color: #b5986b; }
        .btn-primary { background-color: #d4af37; }
        .btn-primary:hover { background-color: #c19b2e; }
        .btn-outline { background-color: transparent; border: 1px solid #c8a97e; color: #c8a97e; }
        .btn-outline:hover { background-color: #c8a97e; color: white; }
        
        /* Hero section */
        .hero { background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(\'assets/images/hero-bg.jpg\') no-repeat center center/cover; height: 80vh; display: flex; align-items: center; text-align: center; color: white; margin-top: 80px; }
        .hero h1 { font-size: 48px; margin-bottom: 20px; }
        .hero p { font-size: 20px; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto; }
        
        /* Products */
        .featured-products { padding: 80px 0; }
        .featured-products h2 { text-align: center; font-size: 36px; margin-bottom: 50px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px; }
        .product-card { background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .product-card:hover { transform: translateY(-5px); }
        .product-card img { width: 100%; height: 200px; object-fit: cover; background: #eee; }
        .product-card h3 { padding: 15px; font-size: 18px; }
        .product-card .price { padding: 0 15px; font-weight: bold; color: #c8a97e; font-size: 20px; }
        .product-card .btn { margin: 15px; }
        
        /* Footer */
        footer { background-color: #2c2c2c; color: white; padding: 60px 0 0; }
        footer .container { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px; }
        .footer-section h3 { margin-bottom: 20px; font-size: 22px; }
        .footer-section p { margin-bottom: 10px; }
        .copyright { text-align: center; padding: 20px 0; margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1>ELITA SHAM</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="/">Главная</a></li>
                    <li><a href="/catalog">Каталог</a></li>
                    <li><a href="/about">О нас</a></li>
                    <li><a href="/contacts">Контакты</a></li>
                    <li><a href="/cart" class="cart-link">Корзина (0)</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <a href="/login" class="btn">Войти</a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Элитные Авторские Свечи</h1>
            <p>Откройте для себя мир изысканных ароматов и элегантного дизайна с нашими эксклюзивными свечами ручной работы</p>
            <a href="/catalog" class="btn btn-primary">Открыть коллекцию</a>
        </div>
    </section>

    <section class="featured-products">
        <div class="container">
            <h2>Популярные товары</h2>
            <div class="products-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <div style="width:100%; height:200px; background:#eee; display:flex; align-items:center; justify-content:center; color:#666;">Rasm</div>
                        <h3><?php echo htmlspecialchars($product[\'name\']); ?></h3>
                        <p class="price"><?php echo formatPrice($product[\'price\']); ?></p>
                        <button class="btn add-to-cart">В корзину</button>
                        <a href="/product?id=<?php echo $product[\'id\']; ?>" class="btn btn-outline">Подробнее</a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <h3>Mahsulotlar topilmadi</h3>
                        <p>Database bo\'sh yoki xatolik yuz berdi</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-section">
                <h3>ELITA SHAM</h3>
                <p>Эксклюзивные авторские свечи премиум-класса</p>
            </div>
            <div class="footer-section">
                <h3>Контакты</h3>
                <p>📞 +7 (999) 999-99-99</p>
                <p>📧 info@elita-sham.ru</p>
            </div>
            <div class="footer-section">
                <h3>Способы оплаты</h3>
                <div class="payment-methods">
                    <span>💳 Visa</span>
                    <span>💳 MasterCard</span>
                    <span>💳 МИР</span>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>© 2024 ELITA SHAM. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>';

    file_put_contents('home.php', $home_content);
    error_log("home.php fayli yaratildi");
}

// 404 sahifasini ko'rsatish
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Sahifa topilmadi</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }
        .error-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: translateY(-3px);
        }
        .debug-info {
            margin-top: 30px;
            font-size: 0.9rem;
            opacity: 0.8;
            text-align: left;
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <p>Siz qidirgan sahifa mavjud emas</p>
        <a href="<?php echo SITE_URL; ?>" class="btn">Bosh sahifaga qaytish</a>
        
        <div class="debug-info">
            <p><strong>Debug ma'lumotlari:</strong></p>
            <p>So'rov: <?php echo htmlspecialchars($request_uri); ?></p>
            <p>Path: <?php echo htmlspecialchars($path); ?></p>
            <p>Toza path: <?php echo htmlspecialchars($clean_path); ?></p>
            <p>SITE_URL: <?php echo SITE_URL; ?></p>
            <p>Route mavjud: <?php echo isset($routes[$clean_path]) ? 'HA' : 'YO\'Q'; ?></p>
            <?php if (isset($routes[$clean_path])): ?>
            <p>Fayl nomi: <?php echo $routes[$clean_path]; ?></p>
            <p>Fayl mavjud: <?php echo file_exists($routes[$clean_path]) ? 'HA' : 'YO\'Q'; ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
