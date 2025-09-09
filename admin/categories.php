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

// Kategoriya qo'shish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        
        if (empty($name)) {
            $errors[] = 'Введите название категории';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $success = 'Категория успешно добавлена';
            } else {
                $errors[] = 'Ошибка при добавлении категории';
            }
        }
    }
    
    // Kategoriyani yangilash
    if (isset($_POST['update_category'])) {
        $id = intval($_POST['category_id']);
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $id])) {
            $success = 'Категория успешно обновлена';
        } else {
            $errors[] = 'Ошибка при обновлении категории';
        }
    }
}

// Kategoriyani o'chirish
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Kategoriyada mahsulotlar borligini tekshirish
    $check = $conn->query("SELECT COUNT(*) FROM products WHERE category_id = $id")->fetchColumn();
    
    if ($check > 0) {
        $errors[] = 'Нельзя удалить категорию с товарами';
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Категория успешно удалена';
        } else {
            $errors[] = 'Ошибка при удалении категории';
        }
    }
}

// Kategoriyalarni olish
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="admin-content">
    <div class="content-header">
        <h2><i class="fas fa-folder"></i> Управление категориями</h2>
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

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Добавить категорию</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Название категории *</label>
                            <input type="text" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Описание</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" name="add_category" class="btn btn-primary">Добавить</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Список категорий</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
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
                                            <button class="btn btn-sm btn-edit" 
                                                    onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo $category['name']; ?>', '<?php echo $category['description']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-delete"
                                               onclick="return confirm('Удалить эту категорию?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tahrirlash modali -->
    <div class="modal" id="editModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Редактировать категорию</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="editForm">
                    <input type="hidden" name="category_id" id="editCategoryId">
                    
                    <div class="form-group">
                        <label>Название категории *</label>
                        <input type="text" name="name" id="editCategoryName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" id="editCategoryDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_category" class="btn btn-primary">Сохранить</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editCategory(id, name, description) {
    document.getElementById('editCategoryId').value = id;
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editCategoryDescription').value = description;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
