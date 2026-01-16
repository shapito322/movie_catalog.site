<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получение информации о фильме
$movie_query = "SELECT * FROM movies WHERE id = $movie_id";
$movie_result = $db->query($movie_query);
$movie = $movie_result->fetch_assoc();

if(!$movie) {
    header("Location: index.php");
    exit();
}

// Обработка добавления комментария
if(isset($_POST['add_comment']) && isset($_SESSION['user_id'])) {
    $comment = $db->escape($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    $insert_comment = "INSERT INTO comments (user_id, movie_id, comment) 
                      VALUES ($user_id, $movie_id, '$comment')";
    $db->query($insert_comment);

    redirect("movie.php?id=$movie_id", "Комментарий добавлен");
}

// Обработка оценки фильма
if(isset($_POST['submit_rating']) && isset($_SESSION['user_id'])) {
    $rating = (float)$_POST['rating'];
    $user_id = $_SESSION['user_id'];

    if($rating >= 1 && $rating <= 10) {
        // Проверяем, есть ли уже оценка от этого пользователя
        $check_rating = "SELECT id FROM ratings WHERE user_id = $user_id AND movie_id = $movie_id";
        $check_result = $db->query($check_rating);

        if($check_result->num_rows > 0) {
            $update_rating = "UPDATE ratings SET rating = $rating WHERE user_id = $user_id AND movie_id = $movie_id";
            $db->query($update_rating);
        } else {
            $insert_rating = "INSERT INTO ratings (user_id, movie_id, rating) 
                             VALUES ($user_id, $movie_id, $rating)";
            $db->query($insert_rating);
        }

        // Обновляем общий рейтинг фильма
        update_movie_rating($db, $movie_id);

        redirect("movie.php?id=$movie_id", "Спасибо за вашу оценку!");
    }
}

// Получение комментариев
$comments_query = "SELECT c.*, u.username, u.avatar 
                   FROM comments c 
                   JOIN users u ON c.user_id = u.id 
                   WHERE c.movie_id = $movie_id 
                   ORDER BY c.created_at DESC";
$comments_result = $db->query($comments_query);

// Получение средней оценки
$avg_rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM ratings WHERE movie_id = $movie_id";
$avg_result = $db->query($avg_rating_query);
$rating_data = $avg_result->fetch_assoc();
$avg_rating = $rating_data['avg_rating'];
$rating_count = $rating_data['count'];

// Проверка, оценил ли пользователь этот фильм
$user_rating = null;
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_rating_query = "SELECT rating FROM ratings WHERE user_id = $user_id AND movie_id = $movie_id";
    $user_rating_result = $db->query($user_rating_query);
    if($user_rating_result->num_rows > 0) {
        $user_rating = $user_rating_result->fetch_assoc()['rating'];
    }
}

// Проверка, в избранном ли фильм
$is_favorite = false;
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check_fav = $db->query("SELECT id FROM favorites WHERE user_id = $user_id AND movie_id = $movie_id");
    $is_favorite = $check_fav->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">MovieCatalog</a>
        <form action="index.php" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Поиск фильмов...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        <div class="nav-links">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile.php"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="admin/"><i class="fas fa-cog"></i> Админка</a>
                <?php endif; ?>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Войти</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container movie-detail">
    <?php if($message = get_message()): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="movie-header">
        <img src="<?php echo !empty($movie['poster']) ? htmlspecialchars($movie['poster']) : 'assets/images/default-poster.jpg'; ?>"
             alt="<?php echo htmlspecialchars($movie['title']); ?>"
             class="movie-poster"
             onerror="this.src='assets/images/default-poster.jpg'">

        <div class="movie-info">
            <div class="movie-title-row">
                <h1><?php echo htmlspecialchars($movie['title']); ?> (<?php echo $movie['year']; ?>)</h1>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <button class="btn-favorite-detail" data-movie="<?php echo $movie['id']; ?>" data-favorite="<?php echo $is_favorite ? 'true' : 'false'; ?>">
                        <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span><?php echo $is_favorite ? 'В избранном' : 'В избранное'; ?></span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="rating-big">
                <div class="stars">
                    <?php
                    $rounded_rating = round($avg_rating);
                    for($i = 1; $i <= 10; $i++):
                        ?>
                        <i class="fas fa-star <?php echo $i <= $rounded_rating ? 'active' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>
                <div class="rating-info">
                    <span class="rating-value"><?php echo number_format($avg_rating, 1); ?>/10</span>
                    <span class="rating-count">(<?php echo $rating_count; ?> оценок)</span>
                </div>
            </div>

            <div class="movie-meta">
                <p><strong>Жанр:</strong> <?php echo htmlspecialchars($movie['genre']); ?></p>
                <p><strong>Режиссер:</strong> <?php echo htmlspecialchars($movie['director']); ?></p>
                <p><strong>Длительность:</strong> <?php echo $movie['duration']; ?> мин.</p>
                <p><strong>Год:</strong> <?php echo $movie['year']; ?></p>
            </div>

            <?php if(!empty($movie['trailer_url'])): ?>
                <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" class="btn-trailer" target="_blank">
                    <i class="fab fa-youtube"></i> Смотреть трейлер
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="movie-description">
        <h2>Описание</h2>
        <p><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
    </div>

    <!-- Форма оценки -->
    <?php if(isset($_SESSION['user_id'])): ?>
        <div class="rating-section">
            <h2>Ваша оценка</h2>
            <form method="POST" class="rating-form">
                <div class="stars-input">
                    <?php for($i = 10; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>"
                                <?php echo $user_rating == $i ? 'checked' : ''; ?>>
                        <label for="star<?php echo $i; ?>"><?php echo $i; ?></label>
                    <?php endfor; ?>
                </div>
                <button type="submit" name="submit_rating" class="btn-rate">Оценить</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Комментарии -->
    <div class="comments-section">
        <h2>Комментарии</h2>

        <?php if(isset($_SESSION['user_id'])): ?>
            <form method="POST" class="comment-form">
                <textarea name="comment" placeholder="Напишите ваш комментарий..." required></textarea>
                <button type="submit" name="add_comment" class="btn-comment">Отправить</button>
            </form>
        <?php endif; ?>

        <div class="comments-list">
            <?php while($comment = $comments_result->fetch_assoc()): ?>
                <div class="comment">
                    <div class="comment-header">
                        <img src="<?php echo !empty($comment['avatar']) ? 'uploads/avatars/' . htmlspecialchars($comment['avatar']) : 'assets/images/default.jpg'; ?>"
                             alt="<?php echo htmlspecialchars($comment['username']); ?>"
                             class="comment-avatar"
                             onerror="this.src='assets/images/default.jpg'">
                        <div>
                            <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                            <span class="comment-date"><?php echo format_date($comment['created_at']); ?></span>
                        </div>
                    </div>
                    <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>

<script src="assets/js/script.js"></script>
</body>
</html>