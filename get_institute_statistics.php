<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit;
}

try {
    $query = "
        SELECT 
            JSON_UNQUOTE(JSON_EXTRACT(tr.result_json, '$.theme')) as institute, 
            COUNT(*) as count
        FROM test_results tr
        JOIN users u ON tr.user_id = u.id
        WHERE u.role = 'abiturient'
    ";

    $conditions = [];
    $params = [];

    if (!empty($_GET['date'])) {
        $conditions[] = "DATE(tr.created_at) = :date";
        $params[':date'] = $_GET['date'];
    }
    if (!empty($_GET['institute'])) {
        $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(tr.result_json, '$.theme')) = :institute";
        $params[':institute'] = $_GET['institute'];
    }
    if (!empty($_GET['login'])) {
        $conditions[] = "u.login LIKE :login";
        $params[':login'] = "%{$_GET['login']}%";
    }

    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    $query .= " GROUP BY institute";

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $validInstitutes = [
        "Политехнический институт",
        "Институт горного дела и строительства",
        "Институт прикладной математики и компьютерных наук",
        "Институт высокоточных систем им. Грязева"
    ];

    $instituteCounts = array_fill_keys($validInstitutes, 0);

    foreach ($results as $row) {
        if (in_array($row['institute'], $validInstitutes)) {
            $instituteCounts[$row['institute']] = $row['count'];
        }
    }

    echo json_encode($instituteCounts, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка сервера']);
}
?>