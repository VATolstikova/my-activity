<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Проверяем авторизацию
requireAuth();

// Получаем историю заказов пользователя
$query = "SELECT t.*, m.title, m.duration_minutes, s.start_time, s.hall_number,
                 st.seat_number, st.section, st.seat_row, st.seat_col
          FROM tickets t
          JOIN sessions s ON t.session_id = s.id
          JOIN movies m ON s.movie_id = m.id
          JOIN seats st ON t.seat_id = st.id
          WHERE t.user_id = :user_id
          ORDER BY t.purchase_time DESC";

$stmt = $conn->prepare($query);
$stmt->execute(['user_id' => $_SESSION['user_id']]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Фильтрация по статусу
$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all') {
    $orders = array_filter($orders, function($order) use ($filter) {
        return $order['ticket_status'] === $filter;
    });
}

// Отмена билета
if (isset($_GET['cancel']) && $_GET['cancel']) {
    $ticket_id = $_GET['cancel'];
    
    // Проверяем, принадлежит ли билет пользователю
    $checkQuery = "SELECT * FROM tickets WHERE id = :ticket_id AND user_id = :user_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([
        'ticket_id' => $ticket_id,
        'user_id' => $_SESSION['user_id']
    ]);
    
    $ticket = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ticket && $ticket['ticket_status'] === 'active') {
        // Отменяем билет
        $updateQuery = "UPDATE tickets SET ticket_status = 'cancelled' WHERE id = :ticket_id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute(['ticket_id' => $ticket_id]);
        
        // Освобождаем место
        $seatQuery = "UPDATE seats SET seat_status = 'available', user_id = NULL WHERE id = :seat_id";
        $seatStmt = $conn->prepare($seatQuery);
        $seatStmt->execute(['seat_id' => $ticket['seat_id']]);
        
        header("Location: orders.php?success=cancelled");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои билеты - Neon Cinema</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div style="margin: 2rem 0;">
            <h1 class="gradient-text">
                <i class="fas fa-ticket-alt"></i> Мои билеты
            </h1>
            <p style="color: var(--text-muted);">История всех ваших покупок и бронирований</p>
        </div>

        <!-- Фильтры -->
        <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="?filter=all" 
                   class="<?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline'; ?>" 
                   style="padding: 0.5rem 1rem; text-decoration: none;">
                    Все
                </a>
                <a href="?filter=active" 
                   class="<?php echo $filter === 'active' ? 'btn-primary' : 'btn-outline'; ?>" 
                   style="padding: 0.5rem 1rem; text-decoration: none;">
                    Активные
                </a>
                <a href="?filter=used" 
                   class="<?php echo $filter === 'used' ? 'btn-primary' : 'btn-outline'; ?>" 
                   style="padding: 0.5rem 1rem; text-decoration: none;">
                    Использованные
                </a>
                <a href="?filter=cancelled" 
                   class="<?php echo $filter === 'cancelled' ? 'btn-primary' : 'btn-outline'; ?>" 
                   style="padding: 0.5rem 1rem; text-decoration: none;">
                    Отмененные
                </a>
            </div>
        </div>

        <!-- Список билетов -->
        <div class="orders-table">
            <?php if (count($orders) > 0): ?>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Фильм</th>
                            <th>Дата сеанса</th>
                            <th>Место</th>
                            <th>Цена</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="padding: 1rem;">
                                    <strong><?php echo htmlspecialchars($order['title']); ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        Зал <?php echo $order['hall_number']; ?>
                                    </small>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo date('d.m.Y', strtotime($order['start_time'])); ?><br>
                                    <small style="color: var(--text-muted);">
                                        <?php echo date('H:i', strtotime($order['start_time'])); ?>
                                    </small>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo $order['seat_number']; ?><br>
                                    <small style="color: var(--text-muted);">
                                        Ряд <?php echo $order['seat_row']; ?>, Место <?php echo $order['seat_col']; ?>
                                    </small>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo $order['price']; ?> ₽
                                </td>
                                <td style="padding: 1rem;">
                                    <?php 
                                    $status_badge_class = 'status-active';
                                    $status_text = 'Активен';
                                    
                                    switch ($order['ticket_status']) {
                                        case 'cancelled':
                                            $status_badge_class = 'status-cancelled';
                                            $status_text = 'Отменен';
                                            break;
                                        case 'used':
                                            $status_badge_class = 'status-used';
                                            $status_text = 'Использован';
                                            break;
                                        case 'expired':
                                            $status_badge_class = 'status-expired';
                                            $status_text = 'Просрочен';
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $status_badge_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="javascript:void(0);" 
                                           onclick="printTicket('<?php echo $order['ticket_number']; ?>')"
                                           class="btn btn-outline" 
                                           style="padding: 0.5rem; font-size: 0.9rem;">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        
                                        <?php if ($order['ticket_status'] === 'active'): ?>
                                            <a href="?cancel=<?php echo $order['id']; ?>" 
                                               class="btn" 
                                               style="padding: 0.5rem; font-size: 0.9rem; background: var(--error); color: white;"
                                               onclick="return confirm('Вы уверены, что хотите отменить билет?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="../api/check_ticket.php?ticket=<?php echo urlencode($order['ticket_number']); ?>" 
                                           target="_blank"
                                           class="btn btn-outline" 
                                           style="padding: 0.5rem; font-size: 0.9rem;">
                                            <i class="fas fa-qrcode"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem;">
                    <i class="fas fa-ticket-alt" style="font-size: 4rem; color: var(--neon-primary); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Билетов не найдено</h3>
                    <p style="color: var(--text-muted); margin-bottom: 2rem;">
                        <?php if ($filter !== 'all'): ?>
                            У вас нет билетов со статусом "<?php echo $filter; ?>"
                        <?php else: ?>
                            У вас еще нет купленных билетов
                        <?php endif; ?>
                    </p>
                    <a href="../index.php" class="btn btn-primary">
                        <i class="fas fa-film"></i> Выбрать фильм
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Статистика -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 3rem;">
            <?php
            $statsQuery = "SELECT 
                          COUNT(*) as total,
                          COUNT(CASE WHEN ticket_status = 'active' THEN 1 END) as active,
                          COUNT(CASE WHEN ticket_status = 'used' THEN 1 END) as used,
                          COUNT(CASE WHEN ticket_status = 'cancelled' THEN 1 END) as cancelled
                          FROM tickets
                          WHERE user_id = :user_id";
            
            $statsStmt = $conn->prepare($statsQuery);
            $statsStmt->execute(['user_id' => $_SESSION['user_id']]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--neon-primary);">
                    <?php echo $stats['total'] ?? 0; ?>
                </div>
                <div style="color: var(--text-muted);">Всего билетов</div>
            </div>
            
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--success);">
                    <?php echo $stats['active'] ?? 0; ?>
                </div>
                <div style="color: var(--text-muted);">Активные</div>
            </div>
            
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--text-muted);">
                    <?php echo $stats['used'] ?? 0; ?>
                </div>
                <div style="color: var(--text-muted);">Использованные</div>
            </div>
            
            <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--error);">
                    <?php echo $stats['cancelled'] ?? 0; ?>
                </div>
                <div style="color: var(--text-muted);">Отмененные</div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
    function printTicket(ticketNumber) {
        // Открываем новое окно для печати билета
        const printWindow = window.open('../templates/print_ticket.php?ticket=' + ticketNumber, '_blank');
        printWindow.onload = function() {
            printWindow.print();
        };
    }
    
    // Автоматическое обновление статуса билетов (если сеанс прошел)
    function updateTicketStatuses() {
        fetch('../api/update_ticket_status.php')
            .then(response => response.json())
            .then(data => {
                if (data.updated > 0) {
                    // Если были обновления, перезагружаем страницу
                    window.location.reload();
                }
            });
    }
    
    // Проверяем статус каждые 30 секунд
    setInterval(updateTicketStatuses, 30000);
    </script>
</body>
</html>