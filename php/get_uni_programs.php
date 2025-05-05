<?php
header('Content-Type: application/json');

// URL сайта ТулГУ с направлениями
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

// Ищем все карточки направлений
$rows = $xpath->query('//div[@class="single-course"]');

if ($rows->length === 0) {
    echo json_encode(['error' => 'Направления не найдены. Возможно, изменилась структура сайта.']);
    exit;
}

$programs = [];

foreach ($rows as $row) {
    // Находим ссылку внутри блока
    $linkNode = $xpath->query('.//a', $row)->item(0);
    if (!$linkNode) continue;

    $title = trim($linkNode->textContent); // Например: "09.03.01 Информатика и вычислительная техника"
    $href = trim($linkNode->getAttribute('href'));

    // Разделяем на код и название программы
    if (preg_match('/^(\d+\.\d+\.\d+)\s+(.+)$/', $title, $matches)) {
        $code = $matches[1];
        $name = $matches[2];

        // ❗️ИСПРАВЛЕНИЕ: теперь проверяем, начинается ли href с http
        if (str_starts_with($href, 'http')) {
            $fullLink = $href; // если полная ссылка — используем как есть
        } else {
            $fullLink = 'https://abitur71.tsu.tula.ru' . $href; // иначе добавляем домен
        }

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

echo json_encode($programs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>