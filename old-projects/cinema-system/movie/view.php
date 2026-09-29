<?php
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Получаем ID фильма из URL
$movie_id = $_GET['id'] ?? 0;

// Получаем информацию о фильме
$query = "SELECT m.*, 
         COUNT(DISTINCT s.id) as total_sessions,
         COUNT(DISTINCT t.id) as total_tickets
         FROM movies m
         LEFT JOIN sessions s ON m.id = s.movie_id
         LEFT JOIN tickets t ON s.id = t.session_id
         WHERE m.id = :id
         GROUP BY m.id";

$stmt = $conn->prepare($query);
$stmt->execute(['id' => $movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    header("Location: ../index.php");
    exit();
}

// Получаем сеансы на ближайшие 7 дней
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+7 days'));

$sessionsQuery = "SELECT s.*, 
                 COUNT(DISTINCT st.id) as total_seats,
                 SUM(CASE WHEN st.seat_status = 'available' THEN 1 ELSE 0 END) as available_seats
                 FROM sessions s
                 LEFT JOIN seats st ON s.id = st.session_id
                 WHERE s.movie_id = :movie_id 
                 AND s.start_time BETWEEN :start_date AND :end_date
                 AND s.start_time > NOW()
                 GROUP BY s.id
                 ORDER BY s.start_time";

$sessionsStmt = $conn->prepare($sessionsQuery);
$sessionsStmt->execute([
    'movie_id' => $movie_id,
    'start_date' => $start_date . ' 00:00:00',
    'end_date' => $end_date . ' 23:59:59'
]);

// Группируем сеансы по дням
$sessions_by_day = [];
while ($session = $sessionsStmt->fetch(PDO::FETCH_ASSOC)) {
    $day = date('Y-m-d', strtotime($session['start_time']));
    if (!isset($sessions_by_day[$day])) {
        $sessions_by_day[$day] = [];
    }
    $sessions_by_day[$day][] = $session;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - Neon Cinema</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .movie-detail {
            padding: 2rem 0;
        }
        
        .movie-header {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .movie-poster-large {
            width: 100%;
            height: 450px;
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--neon-primary);
            overflow: hidden;
        }
        
        .movie-poster-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .movie-poster-large i {
            font-size: 5rem;
            color: var(--neon-primary);
            opacity: 0.7;
        }
        
        .movie-content h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--neon-primary);
        }
        
        .movie-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat {
            background: rgba(255, 0, 255, 0.05);
            padding: 1rem;
            border-radius: 12px;
            border-left: 3px solid var(--neon-primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--neon-primary);
        }
        
        .movie-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .day-schedule {
            margin-bottom: 2rem;
        }
        
        .day-header {
            background: rgba(255, 0, 255, 0.1);
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border-bottom: 1px solid rgba(255, 0, 255, 0.2);
            color: var(--neon-primary);
            font-weight: bold;
        }
        
        .sessions-container {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        .session-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 0, 255, 0.1);
            transition: var(--transition);
        }
        
        .session-card:hover {
            border-color: var(--neon-primary);
            background: rgba(255, 0, 255, 0.05);
        }
        
        .session-card:last-child {
            margin-bottom: 0;
        }
        
        .session-time {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--neon-primary);
        }
        
        .session-info {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .session-stats {
            text-align: center;
        }
        
        .seats-available {
            color: var(--success);
            font-weight: bold;
        }
        
        .seats-total {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .hall-number {
            background: rgba(255, 0, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: var(--neon-primary);
            font-weight: 500;
        }
        
        .price-tag {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--neon-primary);
        }
        
        .no-sessions {
            text-align: center;
            padding: 3rem;
            background: var(--bg-card);
            border-radius: var(--border-radius);
            color: var(--text-muted);
        }
        
        .age-rating {
            display: inline-block;
            background: var(--neon-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .movie-header {
                grid-template-columns: 1fr;
            }
            
            .movie-poster-large {
                height: 350px;
            }
            
            .session-card {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .session-info {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="movie-detail">
            <!-- Заголовок фильма -->
            <div style="margin-bottom: 2rem;">
                <nav style="margin-bottom: 1rem;">
                    <a href="../index.php" style="color: var(--text-muted); text-decoration: none;">
                        <i class="fas fa-home"></i> Главная
                    </a>
                    <span style="color: var(--text-muted); margin: 0 0.5rem;">›</span>
                    <span style="color: var(--neon-primary);"><?php echo htmlspecialchars($movie['title']); ?></span>
                </nav>
                
                <div class="age-rating">
                    16+
                </div>
            </div>

            <!-- Основная информация о фильме -->
            <div class="movie-header">
                <!-- Постер фильма -->
                <div>
                    <div class="movie-poster-large">
                        <?php if (!empty($movie['poster_filename']) && file_exists('../uploads/posters/' . $movie['poster_filename'])): ?>
                            <img src="../uploads/posters/<?php echo htmlspecialchars($movie['poster_filename']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <?php else: ?>
                            <i class="fas fa-film"></i>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Информация о фильме -->
                <div class="movie-content">
                    <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                    
                    <div class="movie-stats">
                        <div class="stat">
                            <div class="stat-label">Год выпуска</div>
                            <div class="stat-value"><?php echo $movie['release_year'] ?: 'Не указан'; ?></div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Длительность</div>
                            <div class="stat-value">
                                <?php echo floor($movie['duration_minutes']/60); ?>ч <?php echo $movie['duration_minutes']%60; ?>м
                            </div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Режиссер</div>
                            <div class="stat-value"><?php echo htmlspecialchars($movie['director'] ?: 'Не указан'); ?></div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Популярность</div>
                            <div class="stat-value">
                                <i class="fas fa-ticket-alt"></i> <?php echo $movie['total_tickets'] ?: 0; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($movie['description']): ?>
                    <div class="movie-description">
                        <h3 style="color: var(--neon-primary); margin-bottom: 1rem;">
                            <i class="fas fa-info-circle"></i> Описание
                        </h3>
                        <p><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button onclick="shareMovie()" class="btn btn-outline">
                            <i class="fas fa-share-alt"></i> Поделиться
                        </button>
                        <button onclick="addToFavorites(<?php echo $movie['id']; ?>)" class="btn btn-outline">
                            <i class="far fa-heart"></i> В избранное
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Расписание сеансов -->
            <div style="margin-top: 3rem;">
                <h2 style="color: var(--neon-primary); margin-bottom: 2rem;">
                    <i class="fas fa-calendar-alt"></i> Расписание сеансов
                </h2>
                
                <?php if (!empty($sessions_by_day)): ?>
                    <?php foreach ($sessions_by_day as $day => $sessions): 
                        $day_formatted = date('d.m.Y', strtotime($day));
                        $today = date('Y-m-d') == $day;
                    ?>
                    <div class="day-schedule">
                        <div class="day-header">
                            <?php if ($today): ?>
                                <i class="fas fa-star" style="color: var(--warning);"></i>
                            <?php endif; ?>
                            <?php echo $day_formatted; ?>
                            <?php if ($today): ?>
                                <span style="color: var(--success); font-size: 0.9rem; margin-left: 1rem;">
                                    (Сегодня)
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sessions-container">
                            <?php foreach ($sessions as $session): 
                                $session_time = date('H:i', strtotime($session['start_time']));
                                $available = $session['available_seats'] ?? 0;
                                $total = $session['total_seats'] ?? 80;
                                $percentage = $total > 0 ? ($available / $total) * 100 : 0;
                            ?>
                            <div class="session-card">
                                <div class="session-time">
                                    <?php echo $session_time; ?>
                                </div>
                                
                                <div class="session-info">
                                    <div class="session-stats">
                                        <div class="seats-available">
                                            <?php echo $available; ?> мест
                                        </div>
                                        <div class="seats-total">
                                            из <?php echo $total; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="hall-number">
                                        <i class="fas fa-door-open"></i> Зал <?php echo $session['hall_number']; ?>
                                    </div>
                                    
                                    <div class="price-tag">
                                        <?php echo $session['base_price']; ?> ₽
                                    </div>
                                </div>
                                
                                <div>
                                    <?php if ($available > 0): ?>
                                        <a href="booking.php?session_id=<?php echo $session['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="fas fa-ticket-alt"></i> Выбрать места
                                        </a>
                                    <?php else: ?>
                                        <button class="btn" style="background: var(--error); color: white; cursor: not-allowed;">
                                            <i class="fas fa-times"></i> Нет мест
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-sessions">
                        <i class="fas fa-calendar-times" style="font-size: 4rem; margin-bottom: 1rem; color: var(--neon-primary);"></i>
                        <h3>Нет доступных сеансов</h3>
                        <p>На ближайшую неделю нет запланированных сеансов для этого фильма.</p>
                        <div style="margin-top: 1.5rem;">
                            <a href="../index.php" class="btn btn-primary">
                                <i class="fas fa-film"></i> Посмотреть другие фильмы
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Дополнительная информация -->
            <div style="margin-top: 4rem; padding: 2rem; background: rgba(255, 0, 255, 0.05); 
                        border-radius: var(--border-radius); border: 1px solid rgba(255, 0, 255, 0.2);">
                <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-info-circle"></i> Полезная информация
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-clock"></i> Время прибытия
                        </h4>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">
                            Рекомендуем прибыть за 15-20 минут до начала сеанса для прохода в зал.
                        </p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-undo"></i> Возврат билетов
                        </h4>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">
                            Возврат билетов возможен не позднее чем за 1 час до начала сеанса.
                        </p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-chair"></i> Выбор мест
                        </h4>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">
                            Вы можете выбрать любые свободные места на интерактивной схеме зала.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Связанные фильмы -->
            <?php
            // Получаем похожие фильмы
            $similarQuery = "SELECT m.* 
                            FROM movies m
                            WHERE m.id != :movie_id 
                            AND (m.director = :director OR m.release_year = :release_year)
                            ORDER BY RAND()
                            LIMIT 3";
            
            $similarStmt = $conn->prepare($similarQuery);
            $similarStmt->execute([
                'movie_id' => $movie_id,
                'director' => $movie['director'] ?: '',
                'release_year' => $movie['release_year'] ?: 0
            ]);
            
            if ($similarStmt->rowCount() > 0):
            ?>
            <div style="margin-top: 4rem;">
                <h3 style="color: var(--neon-primary); margin-bottom: 2rem;">
                    <i class="fas fa-film"></i> Похожие фильмы
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <?php while ($similar = $similarStmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div style="background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden; 
                                border: 1px solid rgba(255, 0, 255, 0.1);">
                        <div style="height: 150px; background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card)); 
                                    display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <?php if (!empty($similar['poster_filename']) && file_exists('../uploads/posters/' . $similar['poster_filename'])): ?>
                                <img src="../uploads/posters/<?php echo htmlspecialchars($similar['poster_filename']); ?>" 
                                     alt="<?php echo htmlspecialchars($similar['title']); ?>"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-film" style="color: var(--neon-primary); opacity: 0.7;"></i>
                            <?php endif; ?>
                        </div>
                        <div style="padding: 1rem;">
                            <h4 style="font-size: 1rem; margin-bottom: 0.5rem; color: var(--text-primary);">
                                <?php echo htmlspecialchars($similar['title']); ?>
                            </h4>
                            <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">
                                <?php if ($similar['release_year']): ?>
                                    <span><?php echo $similar['release_year']; ?> год</span>
                                <?php endif; ?>
                            </div>
                            <a href="view.php?id=<?php echo $similar['id']; ?>" 
                               class="btn btn-outline" style="width: 100%; padding: 0.5rem; font-size: 0.9rem;">
                                Подробнее
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
    // Поделиться фильмом
    function shareMovie() {
        if (navigator.share) {
            navigator.share({
                title: '<?php echo addslashes($movie["title"]); ?> - Neon Cinema',
                text: 'Смотрите фильм "<?php echo addslashes($movie["title"]); ?>" в кинотеатре Neon Cinema',
                url: window.location.href
            })
            .then(() => console.log('Успешно поделились'))
            .catch((error) => console.log('Ошибка при публикации', error));
        } else {
            // Копирование ссылки в буфер обмена
            navigator.clipboard.writeText(window.location.href)
                .then(() => {
                    alert('Ссылка на фильм скопирована в буфер обмена!');
                })
                .catch(() => {
                    prompt('Скопируйте эту ссылку:', window.location.href);
                });
        }
    }
    
    // Добавить в избранное
    function addToFavorites(movieId) {
        // В реальном проекте здесь будет AJAX запрос к серверу
        const btn = event.target.closest('button');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-heart"></i> В избранном';
            btn.style.color = 'var(--error)';
            btn.style.borderColor = 'var(--error)';
            
            // Сохраняем в localStorage
            let favorites = JSON.parse(localStorage.getItem('movie_favorites') || '[]');
            if (!favorites.includes(movieId)) {
                favorites.push(movieId);
                localStorage.setItem('movie_favorites', JSON.stringify(favorites));
            }
        }
    }
    
    // Проверяем, есть ли фильм в избранном
    document.addEventListener('DOMContentLoaded', function() {
        const favorites = JSON.parse(localStorage.getItem('movie_favorites') || '[]');
        const movieId = <?php echo $movie['id']; ?>;
        
        if (favorites.includes(movieId)) {
            const favoriteBtn = document.querySelector('button[onclick*="addToFavorites"]');
            if (favoriteBtn) {
                favoriteBtn.innerHTML = '<i class="fas fa-heart"></i> В избранном';
                favoriteBtn.style.color = 'var(--error)';
                favoriteBtn.style.borderColor = 'var(--error)';
            }
        }
        
        // Автоматическое обновление доступности мест
        function updateSeatAvailability() {
            document.querySelectorAll('.session-card').forEach(card => {
                const btn = card.querySelector('.btn');
                if (btn && btn.href) {
                    const sessionId = btn.href.match(/session_id=(\d+)/)?.[1];
                    if (sessionId) {
                        fetch(`../api/check_seats.php?session_id=${sessionId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    const availableSeats = data.seats.filter(seat => seat.seat_status === 'available').length;
                                    const seatsElement = card.querySelector('.seats-available');
                                    if (seatsElement) {
                                        seatsElement.textContent = availableSeats + ' мест';
                                        
                                        if (availableSeats === 0) {
                                            btn.innerHTML = '<i class="fas fa-times"></i> Нет мест';
                                            btn.className = 'btn';
                                            btn.style.background = 'var(--error)';
                                            btn.style.color = 'white';
                                            btn.style.cursor = 'not-allowed';
                                            btn.onclick = function(e) {
                                                e.preventDefault();
                                            };
                                        } else if (availableSeats < 10) {
                                            seatsElement.style.color = 'var(--warning)';
                                        } else {
                                            seatsElement.style.color = 'var(--success)';
                                        }
                                    }
                                }
                            })
                            .catch(error => console.error('Error updating seats:', error));
                    }
                }
            });
        }
        
        // Обновляем каждые 30 секунд
        setInterval(updateSeatAvailability, 30000);
        updateSeatAvailability();
    });
    
    // Плавная прокрутка к расписанию
    function scrollToSchedule() {
        const scheduleElement = document.querySelector('.day-schedule:first-of-type');
        if (scheduleElement) {
            window.scrollTo({
                top: scheduleElement.offsetTop - 100,
                behavior: 'smooth'
            });
        }
    }
    
    // Создаем плавающую кнопку для быстрого выбора сеанса
    function createFloatingButton() {
        const firstAvailableSession = document.querySelector('.session-card .btn-primary');
        if (firstAvailableSession) {
            const floatingBtn = document.createElement('a');
            floatingBtn.href = firstAvailableSession.href;
            floatingBtn.innerHTML = '<i class="fas fa-ticket-alt"></i> Купить билет';
            floatingBtn.style.position = 'fixed';
            floatingBtn.style.bottom = '20px';
            floatingBtn.style.right = '20px';
            floatingBtn.style.background = 'linear-gradient(135deg, var(--neon-primary), var(--neon-secondary))';
            floatingBtn.style.color = 'white';
            floatingBtn.style.padding = '1rem 1.5rem';
            floatingBtn.style.borderRadius = 'var(--border-radius)';
            floatingBtn.style.textDecoration = 'none';
            floatingBtn.style.fontWeight = 'bold';
            floatingBtn.style.boxShadow = '0 5px 20px rgba(255, 0, 255, 0.5)';
            floatingBtn.style.zIndex = '1000';
            floatingBtn.style.display = 'flex';
            floatingBtn.style.alignItems = 'center';
            floatingBtn.style.gap = '0.5rem';
            
            document.body.appendChild(floatingBtn);
        }
    }
    
    // Создаем кнопку при загрузке
    document.addEventListener('DOMContentLoaded', createFloatingButton);
    </script>
</body>
</html>