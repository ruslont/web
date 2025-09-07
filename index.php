<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Mahsulotlarni olish
$db = new Database();
$conn = $db->getConnection();

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.in_stock > 0 ORDER BY p.created_at DESC LIMIT 8";
$stmt = $conn->prepare($query);
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elita Sham - Элитные Авторские Свечи</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Sarlavha qismi -->
    <header>
        <div class="container">
            <div class="logo">
                <h1>ELITA SHAM</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="catalog.php">Каталог</a></li>
                    <li><a href="#about">О нас</a></li>
                    <li><a href="#contacts">Контакты</a></li>
                    <li><a href="cart.php" class="cart-link">Корзина (<span id="cart-count">0</span>)</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="admin/index.php" class="btn">Личный кабинет</a>
                    <a href="logout.php" class="btn">Выйти</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Войти</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero bo'limi -->
    <section class="hero">
        <div class="container">
            <h1>Элитные Авторские Свечи</h1>
            <p>Откройте для себя мир изысканных ароматов и элегантного дизайна с нашими эксклюзивными свечами ручной работы</p>
            <a href="catalog.php" class="btn btn-primary">Открыть коллекцию</a>
        </div>
    </section>

    <!-- Afzalliklar bo'limi -->
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

    <!-- Mashhur mahsulotlar -->
    <section class="featured-products">
        <div class="container">
            <h2>Популярные товары</h2>
            <div class="products-grid">
                <?php foreach($featured_products as $product): ?>
                <div class="product-card">
                    <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                    <h3><?php echo $product['name']; ?></h3>
                    <p class="price"><?php echo number_format($product['price'], 0, ',', ' '); ?> руб.</p>
                    <button class="btn add-to-cart" data-product-id="<?php echo $product['id']; ?>">В корзину</button>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">Подробнее</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Kategoriyalar bo'limi -->
    <section class="categories">
        <div class="container">
            <h2>Наши коллекции</h2>
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-icon">🌹</div>
                    <h3>Фигурные свечи</h3>
                    <p>Эксклюзивные формы и дизайны</p>
                    <a href="catalog.php?category=1" class="btn btn-outline">Смотреть</a>
                </div>
                <div class="category-card">
                    <div class="category-icon">🌸</div>
                    <h3>Ароматические</h3>
                    <p>Изысканные парфюмерные композиции</p>
                    <a href="catalog.php?category=2" class="btn btn-outline">Смотреть</a>
                </div>
                <div class="category-card">
                    <div class="category-icon">🏛</div>
                    <h3>Подсвечники</h3>
                    <p>Роскошные аксессуары премиум-класса</p>
                    <a href="catalog.php?category=3" class="btn btn-outline">Смотреть</a>
                </div>
                <div class="category-card">
                    <div class="category-icon">🎁</div>
                    <h3>Подарочные наборы</h3>
                    <p>Готовые решения для особых случаев</p>
                    <a href="catalog.php?category=4" class="btn btn-outline">Смотреть</a>
                </div>
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

    <script src="assets/js/script.js"></script>
</body>
</html>
