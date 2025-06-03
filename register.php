<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Все поля обязательны']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'error' => 'Логин уже занят']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (login, password, role) VALUES (?, ?, 'abiturient')");
        $stmt->execute([$login, $hashedPassword]);

        // Авторизация после регистрации
        session_start();
        $userId = $pdo->lastInsertId();
        $_SESSION['user'] = [
            'id' => $userId,
            'login' => $login,
            'role' => 'abiturient'
        ];

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Ошибка регистрации']);
    }
}
?>