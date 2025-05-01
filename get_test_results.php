<?php
// get_test_results.php

require_once 'db.php';

$email = $_GET['email'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM test_results WHERE user_email = :email ORDER BY created_at DESC");
    $stmt->execute([':email' => $email]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>