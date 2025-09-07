<?php
require_once 'config.php';

class Database {
    public $conn;

    public function getConnection() {
        if ($this->conn === null) {
            try {
                // SQLite database faylini yaratish
                $db_dir = dirname(DB_PATH);
                if (!is_dir($db_dir)) {
                    mkdir($db_dir, 0755, true);
                }
                
                $this->conn = new PDO('sqlite:' . DB_PATH);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Database strukturasini yaratish
                $this->initDatabase();
                
            } catch(PDOException $exception) {
                echo "Database connection error: " . $exception->getMessage();
                exit();
            }
        }
        return $this->conn;
    }
    
    public function initDatabase() {
        try {
            // Users jadvali
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    phone TEXT NOT NULL UNIQUE,
                    name TEXT,
                    email TEXT,
                    password TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Categories jadvali
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT,
                    image TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Products jadvali
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS products (
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
                )
            ");
            
            // Orders jadvali
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_number TEXT NOT NULL UNIQUE,
                    user_id INTEGER,
                    customer_name TEXT NOT NULL,
                    customer_phone TEXT NOT NULL,
                    customer_email TEXT,
                    delivery_address TEXT,
                    total_amount REAL NOT NULL,
                    status TEXT DEFAULT 'pending',
                    delivery_method TEXT,
                    payment_method TEXT,
                    tracking_number TEXT,
                    comments TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )
            ");
            
            // Order items jadvali
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS order_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id INTEGER,
                    product_id INTEGER,
                    quantity INTEGER NOT NULL,
                    price REAL NOT NULL,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
                )
            ");
            
            // Demo ma'lumotlar qo'shish
            $this->addDemoData();
            
        } catch(PDOException $exception) {
            echo "Database initialization error: " . $exception->getMessage();
        }
    }
    
    private function addDemoData() {
        // Kategoriyalarni tekshirish
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM categories");
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
                $stmt = $this->conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
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
                $stmt = $this->conn->prepare("INSERT INTO products (name, description, price, category_id, image, weight, burn_time, wax_type, fragrance_notes, in_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute($product);
            }
        }
    }
}
?>
