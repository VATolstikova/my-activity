<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$ticket_number = $_GET['ticket'] ?? '';

if (empty($ticket_number)) {
    die('Номер билета не указан');
}

$query = "SELECT t.*, m.title, m.duration_minutes, s.start_time, s.hall_number,
                 st.seat_number, st.section, u.full_name as user_name
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
    die('Билет не найден');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Билет <?php echo $ticket_number; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4;
            margin: 20mm;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: white;
            color: black;
        }
        
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .ticket {
            border: 3px solid #000;
            border-radius: 15px;
            padding: 30px;
            position: relative;
            background: linear-gradient(135deg, #f8f8f8, #ffffff);
        }
        
        .ticket-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px dashed #000;
            padding-bottom: 20px;
        }
        
        .ticket-header h1 {
            font-size: 36px;
            color: #ff00ff;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .ticket-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .ticket-info {
            padding: 20px;
            border: 2px solid #ff00ff;
            border-radius: 10px;
            background: white;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ddd;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .label {
            font-weight: bold;
            color: #666;
        }
        
        .value {
            font-weight: bold;
            color: #333;
        }
        
        .qr-code {
            text-align: center;
            padding: 20px;
            border: 2px solid #ff00ff;
            border-radius: 10px;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .qr-placeholder {
            width: 200px;
            height: 200px;
            background: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #666;
        }
        
        .ticket-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px dashed #000;
            color: #666;
            font-size: 14px;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(255, 0, 255, 0.1);
            font-weight: bold;
            pointer-events: none;
            z-index: 1;
        }
        
        .ticket-number {
            font-size: 24px;
            font-weight: bold;
            color: #ff00ff;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background: #f8f8f8;
            border-radius: 5px;
            letter-spacing: 2px;
        }
        
        .cinema-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 10px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }
            
            .no-print {
                display: none;
            }
            
            .ticket {
                border: 2px solid #000;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket">
            <div class="watermark">NEON CINEMA</div>
            
            <div class="ticket-header">
                <h1>ЭЛЕКТРОННЫЙ БИЛЕТ</h1>
                <p>Neon Cinema • Система онлайн-бронирования</p>
            </div>
            
            <div class="cinema-info">
                <div>
                    <strong>Адрес:</strong> г. Москва, ул. Кинотеатральная, 1<br>
                    <strong>Телефон:</strong> +7 (999) 123-45-67
                </div>
                <div>
                    <strong>Время работы:</strong> 08:00 - 23:00<br>
                    <strong>Сайт:</strong> neoncinema.ru
                </div>
            </div>
            
            <div class="ticket-body">
                <div class="ticket-info">
                    <div class="info-row">
                        <span class="label">Фильм:</span>
                        <span class="value"><?php echo htmlspecialchars($ticket['title']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="label">Дата и время:</span>
                        <span class="value"><?php echo date('d.m.Y H:i', strtotime($ticket['start_time'])); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="label">Зал:</span>
                        <span class="value">№ <?php echo $ticket['hall_number']; ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="label">Место:</span>
                        <span class="value"><?php echo $ticket['seat_number']; ?> (Секция <?php echo $ticket['section']; ?>)</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="label">Длительность:</span>
                        <span class="value"><?php echo floor($ticket['duration_minutes']/60); ?>ч <?php echo $ticket['duration_minutes']%60; ?>м</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="label">Владелец:</span>
                        <span class="value"><?php echo htmlspecialchars($ticket['user_name']); ?></span>
                    </div>
                </div>
                
                <div class="qr-code">
                    <div class="qr-placeholder">
                        QR-код<br>
                        <?php echo $ticket_number; ?>
                    </div>
                    <p>Отсканируйте QR-код<br>при входе в зал</p>
                </div>
            </div>
            
            <div class="ticket-number">
                № <?php echo $ticket_number; ?>
            </div>
            
            <div class="ticket-footer">
                <p>
                    <strong>Важная информация:</strong><br>
                    1. Приходите за 15 минут до начала сеанса<br>
                    2. Имейте при себе документ, удостоверяющий личность<br>
                    3. Билет действует только на указанный сеанс<br>
                    4. Возврат билетов возможен за 1 час до начала сеанса
                </p>
                <p style="margin-top: 20px; font-size: 12px;">
                    Дата печати: <?php echo date('d.m.Y H:i'); ?> • Система Neon Cinema v1.0
                </p>
            </div>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px; padding: 20px;">
            <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; background: #ff00ff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                🖨️ Печатать билет
            </button>
            <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                ✕ Закрыть
            </button>
        </div>
    </div>
    
    <script>
    window.onload = function() {
        // Автоматически предлагаем печать
        if (!window.opener) {
            window.print();
        }
    };
    </script>
</body>
</html>