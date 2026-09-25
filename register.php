<?php
// Файл: register.php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $name = htmlspecialchars($_POST['name'] ?? '');

    // Валидация
    if (strlen($password) < 6) {
        $error = "Пароль должен быть не менее 6 символов.";
    } else {
        // Проверка уникальности
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email уже зарегистрирован.";
        } else {
            // Создаем пользователя
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name, verification_token) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $hash, $name, $token]);

            // ОТПРАВКА ПИСЬМА (Имитация)
            // В реальности: mail($email, "Подтверждение", "http://yoursite.com/verify.php?token=$token");
            $verifyLink = "http://localhost/verify.php?token=$token"; 
            // Для теста выводим ссылку в alert или просто сообщаем
            setFlash('success', "Регистрация успешна! (Тестовая ссылка: <a href='$verifyLink'>$verifyLink</a>)");
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Регистрация</h1>
            <?php 
            $flash = getFlash();
            if($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>
            <?php if(isset($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            
            <form method="POST">
                <input type="text" name="name" placeholder="Имя (необязательно)">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Пароль (мин. 6 символов)" required>
                <button type="submit">Зарегистрироваться</button>
            </form>
            <p>Есть аккаунт? <a href="index.php">Войти</a></p>
        </div>
    </div>
</body>
</html>