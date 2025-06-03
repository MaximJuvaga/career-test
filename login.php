<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user'] = [
                'id' => $user['id'],
                'login' => $user['login'],
                'role' => $user['role']
            ];
            echo json_encode(['success' => true, 'role' => $user['role']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Неверный логин или пароль']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
    }
    exit;
}
?>