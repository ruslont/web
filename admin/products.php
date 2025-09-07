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

// Yangi mahsulot qo'shish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $weight = floatval($_POST['weight']);
    $burn_time = intval($_POST['burn_time']);
    $wax_type = sanitizeInput($_POST['wax_type']);
    $fragrance_notes = sanitizeInput($_POST['fragrance_notes']);
    $in_stock = intval($_POST['in_stock']);
    
    // Rasm yuklash
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image = $file_name;
        } else {
            $errors[] = 'Ошибка при загрузке изображения';
        }
    }
    
    // Validatsiya
    if (empty($name)) $errors[] = 'Введите название товара';
    if (empty($description)) $errors[] = 'Введите описание товара';
    if ($price <= 0) $errors[] = 'Цена должна быть больше 0';
    if ($category_id <= 0) $errors[] = 'Выберите категорию';
    
    if (empty($errors)) {
        $query = "INSERT INTO products (name, description, price, category_id, image, weight, burn_time, wax_type, fragrance_notes, in_stock, created_at)
                  VALUES (:name, :description, :price, :category_id, :image, :weight, :burn_time, :wax_type, :fragrance_notes, :in_stock, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':weight', $weight);
        $stmt->bindParam(':burn_time', $burn_time);
        $stmt->bindParam(':wax_type', $wax_type);
        $stmt->bindParam(':fragrance_notes', $fragrance_notes);
        $stmt->bindParam(':in_stock', $in_stock);
        
        if ($stmt->execute()) {
            $success = 'Товар успешно добавлен';
        } else {
            $errors[] = 'Ошибка при добавлении товара';
        }
    }
}

// Mahsulotni yangilash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $weight = floatval($_POST['weight']);
    $burn_time = intval($_POST['burn_time']);
    $wax_type = sanitizeInput($_POST['wax_type']);
    $fragrance_notes = sanitizeInput($_POST['fragrance_notes']);
    $in_stock = intval($_POST['in_stock']);
    
    // Rasm yuklash (agar yangi rasm yuklangan bo'lsa)
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image = $file_name;
            
            // Eski rasmni o'chirish
            $old_image_query = "SELECT image FROM products WHERE id = :id";
            $old_image_stmt = $conn->prepare($old_image_query);
            $old_image_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $old_image_stmt->execute();
            $old_image = $old_image_stmt->fetch(PDO::FETCH_ASSOC)['image'];
            
            if ($old_image && file_exists($upload_dir . $old_image)) {
                unlink($upload_dir . $old_image);
            }
        } else {
            $errors[] = 'Ошибка при загрузке изображения';
        }
    }
    
    // Validatsiya
    if (empty($name)) $errors[] = 'Введите название товара';
    if (empty($description)) $errors[] = 'Введите описание товара';
    if ($price <= 0) $errors[] = 'Цена должна быть больше 0';
    if ($category_id <= 0) $errors[] = 'Выберите категорию';
    
    if (empty($errors)) {
        if (!empty($image)) {
            $query = "UPDATE products SET name = :name, description = :description, price = :price, 
                      category_id = :category_id, image = :image, weight = :weight, burn_time = :burn_time, 
                      wax_type = :wax_type, fragrance_notes = :fragrance_notes, in_stock = :in_stock 
                      WHERE id = :id";
        } else {
            $query = "UPDATE products SET name = :name, description = :description, price = :price, 
                      category_id = :category_id, weight = :weight, burn_time = :burn_time, 
                      wax_type = :wax_type, fragrance_notes = :fragrance_notes, in_stock = :in_stock 
                      WHERE id = :id";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':weight', $weight);
        $stmt->bindParam(':burn_time', $burn_time);
        $stmt->bindParam(':wax_type', $wax_type);
        $stmt->bindParam(':fragrance_notes', $fragrance_notes);
        $stmt->bindParam(':in_stock', $in_stock);
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        
        if (!empty($image)) {
            $stmt->bindParam(':image', $image);
        }
        
        if ($stmt->execute()) {
            $success = 'Товар успешно обновлен';
        } else {
            $errors[] = 'Ошибка при обновлении товара';
        }
    }
}

// Mahsulotni o'chirish
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // Rasmni o'chirish
    $image_query = "SELECT image FROM products WHERE id = :id";
    $image_stmt = $conn->prepare($image_query);
    $image_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $image_stmt->execute();
    $image = $image_stmt->fetch(PDO::FETCH_ASSOC)['image'];
    
    if ($image && file_exists('../assets/images/' . $image)) {
        unlink('../assets/images/' . $image);
    }
    
    // Mahsulotni o'chirish
    $delete_query = "DELETE FROM products WHERE id = :id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    
    if ($delete_stmt->execute()) {
        $success = 'Товар успешно удален';
    } else {
        $errors[] = 'Ошибка при удалении товара';
    }
}

// Kategoriyalarni olish
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Mahsulotlarni olish
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_filter;
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Jami mahsulotlar soni
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $per_page);

// Mahsulotlarni olish
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   $where_clause 
                   ORDER BY p.created_at DESC 
                   LIMIT :limit OFFSET :offset";

$products_stmt = $conn->prepare($products_query);
foreach ($params as $key => $value) {
    $products_stmt->bindValue($key, $value);
}
$products_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$products_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Tahrirlash uchun mahsulot
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_query = "SELECT * FROM products WHERE id = :id";
    $edit_stmt = $conn->prepare($edit_query);
    $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $edit_stmt->execute();
    $edit_product = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление товарами - Elita Sham</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Управление товарами</h1>
            <button class="btn btn-primary" onclick="toggleProductForm()">+ Добавить товар</button>
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
            
            <!-- Mahsulot qo'shish/yangilash formasi -->
            <div class="product-form" id="product-form" style="<?php echo $edit_product ? 'display: block;' : 'display: none;'; ?>">
                <h2><?php echo $edit_product ? 'Редактирование товара' : 'Добавление товара'; ?></h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Название товара *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo $edit_product ? $edit_product['name'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Категория *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Выберите категорию</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo ($edit_product && $edit_product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Описание товара *</label>
                        <textarea id="description" name="description" rows="4" required><?php echo $edit_product ? $edit_product['description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Цена (руб) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required 
                                   value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="in_stock">Количество в наличии *</label>
                            <input type="number" id="in_stock" name="in_stock" min="0" required 
                                   value="<?php echo $edit_product ? $edit_product['in_stock'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight">Вес (кг)</label>
                            <input type="number" id="weight" name="weight" step="0.01" min="0" 
                                   value="<?php echo $edit_product ? $edit_product['weight'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="burn_time">Время горения (часов)</label>
                            <input type="number" id="burn_time" name="burn_time" min="0" 
                                   value="<?php echo $edit_product ? $edit_product['burn_time'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="wax_type">Тип воска</label>
                            <input type="text" id="wax_type" name="wax_type" 
                                   value="<?php echo $edit_product ? $edit_product['wax_type'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="fragrance_notes">Ароматические ноты</label>
                            <input type="text" id="fragrance_notes" name="fragrance_notes" 
                                   value="<?php echo $edit_product ? $edit_product['fragrance_notes'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Изображение товара</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if ($edit_product && $edit_product['image']): ?>
                        <div class="current-image">
                            <p>Текущее изображение:</p>
                            <img src="../assets/images/<?php echo $edit_product['image']; ?>" alt="<?php echo $edit_product['name']; ?>" style="max-width: 200px;">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" class="btn btn-primary">
                            <?php echo $edit_product ? 'Обновить товар' : 'Добавить товар'; ?>
                        </button>
                        <a href="products.php" class="btn btn-outline">Отмена</a>
                    </div>
                </form>
            </div>
            
            <!-- Filtrlar va qidiruv -->
            <div class="filters">
                <form method="GET" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Поиск по названию или описанию" 
                                   value="<?php echo $search; ?>">
                        </div>
                        
                        <div class="form-group">
                            <select name="category">
                                <option value="">Все категории</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn">Применить фильтры</button>
                            <a href="products.php" class="btn btn-outline">Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Mahsulotlar jadvali -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Изображение</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Цена</th>
                            <th>В наличии</th>
                            <th>Дата добавления</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <?php if ($product['image']): ?>
                                <img src="../assets/images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-thumb">
                                <?php else: ?>
                                <span class="no-image">Нет изображения</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $product['name']; ?></td>
                            <td><?php echo $product['category_name']; ?></td>
                            <td><?php echo number_format($product['price'], 0, ',', ' '); ?> руб.</td>
                            <td><?php echo $product['in_stock']; ?> шт.</td>
                            <td><?php echo date('d.m.Y', strtotime($product['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-edit">✏️</a>
                                    <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-delete" 
                                       onclick="return confirm('Вы уверены, что хотите удалить этот товар?')">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data">Товары не найдены</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Paginatsiya -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="products.php?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&category=<?php echo $category_filter; ?>" class="page-link">← Назад</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="products.php?page=<?php echo $i; ?>&search=<?php echo $search; ?>&category=<?php echo $category_filter; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="products.php?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&category=<?php echo $category_filter; ?>" class="page-link">Вперед →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function toggleProductForm() {
        const form = document.getElementById('product-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        
        // Formani tozalash
        if (form.style.display === 'block') {
            window.location.href = 'products.php';
        }
    }
    </script>
</body>
</html>
```
