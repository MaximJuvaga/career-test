<?php
require_once 'db.php';

session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM test_results WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка сервера']);
}
?>