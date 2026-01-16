<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Получение фильмов с пагинацией
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Фильтрация
$genre = isset($_GET['genre']) ? $db->escape($_GET['genre']) : '';
$year = isset($_GET['year']) ? $db->escape($_GET['year']) : '';
$search = isset($_GET['search']) ? $db->escape($_GET['search']) : '';

$where = [];
$params = [];

if (!empty($genre)) {
    $where[] = "genre LIKE '%{$genre}%'";
}

if (!empty($year)) {
    $where[] = "year = '{$year}'";
}

if (!empty($search)) {
    $where[] = "(title LIKE '%{$search}%' OR director LIKE '%{$search}%' OR description LIKE '%{$search}%')";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Получение общего количества фильмов
$count_query = "SELECT COUNT(*) as total FROM movies $where_clause";
$count_result = $db->query($count_query);
$total_movies = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_movies / $limit);

// Получение фильмов
$query = "SELECT * FROM movies $where_clause ORDER BY rating DESC LIMIT $limit OFFSET $offset";
$result = $db->query($query);

// Получение уникальных жанров для фильтра
$genres_query = "SELECT DISTINCT genre FROM movies";
$genres_result = $db->query($genres_query);
$all_genres = [];
while($row = $genres_result->fetch_assoc()) {
    $genres = explode(',', $row['genre']);
    foreach($genres as $g) {
        $g = trim($g);
        if(!empty($g) && !in_array($g, $all_genres)) {
            $all_genres[] = $g;
        }
    }
}
sort($all_genres);

// Получение уникальных годов
$years_query = "SELECT DISTINCT year FROM movies ORDER BY year DESC";
$years_result = $db->query($years_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<!-- Навигация -->
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">MovieCatalog</a>
        <form action="index.php" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Поиск фильмов..." value="<?php echo htmlspecialchars($search); ?>">
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

<!-- Фильтры -->
<div class="filters">
    <div class="container">
        <form method="GET" class="filter-form">
            <select name="genre">
                <option value="">Все жанры</option>
                <?php foreach($all_genres as $g): ?>
                    <option value="<?php echo $g; ?>" <?php echo (isset($_GET['genre']) && $_GET['genre'] == $g) ? 'selected' : ''; ?>>
                        <?php echo $g; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="year">
                <option value="">Все годы</option>
                <?php while($year_row = $years_result->fetch_assoc()): ?>
                    <option value="<?php echo $year_row['year']; ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $year_row['year']) ? 'selected' : ''; ?>>
                        <?php echo $year_row['year']; ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Фильтровать</button>
            <a href="index.php" class="clear-btn">Сбросить</a>
        </form>
    </div>
</div>

<main class="container">
    <h1>Каталог фильмов</h1>

    <!-- Сообщения -->
    <?php if($message = get_message()): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="movies-grid">
        <?php while($movie = $result->fetch_assoc()): ?>
            <div class="movie-card">
                <a href="movie.php?id=<?php echo $movie['id']; ?>">
                    <img src="<?php echo !empty($movie['poster']) ? htmlspecialchars($movie['poster']) : 'assets/images/default-poster.jpg'; ?>"
                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                         onerror="this.src='assets/images/default-poster.jpg'">
                    <div class="movie-info">
                        <h3><?php echo htmlspecialchars($movie['title']); ?> (<?php echo $movie['year']; ?>)</h3>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <span><?php echo number_format($movie['rating'], 1); ?></span>
                        </div>
                        <p class="genre"><?php echo htmlspecialchars($movie['genre']); ?></p>
                        <p class="director">Режиссер: <?php echo htmlspecialchars($movie['director']); ?></p>
                    </div>
                </a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="movie-actions">
                        <?php
                        // Проверяем, в избранном ли фильм
                        $is_favorite = false;
                        if(isset($_SESSION['user_id'])) {
                            $user_id = $_SESSION['user_id'];
                            $check_fav = $db->query("SELECT id FROM favorites WHERE user_id = $user_id AND movie_id = {$movie['id']}");
                            $is_favorite = $check_fav->num_rows > 0;
                        }
                        ?>
                        <button class="btn-favorite" data-movie="<?php echo $movie['id']; ?>" data-favorite="<?php echo $is_favorite ? 'true' : 'false'; ?>">
                            <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Пагинация -->
    <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?><?php echo !empty($genre) ? '&genre='.urlencode($genre) : ''; ?><?php echo !empty($year) ? '&year='.$year : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">&laquo;</a>
            <?php endif; ?>

            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($genre) ? '&genre='.urlencode($genre) : ''; ?><?php echo !empty($year) ? '&year='.$year : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>"
                   class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo !empty($genre) ? '&genre='.urlencode($genre) : ''; ?><?php echo !empty($year) ? '&year='.$year : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Футер -->
<footer>
    <div class="container">
        <p>&copy; 2026 MovieCatalog. Все права защищены.</p>
    </div>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>