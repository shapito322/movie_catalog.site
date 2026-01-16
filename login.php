<?php
session_start();
require_once 'includes/database.php';

if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Поиск пользователя по username или email
    $query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $db->getConnection()->prepare($query);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password'])) {
            // Успешный вход
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Неверный пароль";
        }
    } else {
        $error = "Пользователь не найден";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - <?php echo SITE_NAME; ?></title>
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
            <a href="register.php"><i class="fas fa-user-plus"></i> Регистрация</a>
        </div>
    </div>
</nav>

<main class="container auth-container">
    <div class="auth-card">
        <h1>Вход в аккаунт</h1>

        <?php if(!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Имя пользователя или Email:</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <i class="fas fa-user input-icon"></i>
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
                <i class="fas fa-lock input-icon"></i>
            </div>

            <button type="submit" class="btn-auth">
                <i class="fas fa-sign-in-alt"></i> Войти
            </button>

            <div class="auth-links">
                <a href="register.php">Еще нет аккаунта? Зарегистрируйтесь</a>
            </div>
        </form>
    </div>
</main>

<script src="assets/js/script.js"></script>
</body>
</html>