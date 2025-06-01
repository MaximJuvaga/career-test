<?php
header('Content-Type: application/json');

// Все нужные нам программы с их URL-ами
$targetPrograms = [
    '15.03.01' => 'https://abitur71.tsu.tula.ru/program/15.03.01', 
    '15.03.05' => 'https://abitur71.tsu.tula.ru/program/15.03.05', 
    '22.03.01' => 'https://abitur71.tsu.tula.ru/program/22.03.01', 
    '07.03.01' => 'https://abitur71.tsu.tula.ru/program/07.03.01', 
    '08.03.01' => 'https://abitur71.tsu.tula.ru/program/08.03.01', 
    '21.03.01' => 'https://abitur71.tsu.tula.ru/program/21.03.01', 
    '11.05.01' => 'https://abitur71.tsu.tula.ru/program/11.05.01', 
    '17.05.01' => 'https://abitur71.tsu.tula.ru/program/17.05.01' 
];

// Список названий направлений
$programNames = [
    '15.03.01' => 'Машиностроение',
    '15.03.05' => 'Конструкторско-технологическое обеспечение машиностроительных производств',
    '22.03.01' => 'Материаловедение и технологии материалов',
    '07.03.01' => 'Архитектура',
    '08.03.01' => 'Стандартизация и метрология',
    '21.03.01' => 'Нефтегазовое дело',
    '11.05.01' => 'Проектирование и технология электронных средств',
    '17.05.01' => 'Специальные системы обеспечения движения поездов'
];

$programs = [];

foreach ($targetPrograms as $code => $url) {
    $html = @file_get_contents($url);
    if (!$html) continue;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Ищем заголовок (если он есть)
    $titleNode = $xpath->query('//h1[@class="entry-title"]')->item(0);
    $title = trim($titleNode ? $titleNode->textContent : 'Не найдено');

    // Если название не найдено — берём из списка
    $name = $title === 'Не найдено' && isset($programNames[$code])
        ? $programNames[$code]
        : $title;

    // Формируем полную ссылку
    $fullLink = filter_var($url, FILTER_VALIDATE_URL) ? $url : 'https://abitur71.tsu.tula.ru'  . $url;

    $programs[] = [
        'code' => $code,
        'name' => $name,
        'description' => 'Описание временно недоступно.',
        'link' => $fullLink
    ];
}

echo json_encode($programs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>