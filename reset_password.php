<?php
// Файл: reset_password.php
require 'config.php';
$token = $_GET['token'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];
    $tokenPost = $_POST['token'];

    if ($newPass !== $confirmPass) {
        $error = "Пароли не совпадают.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$tokenPost]);
        $user = $stmt->fetch();

        if ($user) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->execute([$hash, $user['id']]);
            header('Location: index.php?success=reset');
            exit;
        } else {
            $error = "Ссылка недействительна или истекла.";
        }
    }
}

if (!$token && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Неверная ссылка.");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Новый пароль</title><link rel="stylesheet" href="style.css"></head>
<body>
    <div class="container">
        <div class="card">
            <h1>Новый пароль</h1>
            <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <input type="password" name="password" placeholder="Новый пароль" required>
                <input type="password" name="confirm_password" placeholder="Повторите пароль" required>
                <button type="submit">Сменить пароль</button>
            </form>
        </div>
    </div>
</body>
</html>