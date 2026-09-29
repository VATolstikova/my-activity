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

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Создаем папку для загрузки постеров, если ее нет
$upload_dir = '../uploads/posters/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Обработка добавления/редактирования фильма
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $duration_minutes = $_POST['duration_minutes'] ?? '';
    $release_year = $_POST['release_year'] ?? '';
    $director = $_POST['director'] ?? '';
    
    if (empty($title) || empty($duration_minutes)) {
        $error = 'Заполните обязательные поля';
    } else {
        // Обработка загрузки постера
        $poster_filename = null;
        
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($_FILES['poster']['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                $error = 'Разрешены только файлы изображений (JPG, PNG, GIF, WebP)';
            } elseif ($_FILES['poster']['size'] > 5 * 1024 * 1024) { // 5MB
                $error = 'Размер файла не должен превышать 5MB';
            } else {
                // Генерируем уникальное имя файла
                $extension = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
                $poster_filename = uniqid('movie_', true) . '.' . $extension;
                $upload_path = $upload_dir . $poster_filename;
                
                if (!move_uploaded_file($_FILES['poster']['tmp_name'], $upload_path)) {
                    $error = 'Ошибка при загрузке файла';
                    $poster_filename = null;
                }
            }
        }
        
        if (!$error) {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Редактирование существующего фильма
                if ($poster_filename) {
                    // Если загружен новый постер, удаляем старый
                    $oldQuery = "SELECT poster_filename FROM movies WHERE id = :id";
                    $oldStmt = $conn->prepare($oldQuery);
                    $oldStmt->execute(['id' => $_POST['id']]);
                    $old_movie = $oldStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($old_movie && $old_movie['poster_filename']) {
                        $old_path = $upload_dir . $old_movie['poster_filename'];
                        if (file_exists($old_path)) {
                            unlink($old_path);
                        }
                    }
                    
                    $query = "UPDATE movies SET 
                             title = :title,
                             description = :description,
                             duration_minutes = :duration_minutes,
                             release_year = :release_year,
                             director = :director,
                             poster_filename = :poster_filename
                             WHERE id = :id";
                    
                    $stmt = $conn->prepare($query);
                    $result = $stmt->execute([
                        'id' => $_POST['id'],
                        'title' => $title,
                        'description' => $description,
                        'duration_minutes' => $duration_minutes,
                        'release_year' => $release_year,
                        'director' => $director,
                        'poster_filename' => $poster_filename
                    ]);
                } else {
                    $query = "UPDATE movies SET 
                             title = :title,
                             description = :description,
                             duration_minutes = :duration_minutes,
                             release_year = :release_year,
                             director = :director
                             WHERE id = :id";
                    
                    $stmt = $conn->prepare($query);
                    $result = $stmt->execute([
                        'id' => $_POST['id'],
                        'title' => $title,
                        'description' => $description,
                        'duration_minutes' => $duration_minutes,
                        'release_year' => $release_year,
                        'director' => $director
                    ]);
                }
                
                if ($result) {
                    $message = 'Фильм успешно обновлен';
                    header("Location: movies.php?success=updated");
                    exit();
                } else {
                    $error = 'Ошибка при обновлении фильма';
                }
            } else {
                // Добавление нового фильма
                $query = "INSERT INTO movies (title, description, duration_minutes, release_year, director, poster_filename) 
                         VALUES (:title, :description, :duration_minutes, :release_year, :director, :poster_filename)";
                
                $stmt = $conn->prepare($query);
                $result = $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'duration_minutes' => $duration_minutes,
                    'release_year' => $release_year,
                    'director' => $director,
                    'poster_filename' => $poster_filename
                ]);
                
                if ($result) {
                    $message = 'Фильм успешно добавлен';
                    header("Location: movies.php?success=added");
                    exit();
                } else {
                    $error = 'Ошибка при добавлении фильма';
                }
            }
        }
    }
}

// Удаление фильма
if (isset($_GET['delete']) && $_GET['delete']) {
    $movie_id = $_GET['delete'];
    
    // Проверяем, нет ли связанных сеансов
    $checkQuery = "SELECT COUNT(*) as session_count FROM sessions WHERE movie_id = :movie_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute(['movie_id' => $movie_id]);
    $check = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check['session_count'] > 0) {
        $error = 'Нельзя удалить фильм, у которого есть активные сеансы';
    } else {
        // Получаем информацию о постере для удаления
        $posterQuery = "SELECT poster_filename FROM movies WHERE id = :id";
        $posterStmt = $conn->prepare($posterQuery);
        $posterStmt->execute(['id' => $movie_id]);
        $movie = $posterStmt->fetch(PDO::FETCH_ASSOC);
        
        // Удаляем файл постера
        if ($movie && $movie['poster_filename']) {
            $poster_path = $upload_dir . $movie['poster_filename'];
            if (file_exists($poster_path)) {
                unlink($poster_path);
            }
        }
        
        $deleteQuery = "DELETE FROM movies WHERE id = :id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->execute(['id' => $movie_id]);
        
        header("Location: movies.php?success=deleted");
        exit();
    }
}

// Получаем список фильмов
$moviesQuery = "SELECT m.*, 
               COUNT(s.id) as session_count,
               COUNT(t.id) as ticket_count
               FROM movies m
               LEFT JOIN sessions s ON m.id = s.movie_id
               LEFT JOIN tickets t ON s.id = t.session_id
               GROUP BY m.id
               ORDER BY m.created_at DESC";
$moviesStmt = $conn->query($moviesQuery);
$movies = $moviesStmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем данные для редактирования
$edit_movie = null;
if (isset($_GET['edit']) && $_GET['edit']) {
    $editQuery = "SELECT * FROM movies WHERE id = :id";
    $editStmt = $conn->prepare($editQuery);
    $editStmt->execute(['id' => $_GET['edit']]);
    $edit_movie = $editStmt->fetch(PDO::FETCH_ASSOC);
    $action = 'edit';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление фильмами - Админ-панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .poster-preview {
            width: 200px;
            height: 300px;
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
            border: 2px dashed rgba(255, 0, 255, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .poster-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .poster-preview i {
            font-size: 3rem;
            color: rgba(255, 0, 255, 0.5);
        }
        
        .upload-btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: rgba(255, 0, 255, 0.1);
            color: var(--neon-primary);
            border: 2px solid var(--neon-primary);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        
        .upload-btn:hover {
            background: rgba(255, 0, 255, 0.2);
        }
        
        .current-poster {
            margin-bottom: 1rem;
            padding: 1rem;
            background: rgba(255, 0, 255, 0.05);
            border-radius: var(--border-radius);
            border: 1px solid rgba(255, 0, 255, 0.2);
        }
        
        .current-poster img {
            max-width: 150px;
            border-radius: 4px;
            margin-right: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div style="margin: 2rem 0;">
            <h1 class="gradient-text">
                <i class="fas fa-film"></i> Управление фильмами
            </h1>
            <p style="color: var(--text-muted);">Добавление, редактирование и удаление фильмов</p>
        </div>

        <!-- Сообщения -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'added' => 'Фильм успешно добавлен',
                    'updated' => 'Фильм успешно обновлен',
                    'deleted' => 'Фильм успешно удален'
                ];
                echo $messages[$_GET['success']];
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($action === 'list' || $action === 'edit'): ?>
            <!-- Форма добавления/редактирования -->
            <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-<?php echo $edit_movie ? 'edit' : 'plus-circle'; ?>"></i>
                    <?php echo $edit_movie ? 'Редактирование фильма' : 'Добавить новый фильм'; ?>
                </h3>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if ($edit_movie): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_movie['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                        <!-- Левая колонка - постер -->
                        <div>
                            <div class="poster-preview" id="posterPreview">
                                <?php if ($edit_movie && $edit_movie['poster_filename']): ?>
                                    <img src="../uploads/posters/<?php echo htmlspecialchars($edit_movie['poster_filename']); ?>" 
                                         alt="Постер фильма"
                                         id="currentPoster">
                                <?php else: ?>
                                    <i class="fas fa-film"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div style="text-align: center;">
                                <label for="poster" class="upload-btn">
                                    <i class="fas fa-upload"></i> Выбрать постер
                                </label>
                                <input type="file" name="poster" id="poster" accept="image/*" 
                                       style="display: none;" onchange="previewPoster(event)">
                                
                                <div style="margin-top: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                                    <i class="fas fa-info-circle"></i> 
                                    JPG, PNG, GIF, WebP до 5MB
                                </div>
                            </div>
                            
                            <?php if ($edit_movie && $edit_movie['poster_filename']): ?>
                                <div class="current-poster">
                                    <p style="margin: 0; color: var(--text-muted);">
                                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                                        Текущий постер загружен
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Правая колонка - информация о фильме -->
                        <div>
                            <div class="form-group">
                                <label class="form-label">Название фильма *</label>
                                <input type="text" name="title" class="form-control" required
                                       value="<?php echo $edit_movie ? htmlspecialchars($edit_movie['title']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Описание</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo $edit_movie ? htmlspecialchars($edit_movie['description']) : ''; ?></textarea>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Режиссер</label>
                                    <input type="text" name="director" class="form-control"
                                           value="<?php echo $edit_movie ? htmlspecialchars($edit_movie['director']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Год выпуска</label>
                                    <input type="number" name="release_year" class="form-control" min="1900" max="<?php echo date('Y'); ?>"
                                           value="<?php echo $edit_movie ? $edit_movie['release_year'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Длительность (минуты) *</label>
                                <input type="number" name="duration_minutes" class="form-control" required min="1"
                                       value="<?php echo $edit_movie ? $edit_movie['duration_minutes'] : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 
                            <?php echo $edit_movie ? 'Сохранить изменения' : 'Добавить фильм'; ?>
                        </button>
                        
                        <?php if ($edit_movie): ?>
                            <a href="movies.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Отмена
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Список фильмов -->
        <div>
            <h3 style="color: var(--neon-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-list"></i> Список фильмов (<?php echo count($movies); ?>)
            </h3>
            
            <?php if (count($movies) > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
                    <?php foreach ($movies as $movie): ?>
                    <div style="background: var(--bg-card); border-radius: var(--border-radius); overflow: hidden; border: 1px solid rgba(255, 0, 255, 0.1);">
                        <!-- Постер фильма -->
                        <div style="height: 200px; background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card)); 
                                    display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <?php if ($movie['poster_filename']): ?>
                                <img src="../uploads/posters/<?php echo htmlspecialchars($movie['poster_filename']); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-film" style="font-size: 3rem; color: rgba(255, 0, 255, 0.5);"></i>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Информация о фильме -->
                        <div style="padding: 1.5rem;">
                            <h4 style="margin-bottom: 0.5rem; color: var(--text-primary);">
                                <?php echo htmlspecialchars($movie['title']); ?>
                            </h4>
                            
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                                <?php if ($movie['director']): ?>
                                    <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($movie['director']); ?></div>
                                <?php endif; ?>
                                
                                <?php if ($movie['release_year']): ?>
                                    <div><i class="fas fa-calendar"></i> <?php echo $movie['release_year']; ?> год</div>
                                <?php endif; ?>
                                
                                <div>
                                    <i class="fas fa-clock"></i> 
                                    <?php echo floor($movie['duration_minutes']/60); ?>ч <?php echo $movie['duration_minutes']%60; ?>м
                                </div>
                            </div>
                            
                            <!-- Статистика -->
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding: 0.5rem; 
                                        background: rgba(255, 0, 255, 0.05); border-radius: 6px;">
                                <div style="text-align: center;">
                                    <div style="font-weight: bold; color: var(--neon-primary);"><?php echo $movie['session_count']; ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Сеансы</div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-weight: bold; color: var(--success);"><?php echo $movie['ticket_count']; ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Билеты</div>
                                </div>
                            </div>
                            
                            <!-- Действия -->
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="movies.php?edit=<?php echo $movie['id']; ?>" 
                                   class="btn btn-outline" style="flex: 1; padding: 0.5rem;">
                                    <i class="fas fa-edit"></i> Редактировать
                                </a>
                                
                                <a href="movies.php?delete=<?php echo $movie['id']; ?>" 
                                   class="btn" 
                                   style="padding: 0.5rem; background: var(--error); color: white;"
                                   onclick="return confirm('Удалить фильм <?php echo addslashes($movie['title']); ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                
                                <a href="sessions.php?movie_id=<?php echo $movie['id']; ?>" 
                                   class="btn btn-outline" style="padding: 0.5rem;">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; background: var(--bg-card); border-radius: var(--border-radius);">
                    <i class="fas fa-film" style="font-size: 4rem; color: var(--neon-primary); margin-bottom: 1rem;"></i>
                    <h3>Фильмы не найдены</h3>
                    <p style="color: var(--text-muted);">Добавьте первый фильм в каталог</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
    function previewPoster(event) {
        const input = event.target;
        const preview = document.getElementById('posterPreview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Удаляем старый контент
                preview.innerHTML = '';
                
                // Создаем новое изображение
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Предпросмотр постера';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                
                preview.appendChild(img);
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Обработка перетаскивания файлов
    const posterPreview = document.getElementById('posterPreview');
    const fileInput = document.getElementById('poster');
    
    posterPreview.addEventListener('click', function() {
        fileInput.click();
    });
    
    posterPreview.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = 'var(--neon-primary)';
        this.style.backgroundColor = 'rgba(255, 0, 255, 0.1)';
    });
    
    posterPreview.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = 'rgba(255, 0, 255, 0.3)';
        this.style.backgroundColor = 'transparent';
    });
    
    posterPreview.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = 'rgba(255, 0, 255, 0.3)';
        this.style.backgroundColor = 'transparent';
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            
            // Имитируем изменение input для вызова previewPoster
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
        }
    });
    </script>
</body>
</html>