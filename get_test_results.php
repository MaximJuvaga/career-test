<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$userId = $_SESSION['user']['id'];
$date = $_GET['date'] ?? null;
$institute = $_GET['institute'] ?? null;

try {
    $query = "SELECT * FROM test_results WHERE user_id = :user_id";
    if ($date) {
        $query .= " AND DATE(created_at) = :date";
    }
    if ($institute) {
        $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(result_json, '$.theme')) LIKE :institute";
    }

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    if ($date) {
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
    }
    if ($institute) {
        $stmt->bindValue(':institute', "%$institute%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера']);
}
?>
