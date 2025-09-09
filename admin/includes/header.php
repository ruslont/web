<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Foydalanuvchi ma'lumotlarini olish
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT name, email FROM users WHERE id = :id");
$user_query->bindParam(':id', $user_id, PDO::PARAM_INT);
$user_query->execute();
$user = $user_query->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ Панель - Elita Sham</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <header class="admin-header">
            <div class="header-container">
                <div class="logo-section">
                    <h1><i class="fas fa-crown"></i> ELITA SHAM Admin</h1>
                </div>
                
                <div class="header-actions">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count">3</span>
                    </div>
                    
                    <div class="user-profile">
                        <img src="../assets/images/avatar.png" alt="Avatar" class="user-avatar">
                        <div class="user-info">
                            <span class="user-name"><?php echo $user['name'] ?? 'Администратор'; ?></span>
                            <span class="user-role">Администратор</span>
                        </div>
                        <div class="user-dropdown">
                            <a href="../index.php"><i class="fas fa-home"></i> На сайт</a>
                            <a href="profile.php"><i class="fas fa-user"></i> Профиль</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="admin-container">
            <?php include 'nav.php'; ?>
            
            <main class="admin-main">
