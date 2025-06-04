<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$userId = $_SESSION['user']['id'];
$data = json_decode(file_get_contents('php://input'), true);
$answers = $data['answers'] ?? [];
$theme = $data['theme'] ?? null;

// Программы по институтам
$programsMapping = [
    'Политехнический институт' => [
        ['code' => '15.03.01', 'name' => 'Инженерная графика'],
        ['code' => '15.03.05', 'name' => 'Конструкторско-технологическое обеспечение машиностроительных производств'],
        ['code' => '22.03.01', 'name' => 'Материаловедение и технологии материалов']
    ],
    'Институт горного дела и строительства' => [
        ['code' => '07.03.01', 'name' => 'Строительство'],
        ['code' => '08.03.01', 'name' => 'Экономика'],
        ['code' => '21.03.01', 'name' => 'Нефть и газ']
    ],
    'Институт прикладной математики и компьютерных наук' => [
        ['code' => '09.03.01', 'name' => 'Информатика и вычислительная техника'],
        ['code' => '09.03.04', 'name' => 'Программная инженерия'],
        ['code' => '10.05.01', 'name' => 'Компьютерная безопасность']
    ],
    'Институт высокоточных систем им. Грязева' => [
        ['code' => '11.05.01', 'name' => 'Радиоэлектронные системы и комплексы'],
        ['code' => '17.05.01', 'name' => 'Стандартизация и метрология']
    ]
];

// Профессии по кодам программ
$professionsMapping = [
    '15.03.01' => ['инженер по САПР', 'конструктор', 'разработчик технологических процессов'],
    '15.03.05' => ['технолог машиностроения', 'инженер-механик', 'технолог производства'],
    '22.03.01' => ['материаловед', 'инженер по качеству материалов', 'исследователь в области материалов'],
    '07.03.01' => ['инженер-строитель', 'проектировщик зданий', 'архитектор'],
    '08.03.01' => ['экономист', 'финансовый аналитик', 'бухгалтер'],
    '21.03.01' => ['инженер нефтегазового комплекса', 'геолог', 'инженер по добыче полезных ископаемых'],
    '09.03.01' => ['инженер', 'разработчик ПО', 'специалист по внедрению'],
    '09.03.04' => ['разработчик программного обеспечения', 'инженер по автоматизации', 'тестировщик ПО'],
    '10.05.01' => ['инженер по информационной безопасности', 'криптограф', 'системный администратор'],
    '11.05.01' => ['радиоинженер', 'специалист по связи', 'инженер по телекоммуникациям'],
    '17.05.01' => ['метролог', 'инженер по контролю качества', 'специалист по сертификации']
];

function getVacancies($profession) {
    if (empty($profession)) return [];

    $url = "https://api.hh.ru/vacancies?text=" . urlencode($profession) . "&area=113&per_page=5";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: CareerTestBot']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Отладка — временно
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return [];

    $data = json_decode($response, true);
    $vacancies = [];

    foreach ($data['items'] as $item) {
        $salary = 'Не указана';
        if (!empty($item['salary'])) {
            $from = $item['salary']['from'] ?? null;
            $to = $item['salary']['to'] ?? null;
            $currency = $item['salary']['currency'] ?? 'руб.';
            if ($from && $to) $salary = "от $from до $to $currency";
            elseif ($from) $salary = "от $from $currency";
            elseif ($to) $salary = "до $to $currency";
        }

        $vacancies[] = [
            'title' => $item['name'] ?? '',
            'employer' => $item['employer']['name'] ?? 'Не указан',
            'salary' => $salary,
            'url' => $item['alternate_url'] ?? '#' 
        ];
    }

    return $vacancies;
}

try {
    // Формируем JSON с вакансиями по каждой программе
    $programsWithVacancies = array_map(function($program) use ($professionsMapping) {
        $firstProfession = $professionsMapping[$program['code']][0] ?? '';
        return [
            'program' => $program,
            'professions' => $professionsMapping[$program['code']] ?? ['Профессия не определена'],
            'vacancies' => $firstProfession ? getVacancies($firstProfession) : []
        ];
    }, $programsMapping[$theme] ?? []);

    // Сохраняем результат в БД
    $stmt = $pdo->prepare("INSERT INTO test_results (user_id, result_json) VALUES (?, ?)");
    $stmt->execute([$userId, json_encode([
        'theme' => $theme ?: 'Не определено',
        'programs' => $programsMapping[$theme] ?? [],
        'professions' => $professionsMapping,
        'vacancies_by_program' => $programsWithVacancies
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка сервера']);
    exit;
}

echo json_encode([
    'theme' => $theme ?: 'Не определено',
    'programs' => array_map(function($program) use ($professionsMapping) {
        $firstProfession = $professionsMapping[$program['code']][0] ?? '';
        return [
            'program' => $program,
            'professions' => $professionsMapping[$program['code']] ?? ['Профессия не определена'],
            'vacancies' => $firstProfession ? getVacancies($firstProfession) : []
        ];
    }, $programsMapping[$theme] ?? [])
]);