<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$errors = [];

// Получение информации о пользователе
$query = "SELECT * FROM users WHERE id = $user_id";
$user = $db->query($query)->fetch_assoc();

// Обновление профиля
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = trim($db->escape($_POST['username']));
    $email = trim($db->escape($_POST['email']));
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Валидация
    if(empty($username)) {
        $errors[] = "Имя пользователя обязательно";
    }

    if(empty($email)) {
        $errors[] = "Email обязателен";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный email";
    }

    // Проверка уникальности (если изменилось)
    if($username != $user['username']) {
        $check = "SELECT id FROM users WHERE username = '$username' AND id != $user_id";
        if($db->query($check)->num_rows > 0) {
            $errors[] = "Имя пользователя уже занято";
        }
    }

    if($email != $user['email']) {
        $check = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
        if($db->query($check)->num_rows > 0) {
            $errors[] = "Email уже зарегистрирован";
        }
    }

    // Смена пароля
    if(!empty($new_password)) {
        if(empty($current_password)) {
            $errors[] = "Для смены пароля введите текущий пароль";
        } elseif(!password_verify($current_password, $user['password'])) {
            $errors[] = "Текущий пароль неверен";
        } elseif(strlen($new_password) < 6) {
            $errors[] = "Новый пароль должен быть не менее 6 символов";
        }
    }

    // Обработка аватарки
    $avatar = $user['avatar'];
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if(in_array($_FILES['avatar']['type'], $allowed_types)) {
            if($_FILES['avatar']['size'] <= $max_size) {
                $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = "avatar_" . $user_id . "_" . time() . "." . $extension;
                $target_path = "uploads/avatars/" . $filename;

                if(move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                    // Удаляем старый аватар, если он не дефолтный
                    if($avatar != 'default.jpg' && file_exists("uploads/avatars/" . $avatar)) {
                        unlink("uploads/avatars/" . $avatar);
                    }
                    $avatar = $filename;
                }
            } else {
                $errors[] = "Размер файла не должен превышать 2MB";
            }
        } else {
            $errors[] = "Допустимые форматы: JPG, PNG, GIF";
        }
    }

    // Сохранение изменений
    if(empty($errors)) {
        $update_fields = "username = '$username', email = '$email', avatar = '$avatar'";

        if(!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_fields .= ", password = '$hashed_password'";
        }

        $update = "UPDATE users SET $update_fields WHERE id = $user_id";

        if($db->query($update)) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $success = "Профиль успешно обновлен";

            // Обновляем данные пользователя
            $user = $db->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
        } else {
            $errors[] = "Ошибка при обновлении профиля";
        }
    }
}

// Получение избранных фильмов
$favorites_query = "SELECT m.* FROM favorites f 
                   JOIN movies m ON f.movie_id = m.id 
                   WHERE f.user_id = $user_id 
                   ORDER BY f.added_at DESC";
$favorites_result = $db->query($favorites_query);

// Получение оценок пользователя
$ratings_query = "SELECT m.*, r.rating FROM ratings r 
                 JOIN movies m ON r.movie_id = m.id 
                 WHERE r.user_id = $user_id 
                 ORDER BY r.rated_at DESC";
$ratings_result = $db->query($ratings_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/profile.css">
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
            <a href="index.php"><i class="fas fa-home"></i> Главная</a>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="admin/"><i class="fas fa-cog"></i> Админка</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        </div>
    </div>
</nav>

<main class="container profile-container">
    <div class="profile-header">
        <div class="avatar-section">
            <img src="<?php echo !empty($user['avatar']) ? 'uploads/avatars/' . $user['avatar'] : 'assets/images/default.jpg'; ?>"
                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                 class="profile-avatar"
                 onerror="this.src='assets/images/default.jpg'">
            <div>
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="member-since">Участник с <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>

        <div class="profile-stats">
            <div class="stat">
                <i class="fas fa-heart"></i>
                <span><?php echo $favorites_result->num_rows; ?> избранных</span>
            </div>
            <div class="stat">
                <i class="fas fa-star"></i>
                <span><?php echo $ratings_result->num_rows; ?> оценок</span>
            </div>
            <div class="stat">
                <i class="fas fa-comment"></i>
                <?php
                $comments_count = $db->query("SELECT COUNT(*) as count FROM comments WHERE user_id = $user_id")->fetch_assoc()['count'];
                ?>
                <span><?php echo $comments_count; ?> комментариев</span>
            </div>
        </div>
    </div>

    <div class="profile-tabs">
        <button class="tab-btn active" onclick="openTab(event, 'edit-profile')">Редактировать профиль</button>
        <button class="tab-btn" onclick="openTab(event, 'favorites')">Избранное</button>
        <button class="tab-btn" onclick="openTab(event, 'ratings')">Мои оценки</button>
    </div>

    <!-- Вкладка редактирования профиля -->
    <div id="edit-profile" class="tab-content active">
        <h2>Редактирование профиля</h2>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="profile-form">
            <div class="form-group">
                <label>Аватар:</label>
                <div class="avatar-upload">
                    <div class="avatar-preview">
                        <img src="<?php echo !empty($user['avatar']) ? 'uploads/avatars/' . $user['avatar'] : 'assets/images/default.jpg'; ?>"
                             alt="Текущий аватар"
                             onerror="this.src='assets/images/default.jpg'">
                    </div>
                    <input type="file" name="avatar" accept="image/*" id="avatar-input">
                    <label for="avatar-input" class="btn-upload">
                        <i class="fas fa-camera"></i> Сменить аватар
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Имя пользователя:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Текущий пароль (для смены пароля):</label>
                <input type="password" name="current_password" placeholder="Введите текущий пароль для смены">
            </div>

            <div class="form-group">
                <label>Новый пароль (оставьте пустым, если не меняете):</label>
                <input type="password" name="new_password" placeholder="Новый пароль">
            </div>

            <button type="submit" name="update_profile" class="btn-save">
                <i class="fas fa-save"></i> Сохранить изменения
            </button>
        </form>
    </div>

    <!-- Вкладка избранного -->
    <div id="favorites" class="tab-content">
        <h2>Избранные фильмы</h2>

        <?php if($favorites_result->num_rows > 0): ?>
            <div class="movies-grid">
                <?php while($favorite = $favorites_result->fetch_assoc()): ?>
                    <div class="movie-card">
                        <a href="movie.php?id=<?php echo $favorite['id']; ?>">
                            <img src="<?php echo !empty($favorite['poster']) ? $favorite['poster'] : 'assets/images/default-poster.jpg'; ?>"
                                 alt="<?php echo htmlspecialchars($favorite['title']); ?>"
                                 onerror="this.src='assets/images/default-poster.jpg'">
                            <div class="movie-info">
                                <h3><?php echo htmlspecialchars($favorite['title']); ?> (<?php echo $favorite['year']; ?>)</h3>
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo number_format($favorite['rating'], 1); ?></span>
                                </div>
                                <p class="genre"><?php echo htmlspecialchars($favorite['genre']); ?></p>
                            </div>
                        </a>
                        <button class="btn-remove-favorite" data-movie="<?php echo $favorite['id']; ?>">
                            <i class="fas fa-times"></i> Удалить из избранного
                        </button>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <p>У вас пока нет избранных фильмов</p>
                <a href="index.php" class="btn-primary">Найти фильмы</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Вкладка оценок -->
    <div id="ratings" class="tab-content">
        <h2>Мои оценки</h2>

        <?php if($ratings_result->num_rows > 0): ?>
            <div class="ratings-list">
                <?php while($rating = $ratings_result->fetch_assoc()): ?>
                    <div class="rating-item">
                        <a href="movie.php?id=<?php echo $rating['id']; ?>" class="rating-movie">
                            <img src="<?php echo !empty($rating['poster']) ? $rating['poster'] : 'assets/images/default-poster.jpg'; ?>"
                                 alt="<?php echo htmlspecialchars($rating['title']); ?>"
                                 onerror="this.src='assets/images/default-poster.jpg'">
                            <div>
                                <h4><?php echo htmlspecialchars($rating['title']); ?> (<?php echo $rating['year']; ?>)</h4>
                                <p class="genre"><?php echo htmlspecialchars($rating['genre']); ?></p>
                            </div>
                        </a>
                        <div class="user-rating">
                            <div class="stars">
                                <?php for($i = 1; $i <= 10; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $rating['rating'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-value"><?php echo $rating['rating']; ?>/10</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <p>Вы еще не оценили ни одного фильма</p>
                <a href="index.php" class="btn-primary">Найти фильмы</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="assets/js/script.js"></script>
<script src="assets/js/profile.js"></script>
</body>
</html>