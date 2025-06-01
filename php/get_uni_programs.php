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

$programs = [];

foreach ($targetPrograms as $code => $url) {
    $html = @file_get_contents($url);
    if (!$html) continue;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $titleNode = $xpath->query('//h1[@class="entry-title"]')->item(0);
    $title = trim($titleNode->textContent ?? 'Не найдено');

    $descriptionNode = $xpath->query('//div[@class="entry-content"]/p[1]')->item(0);
    $description = trim($descriptionNode->textContent ?? 'Описание временно недоступно.');

    $fullLink = filter_var($url, FILTER_VALIDATE_URL) ? $url : 'https://abitur71.tsu.tula.ru'  . $url;

    $programs[] = [
        'code' => $code,
        'name' => $title,
        'description' => $description,
        'link' => $fullLink
    ];
}

echo json_encode($programs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);