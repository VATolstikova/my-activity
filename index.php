<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neon Cinema - Кинотеатр будущего</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Дополнительные стили для неонового эффекта */
        .neon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            padding: 3rem 0;
        }
        
        .section-title {
            text-align: center;
            margin: 4rem 0 3rem;
            font-size: 2.5rem;
            background: linear-gradient(45deg, var(--neon-primary), var(--neon-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            text-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
        }
        
        .movie-poster-placeholder {
            width: 100%;
            height: 350px;
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 2px solid rgba(255, 0, 255, 0.2);
            overflow: hidden;
            position: relative;
        }
        
        .movie-poster-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .movie-card:hover .movie-poster-placeholder img {
            transform: scale(1.05);
        }
        
        .movie-poster-placeholder i {
            font-size: 4rem;
            color: var(--neon-primary);
            opacity: 0.7;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
        
        .feature-card {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(255, 0, 255, 0.1);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--neon-primary);
            box-shadow: 0 10px 30px rgba(255, 0, 255, 0.2);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .feature-icon i {
            font-size: 2rem;
            color: white;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @media (max-width: 768px) {
            .movie-poster-placeholder {
                height: 250px;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1 class="gradient-text">
                <i class="fas fa-film"></i> NEON CINEMA
            </h1>
            <p style="font-size: 1.2rem; color: var(--text-secondary); max-width: 600px; margin: 0 auto 2rem;">
                Окунитесь в мир кино с самым современным оборудованием и неповторимой атмосферой будущего
            </p>
            <a href="#movies" class="btn btn-primary pulse" style="padding: 1rem 2.5rem; font-size: 1.1rem;">
                <i class="fas fa-play-circle"></i> Смотреть сеансы
            </a>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-couch"></i>
                </div>
                <h3 style="color: var(--neon-primary); margin-bottom: 0.5rem;">Комфортные залы</h3>
                <p style="color: var(--text-muted);">80 удобных мест с современными креслами</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3 style="color: var(--neon-primary); margin-bottom: 0.5rem;">Онлайн-бронирование</h3>
                <p style="color: var(--text-muted);">Бронируйте места не выходя из дома</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-film"></i>
                </div>
                <h3 style="color: var(--neon-primary); margin-bottom: 0.5rem;">Лучшие фильмы</h3>
                <p style="color: var(--text-muted);">Новинки и классика мирового кинематографа</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 style="color: var(--neon-primary); margin-bottom: 0.5rem;">Ежедневно 08:00-23:00</h3>
                <p style="color: var(--text-muted);">Работаем для вас каждый день</p>
            </div>
        </section>

        <!-- Movies Section -->
        <section id="movies">
            <h2 class="section-title">
                <i class="fas fa-calendar-star"></i> ФИЛЬМЫ СЕГОДНЯ
            </h2>
            
            <div class="neon-grid">
                <?php
                // Получаем фильмы на сегодня с исправленным запросом
                $today = date('Y-m-d');
                $query = "SELECT m.*, 
                         MIN(s.start_time) as earliest_session,
                         COUNT(DISTINCT s.id) as session_count
                         FROM movies m
                         LEFT JOIN sessions s ON m.id = s.movie_id 
                         AND DATE(s.start_time) = :today
                         WHERE s.start_time IS NOT NULL
                         GROUP BY m.id
                         ORDER BY earliest_session ASC";
                
                $stmt = $conn->prepare($query);
                $stmt->execute(['today' => $today]);
                
                if ($stmt->rowCount() > 0) {
                    while ($movie = $stmt->fetch(PDO::FETCH_ASSOC)) {
                ?>
                <div class="movie-card">
                    <div class="movie-poster-placeholder">
                        <?php if (!empty($movie['poster_filename']) && file_exists('uploads/posters/' . $movie['poster_filename'])): ?>
                            <img src="uploads/posters/<?php echo htmlspecialchars($movie['poster_filename']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <?php else: ?>
                            <i class="fas fa-film"></i>
                        <?php endif; ?>
                    </div>
                    <div class="movie-info" style="padding: 1.5rem;">
                        <h3 class="movie-title" style="font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--neon-primary);">
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </h3>
                        
                        <div class="movie-meta" style="display: flex; gap: 1rem; color: var(--text-muted); 
                                                      margin: 0.5rem 0; font-size: 0.9rem;">
                            <?php if ($movie['release_year']): ?>
                                <span><i class="fas fa-calendar" style="color: var(--neon-primary);"></i> 
                                    <?php echo $movie['release_year']; ?> год
                                </span>
                            <?php endif; ?>
                            
                            <span><i class="far fa-clock" style="color: var(--neon-primary);"></i> 
                                <?php echo floor($movie['duration_minutes']/60); ?>ч <?php echo $movie['duration_minutes']%60; ?>м
                            </span>
                        </div>
                        
                        <?php if ($movie['director']): ?>
                            <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                                <i class="fas fa-user" style="color: var(--neon-primary);"></i> 
                                <?php echo htmlspecialchars($movie['director']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($movie['session_count'] > 0): ?>
                        <div class="sessions-list" style="margin-top: 1rem;">
                            <p style="color: var(--neon-primary); margin-bottom: 0.5rem; font-weight: bold; font-size: 0.9rem;">
                                <i class="fas fa-ticket-alt"></i> Сеансы на сегодня:
                            </p>
                            <?php
                            // Получаем сеансы на сегодня для этого фильма
                            $sessionQuery = "SELECT s.*, 
                                           COUNT(st.id) as total_seats,
                                           SUM(CASE WHEN st.seat_status = 'available' THEN 1 ELSE 0 END) as available_seats
                                           FROM sessions s
                                           LEFT JOIN seats st ON s.id = st.session_id
                                           WHERE s.movie_id = :movie_id 
                                           AND DATE(s.start_time) = :today
                                           AND s.start_time > NOW()
                                           GROUP BY s.id
                                           ORDER BY s.start_time";
                            $sessionStmt = $conn->prepare($sessionQuery);
                            $sessionStmt->execute([
                                'movie_id' => $movie['id'],
                                'today' => $today
                            ]);
                            
                            while ($session = $sessionStmt->fetch(PDO::FETCH_ASSOC)) {
                                $time = date('H:i', strtotime($session['start_time']));
                                $available = $session['available_seats'] ?? 0;
                                $total = $session['total_seats'] ?? 80;
                                $percentage = $total > 0 ? ($available / $total) * 100 : 0;
                            ?>
                            <a href="movie/booking.php?session_id=<?php echo $session['id']; ?>" 
                               class="session-time" style="position: relative;" 
                               title="Свободных мест: <?php echo $available; ?>/<?php echo $total; ?>">
                               <i class="fas fa-play"></i> <?php echo $time; ?>
                               <span style="font-size: 0.7rem; margin-left: 0.3rem;">
                                   <?php echo $available > 0 ? '✓' : '✗'; ?>
                               </span>
                            </a>
                            <?php } ?>
                        </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
                            <a href="movie/view.php?id=<?php echo $movie['id']; ?>" 
                               class="btn btn-outline" style="flex: 1; padding: 0.6rem; font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> Подробнее
                            </a>
                            <?php if ($movie['session_count'] > 0 && isset($session) && $available > 0): ?>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else {
                ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; 
                            background: linear-gradient(145deg, var(--bg-card), #15151f);
                            border-radius: var(--border-radius); border: 2px solid rgba(255, 0, 255, 0.3);">
                    <i class="fas fa-film-slash" style="font-size: 4rem; color: var(--neon-primary); 
                                                       margin-bottom: 1.5rem; text-shadow: 0 0 20px var(--neon-primary);"></i>
                    <h3 style="color: var(--neon-primary); margin-bottom: 1rem;">Нет сеансов на сегодня</h3>
                    <p style="color: var(--text-gray); max-width: 400px; margin: 0 auto 2rem;">
                        На сегодня нет доступных сеансов. Пожалуйста, загляните завтра!
                    </p>
                    <a href="#features" class="btn btn-primary">
                        <i class="fas fa-film"></i> Посмотреть все фильмы
                    </a>
                </div>
                <?php } ?>
            </div>
        </section>
        
        <!-- All Movies Section -->
        <section id="all-movies" style="margin-top: 4rem;">
            <h2 class="section-title">
                <i class="fas fa-film"></i> ВСЕ ФИЛЬМЫ
            </h2>
            
            <?php
            // Получаем все фильмы
            $allMoviesQuery = "SELECT m.*, 
                              COUNT(DISTINCT s.id) as session_count
                              FROM movies m
                              LEFT JOIN sessions s ON m.id = s.movie_id AND s.start_time > NOW()
                              GROUP BY m.id
                              ORDER BY m.created_at DESC";
            
            $allMoviesStmt = $conn->query($allMoviesQuery);
            ?>
            
            <div class="neon-grid">
                <?php while ($movie = $allMoviesStmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="movie-card">
                    <div class="movie-poster-placeholder">
                        <?php if (!empty($movie['poster_filename']) && file_exists('uploads/posters/' . $movie['poster_filename'])): ?>
                            <img src="uploads/posters/<?php echo htmlspecialchars($movie['poster_filename']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <?php else: ?>
                            <i class="fas fa-film"></i>
                        <?php endif; ?>
                    </div>
                    <div class="movie-info" style="padding: 1.5rem;">
                        <h3 class="movie-title" style="font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--neon-primary);">
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </h3>
                        
                        <div class="movie-meta" style="display: flex; gap: 1rem; color: var(--text-muted); 
                                                      margin: 0.5rem 0; font-size: 0.9rem;">
                            <?php if ($movie['release_year']): ?>
                                <span><i class="fas fa-calendar" style="color: var(--neon-primary);"></i> 
                                    <?php echo $movie['release_year']; ?> год
                                </span>
                            <?php endif; ?>
                            
                            <span><i class="far fa-clock" style="color: var(--neon-primary);"></i> 
                                <?php echo floor($movie['duration_minutes']/60); ?>ч <?php echo $movie['duration_minutes']%60; ?>м
                            </span>
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <?php if ($movie['session_count'] > 0): ?>
                                <span class="status-badge status-active" style="font-size: 0.8rem;">
                                    <i class="fas fa-check-circle"></i> Есть сеансы
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-cancelled" style="font-size: 0.8rem;">
                                    <i class="fas fa-clock"></i> Скоро в прокате
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 1.5rem;">
                            <a href="movie/view.php?id=<?php echo $movie['id']; ?>" 
                               class="btn btn-outline" style="width: 100%; padding: 0.6rem; font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> Подробнее о фильме
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
        
        <!-- Contact Info -->
        <section style="margin: 5rem 0; padding: 3rem; 
                        background: linear-gradient(135deg, rgba(255, 0, 255, 0.05));
                        border-radius: var(--border-radius); border: 1px solid rgba(255, 0, 255, 0.2);">
            <div style="text-align: center;">
                <h2 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-map-marker-alt"></i> КАК НАС НАЙТИ
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; max-width: 900px; margin: 0 auto;">
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-clock"></i> Время работы
                        </h4>
                        <p style="color: var(--text-muted);">
                            Пн-Вс: 08:00 - 23:00<br>
                            Без перерывов
                        </p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-phone"></i> Контакты
                        </h4>
                        <p style="color: var(--text-muted);">
                            +7 (999) 123-45-67<br>
                            info@neoncinema.ru
                        </p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-map"></i> Адрес
                        </h4>
                        <p style="color: var(--text-muted);">
                            г. Москва, ул. Кинотеатральная, 1<br>
                            Станция метро "Кинотеатральная"
                        </p>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <a href="api/check_ticket.php" class="btn btn-primary">
                        <i class="fas fa-qrcode"></i> Проверить билет
                    </a>
                </div>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Скрипт для анимации карточек фильмов
    document.addEventListener('DOMContentLoaded', function() {
        // Добавляем эффект при наведении
        const cards = document.querySelectorAll('.movie-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
                this.style.boxShadow = '0 15px 40px rgba(255, 0, 255, 0.3)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Плавная прокрутка к якорям
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Обновляем время в реальном времени
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('ru-RU', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Создаем элемент времени, если его нет
        if (!document.getElementById('currentTime')) {
            const timeElement = document.createElement('div');
            timeElement.id = 'currentTime';
            timeElement.style.position = 'fixed';
            timeElement.style.bottom = '20px';
            timeElement.style.right = '20px';
            timeElement.style.background = 'rgba(255, 0, 255, 0.1)';
            timeElement.style.color = 'var(--neon-primary)';
            timeElement.style.padding = '0.5rem 1rem';
            timeElement.style.borderRadius = 'var(--border-radius)';
            timeElement.style.fontWeight = 'bold';
            timeElement.style.backdropFilter = 'blur(10px)';
            timeElement.style.zIndex = '1000';
            timeElement.style.border = '1px solid rgba(255, 0, 255, 0.3)';
            document.body.appendChild(timeElement);
            
            updateTime();
            setInterval(updateTime, 60000); // Обновлять каждую минуту
        }
    });
    
    // Проверка сеансов в реальном времени
    function checkSessionAvailability() {
        document.querySelectorAll('.session-time').forEach(link => {
            const href = link.getAttribute('href');
            const sessionId = href.match(/session_id=(\d+)/)?.[1];
            
            if (sessionId) {
                fetch(`api/check_seats.php?session_id=${sessionId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const availableSeats = data.seats.filter(seat => seat.seat_status === 'available').length;
                            if (availableSeats === 0) {
                                link.style.opacity = '0.6';
                                link.style.cursor = 'not-allowed';
                                link.title = 'Нет свободных мест';
                                link.onclick = function(e) {
                                    e.preventDefault();
                                    alert('К сожалению, все места на этот сеанс уже заняты.');
                                };
                            }
                        }
                    })
                    .catch(error => console.error('Error checking seats:', error));
            }
        });
    }
    
    // Проверяем доступность каждые 30 секунд
    setInterval(checkSessionAvailability, 30000);
    checkSessionAvailability();
    </script>
</body>
</html>