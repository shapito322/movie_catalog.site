<?php
// Обновление рейтинга фильма
function update_movie_rating($db, $movie_id) {
    $query = "UPDATE movies m 
              SET rating = (
                  SELECT AVG(r.rating) 
                  FROM ratings r 
                  WHERE r.movie_id = m.id
              ) 
              WHERE m.id = $movie_id";
    $db->query($query);
}

// Форматирование даты
function format_date($date_string) {
    $date = new DateTime($date_string);
    $now = new DateTime();
    $diff = $now->diff($date);

    if($diff->days == 0) {
        if($diff->h == 0) {
            return $diff->i . ' минут назад';
        }
        return $diff->h . ' часов назад';
    }

    if($diff->days == 1) {
        return 'Вчера';
    }

    return $date->format('d.m.Y H:i');
}

// Проверка авторизации
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Проверка администратора
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Загрузка файла
function upload_file($file, $target_dir) {
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $target_path = $target_dir . $filename;

    if(move_uploaded_file($file['tmp_name'], $target_path)) {
        return $filename;
    }

    return false;
}

// Редирект с сообщением
function redirect($url, $message = null) {
    if($message) {
        $_SESSION['message'] = $message;
    }
    header("Location: $url");
    exit();
}

// Получение сообщения
function get_message() {
    if(isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}
?>