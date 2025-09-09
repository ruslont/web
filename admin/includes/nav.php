<nav class="admin-sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-bars"></i> Меню</h3>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <a href="index.php">
                <i class="fas fa-chart-line"></i>
                <span>Панель управления</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <a href="orders.php">
                <i class="fas fa-shopping-cart"></i>
                <span>Заказы</span>
                <span class="badge"><?php 
                    $pending = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
                    echo $pending > 0 ? $pending : '';
                ?></span>
            </a>
        </li>
        
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <a href="products.php">
                <i class="fas fa-box"></i>
                <span>Товары</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <a href="categories.php">
                <i class="fas fa-folder"></i>
                <span>Категории</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
            <a href="analytics.php">
                <i class="fas fa-chart-bar"></i>
                <span>Аналитика</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'seo.php' ? 'active' : ''; ?>">
            <a href="seo.php">
                <i class="fas fa-search"></i>
                <span>SEO</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <a href="settings.php">
                <i class="fas fa-cog"></i>
                <span>Настройки</span>
            </a>
        </li>
        
        <li class="menu-divider">
            <span>Интеграции</span>
        </li>
        
        <li class="menu-item">
            <a href="settings.php#payment">
                <i class="fas fa-credit-card"></i>
                <span>ЮKassa</span>
            </a>
        </li>
        
        <li class="menu-item">
            <a href="settings.php#delivery">
                <i class="fas fa-truck"></i>
                <span>Доставка</span>
            </a>
        </li>
        
        <li class="menu-item">
            <a href="settings.php#notifications">
                <i class="fas fa-bell"></i>
                <span>Уведомления</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="system-info">
            <p>Версия: 1.0.0</p>
            <p>PHP: <?php echo PHP_VERSION; ?></p>
        </div>
    </div>
</nav>
