<?php
// Проверяем, существует ли сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar">
    <div class="container">
        <div class="nav-content">
            <a href="index.php" class="logo">
                <i class="fas fa-film"></i> Neon Cinema
            </a>
            
            <div class="nav-links">
                <a href="/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Главная
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="user/profile.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'user/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Личный кабинет
                    </a>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                        <a href="admin/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-cogs"></i> Админ-панель
                        </a>
                    <?php endif; ?>
                    
                    <a href="/auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                    
                    <span style="color: var(--neon-pink);">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                <?php else: ?>
                    <a href="auth/login.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </a>
                    <a href="auth/register.php" class="btn btn-outline">
                        <i class="fas fa-user-plus"></i> Регистрация
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>