<?php
// submit_test.php

require_once 'db.php'; // Подключаем файл с настройками базы данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $q1 = $_POST['q1'] ?? '';
    $q2 = $_POST['q2'] ?? '';
    $email = $_POST['email'] ?? ''; // Email пользователя

    // Простая логика для вывода результата
    if ($q1 === 'yes' && $q2 === 'yes') {
        $profession = "инженер";
    } elseif ($q1 === 'yes' && $q2 === 'no') {
        $profession = "исследователь";
    } elseif ($q1 === 'no' && $q2 === 'yes') {
        $profession = "менеджер проектов";
    } else {
        $profession = "маркетолог";
    }

    // Сохраняем результаты теста в базу данных
    try {
        $stmt = $pdo->prepare("INSERT INTO test_results (user_email, q1, q2, result) VALUES (:email, :q1, :q2, :result)");
        $stmt->execute([
            ':email' => $email,
            ':q1' => $q1,
            ':q2' => $q2,
            ':result' => $profession
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Ошибка при сохранении данных: ' . $e->getMessage()]);
        exit;
    }

    // Запрос к API HeadHunter для поиска вакансий
    $apiUrl = "https://api.hh.ru/vacancies";
    $query = urlencode($profession); // Кодируем название профессии для запроса
    $response = file_get_contents("$apiUrl?text=$query&area=1&per_page=5"); // area=1 - Москва, per_page=5 - 5 вакансий

    if (!$response) {
        echo json_encode([
            'profession' => $profession,
            'vacancies' => [],
            'message' => 'Не удалось получить вакансии. Попробуйте позже.'
        ]);
        exit;
    }

    $vacanciesData = json_decode($response, true);
    $vacancies = [];

    if (!empty($vacanciesData['items'])) {
        foreach ($vacanciesData['items'] as $vacancy) {
            $vacancies[] = [
                'title' => $vacancy['name'],
                'employer' => $vacancy['employer']['name'],
                'salary' => $vacancy['salary'] ? ($vacancy['salary']['from'] . ' - ' . $vacancy['salary']['to']) : 'Не указана',
                'url' => $vacancy['alternate_url']
            ];
        }
    }

    // Формируем ответ
    echo json_encode([
        'profession' => $profession,
        'vacancies' => $vacancies
    ]);
}
?>