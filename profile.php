<?php require_once 'auth_check.php'; ?>
<?php if ($_SESSION['user']['role'] !== 'student'): ?>
    <?php header("Location: index.html"); exit(); ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль</title>
</head>
<body>
    <h2>Привет, <?= htmlspecialchars($_SESSION['user']['username']) ?>!</h2>
    <p>Вы вошли как абитуриент.</p>
    <p><a href="test.html">Пройти тест</a> | <a href="logout.php">Выйти</a></p>
</body>
</html>