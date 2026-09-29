<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Проверяем права администратора
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Получаем статистику для дашборда
$statsQuery = "SELECT 
              (SELECT COUNT(*) FROM users) as total_users,
              (SELECT COUNT(*) FROM movies) as total_movies,
              (SELECT COUNT(*) FROM sessions WHERE DATE(start_time) = CURDATE()) as today_sessions,
              (SELECT COUNT(*) FROM tickets WHERE DATE(purchase_time) = CURDATE()) as today_tickets,
              (SELECT COALESCE(SUM(price), 0) FROM tickets WHERE DATE(purchase_time) = CURDATE()) as today_revenue,
              (SELECT COALESCE(SUM(price), 0) FROM tickets WHERE MONTH(purchase_time) = MONTH(CURDATE())) as monthly_revenue";

$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Задаем значения по умолчанию, если null
$stats['today_revenue'] = $stats['today_revenue'] ?? 0;
$stats['monthly_revenue'] = $stats['monthly_revenue'] ?? 0;
$stats['total_users'] = $stats['total_users'] ?? 0;
$stats['total_movies'] = $stats['total_movies'] ?? 0;
$stats['today_sessions'] = $stats['today_sessions'] ?? 0;
$stats['today_tickets'] = $stats['today_tickets'] ?? 0;

// Получаем последние билеты
$recentTicketsQuery = "SELECT t.*, m.title, u.full_name, s.start_time
                      FROM tickets t
                      JOIN sessions s ON t.session_id = s.id
                      JOIN movies m ON s.movie_id = m.id
                      JOIN users u ON t.user_id = u.id
                      ORDER BY t.purchase_time DESC 
                      LIMIT 10";
$recentTicketsStmt = $conn->query($recentTicketsQuery);
$recentTickets = $recentTicketsStmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем предстоящие сеансы
$upcomingSessionsQuery = "SELECT s.*, m.title, 
                         (SELECT COUNT(*) FROM seats WHERE session_id = s.id AND seat_status = 'sold') as sold_seats,
                         (SELECT COUNT(*) FROM seats WHERE session_id = s.id) as total_seats
                         FROM sessions s
                         JOIN movies m ON s.movie_id = m.id
                         WHERE s.start_time > NOW()
                         ORDER BY s.start_time ASC
                         LIMIT 10";
$upcomingSessionsStmt = $conn->query($upcomingSessionsQuery);
$upcomingSessions = $upcomingSessionsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Neon Cinema</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div style="margin: 2rem 0;">
            <h1 class="gradient-text">
                <i class="fas fa-cogs"></i> Административная панель
            </h1>
            <p style="color: var(--text-muted);">Управление системой кинотеатра</p>
        </div>

        <!-- Статистика -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary)); padding: 2rem; border-radius: var(--border-radius); color: white;">
                <div style="font-size: 2.5rem; font-weight: bold;"><?php echo htmlspecialchars($stats['total_users']); ?></div>
                <div>Пользователей</div>
                <div style="margin-top: 1rem;">
                    <a href="users.php" style="color: white; text-decoration: underline;">Управление →</a>
                </div>
            </div>
            
            <div style="background: var(--neon-pink); padding: 2rem; border-radius: var(--border-radius); color: white;">
                <div style="font-size: 2.5rem; font-weight: bold;"><?php echo htmlspecialchars($stats['today_tickets']); ?></div>
                <div>Билетов сегодня</div>
                <div style="font-size: 1.5rem; margin-top: 0.5rem;">
                    <?php echo number_format((float)$stats['today_revenue'], 0, '', ' '); ?> ₽
                </div>
            </div>
            
            <div style="background: var(--neon-pink); padding: 2rem; border-radius: var(--border-radius); color: white;">
                <div style="font-size: 2.5rem; font-weight: bold;"><?php echo htmlspecialchars($stats['today_sessions']); ?></div>
                <div>Сеансов сегодня</div>
                <div style="margin-top: 1rem;">
                    <a href="sessions.php" style="color: white; text-decoration: underline;">Расписание →</a>
                </div>
            </div>
            
            <div style="background: var(--neon-pink); padding: 2rem; border-radius: var(--border-radius); color: white;">
                <div style="font-size: 2.5rem; font-weight: bold;"><?php echo htmlspecialchars($stats['total_movies']); ?></div>
                <div>Фильмов в базе</div>
                <div style="margin-top: 1rem;">
                    <a href="movies.php" style="color: white; text-decoration: underline;">Каталог →</a>
                </div>
            </div>
        </div>

        <!-- Быстрое меню -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <a href="movies.php?action=add" class="admin-card">
                <i class="fas fa-plus-circle"></i>
                <span>Добавить фильм</span>
            </a>
            
            <a href="sessions.php?action=add" class="admin-card">
                <i class="fas fa-calendar-plus"></i>
                <span>Добавить сеанс</span>
            </a>
            
            <a href="reports.php" class="admin-card">
                <i class="fas fa-chart-bar"></i>
                <span>Отчеты</span>
            </a>
            
            <a href="check_tickets.php" class="admin-card">
                <i class="fas fa-qrcode"></i>
                <span>Проверка билетов</span>
            </a>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
            <!-- Последние билеты -->
            <div>
                <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-ticket-alt"></i> Последние покупки
                </h3>
                
                <div style="background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Билет</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Фильм</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Покупатель</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTickets as $ticket): ?>
                                <tr style="border-bottom: 1px solid rgba(255, 0, 255, 0.1);">
                                    <td style="padding: 1rem;">
                                        <small><?php echo htmlspecialchars(substr($ticket['ticket_number'] ?? '', 0, 10) . '...'); ?></small>
                                    </td>
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($ticket['title'] ?? ''); ?></td>
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($ticket['full_name'] ?? ''); ?></td>
                                    <td style="padding: 1rem; color: var(--neon-primary); font-weight: bold;">
                                        <?php echo htmlspecialchars($ticket['price'] ?? 0); ?> ₽
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ближайшие сеансы -->
            <div>
                <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-clock"></i> Ближайшие сеансы
                </h3>
                
                <div style="background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Время</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Фильм</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Зал</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Продано</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingSessions as $session): 
                                    $sold_seats = $session['sold_seats'] ?? 0;
                                    $total_seats = $session['total_seats'] ?? 1; // избегаем деления на 0
                                    $occupancy = $total_seats > 0 ? ($sold_seats / $total_seats) * 100 : 0;
                                ?>
                                <tr style="border-bottom: 1px solid rgba(255, 0, 255, 0.1);">
                                    <td style="padding: 1rem;">
                                        <?php echo date('H:i', strtotime($session['start_time'] ?? 'now')); ?>
                                    </td>
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($session['title'] ?? ''); ?></td>
                                    <td style="padding: 1rem;">№ <?php echo htmlspecialchars($session['hall_number'] ?? 1); ?></td>
                                    <td style="padding: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="flex: 1; height: 8px; background: rgba(255, 0, 255, 0.1); border-radius: 4px; overflow: hidden;">
                                                <div style="width: <?php echo $occupancy; ?>%; height: 100%; background: var(--neon-primary);"></div>
                                            </div>
                                            <span><?php echo round($occupancy); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ежемесячная выручка -->
        <div>
            <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-chart-line"></i> Выручка за месяц
            </h3>
            
            <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 3rem; font-weight: bold; color: var(--neon-primary); margin-bottom: 1rem;">
                    <?php echo number_format((float)$stats['monthly_revenue'], 0, '', ' '); ?> ₽
                </div>
                <p style="color: var(--text-muted);">Общая выручка за текущий месяц</p>
                
                <div style="margin-top: 2rem;">
                    <a href="reports.php" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Подробные отчеты
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .admin-card {
        background: var(--bg-card);
        padding: 2rem;
        border-radius: var(--border-radius);
        text-decoration: none;
        color: var(--text-primary);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        transition: var(--transition);
        border: 1px solid rgba(255, 0, 255, 0.1);
        text-align: center;
    }
    
    .admin-card:hover {
        border-color: var(--neon-primary);
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(255, 0, 255, 0.2);
    }
    
    .admin-card i {
        font-size: 2.5rem;
        color: var(--neon-primary);
    }
    
    .admin-card span {
        font-weight: 500;
    }
    </style>
</body>
</html>