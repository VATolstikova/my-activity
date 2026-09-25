<?php
// Файл: api.php
require 'config.php';
requireVerified();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    switch ($action) {
        case 'create':
            $title = $_POST['title'];
            $priority = $_POST['priority'];
            $deadline = $_POST['deadline'] ?: null;
            
            // Получаем максимальный порядок для новой задачи
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM tasks WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $max = $stmt->fetch()['max_order'] ?? 0;

            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, priority, deadline, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $priority, $deadline, $max + 1]);
            break;

        case 'update':
            $id = $_POST['id'];
            $title = $_POST['title'];
            $priority = $_POST['priority'];
            $deadline = $_POST['deadline'] ?: null;

            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, priority = ?, deadline = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $priority, $deadline, $id, $user_id]);
            break;

        case 'delete':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            break;

        case 'toggle':
            $id = $_POST['id'];
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$status, $id, $user_id]);
            break;

        case 'reorder':
            $ids = json_decode($_POST['ids'], true);
            if (is_array($ids)) {
                $stmt = $pdo->prepare("UPDATE tasks SET sort_order = ? WHERE id = ? AND user_id = ?");
                foreach ($ids as $index => $id) {
                    $stmt->execute([$index, $id, $user_id]);
                }
            }
            break;
    }
}
?>