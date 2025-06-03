<?php
// db.php
$host = 'localhost';
$dbname = 'career_test';
$username = 'root'; // обычно root
$password = '';     // обычно пусто на локальном сервере

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>