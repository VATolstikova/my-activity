<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Проверяем права администратора
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$movie_id = $_GET['movie_id'] ?? 0;
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Обработка добавления/редактирования сеанса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id_form = $_POST['movie_id'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $hall_number = $_POST['hall_number'] ?? 1;
    $base_price = $_POST['base_price'] ?? '';
    
    if (empty($movie_id_form) || empty($start_time) || empty($base_price)) {
        $error = 'Заполните обязательные поля';
    } else {
        // Проверяем, не пересекается ли сеанс с другими
        $end_time = date('Y-m-d H:i:s', strtotime($start_time . ' +3 hours'));
        
        $checkQuery = "SELECT COUNT(*) as conflict_count 
                      FROM sessions 
                      WHERE hall_number = :hall_number 
                      AND start_time < :end_time 
                      AND DATE_ADD(start_time, INTERVAL 180 MINUTE) > :start_time";
        
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([
            'hall_number' => $hall_number,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
        
        $check = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($check['conflict_count'] > 0) {
            $error = 'Сеанс пересекается с другим сеансом в этом зале';
        } else {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Редактирование существующего сеанса
                $query = "UPDATE sessions SET 
                         movie_id = :movie_id,
                         start_time = :start_time,
                         hall_number = :hall_number,
                         base_price = :base_price
                         WHERE id = :id";
                
                $stmt = $conn->prepare($query);
                $result = $stmt->execute([
                    'id' => $_POST['id'],
                    'movie_id' => $movie_id_form,
                    'start_time' => $start_time,
                    'hall_number' => $hall_number,
                    'base_price' => $base_price
                ]);
                
                if ($result) {
                    $message = 'Сеанс успешно обновлен';
                    header("Location: sessions.php?success=updated");
                    exit();
                } else {
                    $error = 'Ошибка при обновлении сеанса';
                }
            } else {
                // Добавление нового сеанса
                $query = "INSERT INTO sessions (movie_id, start_time, hall_number, base_price) 
                         VALUES (:movie_id, :start_time, :hall_number, :base_price)";
                
                $stmt = $conn->prepare($query);
                $result = $stmt->execute([
                    'movie_id' => $movie_id_form,
                    'start_time' => $start_time,
                    'hall_number' => $hall_number,
                    'base_price' => $base_price
                ]);
                
                if ($result) {
                    $message = 'Сеанс успешно добавлен';
                    header("Location: sessions.php?success=added");
                    exit();
                } else {
                    $error = 'Ошибка при добавлении сеанса';
                }
            }
        }
    }
}

// Удаление сеанса
if (isset($_GET['delete']) && $_GET['delete']) {
    $session_id = $_GET['delete'];
    
    // Проверяем, нет ли проданных билетов
    $checkQuery = "SELECT COUNT(*) as ticket_count FROM tickets WHERE session_id = :session_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute(['session_id' => $session_id]);
    $check = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check['ticket_count'] > 0) {
        $error = 'Нельзя удалить сеанс, на который уже проданы билеты';
    } else {
        $deleteQuery = "DELETE FROM sessions WHERE id = :id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->execute(['id' => $session_id]);
        
        header("Location: sessions.php?success=deleted");
        exit();
    }
}

// Получаем список фильмов для выпадающего списка
$moviesQuery = "SELECT * FROM movies ORDER BY title";
$moviesStmt = $conn->query($moviesQuery);
$all_movies = $moviesStmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список сеансов
$sessionsQuery = "SELECT s.*, m.title as movie_title,
                 (SELECT COUNT(*) FROM seats WHERE session_id = s.id AND seat_status = 'sold') as sold_seats,
                 (SELECT COUNT(*) FROM seats WHERE session_id = s.id) as total_seats
                 FROM sessions s
                 JOIN movies m ON s.movie_id = m.id";
                 
$where = [];
$params = [];

if ($movie_id) {
    $where[] = "s.movie_id = :movie_id";
    $params['movie_id'] = $movie_id;
}

if (!empty($where)) {
    $sessionsQuery .= " WHERE " . implode(" AND ", $where);
}

$sessionsQuery .= " ORDER BY s.start_time DESC";

$sessionsStmt = $conn->prepare($sessionsQuery);
$sessionsStmt->execute($params);
$sessions = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем данные для редактирования
$edit_session = null;
if (isset($_GET['edit']) && $_GET['edit']) {
    $editQuery = "SELECT * FROM sessions WHERE id = :id";
    $editStmt = $conn->prepare($editQuery);
    $editStmt->execute(['id' => $_GET['edit']]);
    $edit_session = $editStmt->fetch(PDO::FETCH_ASSOC);
    $action = 'edit';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление сеансами - Админ-панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div style="margin: 2rem 0;">
            <h1 class="gradient-text">
                <i class="fas fa-calendar-alt"></i> Управление сеансами
            </h1>
            <p style="color: var(--text-muted);">Расписание сеансов и управление залами</p>
        </div>

        <!-- Сообщения -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'added' => 'Сеанс успешно добавлен',
                    'updated' => 'Сеанс успешно обновлен',
                    'deleted' => 'Сеанс успешно удален'
                ];
                echo $messages[$_GET['success']];
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Фильтр по фильму -->
        <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <span style="color: var(--text-muted);">Фильтр по фильму:</span>
                
                <a href="sessions.php" 
                   class="<?php echo !$movie_id ? 'btn-primary' : 'btn-outline'; ?>" 
                   style="padding: 0.5rem 1rem; text-decoration: none;">
                    Все сеансы
                </a>
                
                <?php foreach ($all_movies as $movie): ?>
                    <a href="sessions.php?movie_id=<?php echo $movie['id']; ?>" 
                       class="<?php echo $movie_id == $movie['id'] ? 'btn-primary' : 'btn-outline'; ?>" 
                       style="padding: 0.5rem 1rem; text-decoration: none;">
                        <?php echo htmlspecialchars($movie['title']); ?>
                    </a>
                <?php endforeach; ?>
                
                <a href="movies.php?action=add" class="btn btn-primary" style="margin-left: auto;">
                    <i class="fas fa-plus"></i> Новый фильм
                </a>
            </div>
        </div>

        <?php if ($action === 'list' || $action === 'edit'): ?>
            <!-- Форма добавления/редактирования сеанса -->
            <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-<?php echo $edit_session ? 'edit' : 'plus-circle'; ?>"></i>
                    <?php echo $edit_session ? 'Редактирование сеанса' : 'Добавить новый сеанс'; ?>
                </h3>
                
                <form method="POST" action="">
                    <?php if ($edit_session): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_session['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label">Фильм *</label>
                            <select name="movie_id" class="form-control" required>
                                <option value="">Выберите фильм</option>
                                <?php foreach ($all_movies as $movie): ?>
                                    <option value="<?php echo $movie['id']; ?>"
                                        <?php if (($edit_session && $edit_session['movie_id'] == $movie['id']) || (!$edit_session && $movie_id == $movie['id'])) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Дата и время *</label>
                            <input type="datetime-local" name="start_time" class="form-control" required
                                   value="<?php echo $edit_session ? date('Y-m-d\TH:i', strtotime($edit_session['start_time'])) : ''; ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label">Номер зала *</label>
                            <select name="hall_number" class="form-control" required>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>"
                                        <?php if (($edit_session && $edit_session['hall_number'] == $i) || (!$edit_session && $i == 1)) echo 'selected'; ?>>
                                        Зал № <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Базовая цена (₽) *</label>
                            <input type="number" name="base_price" class="form-control" required min="0" step="50"
                                   value="<?php echo $edit_session ? $edit_session['base_price'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(255, 0, 255, 0.05); border-radius: var(--border-radius);">
                        <p style="color: var(--text-muted); margin: 0;">
                            <i class="fas fa-info-circle"></i> 
                            Длительность сеанса: 3 часа. Система автоматически проверяет пересечения с другими сеансами.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 
                            <?php echo $edit_session ? 'Сохранить изменения' : 'Добавить сеанс'; ?>
                        </button>
                        
                        <?php if ($edit_session): ?>
                            <a href="sessions.php<?php echo $movie_id ? '?movie_id=' . $movie_id : ''; ?>" class="btn btn-outline">
                                <i class="fas fa-times"></i> Отмена
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Список сеансов -->
        <div>
            <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-list"></i> Список сеансов (<?php echo count($sessions); ?>)
            </h3>
            
            <?php if (count($sessions) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden;">
                        <thead>
                            <tr>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Фильм</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Дата и время</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Зал</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Цена</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Заполненность</th>
                                <th style="padding: 1rem; text-align: left; background: rgba(255, 0, 255, 0.1);">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): 
                                $occupancy = $session['total_seats'] > 0 ? ($session['sold_seats'] / $session['total_seats']) * 100 : 0;
                                $is_past = strtotime($session['start_time']) < time();
                            ?>
                            <tr style="border-bottom: 1px solid rgba(255, 0, 255, 0.1); <?php echo $is_past ? 'opacity: 0.7;' : ''; ?>">
                                <td style="padding: 1rem;">
                                    <strong><?php echo htmlspecialchars($session['movie_title']); ?></strong>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo date('d.m.Y', strtotime($session['start_time'])); ?><br>
                                    <small style="color: var(--text-muted);">
                                        <?php echo date('H:i', strtotime($session['start_time'])); ?>
                                    </small>
                                </td>
                                <td style="padding: 1rem;">№ <?php echo $session['hall_number']; ?></td>
                                <td style="padding: 1rem; color: var(--neon-primary); font-weight: bold;">
                                    <?php echo $session['base_price']; ?> ₽
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="flex: 1; height: 8px; background: rgba(255, 0, 255, 0.1); border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?php echo $occupancy; ?>%; height: 100%; background: var(--neon-primary);"></div>
                                        </div>
                                        <span><?php echo $session['sold_seats']; ?>/<?php echo $session['total_seats']; ?></span>
                                    </div>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="sessions.php?edit=<?php echo $session['id']; ?><?php echo $movie_id ? '&movie_id=' . $movie_id : ''; ?>" 
                                           class="btn btn-outline" style="padding: 0.5rem;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if (!$is_past && $session['sold_seats'] == 0): ?>
                                            <a href="sessions.php?delete=<?php echo $session['id']; ?><?php echo $movie_id ? '&movie_id=' . $movie_id : ''; ?>" 
                                               class="btn" 
                                               style="padding: 0.5rem; background: var(--error); color: white;"
                                               onclick="return confirm('Удалить сеанс?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn" 
                                                    style="padding: 0.5rem; background: var(--error); color: white; opacity: 0.5; cursor: not-allowed;"
                                                    title="<?php echo $is_past ? 'Прошедший сеанс' : 'Есть проданные билеты'; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="../movie/booking.php?session_id=<?php echo $session['id']; ?>" 
                                           target="_blank"
                                           class="btn btn-outline" style="padding: 0.5rem;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; background: var(--bg-card); border-radius: var(--border-radius);">
                    <i class="fas fa-calendar-times" style="font-size: 4rem; color: var(--neon-primary); margin-bottom: 1rem;"></i>
                    <h3>Сеансы не найдены</h3>
                    <p style="color: var(--text-muted); margin-bottom: 2rem;">
                        <?php if ($movie_id): ?>
                            Для выбранного фильма нет сеансов
                        <?php else: ?>
                            Добавьте первый сеанс в расписание
                        <?php endif; ?>
                    </p>
                    <a href="sessions.php?action=add" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Добавить сеанс
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>