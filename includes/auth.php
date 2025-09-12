<?php
/**
 * auth.php
 * Admin panel uchun autentifikatsiya tekshiruv fayli
 */

// Sessiyani ishga tushiramiz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Agar foydalanuvchi login qilmagan bo‘lsa, login sahifasiga yuboramiz
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Xavfsizlik uchun sessiyani avtomatik yangilab turish
if (!isset($_SESSION['last_regen'])) {
    $_SESSION['last_regen'] = time();
} elseif (time() - $_SESSION['last_regen'] > 600) { // 10 daqiqa
    session_regenerate_id(true);
    $_SESSION['last_regen'] = time();
}

// Xavfsizlik: foydalanuvchi agentini tekshirish
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
} elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
    session_unset();
    session_destroy();
    header("Location: /login.php");
    exit;
}

// Xavfsizlik: IP-manzilni tekshirish (ixtiyoriy)
if (!isset($_SESSION['ip_address'])) {
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
} elseif ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    session_unset();
    session_destroy();
    header("Location: /login.php");
    exit;
}

// Agar foydalanuvchi roli ham tekshirilishi kerak bo‘lsa (masalan admin panel uchun):
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    die("⛔ Sizda admin panelga kirish huquqi yo‘q.");
}
?>
