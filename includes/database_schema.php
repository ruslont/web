<?php
// includes/database_schema.php
// Database strukturasini yaratish

try {
    // Users jadvali
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phone TEXT NOT NULL UNIQUE,
        name TEXT,
        email TEXT,
        password TEXT,
        role TEXT DEFAULT 'user',
        email_verified INTEGER DEFAULT 0,
        phone_verified INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Kategoriyalar jadvali
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        image TEXT,
        slug TEXT UNIQUE,
        sort_order INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Mahsulotlar jadvali
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        short_description TEXT,
        price REAL NOT NULL,
        old_price REAL,
        category_id INTEGER,
        image TEXT,
        images TEXT,
        weight REAL,
        dimensions TEXT,
        burn_time INTEGER,
        wax_type TEXT,
        fragrance_notes TEXT,
        in_stock INTEGER DEFAULT 0,
        sku TEXT UNIQUE,
        seo_title TEXT,
        seo_description TEXT,
        seo_keywords TEXT,
        active INTEGER DEFAULT 1,
        featured INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )");
    
    // Demo ma'lumotlar qo'shish
    // ...
    
} catch (PDOException $e) {
    error_log("Database schema error: " . $e->getMessage());
}
?>
