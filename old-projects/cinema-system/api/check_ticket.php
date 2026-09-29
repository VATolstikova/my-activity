<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$ticket_number = $_GET['ticket'] ?? '';

if (empty($ticket_number)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Номер билета не указан'
    ]);
    exit();
}

// Ищем билет
$query = "SELECT t.*, m.title, s.start_time, s.hall_number, 
                 st.seat_number, u.full_name as user_name
          FROM tickets t
          JOIN sessions s ON t.session_id = s.id
          JOIN movies m ON s.movie_id = m.id
          JOIN seats st ON t.seat_id = st.id
          JOIN users u ON t.user_id = u.id
          WHERE t.ticket_number = :ticket_number";

$stmt = $conn->prepare($query);
$stmt->execute(['ticket_number' => $ticket_number]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Билет не найден',
        'ticket_valid' => false
    ]);
    exit();
}

// Проверяем актуальность билета
$is_valid = ($ticket['ticket_status'] === 'active' && strtotime($ticket['start_time']) > time());

// Обновляем статус, если сеанс уже прошел
if (!$is_valid && $ticket['ticket_status'] === 'active') {
    $updateQuery = "UPDATE tickets SET ticket_status = 'expired' WHERE ticket_number = :ticket_number";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute(['ticket_number' => $ticket_number]);
    $ticket['ticket_status'] = 'expired';
}

// Отмечаем как использованный, если проверка проходит
if ($is_valid && isset($_GET['use']) && $_GET['use'] == 'true') {
    $useQuery = "UPDATE tickets SET ticket_status = 'used' WHERE ticket_number = :ticket_number";
    $useStmt = $conn->prepare($useQuery);
    $useStmt->execute(['ticket_number' => $ticket_number]);
    $ticket['ticket_status'] = 'used';
}

// Формируем ответ
$response = [
    'status' => 'success',
    'ticket_number' => $ticket['ticket_number'],
    'movie_title' => $ticket['title'],
    'start_time' => $ticket['start_time'],
    'hall_number' => $ticket['hall_number'],
    'seat_number' => $ticket['seat_number'],
    'user_name' => $ticket['user_name'],
    'ticket_status' => $ticket['ticket_status'],
    'ticket_valid' => $is_valid,
    'checked_at' => date('Y-m-d H:i:s')
];

// Если запрос через браузер, показываем красивую страницу
if (!isset($_GET['json']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Проверка билета - Neon Cinema</title>
        <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            .check-container {
                max-width: 600px;
                margin: 4rem auto;
                text-align: center;
            }
            
            .ticket-status {
                padding: 1rem 2rem;
                border-radius: var(--border-radius);
                font-size: 1.5rem;
                font-weight: bold;
                margin: 2rem 0;
            }
            
            .status-valid {
                background: rgba(0, 255, 157, 0.1);
                color: var(--success);
                border: 2px solid var(--success);
            }
            
            .status-invalid {
                background: rgba(255, 61, 113, 0.1);
                color: var(--error);
                border: 2px solid var(--error);
            }
            
            .ticket-details {
                background: var(--bg-card);
                padding: 2rem;
                border-radius: var(--border-radius);
                text-align: left;
                margin-top: 2rem;
            }
            
            .ticket-row {
                display: flex;
                justify-content: space-between;
                padding: 0.75rem 0;
                border-bottom: 1px solid rgba(255, 0, 255, 0.1);
            }
            
            .ticket-row:last-child {
                border-bottom: none;
            }
        </style>
    </head>
    <body>
        <?php include '../includes/header.php'; ?>
        
        <div class="container">
            <div class="check-container">
                <h1 class="gradient-text">
                    <i class="fas fa-qrcode"></i> Проверка билета
                </h1>
                
                <div class="ticket-status <?php echo $is_valid ? 'status-valid' : 'status-invalid'; ?>">
                    <?php if ($is_valid): ?>
                        <i class="fas fa-check-circle"></i> БИЛЕТ АКТУАЛЕН
                    <?php else: ?>
                        <i class="fas fa-times-circle"></i> БИЛЕТ НЕАКТУАЛЕН
                    <?php endif; ?>
                </div>
                
                <div class="ticket-details">
                    <div class="ticket-row">
                        <span style="color: var(--text-muted);">Номер билета:</span>
                        <span style="font-weight: bold; color: var(--neon-primary);"><?php echo $ticket['ticket_number']; ?></span>
                    </div>
                    
                    <div class="ticket-row">
                        <span style="color: var(--text-muted);">Фильм:</span>
                        <span><?php echo htmlspecialchars($ticket['movie_title']); ?></span>
                    </div>
                    
                    <div class="ticket-row">
                        <span style="color: var(--text-muted);">Дата и время:</span>
                        <span><?php echo date('d.m.Y H:i', strtotime($ticket['start_time'])); ?></span>
                    </div>
                    
                    <div class="ticket-row">
                        <span style="color: var(--text-muted);">Зал:</span>
                        <span>№ <?php echo $ticket['hall_number']; ?></span>
                    </div>
                    
                    <div class="ticket-row">
                        <span style="color: var(--text-muted);">Место:</span>
                        <span><?php echo $ticket['seat_number']; ?></span>
                    </div>
                    
                    <div class="ticket-row">
                        <span style="color: var(--text-muted);">Владелец:</span>
                        <span><?php echo htmlspecialchars($ticket['user_name']); ?></span>
                    </div>
                    
                    <div class="ticket-row">
                        <span style="color: var(--text-muted);">Статус:</span>
                        <span class="status-badge <?php echo $is_valid ? 'status-active' : 'status-cancelled'; ?>">
                            <?php 
                            $status_text = 'Активен';
                            if (!$is_valid) {
                                $status_text = $ticket['ticket_status'] === 'expired' ? 'Просрочен' : 'Неактивен';
                            }
                            echo $status_text;
                            ?>
                        </span>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <?php if ($is_valid): ?>
                        <button onclick="useTicket()" class="btn btn-primary">
                            <i class="fas fa-check"></i> Отметить как использованный
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline" style="margin-left: 1rem;">
                        <i class="fas fa-redo"></i> Проверить другой
                    </a>
                </div>
            </div>
        </div>
        
        <?php include '../includes/footer.php'; ?>
        
        <script>
        function useTicket() {
            if (confirm('Отметить билет как использованный? Это действие нельзя отменить.')) {
                window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?ticket=<?php echo $ticket_number; ?>&use=true';
            }
        }
        </script>
    </body>
    </html>
    <?php
    exit();
}

echo json_encode($response);
?>