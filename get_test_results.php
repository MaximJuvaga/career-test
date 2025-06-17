<?php
require_once 'db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'abiturient') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit;
}

$userId = $_SESSION['user']['id'];
$date = $_GET['date'] ?? null;
$institute = $_GET['institute'] ?? null;

try {
    // Базовый запрос: только результаты пользователя
    $query = "SELECT * FROM test_results WHERE user_id = :user_id";

    // Добавляем фильтры, если они заданы
    if ($date) {
        $query .= " AND DATE(created_at) = :date";
    }
    if ($institute) {
        $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(result_json, '$.theme')) LIKE :institute";
    }

    // Подготавливаем запрос
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    // Привязываем параметры, если они есть
    if ($date) {
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
    }
    if ($institute) {
        $stmt->bindValue(':institute', "%$institute%", PDO::PARAM_STR);
    }

    // Выполняем запрос
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Возвращаем результаты в формате JSON
    echo json_encode($results, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()]);
}
?>