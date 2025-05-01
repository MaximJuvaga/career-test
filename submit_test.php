<?php
// submit_test.php

require_once 'db.php'; // Подключаем файл с настройками базы данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем ответы из формы
    $q1 = $_POST['q1'] ?? '';
    $q2 = $_POST['q2'] ?? '';
    $email = $_POST['email'] ?? ''; // Email пользователя

    // Простая логика для вывода результата
    if ($q1 === 'yes' && $q2 === 'yes') {
        $result = "Вам подходит работа инженером!";
    } elseif ($q1 === 'yes' && $q2 === 'no') {
        $result = "Вам подходит работа исследователем!";
    } elseif ($q1 === 'no' && $q2 === 'yes') {
        $result = "Вам подходит работа в сфере управления проектами!";
    } else {
        $result = "Вам подходит работа в сфере маркетинга!";
    }

    try {
        // Сохраняем результаты теста в базу данных
        $stmt = $pdo->prepare("INSERT INTO test_results (user_email, q1, q2, result) VALUES (:email, :q1, :q2, :result)");
        $stmt->execute([
            ':email' => $email,
            ':q1' => $q1,
            ':q2' => $q2,
            ':result' => $result
        ]);

        echo $result;
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}
?>