<?php
/**
 * Общие функции для системы кинотеатра
 */

/**
 * Генерация уникального номера билета
 */
function generateTicketNumber() {
    $prefix = 'TCK';
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . '-' . $date . '-' . $random;
}

/**
 * Проверка доступности сеанса (рабочие часы кинотеатра)
 */
function isSessionTimeValid($start_time) {
    $start_hour = date('H', strtotime($start_time));
    return ($start_hour >= 8 && $start_hour <= 23);
}

/**
 * Форматирование времени
 */
function formatDateTime($datetime, $format = 'd.m.Y H:i') {
    return date($format, strtotime($datetime));
}

/**
 * Проверка, можно ли бронировать места
 */
function canBookSeats($session_id, $user_id, $db) {
    // Проверяем, сколько билетов уже купил пользователь на этот сеанс
    $query = "SELECT COUNT(*) as ticket_count FROM tickets 
              WHERE session_id = :session_id 
              AND user_id = :user_id 
              AND ticket_status = 'active'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        'session_id' => $session_id,
        'user_id' => $user_id
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['ticket_count'] < 5; // Максимум 5 билетов на пользователя
}

/**
 * Получение информации о сеансе
 */
function getSessionInfo($session_id, $db) {
    $query = "SELECT s.*, m.title, m.duration_minutes 
              FROM sessions s
              JOIN movies m ON s.movie_id = m.id
              WHERE s.id = :session_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['session_id' => $session_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Получение карты мест для сеанса
 */
function getSeatMap($session_id, $db) {
    $query = "SELECT * FROM seats 
              WHERE session_id = :session_id 
              ORDER BY section, seat_row, seat_col";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['session_id' => $session_id]);
    
    $seats = [];
    while ($seat = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $seats[$seat['section']][$seat['seat_row']][$seat['seat_col']] = $seat;
    }
    
    return $seats;
}

/**
 * Проверка доступности места
 */
function isSeatAvailable($seat_id, $db) {
    $query = "SELECT seat_status FROM seats WHERE id = :seat_id";
    $stmt = $db->prepare($query);
    $stmt->execute(['seat_id' => $seat_id]);
    $seat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $seat && $seat['seat_status'] === 'available';
}

/**
 * Создание QR кода для билета
 */
function generateQRCode($ticket_number) {
    // В реальном проекте здесь была бы генерация QR кода
    // Например, с помощью библиотеки phpqrcode
    return 'data:image/svg+xml;base64,' . base64_encode('
        <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150">
            <rect width="100%" height="100%" fill="#ffffff"/>
            <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#000000" font-family="Arial">
                ' . $ticket_number . '
            </text>
        </svg>
    ');
}
?>