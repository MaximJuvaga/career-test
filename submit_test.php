<?php
// submit_test.php

require_once 'db.php'; // Подключаем файл с настройками базы данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из POST-запроса
    $q1 = $_POST['q1'] ?? '';
    $q2 = $_POST['q2'] ?? '';
    $email = $_POST['email'] ?? ''; // Email пользователя

    // Простая логика для вывода результата
    if ($q1 === 'yes' && $q2 === 'yes') {
        $profession = "оператор дронов";
    } elseif ($q1 === 'yes' && $q2 === 'no') {
        $profession = "разработчик программного обеспечения для БПЛА";
    } elseif ($q1 === 'no' && $q2 === 'yes') {
        $profession = "менеджер проектов БПЛА";
    } else {
        $profession = "маркетолог в сфере технологий";
    }

    // Логируем результат теста
    error_log("Результат теста: профессия = $profession");

    // Сохраняем результаты теста в базу данных
    try {
        $stmt = $pdo->prepare("INSERT INTO test_results (user_email, q1, q2, result) VALUES (:email, :q1, :q2, :result)");
        $stmt->execute([
            ':email' => $email,
            ':q1' => $q1,
            ':q2' => $q2,
            ':result' => $profession
        ]);
        error_log("Данные успешно сохранены в базу данных.");
    } catch (PDOException $e) {
        error_log("Ошибка при сохранении данных в базу: " . $e->getMessage());
        echo json_encode(['error' => 'Ошибка при сохранении данных: ' . $e->getMessage()]);
        exit;
    }

    // Запрос к API HeadHunter для поиска вакансий
    $apiUrl = "https://api.hh.ru/vacancies";
    $query = urlencode($profession); // Кодируем название профессии для запроса
    $area = 113; // Россия
    $perPage = 5;

    $url = "$apiUrl?text=$query&area=$area&per_page=$perPage";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36'
    ]);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Ошибка cURL: " . curl_error($ch));
        echo json_encode(['error' => 'Ошибка при выполнении запроса к API: ' . curl_error($ch)]);
        curl_close($ch);
        exit;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        error_log("API HeadHunter вернул ошибку: HTTP $httpCode");
        echo json_encode(['error' => "API HeadHunter вернул ошибку: HTTP $httpCode"]);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    // Логируем ответ от API
    error_log("Ответ от API HeadHunter: " . $response);

    $vacanciesData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Ошибка при декодировании JSON-ответа.");
        echo json_encode(['error' => 'Ошибка при декодировании JSON-ответа.']);
        exit;
    }

    // Проверяем, есть ли вакансии в ответе
    if (empty($vacanciesData['items'])) {
        error_log("По запросу '$profession' не найдено вакансий.");
        echo json_encode([
            'profession' => $profession,
            'vacancies' => [],
            'message' => 'Не найдено вакансий для этой профессии.'
        ]);
        exit;
    }

    // Формируем список вакансий
    $vacancies = [];
    foreach ($vacanciesData['items'] as $vacancy) {
        $vacancies[] = [
            'title' => $vacancy['name'],
            'employer' => $vacancy['employer']['name'],
            'salary' => $vacancy['salary'] ? ($vacancy['salary']['from'] . ' - ' . $vacancy['salary']['to']) : 'Не указана',
            'url' => $vacancy['alternate_url']
        ];
    }

    // Логируем успешное формирование списка вакансий
    error_log("Список вакансий успешно сформирован.");

    // Формируем ответ
    echo json_encode([
        'profession' => $profession,
        'vacancies' => $vacancies
    ]);
}
?>