<?php

$host = 'sql205.infinityfree.com'; 
$dbname = 'if0_39305155_career_test_db'; 
$username = 'if0_39305155'; 
$password = '9AnsjuVY7dS'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>