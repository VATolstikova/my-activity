<?php
// Файл: dashboard.php
require 'config.php';
requireVerified(); // Проверка входа и верификации

$user_id = $_SESSION['user_id'];

// Получение задач с фильтрами
$where = "WHERE user_id = ?";
$params = [$user_id];

// Поиск
if (!empty($_GET['search'])) {
    $where .= " AND title LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
}

// Фильтр статуса
if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
    $where .= " AND status = ?";
    $params[] = $_GET['status'];
}

// Сортировка
$orderBy = "sort_order ASC";
if (!empty($_GET['sort']) && $_GET['sort'] === 'priority') {
    // ENUM сортировка: high > medium > low
    $orderBy = "FIELD(priority, 'high', 'medium', 'low') ASC, sort_order ASC";
}

$stmt = $pdo->prepare("SELECT * FROM tasks $where ORDER BY $orderBy");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Статистика
$stmtCount = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) as done FROM tasks WHERE user_id = ?");
$stmtCount->execute([$user_id]);
$stats = $stmtCount->fetch();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои задачи</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container container-wide">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>Привет, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</h2>
            <div>
                <span style="margin-right:15px;">Выполнено: <b><?= $stats['done'] ?>/<?= $stats['total'] ?></b></span>
                <a href="index.php" style="color:var(--danger)">Выйти</a>
            </div>
        </header>

        <!-- Панель управления -->
        <div class="card" style="padding: 20px; text-align: left;">
            <form method="GET" class="controls">
                <input type="text" name="search" placeholder="Поиск задачи..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?= (($_GET['status']??'') == 'all' || empty($_GET['status'])) ? 'selected' : '' ?>>Все</option>
                    <option value="active" <?= (($_GET['status']??'') == 'active') ? 'selected' : '' ?>>Активные</option>
                    <option value="done" <?= (($_GET['status']??'') == 'done') ? 'selected' : '' ?>>Выполненные</option>
                </select>
                <select name="sort" onchange="this.form.submit()">
                    <option value="default" <?= (($_GET['sort']??'') == 'default' || empty($_GET['sort'])) ? 'selected' : '' ?>>По порядку</option>
                    <option value="priority" <?= (($_GET['sort']??'') == 'priority') ? 'selected' : '' ?>>По приоритету</option>
                </select>
                <button type="button" onclick="openModal()" style="width:auto; margin:0;">+ Новая задача</button>
            </form>

            <!-- Список задач -->
            <ul class="task-list" id="taskList">
                <?php foreach ($tasks as $task): ?>
                <li class="task-item priority-<?= $task['priority'] ?> <?= $task['status'] == 'done' ? 'done' : '' ?>" 
                    data-id="<?= $task['id'] ?>" draggable="true">
                    
                    <div style="flex:1;">
                        <strong><?= htmlspecialchars($task['title']) ?></strong>
                        <div style="font-size:12px; color:#888;">
                            Приоритет: <?= $task['priority'] ?> | Дедлайн: <?= $task['deadline'] ? date('d.m.Y', strtotime($task['deadline'])) : '-' ?>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:10px;">
                        <button onclick="toggleStatus(<?= $task['id'] ?>, '<?= $task['status'] ?>')" style="width:auto; padding:5px 10px; font-size:12px;">
                            <?= $task['status'] == 'done' ? '↩' : '✓' ?>
                        </button>
                        <button onclick="editTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>', '<?= $task['priority'] ?>', '<?= $task['deadline'] ?>')" style="width:auto; padding:5px 10px; font-size:12px; background:#ccc;">✎</button>
                        <button onclick="deleteTask(<?= $task['id'] ?>)" style="width:auto; padding:5px 10px; font-size:12px; background:var(--danger);">✕</button>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Модальное окно (Создание/Редактирование) -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Новая задача</h2>
            <form id="taskForm">
                <input type="hidden" id="taskId">
                <input type="text" id="taskTitle" placeholder="Название" required>
                <select id="taskPriority">
                    <option value="low">Низкий</option>
                    <option value="medium">Средний</option>
                    <option value="high">Высокий</option>
                </select>
                <label style="font-size:12px; color:#666; display:block; margin-bottom:5px;">Дедлайн:</label>
                <input type="datetime-local" id="taskDeadline">
                <button type="submit">Сохранить</button>
            </form>
        </div>
    </div>

    <script>
        // --- AJAX Логика ---
        const api = 'api.php';

        // Создание / Редактирование
        document.getElementById('taskForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('taskId').value;
            const data = new FormData();
            data.append('action', id ? 'update' : 'create');
            data.append('title', document.getElementById('taskTitle').value);
            data.append('priority', document.getElementById('taskPriority').value);
            data.append('deadline', document.getElementById('taskDeadline').value);
            if(id) data.append('id', id);

            fetch(api, { method: 'POST', body: data })
                .then(() => location.reload());
        });

        // Удаление
        function deleteTask(id) {
            if(confirm('Удалить задачу?')) {
                const data = new FormData();
                data.append('action', 'delete');
                data.append('id', id);
                fetch(api, { method: 'POST', body: data }).then(() => location.reload());
            }
        }

        // Статус
        function toggleStatus(id, current) {
            const data = new FormData();
            data.append('action', 'toggle');
            data.append('id', id);
            data.append('status', current === 'active' ? 'done' : 'active');
            fetch(api, { method: 'POST', body: data }).then(() => location.reload());
        }

        // --- Drag & Drop (Vanilla JS) ---
        const list = document.getElementById('taskList');
        let draggedItem = null;

        list.addEventListener('dragstart', function(e) {
            draggedItem = e.target;
            e.target.classList.add('dragging');
        });

        list.addEventListener('dragend', function(e) {
            e.target.classList.remove('dragging');
            draggedItem = null;
            // Отправляем новый порядок на сервер
            saveOrder();
        });

        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(list, e.clientY);
            if (afterElement == null) {
                list.appendChild(draggedItem);
            } else {
                list.insertBefore(draggedItem, afterElement);
            }
        });

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.task-item:not(.dragging)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        function saveOrder() {
            const ids = [...list.querySelectorAll('.task-item')].map(el => el.dataset.id);
            const data = new FormData();
            data.append('action', 'reorder');
            data.append('ids', JSON.stringify(ids));
            fetch(api, { method: 'POST', body: data });
        }

        // --- Modal Helpers ---
        function openModal() {
            document.getElementById('taskForm').reset();
            document.getElementById('taskId').value = '';
            document.getElementById('modalTitle').innerText = 'Новая задача';
            document.getElementById('taskModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('taskModal').style.display = 'none';
        }
        function editTask(id, title, priority, deadline) {
            document.getElementById('taskId').value = id;
            document.getElementById('taskTitle').value = title;
            document.getElementById('taskPriority').value = priority;
            document.getElementById('taskDeadline').value = deadline ? deadline.replace(' ', 'T') : '';
            document.getElementById('modalTitle').innerText = 'Редактировать';
            document.getElementById('taskModal').style.display = 'flex';
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('taskModal')) closeModal();
        }
    </script>
</body>
</html>