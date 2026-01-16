<?php
require_once '../includes/database.php';

header('Content-Type: application/json');

$search = isset($_GET['q']) ? $db->escape($_GET['q']) : '';

if(strlen($search) < 2) {
    echo json_encode(['success' => false, 'message' => 'Слишком короткий запрос']);
    exit();
}

$query = "SELECT id, title, year, poster, rating FROM movies 
          WHERE title LIKE '%$search%' 
          OR director LIKE '%$search%'
          OR description LIKE '%$search%'
          ORDER BY rating DESC 
          LIMIT 10";

$result = $db->query($query);
$movies = [];

while($row = $result->fetch_assoc()) {
    $movies[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'year' => $row['year'],
        'poster' => $row['poster'] ?: 'assets/images/default-poster.jpg',
        'rating' => $row['rating']
    ];
}

echo json_encode(['success' => true, 'movies' => $movies]);
?>