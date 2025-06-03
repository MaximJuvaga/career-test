<?php
require_once 'db.php';

session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT id, login, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'id' => $user['id'],
            'login' => $user['login'],
            'role' => $user['role']
        ]);
    } else {
        echo json_encode(['error' => 'Пользователь не найден']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка сервера']);
}
?>