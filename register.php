<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = new Database();
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");
    $confirm = trim($_POST['confirm'] ?? "");

    if ($name && $email && $password && $confirm) {
        if ($password !== $confirm) {
            $message = "⚠ Пароли не совпадают!";
        } else {
            // email bor-yo‘qligini tekshirish
            $check = $db->query("SELECT id FROM users WHERE email = ?", [$email])->fetch();
            if ($check) {
                $message = "⚠ Пользователь с таким email уже существует!";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->query("INSERT INTO users (name, email, password) VALUES (?, ?, ?)", [$name, $email, $hash]);
                $message = "✅ Регистрация прошла успешно! Теперь вы можете <a href='login.php'>войти</a>.";
            }
        }
    } else {
        $message = "⚠ Пожалуйста, заполните все поля.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="container">
    <h1>Регистрация</h1>

    <?php if ($message): ?>
        <p style="color: red; font-weight: bold;"><?= $message ?></p>
    <?php endif; ?>

    <form method="post" action="register.php" class="register-form">
        <label>Имя:<br>
            <input type="text" name="name" required>
        </label><br><br>

        <label>Email:<br>
            <input type="email" name="email" required>
        </label><br><br>

        <label>Пароль:<br>
            <input type="password" name="password" required>
        </label><br><br>

        <label>Подтвердите пароль:<br>
            <input type="password" name="confirm" required>
        </label><br><br>

        <button type="submit">Зарегистрироваться</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
