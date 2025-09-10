<?php
// Xavfsizlik funksiyasi - ma'lumotlarni tozalash
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// URL yasash
function url($path = '') {
    if (!defined('SITE_URL')) {
        return 'http://localhost:8000/' . ltrim($path, '/');
    }
    return SITE_URL . ltrim($path, '/');
}

// Asset fayllariga yo'l
function asset($path) {
    return url('assets/' . ltrim($path, '/'));
}

// Database ulanishi
function getDBConnection() {
    try {
        if (!file_exists(DB_PATH)) {
            throw new Exception("Database fayli topilmadi");
        }
        
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $db;
    } catch (PDOException $e) {
        error_log("Database ulanish xatosi: " . $e->getMessage());
        return null;
    } catch (Exception $e) {
        error_log("Database xatosi: " . $e->getMessage());
        return null;
    }
}

// SQLite uchun random funksiya
function sqliteRandom() {
    return "ABS(RANDOM()) % 1000000";
}

// Narxni formatlash
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' руб.';
}

// Xatolarni ko'rsatish
function displayError($message, $details = '') {
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo "<div style='color: red; padding: 10px; margin: 10px; border: 1px solid red; border-radius: 5px;'>";
        echo "<strong>⚠️ Xato:</strong> " . htmlspecialchars($message);
        if ($details) {
            echo "<br><small>" . htmlspecialchars($details) . "</small>";
        }
        echo "</div>";
    }
    error_log("Xato: " . $message . " | Tafsilotlar: " . $details);
}

// Muvaffaqiyat xabarini ko'rsatish
function displaySuccess($message) {
    echo "<div style='color: green; padding: 10px; margin: 10px; border: 1px solid green; border-radius: 5px;'>";
    echo "✅ " . htmlspecialchars($message);
    echo "</div>";
}

// Fayl mavjudligini tekshirish
function includeIfExists($file) {
    if (file_exists($file)) {
        include $file;
        return true;
    }
    error_log("Fayl topilmadi: " . $file);
    return false;
}

// Redirect qilish
function redirect($path, $status_code = 302) {
    header('Location: ' . url($path), true, $status_code);
    exit;
}

// CSRF token yaratish
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF tokenni tekshirish
function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Savatdagi mahsulotlar soni
function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'] ?? 0;
        }
    }
    return $count;
}

// Rasmni ko'rsatish
function displayImage($image_path, $alt = "Image", $class = "") {
    if (file_exists(__DIR__ . '/../assets/images/' . $image_path)) {
        return '<img src="' . asset('images/' . $image_path) . '" alt="' . htmlspecialchars($alt) . '" class="' . $class . '">';
    } else {
        return '<div class="image-placeholder ' . $class . '">' . htmlspecialchars($alt) . '</div>';
    }
}
?>
