<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Проверяем авторизацию
requireAuth();

// Получаем информацию о пользователе
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем статистику пользователя
$statsQuery = "SELECT 
              COUNT(*) as total_tickets,
              COUNT(CASE WHEN ticket_status = 'active' THEN 1 END) as active_tickets,
              SUM(price) as total_spent
              FROM tickets
              WHERE user_id = :user_id";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute(['user_id' => $_SESSION['user_id']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Neon Cinema</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="profile-header">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            
            <div style="flex: 1;">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                </p>
                
                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: var(--neon-primary);">
                            <?php echo $stats['total_tickets'] ?? 0; ?>
                        </div>
                        <div style="color: var(--text-muted);">Всего билетов</div>
                    </div>
                    
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: var(--success);">
                            <?php echo $stats['active_tickets'] ?? 0; ?>
                        </div>
                        <div style="color: var(--text-muted);">Активные билеты</div>
                    </div>
                    
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: var(--neon-primary);">
                            <?php echo number_format($stats['total_spent'] ?? 0, 0, '', ' '); ?> ₽
                        </div>
                        <div style="color: var(--text-muted);">Потрачено всего</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem; margin-top: 3rem;">
            <!-- Боковое меню -->
            <div>
                <div style="background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden;">
                    <a href="profile.php" 
                       class="nav-link active" 
                       style="display: block; margin: 0; border-radius: 0;">
                        <i class="fas fa-user"></i> Профиль
                    </a>
                    <a href="orders.php" 
                       class="nav-link" 
                       style="display: block; margin: 0; border-radius: 0;">
                        <i class="fas fa-ticket-alt"></i> Мои билеты
                    </a>
                    <a href="../auth/logout.php" 
                       class="nav-link" 
                       style="display: block; margin: 0; border-radius: 0; color: var(--error);">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </div>
                
                <!-- Ближайшие сеансы -->
                <div style="margin-top: 2rem;">
                    <h4 style="color: var(--neon-primary); margin-bottom: 1rem;">
                        <i class="fas fa-clock"></i> Ближайшие сеансы
                    </h4>
                    
                    <?php
                    $upcomingQuery = "SELECT t.*, m.title, s.start_time 
                                     FROM tickets t
                                     JOIN sessions s ON t.session_id = s.id
                                     JOIN movies m ON s.movie_id = m.id
                                     WHERE t.user_id = :user_id 
                                     AND t.ticket_status = 'active'
                                     AND s.start_time > NOW()
                                     ORDER BY s.start_time
                                     LIMIT 3";
                    
                    $upcomingStmt = $conn->prepare($upcomingQuery);
                    $upcomingStmt->execute(['user_id' => $_SESSION['user_id']]);
                    
                    if ($upcomingStmt->rowCount() > 0) {
                        while ($ticket = $upcomingStmt->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                    <div style="background: var(--bg-card); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 0.5rem;">
                        <div style="font-weight: bold; color: var(--text-primary);">
                            <?php echo htmlspecialchars($ticket['title']); ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.9rem;">
                            <?php echo date('d.m H:i', strtotime($ticket['start_time'])); ?>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                        echo '<p style="color: var(--text-muted); font-size: 0.9rem;">Нет предстоящих сеансов</p>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Основной контент -->
            <div>
                <h3 style="color: var(--neon-primary); margin-bottom: 2rem;">
                    <i class="fas fa-chart-line"></i> Активность
                </h3>
                
                <!-- История покупок -->
                <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius);">
                    <h4 style="margin-bottom: 1.5rem;">Последние покупки</h4>
                    
                    <?php
                    $historyQuery = "SELECT t.*, m.title, s.start_time, st.seat_number
                                    FROM tickets t
                                    JOIN sessions s ON t.session_id = s.id
                                    JOIN movies m ON s.movie_id = m.id
                                    JOIN seats st ON t.seat_id = st.id
                                    WHERE t.user_id = :user_id
                                    ORDER BY t.purchase_time DESC
                                    LIMIT 5";
                    
                    $historyStmt = $conn->prepare($historyQuery);
                    $historyStmt->execute(['user_id' => $_SESSION['user_id']]);
                    
                    if ($historyStmt->rowCount() > 0) {
                    ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid rgba(255, 0, 255, 0.2); color: var(--text-muted);">Фильм</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid rgba(255, 0, 255, 0.2); color: var(--text-muted);">Дата</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid rgba(255, 0, 255, 0.2); color: var(--text-muted);">Место</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid rgba(255, 0, 255, 0.2); color: var(--text-muted);">Цена</th>
                                    <th style="padding: 0.75rem; text-align: left; border-bottom: 1px solid rgba(255, 0, 255, 0.2); color: var(--text-muted);">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ticket = $historyStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td style="padding: 0.75rem; border-bottom: 1px solid rgba(255, 0, 255, 0.1);"><?php echo htmlspecialchars($ticket['title']); ?></td>
                                    <td style="padding: 0.75rem; border-bottom: 1px solid rgba(255, 0, 255, 0.1);"><?php echo date('d.m.Y H:i', strtotime($ticket['start_time'])); ?></td>
                                    <td style="padding: 0.75rem; border-bottom: 1px solid rgba(255, 0, 255, 0.1);"><?php echo $ticket['seat_number']; ?></td>
                                    <td style="padding: 0.75rem; border-bottom: 1px solid rgba(255, 0, 255, 0.1);"><?php echo $ticket['price']; ?> ₽</td>
                                    <td style="padding: 0.75rem; border-bottom: 1px solid rgba(255, 0, 255, 0.1);">
                                        <span class="status-badge status-<?php echo $ticket['ticket_status']; ?>">
                                            <?php 
                                            $statuses = [
                                                'active' => 'Активен',
                                                'cancelled' => 'Отменен',
                                                'expired' => 'Просрочен',
                                                'used' => 'Использован'
                                            ];
                                            echo $statuses[$ticket['ticket_status']];
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="orders.php" class="btn btn-outline">
                            <i class="fas fa-history"></i> Вся история
                        </a>
                    </div>
                    <?php } else { ?>
                        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            <i class="fas fa-ticket-alt" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--neon-primary);"></i>
                            У вас еще нет покупок
                        </p>
                    <?php } ?>
                </div>
                
                <!-- Рекомендации -->
                <div style="margin-top: 2rem;">
                    <h4 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                        <i class="fas fa-fire"></i> Рекомендуем посмотреть
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php
                        $recommendQuery = "SELECT * FROM movies 
                                          WHERE id != :last_movie_id 
                                          ORDER BY RAND() 
                                          LIMIT 3";
                        $recommendStmt = $conn->prepare($recommendQuery);
                        $recommendStmt->execute(['last_movie_id' => 0]);
                        
                        while ($movie = $recommendStmt->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                        <div style="background: var(--bg-card); border-radius: var(--border-radius); padding: 1rem; text-align: center;">
                            <div style="width: 60px; height: 60px; background: linear-gradient(45deg, var(--neon-primary), var(--neon-secondary)); 
                                        border-radius: 50%; display: flex; align-items: center; justify-content: center;
                                        margin: 0 auto 1rem;">
                                <i class="fas fa-film" style="color: white;"></i>
                            </div>
                            <div style="font-weight: bold; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($movie['title']); ?></div>
                            <a href="../movie/view.php?id=<?php echo $movie['id']; ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                Подробнее
                            </a>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .status-active {
        background: rgba(0, 255, 157, 0.1);
        color: var(--success);
    }
    
    .status-cancelled, .status-expired, .status-used {
        background: rgba(255, 61, 113, 0.1);
        color: var(--error);
    }
    </style>
</body>
</html>