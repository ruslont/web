CREATE DATABASE IF NOT EXISTS elita_sham CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE elita_sham;

-- Foydalanuvchilar jadvali
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kategoriyalar jadvali
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255)
);

-- Mahsulotlar jadvali
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT,
    image VARCHAR(255),
    weight DECIMAL(10, 2),
    burn_time INT, -- Yonish vaqti soatlarda
    wax_type VARCHAR(100), -- Momiq turi
    fragrance_notes TEXT, -- Hid izohlari
    in_stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Buyurtmalar jadvali
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(100),
    delivery_address TEXT,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    delivery_method VARCHAR(50),
    payment_method VARCHAR(50),
    tracking_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Buyurtma elementlari jadvali
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Savat elementlari jadvali (session-based, lekin ba'zi ma'lumotlarni saqlash uchun)
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Demo ma'lumotlar
INSERT INTO categories (name, description) VALUES 
('Фигурные свечи', 'Эксклюзивные формы и дизайны'),
('Ароматические', 'Изысканные парфюмерные композиции'),
('Подсвечники', 'Роскошные аксессуары премиум-класса'),
('Подарочные наборы', 'Готовые решения для особых случаев');

INSERT INTO products (name, description, price, category_id, image, weight, burn_time, wax_type, fragrance_notes, in_stock) VALUES
('Роза элеганс', 'Изысканная свеча в форме розы с ароматом ванили и бергамота', 4500.00, 1, 'rose.jpg', 0.5, 40, 'Соевый воск', 'Ваниль, Бергамот, Жасмин', 15),
('Лотос гармонии', 'Свеча в форме лотоса с успокаивающим ароматом лаванды', 3800.00, 1, 'lotus.jpg', 0.4, 35, 'Соевый воск', 'Лаванда, Сандал, Иланг-иланг', 12),
('Цитрусовый бриз', 'Ароматическая свеча с освежающим цитрусовым ароматом', 3200.00, 2, 'citrus.jpg', 0.6, 50, 'Соевый воск', 'Апельсин, Лимон, Бергамот', 20),
('Древесный амбар', 'Свеча с теплым древесным ароматом для уютной атмосферы', 3500.00, 2, 'wood.jpg', 0.6, 55, 'Соевый воск', 'Сандал, Кедр, Пачули', 18),
('Хрустальный подсвечник', 'Элегантный хрустальный подсвечник для создания особой атмосферы', 6200.00, 3, 'crystal.jpg', 0.8, NULL, NULL, NULL, 8),
('Подарочный набор "Романтика"', 'Набор из двух свечей и подсвечника для романтического вечера', 8900.00, 4, 'romance.jpg', 1.2, NULL, NULL, NULL, 10);
