<?php
session_start();
require_once '../includes/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Обработка действий
if(isset($_POST['save_user'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = $db->escape($_POST['username']);
    $email = $db->escape($_POST['email']);
    $role = $db->escape($_POST['role']);

    if($id > 0) {
        $query = "UPDATE users SET username = '$username', email = '$email', role = '$role' WHERE id = $id";
    } else {
        // Для нового пользователя
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password, role) 
                  VALUES ('$username', '$email', '$password', '$role')";
    }

    $db->query($query);
    header("Location: users.php");
    exit();
}

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM users WHERE id = $id AND id != {$_SESSION['user_id']}");
    header("Location: users.php");
    exit();
}

// Получение пользователей
$users_query = "SELECT * FROM users ORDER BY id DESC";
$users_result = $db->query($users_query);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Админка</title>
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
            <a href="users.php" class="active"><i class="fas fa-users"></i> Пользователи</a>
            <a href="comments.php"><i class="fas fa-comments"></i> Комментарии</a>
            <a href="../index.php"><i class="fas fa-home"></i> На сайт</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        </nav>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <h1>Управление пользователями</h1>
            <button class="btn-add" onclick="showUserForm()">
                <i class="fas fa-user-plus"></i> Добавить пользователя
            </button>
        </header>

        <!-- Форма добавления/редактирования -->
        <div id="userForm" class="form-modal" style="display: none;">
            <div class="modal-content">
                <h2 id="formTitle">Добавить пользователя</h2>
                <form method="POST" id="userFormElement">
                    <input type="hidden" name="id" id="userId" value="0">

                    <div class="form-group">
                        <label>Имя пользователя:</label>
                        <input type="text" name="username" id="userUsername" required>
                    </div>

                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" id="userEmail" required>
                    </div>

                    <div class="form-group">
                        <label>Пароль (только для новых):</label>
                        <input type="password" name="password" id="userPassword">
                        <small>Оставьте пустым, чтобы не менять пароль</small>
                    </div>

                    <div class="form-group">
                        <label>Роль:</label>
                        <select name="role" id="userRole" required>
                            <option value="user">Пользователь</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_user" class="btn-save">Сохранить</button>
                        <button type="button" onclick="hideUserForm()" class="btn-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Таблица пользователей -->
        <table class="admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Имя пользователя</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php while($user = $users_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td>
                        <div class="user-info">
                            <img src="<?php echo !empty($user['avatar']) ? '../uploads/avatars/'.$user['avatar'] : '../assets/images/default.jpg'; ?>"
                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                 class="user-avatar"
                                 onerror="this.src='../assets/images/default.jpg'">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="role-badge <?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                    <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                    <td class="actions">
                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>',
                                    '<?php echo addslashes($user['email']); ?>', '<?php echo $user['role']; ?>')"
                                    class="btn-edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?delete=<?php echo $user['id']; ?>"
                               onclick="return confirm('Удалить пользователя?')"
                               class="btn-delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Текущий пользователь</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<script>
    function showUserForm() {
        document.getElementById('userForm').style.display = 'block';
        document.getElementById('formTitle').textContent = 'Добавить пользователя';
        document.getElementById('userId').value = '0';
        document.getElementById('userFormElement').reset();
        document.getElementById('userPassword').required = true;
    }

    function hideUserForm() {
        document.getElementById('userForm').style.display = 'none';
    }

    function editUser(id, username, email, role) {
        document.getElementById('userForm').style.display = 'block';
        document.getElementById('formTitle').textContent = 'Редактировать пользователя';
        document.getElementById('userId').value = id;
        document.getElementById('userUsername').value = username;
        document.getElementById('userEmail').value = email;
        document.getElementById('userRole').value = role;
        document.getElementById('userPassword').required = false;
        document.getElementById('userPassword').placeholder = 'Оставьте пустым для сохранения старого пароля';
    }
</script>
</body>
</html>