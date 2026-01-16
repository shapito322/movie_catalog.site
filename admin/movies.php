<?php
session_start();
require_once '../includes/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Обработка добавления/редактирования фильма
if(isset($_POST['save_movie'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = $db->escape($_POST['title']);
    $description = $db->escape($_POST['description']);
    $year = (int)$_POST['year'];
    $genre = $db->escape($_POST['genre']);
    $director = $db->escape($_POST['director']);
    $duration = (int)$_POST['duration'];
    $poster = $db->escape($_POST['poster']);
    $trailer_url = $db->escape($_POST['trailer_url']);

    if($id > 0) {
        // Редактирование
        $query = "UPDATE movies SET 
                  title = '$title',
                  description = '$description',
                  year = $year,
                  genre = '$genre',
                  director = '$director',
                  duration = $duration,
                  poster = '$poster',
                  trailer_url = '$trailer_url'
                  WHERE id = $id";
    } else {
        // Добавление
        $query = "INSERT INTO movies (title, description, year, genre, director, duration, poster, trailer_url) 
                  VALUES ('$title', '$description', $year, '$genre', '$director', $duration, '$poster', '$trailer_url')";
    }

    $db->query($query);
    header("Location: movies.php");
    exit();
}

// Обработка удаления
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM movies WHERE id = $id");
    header("Location: movies.php");
    exit();
}

// Получение списка фильмов
$movies_query = "SELECT * FROM movies ORDER BY id DESC";
$movies_result = $db->query($movies_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление фильмами - Админка</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <h2>MovieCatalog Admin</h2>
        <nav>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Дашборд</a>
            <a href="movies.php" class="active"><i class="fas fa-film"></i> Фильмы</a>
            <a href="users.php"><i class="fas fa-users"></i> Пользователи</a>
            <a href="comments.php"><i class="fas fa-comments"></i> Комментарии</a>
            <a href="../index.php"><i class="fas fa-home"></i> На сайт</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Управление фильмами</h1>
            <button class="btn-add" onclick="showMovieForm()">
                <i class="fas fa-plus"></i> Добавить фильм
            </button>
        </header>

        <!-- Форма добавления/редактирования -->
        <div id="movieForm" class="form-modal" style="display: none;">
            <div class="modal-content">
                <h2 id="formTitle">Добавить фильм</h2>
                <form method="POST" id="movieFormElement">
                    <input type="hidden" name="id" id="movieId" value="0">

                    <div class="form-group">
                        <label>Название:</label>
                        <input type="text" name="title" id="movieTitle" required>
                    </div>

                    <div class="form-group">
                        <label>Год:</label>
                        <input type="number" name="year" id="movieYear" min="1900" max="2030" required>
                    </div>

                    <div class="form-group">
                        <label>Жанр:</label>
                        <input type="text" name="genre" id="movieGenre" required>
                    </div>

                    <div class="form-group">
                        <label>Режиссер:</label>
                        <input type="text" name="director" id="movieDirector" required>
                    </div>

                    <div class="form-group">
                        <label>Длительность (мин):</label>
                        <input type="number" name="duration" id="movieDuration" required>
                    </div>

                    <div class="form-group">
                        <label>Постер (URL):</label>
                        <input type="text" name="poster" id="moviePoster" placeholder="https://example.com/poster.jpg">
                    </div>

                    <div class="form-group">
                        <label>Трейлер (URL):</label>
                        <input type="text" name="trailer_url" id="movieTrailer" placeholder="https://youtube.com/...">
                    </div>

                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" id="movieDescription" rows="5"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_movie" class="btn-save">Сохранить</button>
                        <button type="button" onclick="hideMovieForm()" class="btn-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Таблица фильмов -->
        <table class="admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Постер</th>
                <th>Название</th>
                <th>Год</th>
                <th>Жанр</th>
                <th>Рейтинг</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php while($movie = $movies_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $movie['id']; ?></td>
                    <td>
                        <img src="<?php echo !empty($movie['poster']) ? $movie['poster'] : '../assets/images/default-poster.jpg'; ?>"
                             alt="<?php echo htmlspecialchars($movie['title']); ?>"
                             class="table-poster"
                             onerror="this.src='../assets/images/default-poster.jpg'">
                    </td>
                    <td><?php echo htmlspecialchars($movie['title']); ?></td>
                    <td><?php echo $movie['year']; ?></td>
                    <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                    <td><?php echo number_format($movie['rating'], 1); ?></td>
                    <td class="actions">
                        <button onclick="editMovie(<?php echo $movie['id']; ?>, '<?php echo addslashes($movie['title']); ?>',
                        <?php echo $movie['year']; ?>, '<?php echo addslashes($movie['genre']); ?>',
                                '<?php echo addslashes($movie['director']); ?>', <?php echo $movie['duration']; ?>,
                                '<?php echo addslashes($movie['poster']); ?>', '<?php echo addslashes($movie['trailer_url']); ?>',
                                '<?php echo addslashes($movie['description']); ?>')"
                                class="btn-edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?php echo $movie['id']; ?>"
                           onclick="return confirm('Удалить фильм?')"
                           class="btn-delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<script>
    function showMovieForm() {
        document.getElementById('movieForm').style.display = 'block';
        document.getElementById('formTitle').textContent = 'Добавить фильм';
        document.getElementById('movieId').value = '0';
        document.getElementById('movieFormElement').reset();
    }

    function hideMovieForm() {
        document.getElementById('movieForm').style.display = 'none';
    }

    function editMovie(id, title, year, genre, director, duration, poster, trailer_url, description) {
        document.getElementById('movieForm').style.display = 'block';
        document.getElementById('formTitle').textContent = 'Редактировать фильм';
        document.getElementById('movieId').value = id;
        document.getElementById('movieTitle').value = title;
        document.getElementById('movieYear').value = year;
        document.getElementById('movieGenre').value = genre;
        document.getElementById('movieDirector').value = director;
        document.getElementById('movieDuration').value = duration;
        document.getElementById('moviePoster').value = poster;
        document.getElementById('movieTrailer').value = trailer_url;
        document.getElementById('movieDescription').value = description;
    }
</script>
</body>
</html>