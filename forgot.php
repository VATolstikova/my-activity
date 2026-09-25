<?php
// Файл: forgot.php
require 'config.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);
        
        // Отправка письма (имитация)
        $resetLink = "http://localhost/reset_password.php?token=$token";
        $msg = "Ссылка для сброса (тест): <a href='$resetLink'>$resetLink</a>";
    } else {
        // Для безопасности пишем одно и то же сообщение
        $msg = "Если email существует, письмо отправлено.";
    }
}
?>
<!-- HTML форма аналогична регистрации, только поле email и кнопка "Сбросить" -->
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Сброс пароля</title><link rel="stylesheet" href="style.css"></head>
<body>
    <div class="container">
        <div class="card">
            <h1>Забыли пароль?</h1>
            <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
            <form method="POST">
                <input type="email" name="email" placeholder="Ваш Email" required>
                <button type="submit">Отправить ссылку</button>
            </form>
            <p><a href="index.php">Назад</a></p>
        </div>
    </div>
</body>
</html>