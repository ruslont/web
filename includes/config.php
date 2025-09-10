<?php
// Xatolarni boshqarish
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Sessionni boshlash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Doimiy o'zgaruvchilarni faqat bir marta aniqlash
if (!defined('SITE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? '8000';
    
    // Portni portga qo'shish agar standart bo'lmasa
    $port_suffix = ($port !== '80' && $port !== '443') ? ":$port" : "";
    
    define('SITE_URL', $protocol . '://' . $host . $port_suffix . '/');
}

if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/../db/elita_sham.db');
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Elita Sham');
    define('SITE_DESCRIPTION', 'Элитные Авторские Свечи');
}

// Development rejimi
define('DEVELOPMENT_MODE', true);

// Xavfsizlik sozlamalari
define('CSRF_TOKEN_SECRET', 'elita_sham_secret_2024');

// GD extension mavjudligini tekshirish va sozlash
define('GD_AVAILABLE', extension_loaded('gd'));

// Database mavjudligini tekshirish
function checkDatabase() {
    if (!file_exists(DB_PATH)) {
        // Database papkasini yaratish
        $db_dir = dirname(DB_PATH);
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        // Yangi database yaratish
        try {
            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            initializeDatabase($db);
            return true;
        } catch (PDOException $e) {
            error_log("Database yaratish xatosi: " . $e->getMessage());
            return false;
        }
    }
    return is_writable(DB_PATH);
}

// Database strukturasini yaratish
function initializeDatabase($db) {
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
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )");
        
        // Demo ma'lumotlar
        addDemoData($db);
        
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
    }
}

// Demo ma'lumotlar qo'shish
function addDemoData($db) {
    // Kategoriyalarni tekshirish
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
            ['Цитрусовый бриз', 'Ароматическая свеча с освежающим цитрусовым ароматом', 3200.00, 2, 'citrus.jpg', 0.6, 50, 'Соевый воск', 'Апельсин, Лимон, Бергамот', 20]
        ];
        
        foreach ($products as $product) {
            $stmt = $db->prepare("INSERT INTO products (name, description, price, category_id, image, weight, burn_time, wax_type, fragrance_notes, in_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($product);
        }
    }
}

// Database mavjudligini tekshirish
checkDatabase();
?>
