<?php
// db.php

$host = 'localhost'; // Адрес сервера MySQL
$dbname = 'career_test'; // Название базы данных
$username = 'root'; // Имя пользователя MySQL (по умолчанию root для локального сервера)
$password = ''; // Пароль (пустой для локального сервера)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>