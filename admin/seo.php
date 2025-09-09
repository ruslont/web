<?php
// SEO sozlamalari boshqaruvi
class SEOManager {
    public static function generateSitemap() {
        $db = new Database();
        $conn = $db->getConnection();
        
        $baseUrl = SITE_URL;
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Asosiy sahifalar
        $pages = ['', 'catalog', 'about', 'contacts', 'login'];
        foreach ($pages as $page) {
            $sitemap .= self::createUrlElement($baseUrl . $page, 'daily', 1.0);
        }
        
        // Mahsulotlar
        $products = $conn->query("SELECT id, updated_at FROM products")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $product) {
            $sitemap .= self::createUrlElement($baseUrl . 'product.php?id=' . $product['id'], 'weekly', 0.8);
        }
        
        // Kategoriyalar
        $categories = $conn->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categories as $category) {
            $sitemap .= self::createUrlElement($baseUrl . 'catalog.php?category=' . $category['id'], 'weekly', 0.7);
        }
        
        $sitemap .= '</urlset>';
        
        file_put_contents('../sitemap.xml', $sitemap);
        return true;
    }
    
    private static function createUrlElement($url, $changefreq, $priority) {
        return "
        <url>
            <loc>{$url}</loc>
            <changefreq>{$changefreq}</changefreq>
            <priority>{$priority}</priority>
        </url>";
    }
    
    public static function generateRobotsTxt() {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /includes/\n";
        $content .= "Sitemap: " . SITE_URL . "sitemap.xml\n";
        
        file_put_contents('../robots.txt', $content);
        return true;
    }
}
?>

<!-- Admin panelda SEO boshqaruvi -->
<div class="seo-management">
    <h2>SEO Настройки</h2>
    
    <div class="seo-actions">
        <button class="btn" onclick="generateSitemap()">🔄 Сгенерировать sitemap.xml</button>
        <button class="btn" onclick="generateRobots()">📝 Сгенерировать robots.txt</button>
    </div>
    
    <div class="meta-tags-management">
        <h3>Мета-теги для страниц</h3>
        
        <?php foreach (['index', 'catalog', 'product'] as $page): ?>
        <div class="meta-form">
            <h4><?= ucfirst($page) ?> страница</h4>
            <input type="text" placeholder="Title" name="<?= $page ?>_title">
            <textarea placeholder="Description" name="<?= $page ?>_description"></textarea>
            <input type="text" placeholder="Keywords" name="<?= $page ?>_keywords">
            <button class="btn btn-sm">💾 Сохранить</button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
