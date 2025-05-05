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

// Ищем карточки направлений
$rows = $xpath->query('//div[@class="single-course"]');

if ($rows->length === 0) {
    echo json_encode(['error' => 'Направления не найдены. Возможно, изменилась структура сайта.']);
    exit;
}

// Фиктивные описания для направлений
$descriptions = [
    "09.03.01" => "Подготовка специалистов в области информатики и вычислительной техники.",
    "09.03.04" => "Разработка программного обеспечения и внедрение цифровых решений.",
    "15.03.04" => "Инженерия автоматизированных систем и технологий.",
    "13.03.02" => "Обеспечение надежности и эффективности энергетических систем.",
    "38.03.02" => "Подготовка управленческих кадров для бизнеса и IT-сферы.",
    "38.03.01" => "Финансовый анализ, планирование и экономическое моделирование.",
    "42.03.01" => "Создание рекламных стратегий и управление брендом в цифровой среде."
];

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

        // Формируем полную ссылку
        $fullLink = str_starts_with($href, 'http') ? $href : 'https://abitur71.tsu.tula.ru' . $href;

        // Берём фиктивное описание или ставим дефолтное
        $description = $descriptions[$code] ?? 'Описание временно недоступно.';
        $level = in_array($code, ['09.03.01', '09.03.04']) ? 'Бакалавриат' : 'Специалитет';
        $form = 'Очная форма обучения';

        $programs[] = [
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'level' => $level,
            'form' => $form,
            'link' => $fullLink
        ];
    }
}

echo json_encode($programs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>