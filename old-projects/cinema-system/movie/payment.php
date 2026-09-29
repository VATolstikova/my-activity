<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Проверяем авторизацию
requireAuth();

// Получаем ID сеанса
$session_id = $_GET['session_id'] ?? 0;
if (!$session_id) {
    header("Location: ../index.php");
    exit();
}

// Получаем информацию о сеансе
$session = getSessionInfo($session_id, $conn);
if (!$session) {
    header("Location: ../index.php");
    exit();
}

// Получаем забронированные пользователем места
$query = "SELECT s.*, m.title 
          FROM seats s
          JOIN sessions ss ON s.session_id = ss.id
          JOIN movies m ON ss.movie_id = m.id
          WHERE s.session_id = :session_id 
          AND s.user_id = :user_id 
          AND s.seat_status = 'booked'
          AND s.booked_until > NOW()";
$stmt = $conn->prepare($query);
$stmt->execute([
    'session_id' => $session_id,
    'user_id' => $_SESSION['user_id']
]);

$booked_seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($booked_seats)) {
    header("Location: booking.php?session_id=" . $session_id . "&error=no_bookings");
    exit();
}

// Обработка оплаты
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Генерируем билеты
    $tickets_created = [];
    
    foreach ($booked_seats as $seat) {
        // Генерируем уникальный номер билета
        $ticket_number = generateTicketNumber();
        
        // Создаем билет
        $ticketQuery = "INSERT INTO tickets 
                       (ticket_number, user_id, session_id, seat_id, price, qr_code_path)
                       VALUES (:ticket_number, :user_id, :session_id, :seat_id, :price, :qr_code)";
        
        $ticketStmt = $conn->prepare($ticketQuery);
        
        $qr_code = generateQRCode($ticket_number);
        
        $ticketStmt->execute([
            'ticket_number' => $ticket_number,
            'user_id' => $_SESSION['user_id'],
            'session_id' => $session_id,
            'seat_id' => $seat['id'],
            'price' => $session['base_price'],
            'qr_code' => $qr_code
        ]);
        
        $ticket_id = $conn->lastInsertId();
        $tickets_created[] = $ticket_id;
        
        // Обновляем статус места
        $updateSeatQuery = "UPDATE seats SET seat_status = 'sold' WHERE id = :seat_id";
        $updateStmt = $conn->prepare($updateSeatQuery);
        $updateStmt->execute(['seat_id' => $seat['id']]);
    }
    
    $success = 'Оплата прошла успешно! Билеты созданы.';
    header("Refresh: 2; URL=../user/orders.php");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата - <?php echo htmlspecialchars($session['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="movie-detail">
            <div class="movie-header">
                <div>
                    <div class="movie-poster-placeholder">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
                
                <div class="movie-content">
                    <h1>Оплата билетов</h1>
                    
                    <div class="movie-stats">
                        <div class="stat">
                            <div class="stat-label">Фильм</div>
                            <div class="stat-value"><?php echo htmlspecialchars($session['title']); ?></div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Дата и время</div>
                            <div class="stat-value">
                                <?php echo date('d.m.Y H:i', strtotime($session['start_time'])); ?>
                            </div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Зал</div>
                            <div class="stat-value">№ <?php echo $session['hall_number']; ?></div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Цена за место</div>
                            <div class="stat-value"><?php echo $session['base_price']; ?> ₽</div>
                        </div>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Информация о заказе -->
            <div style="margin: 3rem 0;">
                <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-shopping-bag"></i> Детали заказа
                </h3>
                
                <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius);">
                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--text-secondary); margin-bottom: 1rem;">Выбранные места:</h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <?php foreach ($booked_seats as $seat): ?>
                                <span class="session-time"><?php echo $seat['seat_number']; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid rgba(255, 0, 255, 0.2); padding-top: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Количество мест:</span>
                            <span><?php echo count($booked_seats); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Цена за место:</span>
                            <span><?php echo $session['base_price']; ?> ₽</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.2rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255, 0, 255, 0.2);">
                            <span>Итого к оплате:</span>
                            <span style="color: var(--neon-primary);">
                                <?php echo $session['base_price'] * count($booked_seats); ?> ₽
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Форма оплаты -->
            <form method="POST" action="" id="payment-form">
                <div style="background: var(--bg-card); padding: 2.5rem; border-radius: var(--border-radius);">
                    <h3 style="color: var(--neon-primary); margin-bottom: 2rem;">
                        <i class="fas fa-credit-card"></i> Способ оплаты
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="payment-method">
                            <input type="radio" id="card" name="payment_method" value="card" checked>
                            <label for="card" class="payment-label">
                                <i class="fas fa-credit-card"></i>
                                <span>Банковская карта</span>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="qiwi" name="payment_method" value="qiwi">
                            <label for="qiwi" class="payment-label">
                                <i class="fas fa-wallet"></i>
                                <span>QIWI Кошелек</span>
                            </label>
                        </div>
                        
                        <div class="payment-method">
                            <input type="radio" id="yoomoney" name="payment_method" value="yoomoney">
                            <label for="yoomoney" class="payment-label">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>ЮMoney</span>
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255, 0, 255, 0.2);">
                        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                            <a href="booking.php?session_id=<?php echo $session_id; ?>" 
                               class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Назад
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Оплатить <?php echo $session['base_price'] * count($booked_seats); ?> ₽
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Информация о бронировании -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(255, 0, 255, 0.05); 
                        border-radius: var(--border-radius); border: 1px solid rgba(255, 0, 255, 0.2);">
                <p style="color: var(--text-muted);">
                    <i class="fas fa-info-circle"></i> 
                    Ваше бронирование активно до: 
                    <span style="color: var(--neon-primary); font-weight: bold;">
                        <?php echo date('H:i', strtotime('+15 minutes')); ?>
                    </span>
                </p>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <style>
    .payment-method {
        display: flex;
        align-items: center;
    }
    
    .payment-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid rgba(255, 0, 255, 0.3);
        border-radius: var(--border-radius);
        width: 100%;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .payment-label:hover {
        background: rgba(255, 0, 255, 0.1);
        border-color: var(--neon-primary);
    }
    
    .payment-method input[type="radio"]:checked + .payment-label {
        background: rgba(255, 0, 255, 0.15);
        border-color: var(--neon-primary);
        color: var(--neon-primary);
    }
    
    .payment-method input[type="radio"] {
        display: none;
    }
    </style>
    
    <script>
    // Таймер обратного отсчета
    const bookingEndTime = new Date(<?php echo time() * 1000 + 900000; ?>); // +15 минут
    
    function updateTimer() {
        const now = new Date();
        const diff = bookingEndTime - now;
        
        if (diff <= 0) {
            document.getElementById('timer').textContent = '00:00';
            alert('Время бронирования истекло!');
            window.location.href = 'booking.php?session_id=<?php echo $session_id; ?>';
            return;
        }
        
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        document.getElementById('timer').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    setInterval(updateTimer, 1000);
    updateTimer();
    </script>
</body>
</html>