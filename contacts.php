<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$success = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $message = trim($_POST['message'] ?? "");

    if ($name && $email && $message) {
        // Bu yerda siz xohlasangiz email yuborish funksiyasini qo‘shishingiz mumkin
        // mail($to, "Новое сообщение с сайта", $message, "From: $email");
        $success = "✅ Ваше сообщение успешно отправлено!";
    } else {
        $success = "⚠ Пожалуйста, заполните все поля.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Контакты - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="container">
    <h1>Контакты</h1>

    <p><b>Наш адрес:</b> г. Ташкент, ул. Ислам Каримов, 10</p>
    <p><b>Телефон:</b> +7  999 123-45-67</p>
    <p><b>Email:</b> info@elitasham.uz</p>

    <h2>Свяжитесь с нами</h2>

    <?php if ($success): ?>
        <p style="color: green; font-weight: bold;"><?= $success ?></p>
    <?php endif; ?>

    <form method="post" action="contacts.php" class="contact-form">
        <label>Имя:<br>
            <input type="text" name="name" required>
        </label><br><br>

        <label>Email:<br>
            <input type="email" name="email" required>
        </label><br><br>

        <label>Сообщение:<br>
            <textarea name="message" rows="5" required></textarea>
        </label><br><br>

        <button type="submit">Отправить</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
