<?php
// Файл: config.php
session_start();

// Настройки БД
$host = 'MySQL-8.0';
$db   = 'task_tracker';
$user = 'root'; // Ваш логин БД
$pass = '';     // Ваш пароль БД
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Функция для проверки авторизации
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Функция для проверки подтверждения почты
function requireVerified() {
    requireLogin();
    // В реальном проекте нужно делать запрос к БД, здесь упростим:
    // Проверка делается при логине, но если нужно строго:
    $pdo = $GLOBALS['pdo'];
    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user['is_verified']) {
        session_destroy();
        header('Location: index.php?error=verify_email');
        exit;
    }
}

// Хелпер для рендеринга ошибок/успеха
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function getFlash() {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
?>