<?php
session_start();
require_once 'includes/database.php';

if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($db->escape($_POST['username']));
    $email = trim($db->escape($_POST['email']));
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Валидация
    if(empty($username)) {
        $errors[] = "Имя пользователя обязательно";
    } elseif(strlen($username) < 3) {
        $errors[] = "Имя пользователя должно быть не менее 3 символов";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Имя пользователя может содержать только буквы, цифры и подчеркивания";
    }

    if(empty($email)) {
        $errors[] = "Email обязателен";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный email";
    }

    if(empty($password)) {
        $errors[] = "Пароль обязателен";
    } elseif(strlen($password) < 6) {
        $errors[] = "Пароль должен быть не менее 6 символов";
    }

    if($password !== $password_confirm) {
        $errors[] = "Пароли не совпадают";
    }

    // Проверка уникальности
    if(empty($errors)) {
        $check_username = "SELECT id FROM users WHERE username = '$username'";
        $check_email = "SELECT id FROM users WHERE email = '$email'";

        if($db->query($check_username)->num_rows > 0) {
            $errors[] = "Имя пользователя уже занято";
        }

        if($db->query($check_email)->num_rows > 0) {
            $errors[] = "Email уже зарегистрирован";
        }
    }

    // Регистрация
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password) 
                  VALUES ('$username', '$email', '$hashed_password')";

        if($db->query($query)) {
            $success = "Регистрация успешна! Теперь вы можете войти.";

            // Автоматический вход после регистрации
            $user_id = $db->insertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            $_SESSION['email'] = $email;

            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Ошибка при регистрации. Попробуйте позже.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">MovieCatalog</a>
        <div class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> На главную</a>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Вход</a>
        </div>
    </div>
</nav>

<main class="container auth-container">
    <div class="auth-card">
        <h1>Регистрация</h1>

        <?php if(!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <i class="fas fa-user input-icon"></i>
                <small>Только буквы, цифры и подчеркивания, минимум 3 символа</small>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <i class="fas fa-envelope input-icon"></i>
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
                <i class="fas fa-lock input-icon"></i>
                <small>Минимум 6 символов</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Подтверждение пароля:</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
                <i class="fas fa-lock input-icon"></i>
            </div>

            <button type="submit" class="btn-auth">
                <i class="fas fa-user-plus"></i> Зарегистрироваться
            </button>

            <div class="auth-links">
                <a href="login.php">Уже есть аккаунт? Войдите</a>
            </div>
        </form>
    </div>
</main>

<script src="assets/js/script.js"></script>
</body>
</html>