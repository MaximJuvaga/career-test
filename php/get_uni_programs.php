<?php
header('Content-Type: application/json');

// URL, откуда берём данные
$url = 'https://abitur71.tsu.tula.ru/admissions/f';

// Загружаем HTML
$html = @file_get_contents($url);
if (!$html) {
    echo json_encode(['error' => 'Не удалось загрузить данные с сайта ТулГУ']);
    exit;
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
@$dom->loadHTML($html);

$xpath = new DOMXPath($dom);

// Ищем все блоки направлений
$rows = $xpath->query('//div[contains(@class, "single-course")]');

if ($rows->length === 0) {
    echo json_encode(['error' => 'Направления не найдены. Возможно, изменилась структура сайта.']);
    exit;
}

$programs = [];

foreach ($rows as $row) {
    // Находим ссылку внутри карточки
    $linkNode = $xpath->query('.//a', $row)->item(0);
    if (!$linkNode) continue;

    $href = trim($linkNode->getAttribute('href'));

    // ❗️ИСПРАВЛЕНИЕ: проверяем, начинается ли href с https://...
    if (str_starts_with($href, 'http')) {
        // Если ссылка уже полная — используем её как есть
        $fullLink = $href;
    } else {
        // Если относительная — добавляем домен
        $fullLink = 'https://abitur71.tsu.tula.ru' . $href;
    }

    // Получаем текст ссылки (он содержит код и название программы)
    $title = trim($linkNode->textContent); // Пример: "09.03.01 Информатика и вычислительная техника"

    // Разделяем на код и название
    if (preg_match('/^(\d+\.\d+\.\d+)\s+(.+)$/', $title, $matches)) {
        $code = $matches[1];
        $name = $matches[2];

        $programs[] = [
            'name' => $name,
            'code' => $code,
            'description' => 'Описание временно недоступно.',
            'level' => 'Бакалавриат',
            'form' => 'Очная форма обучения',
            'link' => $fullLink
        ];
    }
}

if (empty($programs)) {
    echo json_encode(['error' => 'Не удалось извлечь ни одно направление. Проверьте формат данных.']);
} else {
    echo json_encode($programs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>