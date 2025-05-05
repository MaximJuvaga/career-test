<?php
$url = 'https://abitur71.tsu.tula.ru/admissions/f';
$html = @file_get_contents($url);

if ($html) {
    echo "Сайт доступен!";
} else {
    echo "Ошибка: сайт ТулГУ не отвечает или заблокировал запрос.";
}
?>