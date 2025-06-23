<?php
// db.php

$host = 'sql205.infinityfree.com'; // MYSQL HOSTNAME
$dbname = 'if0_39305155_career_test_db'; // MYSQL DATABASE NAME (замените xxx на ваше имя БД)
$username = 'if0_39305155'; // MYSQL USERNAME
$password = '9AnsjuVY7dS'; // MYSQL PASSWORD

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>