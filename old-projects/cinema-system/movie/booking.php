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

// Проверяем, можно ли бронировать билеты на этот сеанс
if (!canBookSeats($session_id, $_SESSION['user_id'], $conn)) {
    header("Location: ../index.php?error=max_tickets");
    exit();
}

// Получаем карту мест
$seats = getSeatMap($session_id, $conn);

// Обработка бронирования
$error = '';
$success = '';
$selected_seats = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selected_seats']) && !empty($_POST['selected_seats'])) {
        $selected_seats = json_decode($_POST['selected_seats'], true);
        
        // Проверяем количество выбранных мест
        if (count($selected_seats) > 5) {
            $error = 'Можно выбрать не более 5 мест';
        } elseif (count($selected_seats) == 0) {
            $error = 'Выберите хотя бы одно место';
        } else {
            // Проверяем доступность всех выбранных мест
            $all_available = true;
            foreach ($selected_seats as $seat_id) {
                if (!isSeatAvailable($seat_id, $conn)) {
                    $all_available = false;
                    $error = 'Одно или несколько выбранных мест уже заняты';
                    break;
                }
            }
            
            if ($all_available) {
                // Бронируем места на 15 минут
                $booked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                foreach ($selected_seats as $seat_id) {
                    $query = "UPDATE seats SET 
                             seat_status = 'booked',
                             user_id = :user_id,
                             booked_until = :booked_until
                             WHERE id = :seat_id 
                             AND seat_status = 'available'";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        'user_id' => $_SESSION['user_id'],
                        'booked_until' => $booked_until,
                        'seat_id' => $seat_id
                    ]);
                }
                
                $success = 'Места успешно забронированы! У вас есть 15 минут для оплаты.';
                header("Refresh: 2; URL=payment.php?session_id=" . $session_id);
            }
        }
    } else {
        $error = 'Выберите места для бронирования';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование мест - <?php echo htmlspecialchars($session['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="movie-detail">
            <!-- Информация о фильме -->
            <div class="movie-header">
                <div>
                    <div class="movie-poster-placeholder">
                        <i class="fas fa-film"></i>
                    </div>
                </div>
                
                <div class="movie-content">
                    <h1><?php echo htmlspecialchars($session['title']); ?></h1>
                    
                    <div class="movie-stats">
                        <div class="stat">
                            <div class="stat-label">Дата и время</div>
                            <div class="stat-value">
                                <?php echo date('d.m.Y H:i', strtotime($session['start_time'])); ?>
                            </div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Длительность</div>
                            <div class="stat-value">
                                <?php echo floor($session['duration_minutes']/60); ?>ч <?php echo $session['duration_minutes']%60; ?>м
                            </div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Цена за место</div>
                            <div class="stat-value">
                                <?php echo $session['base_price']; ?> ₽
                            </div>
                        </div>
                        
                        <div class="stat">
                            <div class="stat-label">Зал</div>
                            <div class="stat-value">
                                № <?php echo $session['hall_number']; ?>
                            </div>
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
            
            <!-- Карта зала -->
            <div class="cinema-hall">
                <div class="hall-screen"></div>
                
                <div id="seat-map">
                    <?php for ($section = 1; $section <= 4; $section++): ?>
                        <div class="section-divider">Секция <?php echo $section; ?></div>
                        <div class="seats-grid">
                            <?php if (isset($seats[$section])): ?>
                                <?php foreach ($seats[$section] as $row => $row_seats): ?>
                                    <?php foreach ($row_seats as $col => $seat): ?>
                                        <div class="seat 
                                            <?php echo $seat['seat_status'] == 'available' ? 'seat-available' : 'seat-unavailable'; ?>"
                                            data-seat-id="<?php echo $seat['id']; ?>"
                                            data-seat-number="<?php echo $seat['seat_number']; ?>"
                                            data-price="<?php echo $session['base_price']; ?>">
                                            <?php echo $seat['seat_number']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <!-- Легенда -->
                <div style="display: flex; justify-content: center; gap: 2rem; margin: 2rem 0; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div class="seat seat-available" style="width: 20px; height: 20px;"></div>
                        <span>Свободно</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div class="seat seat-unavailable" style="width: 20px; height: 20px;"></div>
                        <span>Занято</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div class="seat seat-selected" style="width: 20px; height: 20px;"></div>
                        <span>Выбрано</span>
                    </div>
                </div>
                
                <!-- Форма бронирования -->
                <form method="POST" action="" id="booking-form">
                    <input type="hidden" name="selected_seats" id="selected-seats-input">
                    
                    <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); margin-top: 2rem;">
                        <h3 style="margin-bottom: 1.5rem; color: var(--neon-primary);">
                            <i class="fas fa-shopping-cart"></i> Ваш заказ
                        </h3>
                        
                        <div id="selected-seats-list" style="margin-bottom: 1.5rem; min-height: 50px;">
                            <p style="color: var(--text-muted);">Выберите места на схеме</p>
                        </div>
                        
                        <div id="order-summary" style="display: none;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <span>Количество мест:</span>
                                <span id="seats-count">0</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-weight: bold;">
                                <span>Итого:</span>
                                <span id="total-price">0 ₽</span>
                            </div>
                            
                            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                                <button type="button" class="btn btn-outline" onclick="clearSelection()">
                                    <i class="fas fa-trash"></i> Очистить
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-lock"></i> Забронировать
                                </button>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 0, 255, 0.2);">
                            <p style="color: var(--text-muted); font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> Бронирование сохраняется на 15 минут.
                                После этого места снова становятся доступными.
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
    let selectedSeats = [];
    let seatPrices = {};
    
    // Инициализация карты мест
    document.addEventListener('DOMContentLoaded', function() {
        // Собираем информацию о ценах
        document.querySelectorAll('.seat').forEach(seat => {
            const seatId = seat.getAttribute('data-seat-id');
            const price = seat.getAttribute('data-price');
            seatPrices[seatId] = parseInt(price);
        });
        
        // Обработка клика по месту
        document.querySelectorAll('.seat-available').forEach(seat => {
            seat.addEventListener('click', function() {
                const seatId = this.getAttribute('data-seat-id');
                const seatNumber = this.getAttribute('data-seat-number');
                
                if (this.classList.contains('seat-selected')) {
                    // Убираем из выбранных
                    this.classList.remove('seat-selected');
                    selectedSeats = selectedSeats.filter(id => id !== seatId);
                } else {
                    // Проверяем лимит в 5 мест
                    if (selectedSeats.length >= 5) {
                        alert('Можно выбрать не более 5 мест');
                        return;
                    }
                    
                    // Добавляем в выбранные
                    this.classList.add('seat-selected');
                    selectedSeats.push(seatId);
                }
                
                updateOrderSummary();
                document.getElementById('selected-seats-input').value = JSON.stringify(selectedSeats);
            });
        });
        
        updateOrderSummary();
    });
    
    function updateOrderSummary() {
        const seatsList = document.getElementById('selected-seats-list');
        const orderSummary = document.getElementById('order-summary');
        const seatsCount = document.getElementById('seats-count');
        const totalPrice = document.getElementById('total-price');
        
        if (selectedSeats.length === 0) {
            seatsList.innerHTML = '<p style="color: var(--text-muted);">Выберите места на схеме</p>';
            orderSummary.style.display = 'none';
            return;
        }
        
        // Показываем выбранные места
        let seatsHtml = '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">';
        selectedSeats.forEach(seatId => {
            const seatElement = document.querySelector(`[data-seat-id="${seatId}"]`);
            const seatNumber = seatElement.getAttribute('data-seat-number');
            seatsHtml += `<span class="session-time">${seatNumber}</span>`;
        });
        seatsHtml += '</div>';
        seatsList.innerHTML = seatsHtml;
        
        // Считаем сумму
        let total = 0;
        selectedSeats.forEach(seatId => {
            total += seatPrices[seatId];
        });
        
        seatsCount.textContent = selectedSeats.length;
        totalPrice.textContent = total + ' ₽';
        orderSummary.style.display = 'block';
    }
    
    function clearSelection() {
        selectedSeats.forEach(seatId => {
            const seatElement = document.querySelector(`[data-seat-id="${seatId}"]`);
            seatElement.classList.remove('seat-selected');
        });
        
        selectedSeats = [];
        document.getElementById('selected-seats-input').value = '';
        updateOrderSummary();
    }
    
    // Автоматическое обновление статуса мест
    function refreshSeatStatus() {
        fetch(`../api/check_seats.php?session_id=<?php echo $session_id; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    data.seats.forEach(seat => {
                        const seatElement = document.querySelector(`[data-seat-id="${seat.id}"]`);
                        if (seatElement) {
                            if (seat.seat_status !== 'available' && !selectedSeats.includes(seat.id.toString())) {
                                seatElement.className = 'seat seat-unavailable';
                                seatElement.onclick = null;
                            }
                        }
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Обновляем статус каждые 10 секунд
    setInterval(refreshSeatStatus, 10000);
    </script>
</body>
</html>