<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Проверяем права администратора
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Обработка изменения роли пользователя
if (isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    $updateQuery = "UPDATE users SET role = :role WHERE id = :id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([
        'role' => $new_role,
        'id' => $user_id
    ]);
    
    header("Location: users.php?success=role_updated");
    exit();
}

// Получаем всех пользователей с их статистикой
$usersQuery = "SELECT u.*, 
              COUNT(DISTINCT t.id) as ticket_count,
              COALESCE(SUM(t.price), 0) as total_spent
              FROM users u
              LEFT JOIN tickets t ON u.id = t.user_id
              GROUP BY u.id
              ORDER BY u.created_at DESC";

$usersStmt = $conn->query($usersQuery);
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Админ-панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div style="margin: 2rem 0;">
            <h1 class="gradient-text">
                <i class="fas fa-users"></i> Управление пользователями
            </h1>
            <p style="color: var(--text-muted);">Просмотр и управление учетными записями пользователей</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Роль пользователя успешно обновлена
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--neon-primary);">
                    <?php echo count($users); ?>
                </div>
                <div style="color: var(--text-muted);">Всего пользователей</div>
            </div>
            
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--success);">
                    <?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?>
                </div>
                <div style="color: var(--text-muted);">Администраторов</div>
            </div>
            
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--text-muted);">
                    <?php echo count(array_filter($users, fn($u) => $u['role'] === 'user')); ?>
                </div>
                <div style="color: var(--text-muted);">Обычных пользователей</div>
            </div>
            
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--neon-primary);">
                    <?php echo array_sum(array_column($users, 'ticket_count')); ?>
                </div>
                <div style="color: var(--text-muted);">Всего билетов</div>
            </div>
        </div>

        <!-- Список пользователей -->
        <div style="margin-top: 2rem;">
            <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-list"></i> Список пользователей (<?php echo count($users); ?>)
            </h3>
            
            <?php if (count($users) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden;">
                        <thead>
                            <tr>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Пользователь</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Email</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Регистрация</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Статистика</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Роль</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr style="border-bottom: 1px solid rgba(255, 0, 255, 0.1);">
                                <td style="padding: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary));
                                                    border-radius: 50%; display: flex; align-items: center; justify-content: center;
                                                    color: white; font-weight: bold;">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                            <small style="color: var(--text-muted);">ID: <?php echo $user['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo date('d.m.Y', strtotime($user['created_at'])); ?><br>
                                    <small style="color: var(--text-muted);">
                                        <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                    </small>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="font-weight: bold; color: var(--neon-primary);">
                                        <?php echo $user['ticket_count']; ?> билетов
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.9rem;">
                                        <?php echo number_format((float)$user['total_spent'], 0, '', ' '); ?> ₽
                                    </div>
                                </td>
                                <td style="padding: 1rem;">
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role" class="form-control" style="width: 120px;" onchange="this.form.submit()">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Пользователь</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Администратор</option>
                                        </select>
                                        <input type="hidden" name="change_role" value="1">
                                    </form>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="../user/profile.php?user_id=<?php echo $user['id']; ?>" 
                                           target="_blank"
                                           class="btn btn-outline" style="padding: 0.5rem;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn" 
                                                    style="padding: 0.5rem; background: var(--error); color: white;"
                                                    onclick="showDeleteModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; background: var(--bg-card); border-radius: var(--border-radius);">
                    <i class="fas fa-users" style="font-size: 4rem; color: var(--neon-primary); margin-bottom: 1rem;"></i>
                    <h3>Пользователи не найдены</h3>
                    <p style="color: var(--text-muted);">В системе пока нет зарегистрированных пользователей</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <!-- Модальное окно для удаления -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                                 background: rgba(0, 0, 0, 0.8); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); max-width: 400px; width: 90%;">
            <h3 style="color: var(--error); margin-bottom: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> Удаление пользователя
            </h3>
            <p style="margin-bottom: 1.5rem;">Вы уверены, что хотите удалить пользователя <span id="userName"></span>?</p>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">
                <i class="fas fa-info-circle"></i> Это действие нельзя отменить. Все данные пользователя будут удалены.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button onclick="hideDeleteModal()" class="btn btn-outline">
                    Отмена
                </button>
                <a href="" id="deleteLink" class="btn" style="background: var(--error); color: white;">
                    <i class="fas fa-trash"></i> Удалить
                </a>
            </div>
        </div>
    </div>
    
    <script>
    function showDeleteModal(userId, userName) {
        document.getElementById('userName').textContent = userName;
        document.getElementById('deleteLink').href = `delete_user.php?id=${userId}`;
        document.getElementById('deleteModal').style.display = 'flex';
    }
    
    function hideDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    // Закрытие модального окна по клику вне его
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideDeleteModal();
        }
    });
    </script>
</body>
</html>