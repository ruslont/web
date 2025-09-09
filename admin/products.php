<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

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
    $seo_title = sanitizeInput($_POST['seo_title']);
    $seo_description = sanitizeInput($_POST['seo_description']);
    $seo_keywords = sanitizeInput($_POST['seo_keywords']);

    // Rasm yuklash
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image = 'products/' . $file_name;
        } else {
            $errors[] = 'Ошибка при загрузке изображения';
        }
    }

    if (empty($errors)) {
        $query = "INSERT INTO products (name, description, price, category_id, image, weight, burn_time, wax_type, fragrance_notes, in_stock, seo_title, seo_description, seo_keywords) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if ($stmt->execute([$name, $description, $price, $category_id, $image, $weight, $burn_time, $wax_type, $fragrance_notes, $in_stock, $seo_title, $seo_description, $seo_keywords])) {
            $success = 'Товар успешно добавлен';
        } else {
            $errors[] = 'Ошибка при добавлении товара';
        }
    }
}

// Kategoriyalarni olish
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Mahsulotlarni olish
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.created_at DESC 
                   LIMIT $per_page OFFSET $offset";
$products = $conn->query($products_query)->fetchAll(PDO::FETCH_ASSOC);

$total_products = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_pages = ceil($total_products / $per_page);
?>

<?php include 'includes/header.php'; ?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-box"></i> Управление товарами</h2>
        <button class="btn btn-primary" onclick="toggleProductForm()">
            <i class="fas fa-plus"></i> Добавить товар
        </button>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
        <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
    <?php endif; ?>

    <!-- Mahsulot qo'shish formasi -->
    <div class="product-form" id="product-form" style="display: none;">
        <h3>Добавление товара</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Название товара *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Категория *</label>
                    <select name="category_id" required>
                        <option value="">Выберите категорию</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Цена (руб) *</label>
                    <input type="number" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Количество в наличии *</label>
                    <input type="number" name="in_stock" min="0" required>
                </div>
            </div>

            <div class="form-group">
                <label>Описание товара *</label>
                <textarea name="description" rows="4" required></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Вес (кг)</label>
                    <input type="number" name="weight" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label>Время горения (часов)</label>
                    <input type="number" name="burn_time" min="0">
                </div>
                
                <div class="form-group">
                    <label>Тип воска</label>
                    <input type="text" name="wax_type">
                </div>
                
                <div class="form-group">
                    <label>Ароматические ноты</label>
                    <input type="text" name="fragrance_notes">
                </div>
            </div>

            <div class="form-group">
                <label>Изображение товара</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <div class="form-group">
                <label>SEO Title</label>
                <input type="text" name="seo_title">
            </div>
            
            <div class="form-group">
                <label>SEO Description</label>
                <textarea name="seo_description" rows="2"></textarea>
            </div>
            
            <div class="form-group">
                <label>SEO Keywords</label>
                <input type="text" name="seo_keywords">
            </div>

            <div class="form-actions">
                <button type="submit" name="add_product" class="btn btn-primary">Добавить товар</button>
                <button type="button" class="btn btn-secondary" onclick="toggleProductForm()">Отмена</button>
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
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
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
                    <td>
                        <div class="action-buttons">
                            <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-delete" 
                               onclick="return confirm('Удалить этот товар?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginatsiya -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleProductForm() {
    const form = document.getElementById('product-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
