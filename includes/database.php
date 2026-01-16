<?php
// includes/database.php

// Подключаем конфигурацию
require_once 'config.php';

class Database {
    private $connection;
    private static $instance = null;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8mb4");

        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    // Singleton pattern для получения одного экземпляра
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }

    public function insertId() {
        return $this->connection->insert_id;
    }

    public function affectedRows() {
        return $this->connection->affected_rows;
    }

    // Подготовленные запросы
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
}

// Создаем глобальный экземпляр базы данных
$db = Database::getInstance();
?>