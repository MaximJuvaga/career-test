<?php require_once 'auth_check.php'; ?>
<?php if ($_SESSION['user']['role'] !== 'teacher'): ?>
    <?php header("Location: index.html"); exit(); ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика</title>
</head>
<body>
    <h2>Добро пожаловать, преподаватель!</h2>
    <p><a href="programs.html">Смотреть направления подготовки</a></p>
    <p><a href="test.html">Тестирование</a></p>
    <p><a href="logout.php">Выйти</a></p>
</body>
</html>