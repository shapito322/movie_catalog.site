<?php
// test_connection.php

// Подключаем конфигурационный файл
require_once 'includes/config.php';

echo "<h2>Тестирование подключения к базе данных</h2>";

// Создаем подключение
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("<p style='color: red;'>✗ Ошибка подключения к MySQL: " . $conn->connect_error . "</p>");
}

echo "<p style='color: green;'>✓ Подключение к MySQL серверу успешно</p>";

// Проверяем, существует ли база данных
$result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ База данных '" . DB_NAME . "' существует</p>";

    // Пробуем выбрать базу данных
    if ($conn->select_db(DB_NAME)) {
        echo "<p style='color: green;'>✓ Успешно выбрана база данных '" . DB_NAME . "'</p>";

        // Тестируем запрос
        $test_query = "SELECT 1 as test";
        if ($conn->query($test_query)) {
            echo "<p style='color: green;'>✓ Тестовый запрос выполнен успешно</p>";
        } else {
            echo "<p style='color: red;'>✗ Ошибка выполнения тестового запроса: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Не удалось выбрать базу данных '" . DB_NAME . "'</p>";
    }
} else {
    echo "<p style='color: red;'>✗ База данных '" . DB_NAME . "' не найдена</p>";
    echo "<p>Создайте базу данных через phpMyAdmin или выполните:</p>";
    echo "<pre>CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</pre>";
}

// Закрываем соединение
$conn->close();

echo "<h3>Настройки подключения:</h3>";
echo "<ul>";
echo "<li>DB_HOST: " . DB_HOST . "</li>";
echo "<li>DB_USER: " . DB_USER . "</li>";
echo "<li>DB_PASS: " . (DB_PASS ? '***' : '(пусто)') . "</li>";
echo "<li>DB_NAME: " . DB_NAME . "</li>";
echo "</ul>";

// Проверка файла config.php
echo "<h3>Проверка файла config.php:</h3>";
echo "<p>Путь: " . realpath('includes/config.php') . "</p>";

// Проверка прав доступа к папке includes
echo "<h3>Проверка прав доступа:</h3>";
if (is_readable('includes/config.php')) {
    echo "<p style='color: green;'>✓ Файл config.php доступен для чтения</p>";
} else {
    echo "<p style='color: red;'>✗ Файл config.php не доступен для чтения</p>";
}
?>