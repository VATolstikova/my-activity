<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$session_id = $_GET['session_id'] ?? 0;

if (!$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Session ID required']);
    exit();
}

// Удаляем просроченные бронирования (старше 15 минут)
$cleanupQuery = "UPDATE seats SET 
                seat_status = 'available',
                user_id = NULL,
                booked_until = NULL
                WHERE session_id = :session_id 
                AND seat_status = 'booked'
                AND booked_until < NOW()";
$cleanupStmt = $conn->prepare($cleanupQuery);
$cleanupStmt->execute(['session_id' => $session_id]);

// Получаем актуальный статус мест
$query = "SELECT id, seat_number, seat_status FROM seats 
          WHERE session_id = :session_id";
$stmt = $conn->prepare($query);
$stmt->execute(['session_id' => $session_id]);

$seats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $seats[] = $row;
}

echo json_encode([
    'status' => 'success',
    'session_id' => $session_id,
    'seats' => $seats
]);
?>