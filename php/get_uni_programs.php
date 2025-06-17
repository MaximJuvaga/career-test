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
    // Политехнический институт
    '15.03.01' => 'Машиностроение',
    '15.03.05' => 'Конструкторско-технологическое обеспечение машиностроительных производств',
    '22.03.01' => 'Материаловедение и технологии материалов',
    '15.03.04' => 'Автоматизация технологических процессов и производств',
    '27.03.01' => 'Стандартизация и метрология',
    '23.03.01' => 'Технология транспортных процессов',

    // Институт горного дела и строительства
    '07.03.01' => 'Архитектура',
    '21.03.01' => 'Нефтегазовое дело',
    '54.03.01' => 'Дизайн',
    '21.03.02' => 'Землеустройство и кадастры',
    '21.05.04' => 'Горное дело',
    '08.03.01' => 'Экономика',

    // Институт прикладной математики и компьютерных наук
    '09.03.01' => 'Информатика и вычислительная техника',
    '09.03.04' => 'Программная инженерия',
    '10.05.01' => 'Информационная безопасность',
    '01.03.02' => 'Прикладная математика и информатика',
    '09.03.03' => 'Прикладная информатика',
    '09.03.02' => 'Информационные системы и технологии',

    // Институт высокоточных систем им. Грязева
    '11.05.01' => 'Радиоэлектронные системы и комплексы',
    '17.05.01' => 'Специальные системы обеспечения движения поездов',
    '24.05.02' => 'Проектирование авиационных и ракетных двигателей',
    '15.05.01' => 'Проектирование технологических машин и комплексов',
    '15.03.06' => 'Мехатроника и робототехника',
    '13.03.02' => 'Электроэнергетика и электротехника'
];

$targetPrograms = [];
foreach ($programNames as $code => $name) {
    $targetPrograms[$code] = "https://abitur71.tsu.tula.ru/program/{$code}"; 
}

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