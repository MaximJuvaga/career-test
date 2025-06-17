<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit;
}

$login = $_GET['login'] ?? '';
$date = $_GET['date'] ?? '';
$institute = $_GET['institute'] ?? '';

try {
    $query = "SELECT tr.*, u.login 
              FROM test_results tr
              JOIN users u ON tr.user_id = u.id
              WHERE 1=1";

    if ($login) {
        $query .= " AND u.login LIKE :login";
    }
    if ($date) {
        $query .= " AND DATE(tr.created_at) = :date";
    }
    if ($institute) {
        $query .= " AND JSON_UNQUOTE(JSON_EXTRACT(tr.result_json, '$.theme')) = :institute";
    }

    $stmt = $pdo->prepare($query);

    if ($login) {
        $stmt->bindValue(':login', "%$login%", PDO::PARAM_STR);
    }
    if ($date) {
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
    }
    if ($institute) {
        $stmt->bindValue(':institute', $institute, PDO::PARAM_STR);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedResults = array_map(function ($result) {
        $parsed = json_decode($result['result_json'], true);

        return [
            'id' => $result['id'],
            'login' => $result['login'],
            'created_at' => $result['created_at'],
            'result_json' => $result['result_json'] 
        ];
    }, $results);

    echo json_encode($formattedResults, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка сервера']);
}
?>