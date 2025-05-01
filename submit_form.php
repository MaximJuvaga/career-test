<?php
// submit_form.php

require_once 'db.php'; // Подключаем файл с настройками базы данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $name = $_POST['name'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $city = $_POST['city'];

    try {
        // Подготавливаем SQL-запрос
        $stmt = $pdo->prepare("INSERT INTO users (name, email, age, city) VALUES (:name, :email, :age, :city)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':age' => $age,
            ':city' => $city
        ]);

        echo "Данные успешно сохранены!";
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}
?>