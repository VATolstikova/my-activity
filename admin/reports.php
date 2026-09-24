<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Проверяем права администратора
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Получаем параметры фильтрации
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$movie_id = $_GET['movie_id'] ?? 0;

// Получаем статистику продаж
$salesQuery = "SELECT DATE(t.purchase_time) as sale_date,
               COUNT(t.id) as ticket_count,
               SUM(t.price) as total_revenue,
               COUNT(DISTINCT t.user_id) as unique_customers
               FROM tickets t
               WHERE DATE(t.purchase_time) BETWEEN :start_date AND :end_date";
               
$params = [
    'start_date' => $start_date,
    'end_date' => $end_date
];

if ($movie_id) {
    $salesQuery .= " AND t.session_id IN (SELECT id FROM sessions WHERE movie_id = :movie_id)";
    $params['movie_id'] = $movie_id;
}

$salesQuery .= " GROUP BY DATE(t.purchase_time) ORDER BY sale_date DESC";

$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute($params);
$salesData = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем статистику по фильмам
$moviesStatsQuery = "SELECT m.title,
                    COUNT(t.id) as ticket_count,
                    SUM(t.price) as total_revenue,
                    AVG(t.price) as avg_price
                    FROM movies m
                    LEFT JOIN sessions s ON m.id = s.movie_id
                    LEFT JOIN tickets t ON s.id = t.session_id
                    WHERE DATE(t.purchase_time) BETWEEN :start_date AND :end_date
                    GROUP BY m.id
                    ORDER BY total_revenue DESC";

$moviesStatsStmt = $conn->prepare($moviesStatsQuery);
$moviesStatsStmt->execute($params);
$moviesStats = $moviesStatsStmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список фильмов для фильтра
$moviesQuery = "SELECT * FROM movies ORDER BY title";
$moviesStmt = $conn->query($moviesQuery);
$all_movies = $moviesStmt->fetchAll(PDO::FETCH_ASSOC);

// Итоговая статистика
$total_revenue = array_sum(array_column($salesData, 'total_revenue'));
$total_tickets = array_sum(array_column($salesData, 'ticket_count'));
$avg_ticket_price = $total_tickets > 0 ? $total_revenue / $total_tickets : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты - Админ-панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div style="margin: 2rem 0;">
            <h1 class="gradient-text">
                <i class="fas fa-chart-bar"></i> Отчеты и аналитика
            </h1>
            <p style="color: var(--text-muted);">Статистика продаж и анализ данных</p>
        </div>

        <!-- Фильтры -->
        <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
            <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-filter"></i> Фильтры отчетов
            </h3>
            
            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Начальная дата</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Конечная дата</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Фильм</label>
                    <select name="movie_id" class="form-control">
                        <option value="0">Все фильмы</option>
                        <?php foreach ($all_movies as $movie): ?>
                            <option value="<?php echo $movie['id']; ?>" <?php echo $movie_id == $movie['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($movie['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; align-items: flex-end; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Применить
                    </button>
                    <a href="reports.php" class="btn btn-outline">
                        <i class="fas fa-redo"></i> Сбросить
                    </a>
                </div>
            </form>
        </div>

        <!-- Итоговая статистика -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary)); padding: 2rem; border-radius: var(--border-radius); color: white;">
                <div style="font-size: 2.5rem; font-weight: bold;"><?php echo $total_tickets; ?></div>
                <div>Билетов продано</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #00ff9d, #00cc7a); padding: 2rem; border-radius: var(--border-radius); color: white;">
                <div style="font-size: 2.5rem; font-weight: bold;"><?php echo number_format((float)$total_revenue, 0, '', ' '); ?> ₽</div>
                <div>Общая выручка</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #ffaa00, #cc8800); padding: 2rem; border-radius: var(--border-radius); color: white;">
                <div style="font-size: 2.5rem; font-weight: bold;"><?php echo count($salesData); ?></div>
                <div>Дней продаж</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #ff3d71, #cc315a); padding: 2rem; border-radius: var(--border-radius); color: white;">
                <div style="font-size: 2.5rem; font-weight: bold;"><?php echo number_format((float)$avg_ticket_price, 0, '', ' '); ?> ₽</div>
                <div>Средний чек</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
            <!-- Ежедневные продажи -->
            <div>
                <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-calendar-day"></i> Ежедневные продажи
                </h3>
                
                <div style="background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Дата</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Билеты</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Клиенты</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Выручка</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesData as $day): ?>
                                <tr style="border-bottom: 1px solid rgba(255, 0, 255, 0.1);">
                                    <td style="padding: 1rem;">
                                        <?php echo date('d.m.Y', strtotime($day['sale_date'])); ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <span class="status-badge status-active"><?php echo $day['ticket_count']; ?></span>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?php echo $day['unique_customers']; ?>
                                    </td>
                                    <td style="padding: 1rem; color: var(--neon-primary); font-weight: bold; text-align: right;">
                                        <?php echo number_format((float)$day['total_revenue'], 0, '', ' '); ?> ₽
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Статистика по фильмам -->
            <div>
                <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-film"></i> Статистика по фильмам
                </h3>
                
                <div style="background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Фильм</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Билеты</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Выручка</th>
                                    <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Средний чек</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moviesStats as $movie): 
                                    if ($movie['ticket_count'] > 0):
                                ?>
                                <tr style="border-bottom: 1px solid rgba(255, 0, 255, 0.1);">
                                    <td style="padding: 1rem;">
                                        <strong><?php echo htmlspecialchars($movie['title']); ?></strong>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <span class="status-badge status-active"><?php echo $movie['ticket_count']; ?></span>
                                    </td>
                                    <td style="padding: 1rem; color: var(--neon-primary); font-weight: bold;">
                                        <?php echo number_format((float)$movie['total_revenue'], 0, '', ' '); ?> ₽
                                    </td>
                                    <td style="padding: 1rem;">
                                        <?php echo number_format((float)$movie['avg_price'], 0, '', ' '); ?> ₽
                                    </td>
                                </tr>
                                <?php endif; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Экспорт данных -->
        <div style="text-align: center; padding: 2rem; background: var(--bg-card); border-radius: var(--border-radius);">
            <h3 style="color: var(--neon-primary); margin-bottom: 1rem;">
                <i class="fas fa-file-export"></i> Экспорт данных
            </h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                Экспортируйте данные отчетов в различных форматах
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="export.php?type=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&movie_id=<?php echo $movie_id; ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <a href="export.php?type=pdf&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&movie_id=<?php echo $movie_id; ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <button onclick="printReport()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Печать
                </button>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
    function printReport() {
        window.print();
    }
    </script>
    
    <style>
    @media print {
        .navbar, .footer, .btn, a {
            display: none !important;
        }
        
        .container {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
    </style>
</body>
</html>