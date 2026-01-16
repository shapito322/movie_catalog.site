<?php
session_start();
require_once '../includes/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Статистика
$stats = [];

// Количество пользователей
$result = $db->query("SELECT COUNT(*) as count FROM users");
$stats['users'] = $result->fetch_assoc()['count'];

// Количество фильмов
$result = $db->query("SELECT COUNT(*) as count FROM movies");
$stats['movies'] = $result->fetch_assoc()['count'];

// Количество комментариев
$result = $db->query("SELECT COUNT(*) as count FROM comments");
$stats['comments'] = $result->fetch_assoc()['count'];

// Количество оценок
$result = $db->query("SELECT COUNT(*) as count FROM ratings");
$stats['ratings'] = $result->fetch_assoc()['count'];

// Последние фильмы
$recent_movies = $db->query("SELECT * FROM movies ORDER BY created_at DESC LIMIT 5");

// Последние пользователи
$recent_users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Админка</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <h2>MovieCatalog Admin</h2>
        <nav>
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Дашборд</a>
            <a href="movies.php"><i class="fas fa-film"></i> Фильмы</a>
            <a href="users.php"><i class="fas fa-users"></i> Пользователи</a>
            <a href="comments.php"><i class="fas fa-comments"></i> Комментарии</a>
            <a href="../index.php"><i class="fas fa-home"></i> На сайт</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Панель управления</h1>
            <p>Добро пожаловать, <?php echo $_SESSION['username']; ?>!</p>
        </header>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #4ecdc4;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['users']; ?></h3>
                    <p>Пользователей</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background-color: #ff6b6b;">
                    <i class="fas fa-film"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['movies']; ?></h3>
                    <p>Фильмов</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background-color: #45b7d1;">
                    <i class="fas fa-comment"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['comments']; ?></h3>
                    <p>Комментариев</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background-color: #96ceb4;">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['ratings']; ?></h3>
                    <p>Оценок</p>
                </div>
            </div>
        </div>

        <!-- Последние фильмы -->
        <div class="dashboard-section">
            <h2>Последние фильмы</h2>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Год</th>
                    <th>Рейтинг</th>
                    <th>Дата добавления</th>
                </tr>
                </thead>
                <tbody>
                <?php while($movie = $recent_movies->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $movie['id']; ?></td>
                        <td><?php echo htmlspecialchars($movie['title']); ?></td>
                        <td><?php echo $movie['year']; ?></td>
                        <td><?php echo number_format($movie['rating'], 1); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($movie['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Последние пользователи -->
        <div class="dashboard-section">
            <h2>Последние пользователи</h2>
            <table class="admin-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя пользователя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Дата регистрации</th>
                </tr>
                </thead>
                <tbody>
                <?php while($user = $recent_users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="role-badge <?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                        <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>