<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

// Обновляем статус просроченных билетов
$updateQuery = "UPDATE tickets t
               JOIN sessions s ON t.session_id = s.id
               SET t.ticket_status = 'expired'
               WHERE t.ticket_status = 'active'
               AND s.start_time < NOW()";

$updateStmt = $conn->prepare($updateQuery);
$updated = $updateStmt->execute();

// Очищаем просроченные бронирования (более 15 минут)
$cleanupQuery = "UPDATE seats SET 
                seat_status = 'available',
                user_id = NULL,
                booked_until = NULL
                WHERE seat_status = 'booked'
                AND booked_until < NOW()";

$cleanupStmt = $conn->prepare($cleanupQuery);
$cleaned = $cleanupStmt->execute();

echo json_encode([
    'status' => 'success',
    'updated' => $updateStmt->rowCount(),
    'cleaned' => $cleanupStmt->rowCount(),
    'timestamp' => date('Y-m-d H:i:s')
]);
?>