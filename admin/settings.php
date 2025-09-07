<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Foydalanuvchi admin ekanligini tekshirish
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Xatoliklar va muvaffaqiyat xabarlari
$errors = [];
$success = '';

// Kategoriya qo'shish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    
    if (empty($name)) {
        $errors[] = 'Введите название категории';
    } else {
        $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        
        if ($stmt->execute()) {
            $success = 'Категория успешно добавлена';
        } else {
            $errors[] = 'Ошибка при добавлении категории';
        }
    }
}

// Kategoriyani yangilash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $category_id = intval($_POST['category_id']);
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    
    if (empty($name)) {
        $errors[] = 'Введите название категории';
    } else {
        $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $success = 'Категория успешно обновлена';
        } else {
            $errors[] = 'Ошибка при обновлении категории';
        }
    }
}

// Kategoriyani o'chirish
if (isset($_GET['delete_category'])) {
    $category_id = intval($_GET['delete_category']);
    
    // Kategoriyaga tegishli mahsulotlar mavjudligini tekshirish
    $check_query = "SELECT COUNT(*) as product_count FROM products WHERE category_id = :category_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $product_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['product_count'];
    
    if ($product_count > 0) {
        $errors[] = 'Невозможно удалить категорию, так как в ней есть товары';
    } else {
        $delete_query = "DELETE FROM categories WHERE id = :id";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
        
        if ($delete_stmt->execute()) {
            $success = 'Категория успешно удалена';
        } else {
            $errors[] = 'Ошибка при удалении категории';
        }
    }
}

// API sozlamalarini yangilash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    // Bu yerda sozlamalarni saqlash logikasi qo'shiladi
    // Masalan, konfiguratsiya faylini yangilash yoki ma'lumotlar bazasida saqlash
    
    $success = 'Настройки успешно обновлены';
}

// Kategoriyalarni olish
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Tahrirlash uchun kategoriya
$edit_category = null;
if (isset($_GET['edit_category'])) {
    $category_id = intval($_GET['edit_category']);
    $query = "SELECT * FROM categories WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
    $stmt->execute();
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - Elita Sham</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Настройки системы</h1>
        </div>
        
        <?php include 'includes/nav.php'; ?>
        
        <div class="admin-content">
            <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="settings-tabs">
                <div class="tab-headers">
                    <button class="tab-header active" data-tab="categories">Категории</button>
                    <button class="tab-header" data-tab="api">API настройки</button>
                    <button class="tab-header" data-tab="general">Общие настройки</button>
                </div>
                
                <div class="tab-content active" id="categories-tab">
                    <h2>Управление категориями</h2>
                    
                    <!-- Kategoriya qo'shish/yangilash formasi -->
                    <div class="category-form">
                        <h3><?php echo $edit_category ? 'Редактирование категории' : 'Добавление категории'; ?></h3>
                        
                        <form method="POST">
                            <?php if ($edit_category): ?>
                            <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Название категории *</label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?php echo $edit_category ? $edit_category['name'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Описание категории</label>
                                <textarea id="description" name="description" rows="3"><?php echo $edit_category ? $edit_category['description'] : ''; ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="<?php echo $edit_category ? 'update_category' : 'add_category'; ?>" class="btn btn-primary">
                                    <?php echo $edit_category ? 'Обновить категорию' : 'Добавить категорию'; ?>
                                </button>
                                
                                <?php if ($edit_category): ?>
                                <a href="settings.php" class="btn btn-outline">Отмена</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Kategoriyalar ro'yxati -->
                    <div class="categories-list">
                        <h3>Список категорий</h3>
                        
                        <?php if (count($categories) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Описание</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td><?php echo $category['name']; ?></td>
                                    <td><?php echo $category['description'] ?: '—'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="settings.php?edit_category=<?php echo $category['id']; ?>" class="btn btn-sm btn-edit">✏️</a>
                                            <a href="settings.php?delete_category=<?php echo $category['id']; ?>" class="btn btn-sm btn-delete" 
                                               onclick="return confirm('Вы уверены, что хотите удалить эту категорию?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="no-data">Категории не найдены</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="tab-content" id="api-tab">
                    <h2>Настройки API</h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="telegram_bot_token">Telegram Bot Token</label>
                            <input type="text" id="telegram_bot_token" name="telegram_bot_token" 
                                   value="<?php echo TELEGRAM_BOT_TOKEN; ?>" 
                                   placeholder="Введите токен Telegram бота">
                        </div>
                        
                        <div class="form-group">
                            <label for="telegram_chat_id">Telegram Chat ID</label>
                            <input type="text" id="telegram_chat_id" name="telegram_chat_id" 
                                   value="<?php echo TELEGRAM_CHAT_ID; ?>" 
                                   placeholder="Введите ID чата Telegram">
                        </div>
                        
                        <div class="form-group">
                            <label for="sms_api_key">SMS API Key</label>
                            <input type="text" id="sms_api_key" name="sms_api_key" 
                                   value="<?php echo SMS_API_KEY; ?>" 
                                   placeholder="Введите ключ SMS API">
                        </div>
                        
                        <div class="form-group">
                            <label for="yandex_delivery_api_key">Yandex Delivery API Key</label>
                            <input type="text" id="yandex_delivery_api_key" name="yandex_delivery_api_key" 
                                   value="<?php echo YANDEX_DELIVERY_API_KEY; ?>" 
                                   placeholder="Введите ключ API Яндекс.Доставки">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_settings" class="btn btn-primary">Сохранить настройки</button>
                        </div>
                    </form>
                </div>
                
                <div class="tab-content" id="general-tab">
                    <h2>Общие настройки</h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="site_name">Название сайта</label>
                            <input type="text" id="site_name" name="site_name" value="Elita Sham" placeholder="Введите название сайта">
                        </div>
                        
                        <div class="form-group">
                            <label for="site_email">Email сайта</label>
                            <input type="email" id="site_email" name="site_email" value="info@elita-sham.ru" placeholder="Введите email сайта">
                        </div>
                        
                        <div class="form-group">
                            <label for="site_phone">Телефон сайта</label>
                            <input type="tel" id="site_phone" name="site_phone" value="+7 (999) 999-99-99" placeholder="Введите телефон сайта">
                        </div>
                        
                        <div class="form-group">
                            <label for="site_address">Адрес</label>
                            <textarea id="site_address" name="site_address" rows="2" placeholder="Введите адрес">Москва, Россия</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_cost">Стоимость доставки (руб)</label>
                            <input type="number" id="delivery_cost" name="delivery_cost" value="500" min="0" placeholder="Введите стоимость доставки">
                        </div>
                        
                        <div class="form-group">
                            <label for="free_delivery_threshold">Бесплатная доставка от (руб)</label>
                            <input type="number" id="free_delivery_threshold" name="free_delivery_threshold" value="5000" min="0" placeholder="Введите порог бесплатной доставки">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_settings" class="btn btn-primary">Сохранить настройки</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Tablar funksiyasi
    document.querySelectorAll('.tab-header').forEach(header => {
        header.addEventListener('click', () => {
            // Faq tabni yopish
            document.querySelectorAll('.tab-header').forEach(h => h.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Yangi tabni ochish
            header.classList.add('active');
            const tabId = header.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });
    </script>
</body>
</html>
