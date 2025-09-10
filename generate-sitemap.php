<?php
// generate-sitemap.php - Dinamik sitemap yaratish

// Database ulanishi
try {
    $db = new PDO('sqlite:' . __DIR__ . '/db/elita_sham.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mahsulotlarni olish
    $products = $db->query("SELECT id, name, created_at FROM products WHERE in_stock > 0")->fetchAll(PDO::FETCH_ASSOC);
    
    // Kategoriyalarni olish
    $categories = $db->query("SELECT id, name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database ulanish xatosi: " . $e->getMessage());
}

// Sitemap yaratish
header('Content-Type: application/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Asosiy sahifalar
$main_pages = [
    '/' => ['daily', '1.0'],
    '/catalog' => ['weekly', '0.8'],
    '/about' => ['monthly', '0.6'],
    '/contacts' => ['monthly', '0.6'],
    '/login' => ['monthly', '0.4'],
    '/register' => ['monthly', '0.4']
];

foreach ($main_pages as $url => $params) {
    echo "    <url>\n";
    echo "        <loc>http://localhost:8000{$url}</loc>\n";
    echo "        <lastmod>2024-01-15</lastmod>\n";
    echo "        <changefreq>{$params[0]}</changefreq>\n";
    echo "        <priority>{$params[1]}</priority>\n";
    echo "    </url>\n";
}

// Kategoriyalar
foreach ($categories as $category) {
    echo "    <url>\n";
    echo "        <loc>http://localhost:8000/catalog?category={$category['id']}</loc>\n";
    echo "        <lastmod>2024-01-15</lastmod>\n";
    echo "        <changefreq>weekly</changefreq>\n";
    echo "        <priority>0.7</priority>\n";
    echo "    </url>\n";
}

// Mahsulotlar
foreach ($products as $product) {
    echo "    <url>\n";
    echo "        <loc>http://localhost:8000/product?id={$product['id']}</loc>\n";
    echo "        <lastmod>" . substr($product['created_at'], 0, 10) . "</lastmod>\n";
    echo "        <changefreq>monthly</changefreq>\n";
    echo "        <priority>0.6</priority>\n";
    echo "    </url>\n";
}

echo '</urlset>';
?>
