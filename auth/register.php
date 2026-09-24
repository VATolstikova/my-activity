<?php
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    
    // Валидация
    if (empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = 'Заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Проверяем, не зарегистрирован ли уже email
        $checkQuery = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute(['email' => $email]);
        
        if ($checkStmt->fetch()) {
            $error = 'Пользователь с таким email уже зарегистрирован';
        } else {
            // Хешируем пароль
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Создаем пользователя
            $query = "INSERT INTO users (email, password, full_name) VALUES (:email, :password, :full_name)";
            $stmt = $conn->prepare($query);
            
            if ($stmt->execute([
                'email' => $email,
                'password' => $hashedPassword,
                'full_name' => $full_name
            ])) {
                $success = 'Регистрация успешна! Вы можете войти в систему.';
                header("Refresh: 2; URL=login.php");
            } else {
                $error = 'Ошибка при регистрации. Попробуйте позже.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Neon Cinema</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="../index.php" class="logo">Neon Cinema</a>
                <div class="nav-links">
                    <a href="../index.php" class="nav-link">
                        <i class="fas fa-home"></i> На главную
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="auth-form">
            <h2 style="text-align: center; margin-bottom: 2rem; color: var(--neon-pink);">
                <i class="fas fa-user-plus"></i> Регистрация
            </h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">ФИО</label>
                    <input type="text" name="full_name" class="form-control" required 
                           placeholder="Иванов Иван Иванович">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="example@mail.ru">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Пароль</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="Не менее 6 символов">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Подтверждение пароля</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Зарегистрироваться
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem; color: var(--text-gray);">
                Уже есть аккаунт? <a href="login.php" style="color: var(--neon-pink);">Войти</a>
            </div>
        </div>
    </div>
</body>
</html>