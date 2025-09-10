<?php
// Header fayli - barcha sahifalar uchun umumiy
if (!isset($page_title)) {
    $page_title = SITE_NAME;
}

// Cart sonini hisoblash
$cart_count = getCartCount();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <style>
        /* Asosiy stillar */
        :root {
            --primary-color: #c8a97e;
            --secondary-color: #2c2c2c;
            --accent-color: #d4af37;
            --light-color: #f8f5f0;
            --dark-color: #1a1a1a;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; line-height: 1.6; background-color: var(--light-color); color: var(--dark-color); }
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        
        /* Header */
        header { background-color: rgba(255, 255, 255, 0.95); box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000; }
        header .container { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .logo h1 { color: var(--primary-color); font-size: 28px; }
        nav ul { display: flex; list-style: none; }
        nav ul li { margin: 0 15px; }
        nav ul li a { text-decoration: none; color: var(--dark-color); font-weight: 500; transition: color 0.3s; }
        nav ul li a:hover { color: var(--primary-color); }
        .cart-link { display: flex; align-items: center; }
        .auth-buttons .btn { margin-left: 10px; }
        
        /* Buttons */
        .btn { display: inline-block; padding: 10px 20px; background-color: var(--primary-color); color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; transition: background-color 0.3s; }
        .btn:hover { background-color: #b5986b; }
        .btn-primary { background-color: var(--accent-color); }
        .btn-primary:hover { background-color: #c19b2e; }
        .btn-outline { background-color: transparent; border: 1px solid var(--primary-color); color: var(--primary-color); }
        .btn-outline:hover { background-color: var(--primary-color); color: white; }
        
        /* Main content */
        main { margin-top: 80px; min-height: calc(100vh - 200px); padding: 20px 0; }
        
        /* Image placeholder */
        .image-placeholder { background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; font-weight: bold; }
        
        /* Responsive */
        @media (max-width: 768px) {
            header .container { flex-direction: column; }
            nav ul { margin-top: 15px; flex-wrap: wrap; justify-content: center; }
            nav ul li { margin: 5px 10px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo url('/'); ?>">Главная</a></li>
                    <li><a href="<?php echo url('/catalog'); ?>">Каталог</a></li>
                    <li><a href="<?php echo url('/about'); ?>">О нас</a></li>
                    <li><a href="<?php echo url('/contacts'); ?>">Контакты</a></li>
                    <li><a href="<?php echo url('/cart'); ?>" class="cart-link">Корзина (<?php echo $cart_count; ?>)</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo url('/admin'); ?>" class="btn">Личный кабинет</a>
                    <a href="<?php echo url('/logout'); ?>" class="btn">Выйти</a>
                <?php else: ?>
                    <a href="<?php echo url('/login'); ?>" class="btn">Войти</a>
                    <a href="<?php echo url('/register'); ?>" class="btn btn-outline">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container">
