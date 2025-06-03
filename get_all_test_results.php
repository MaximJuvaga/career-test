<?php
require_once 'db.php';

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM test_results ORDER BY created_at DESC");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка сервера']);
}
?>