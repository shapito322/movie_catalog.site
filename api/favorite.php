<?php
session_start();
require_once '../includes/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : 'toggle';

if($movie_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID фильма']);
    exit();
}

// Проверяем существование фильма
$movie_check = $db->query("SELECT id FROM movies WHERE id = $movie_id");
if($movie_check->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Фильм не найден']);
    exit();
}

if($action == 'add') {
    // Добавляем в избранное
    $check = $db->query("SELECT id FROM favorites WHERE user_id = $user_id AND movie_id = $movie_id");
    if($check->num_rows == 0) {
        $db->query("INSERT INTO favorites (user_id, movie_id) VALUES ($user_id, $movie_id)");
        echo json_encode(['success' => true, 'action' => 'added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Уже в избранном']);
    }
} elseif($action == 'remove') {
    // Удаляем из избранного
    $db->query("DELETE FROM favorites WHERE user_id = $user_id AND movie_id = $movie_id");
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    // Переключаем
    $check = $db->query("SELECT id FROM favorites WHERE user_id = $user_id AND movie_id = $movie_id");
    if($check->num_rows == 0) {
        $db->query("INSERT INTO favorites (user_id, movie_id) VALUES ($user_id, $movie_id)");
        echo json_encode(['success' => true, 'action' => 'added']);
    } else {
        $db->query("DELETE FROM favorites WHERE user_id = $user_id AND movie_id = $movie_id");
        echo json_encode(['success' => true, 'action' => 'removed']);
    }
}
?>