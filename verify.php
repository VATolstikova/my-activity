<?php
// Файл: verify.php
require 'config.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = false;

if ($token) {
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() > 0) {
        $success = true;
    } else {
        $error = true; // Токен неверный или уже использован
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <?php if($success): ?>
                <h2 style="color: var(--success)">Email подтвержден!</h2>
                <p>Теперь вы можете войти.</p>
                <a href="index.php">Перейти ко входу</a>
            <?php elseif($error): ?>
                <h2 style="color: var(--danger)">Ошибка</h2>
                <p>Неверная или устаревшая ссылка.</p>
                <a href="index.php">На главную</a>
            <?php else: ?>
                <p>Нет токена.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>