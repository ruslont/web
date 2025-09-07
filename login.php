<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Agar foydalanuvchi allaqachon kirgan bo'lsa, bosh sahifaga yo'naltirish
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$phone = '';
$otp_sent = false;
$otp_method = 'telegram';

// OTP yuborish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $phone = sanitizeInput($_POST['phone']);
    $otp_method = isset($_POST['otp_method']) ? sanitizeInput($_POST['otp_method']) : 'telegram';
    
    if (empty($phone)) {
        $errors[] = 'Введите номер телефона';
    } elseif (!preg_match('/^\+7\d{10}$/', $phone)) {
        $errors[] = 'Введите номер в формате +7XXXXXXXXXX';
    } else {
        if (sendOTP($phone, $otp_method)) {
            $otp_sent = true;
            $_SESSION['login_phone'] = $phone;
            $_SESSION['otp_method'] = $otp_method;
        } else {
            $errors[] = 'Ошибка при отправке кода. Попробуйте позже.';
        }
    }
}

// OTP tekshirish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = sanitizeInput($_POST['otp']);
    $phone = $_SESSION['login_phone'] ?? '';
    
    if (empty($entered_otp)) {
        $errors[] = 'Введите код подтверждения';
    } elseif (verifyOTP($entered_otp)) {
        // Foydalanuvchini ro'yxatdan o'tkazish yoki tizimga kirish
        $db = new Database();
        $conn = $db->getConnection();
        
        // Foydalanuvchi mavjudligini tekshirish
        $query = "SELECT id FROM users WHERE phone = :phone";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Foydalanuvchi mavjud, kirish
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_phone'] = $phone;
        } else {
            // Yangi foydalanuvchi yaratish
            $query = "INSERT INTO users (phone, created_at) VALUES (:phone, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':phone', $phone);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->lastInsertId();
                $_SESSION['user_phone'] = $phone;
            } else {
                $errors[] = 'Ошибка при создании пользователя';
            }
        }
        
        // Muvaffaqiyatli kirishdan so'ng, oldingi sahifaga yo'naltirish
        if (empty($errors)) {
            $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirect_url);
            exit;
        }
    } else {
        $errors[] = 'Неверный код подтверждения';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Elita Sham</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="login-page">
        <div class="container">
            <div class="login-content">
                <div class="login-form">
                    <h1>Вход в систему</h1>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="errors">
                        <?php foreach ($errors as $error): ?>
                        <div class="error"><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$otp_sent): ?>
                    <form method="POST" id="phone-form">
                        <div class="form-group">
                            <label for="phone">Номер телефона</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo $phone; ?>" 
                                   placeholder="+7XXXXXXXXXX" required 
                                   pattern="\+7\d{10}">
                        </div>
                        
                        <div class="form-group">
                            <label>Способ получения кода</label>
                            <div class="otp-methods">
                                <div class="otp-method <?php echo $otp_method == 'telegram' ? 'selected' : ''; ?>" data-method="telegram">
                                    <div class="method-icon">📱</div>
                                    <div class="method-name">Telegram</div>
                                </div>
                                <div class="otp-method <?php echo $otp_method == 'sms' ? 'selected' : ''; ?>" data-method="sms">
                                    <div class="method-icon">💬</div>
                                    <div class="method-name">SMS</div>
                                </div>
                                <div class="otp-method <?php echo $otp_method == 'whatsapp' ? 'selected' : ''; ?>" data-method="whatsapp">
                                    <div class="method-icon">📲</div>
                                    <div class="method-name">WhatsApp</div>
                                </div>
                            </div>
                            <input type="hidden" name="otp_method" id="otp-method" value="<?php echo $otp_method; ?>">
                        </div>
                        
                        <button type="submit" name="send_otp" class="btn btn-primary">Получить код</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" id="otp-form">
                        <div class="form-group">
                            <label for="otp">Код подтверждения</label>
                            <input type="text" id="otp" name="otp" 
                                   placeholder="Введите 6-значный код" required 
                                   pattern="\d{6}" maxlength="6">
                            <div class="otp-timer">
                                Код действителен в течение: <span id="otp-timer">5:00</span>
                            </div>
                            <div class="otp-method-info">
                                Код отправлен через <?php 
                                $method_names = [
                                    'telegram' => 'Telegram',
                                    'sms' => 'SMS',
                                    'whatsapp' => 'WhatsApp'
                                ];
                                echo $method_names[$_SESSION['otp_method']]; 
                                ?> на номер <?php echo $_SESSION['login_phone']; ?>
                            </div>
                        </div>
                        
                        <button type="submit" name="verify_otp" class="btn btn-primary">Подтвердить</button>
                        
                        <div class="resend-otp">
                            Не получили код? 
                            <a href="#" onclick="resendOTP(); return false;">Отправить повторно</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
                
                <div class="login-info">
                    <h2>Преимущества входа</h2>
                    <ul>
                        <li>Быстрое оформление заказов</li>
                        <li>История ваших покупок</li>
                        <li>Отслеживание статуса заказов</li>
                        <li>Персональные скидки и предложения</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/login.js"></script>
</body>
</html>
