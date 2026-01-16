<?php
session_start();
require_once '../includes/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Удаление комментария
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM comments WHERE id = $id");
    header("Location: comments.php");
    exit();
}

// Получение комментариев
$comments_query = "SELECT c.*, u.username, m.title as movie_title 
                   FROM comments c 
                   JOIN users u ON c.user_id = u.id 
                   JOIN movies m ON c.movie_id = m.id 
                   ORDER BY c.created_at DESC";
$comments_result = $db->query($comments_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление комментариями - Админка</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <h2>MovieCatalog Admin</h2>
        <nav>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Дашборд</a>
            <a href="movies.php"><i class="fas fa-film"></i> Фильмы</a>
            <a href="users.php"><i class="fas fa-users"></i> Пользователи</a>
            <a href="comments.php" class="active"><i class="fas fa-comments"></i> Комментарии</a>
            <a href="../index.php"><i class="fas fa-home"></i> На сайт</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Управление комментариями</h1>
        </header>

        <!-- Таблица комментариев -->
        <table class="admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Фильм</th>
                <th>Комментарий</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php while($comment = $comments_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $comment['id']; ?></td>
                    <td><?php echo htmlspecialchars($comment['username']); ?></td>
                    <td><?php echo htmlspecialchars($comment['movie_title']); ?></td>
                    <td class="comment-cell">
                        <?php echo nl2br(htmlspecialchars(substr($comment['comment'], 0, 100))); ?>
                        <?php if(strlen($comment['comment']) > 100): ?>...<?php endif; ?>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></td>
                    <td class="actions">
                        <a href="?delete=<?php echo $comment['id']; ?>"
                           onclick="return confirm('Удалить комментарий?')"
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
</body>
</html>