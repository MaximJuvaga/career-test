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
$questions = $data['questions'] ?? [];

// Программы по институтам
$programsMapping = [
    'Политехнический институт' => [
        ['code' => '15.03.01', 'name' => 'Машиностроение'],
        ['code' => '15.03.05', 'name' => 'Конструкторско-технологическое обеспечение машиностроительных производств'],
        ['code' => '22.03.01', 'name' => 'Материаловедение и технологии материалов'],
        ['code' => '15.03.04', 'name' => 'Автоматизация технологических процессов и производств'],
        ['code' => '27.03.01', 'name' => 'Стандартизация и метрология'],
        ['code' => '23.03.01', 'name' => 'Технология транспортных процессов']
    ],
    'Институт горного дела и строительства' => [
        ['code' => '07.03.01', 'name' => 'Архитектура'],
        ['code' => '21.03.01', 'name' => 'Нефтегазовое дело'],
        ['code' => '54.03.01', 'name' => 'Дизайн'],
        ['code' => '21.03.02', 'name' => 'Землеустройство и кадастры'],
        ['code' => '21.05.04', 'name' => 'Горное дело'],
        ['code' => '08.03.01', 'name' => 'Экономика']
    ],
    'Институт прикладной математики и компьютерных наук' => [
        ['code' => '09.03.01', 'name' => 'Информатика и вычислительная техника'],
        ['code' => '09.03.04', 'name' => 'Программная инженерия'],
        ['code' => '10.05.01', 'name' => 'Информационная безопасность'],
        ['code' => '01.03.02', 'name' => 'Прикладная математика и информатика'],
        ['code' => '09.03.03', 'name' => 'Прикладная информатика'],
        ['code' => '09.03.02', 'name' => 'Информационные системы и технологии']
    ],
    'Институт высокоточных систем им. Грязева' => [
        ['code' => '11.05.01', 'name' => 'Радиоэлектронные системы и комплексы'],
        ['code' => '17.05.01', 'name' => 'Специальные системы обеспечения движения поездов'],
        ['code' => '24.05.02', 'name' => 'Проектирование авиационных и ракетных двигателей'],
        ['code' => '15.05.01', 'name' => 'Проектирование технологических машин и комплексов'],
        ['code' => '15.03.06', 'name' => 'Мехатроника и робототехника'],
        ['code' => '13.03.02', 'name' => 'Электроэнергетика и электротехника']
    ]
];

// Профессии для парсинга вакансий
$professionsMapping = [
    // Политехнический институт
    '15.03.01' => ['инженер-конструктор', 'CAD-инженер', 'инженер по технологиям машиностроения'],
    '15.03.05' => ['инженер по автоматизации', 'технолог сварки', 'инженер по станкам с ЧПУ'],
    '22.03.01' => ['материаловед', 'инженер по качеству материалов', 'металлург'],
    '15.03.04' => ['инженер по автоматизации', 'наладчик автоматики', 'инженер ПЛК'],
    '27.03.01' => ['метролог', 'инженер по качеству', 'ISO-специалист'],
    '23.03.01' => ['инженер транспортных систем', 'логист', 'диспетчер транспортных операций'],
    // Горное дело и строительство
    '07.03.01' => ['архитектор', 'BIM-моделлер', 'проектировщик зданий'],
    '21.03.01' => ['инженер нефтегазового комплекса', 'геолог', 'инженер по добыче полезных ископаемых'],
    '54.03.01' => ['дизайнер интерьера', 'графический дизайнер', 'UX/UI-дизайнер'],
    '21.03.02' => ['землеустроитель', 'кадастровый инженер', 'инженер по геодезии'],
    '21.05.04' => ['инженер по бурению', 'инженер по разработке месторождений', 'горный инженер'],
    '08.03.01' => ['экономист предприятия', 'финансовый аналитик', 'бюджетный контролёр'],
    // Прикладная математика и КН
    '09.03.01' => ['системный программист', 'DevOps-инженер', 'QA-инженер'],
    '09.03.04' => ['fullstack-разработчик', 'backend-разработчик', 'тестировщик ПО'],
    '10.05.01' => ['специалист по кибербезопасности', 'сетевой инженер', 'инженер по защите информации'],
    '01.03.02' => ['аналитик данных', 'Data Scientist', 'математик в ИИ'],
    '09.03.03' => ['системный аналитик', 'IT-консультант', 'бизнес-аналитик'],
    '09.03.02' => ['инженер по информационным системам', 'специалист по цифровой трансформации', 'интегратор решений'],
    // Высокоточные системы
    '11.05.01' => ['радиоинженер', 'инженер связи', 'специалист по радиочастотам'],
    '17.05.01' => ['инженер по метрологии', 'инженер по качеству', 'калибровщик'],
    '24.05.02' => ['инженер по авиадвигателям', 'конструктор авиационных систем', 'инженер по летательным аппаратам'],
    '15.05.01' => ['инженер по проектированию оборудования', 'конструктор технологических систем', 'инженер по механическим системам'],
    '15.03.06' => ['инженер-мехатроник', 'специалист по робототехнике', 'программист автоматизированных систем'],
    '13.03.02' => ['энергоинженер', 'инженер по энергоэффективности', 'электромеханик']
];

function getVacancies($profession) {
    if (empty($profession)) return [];
    $url = "https://api.hh.ru/vacancies?text=" . urlencode($profession) . "&area=113&per_page=2";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: CareerTestBot']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    if (!$response) return [];
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) return [];
    return array_slice(array_map(function ($item) {
        $salary = 'Не указана';
        if (!empty($item['salary'])) {
            $from = $item['salary']['from'] ?? null;
            $to = $item['salary']['to'] ?? null;
            $currency = $item['salary']['currency'] ?? 'руб.';
            if ($from && $to) $salary = "от $from до $to $currency";
            elseif ($from) $salary = "от $from $currency";
            elseif ($to) $salary = "до $to $currency";
        }
        return [
            'title' => $item['name'] ?? '',
            'employer' => $item['employer']['name'] ?? 'Не указан',
            'salary' => $salary,
            'url' => $item['alternate_url'] ?? '#' 
        ];
    }, $data['items'] ?? []), 0, 2);
}

try {
    // Подсчёт баллов по институтам
    $scores = [
        "Политехнический институт" => 0,
        "Институт горного дела и строительства" => 0,
        "Институт прикладной математики и компьютерных наук" => 0,
        "Институт высокоточных систем им. Грязева" => 0
    ];

    foreach ($answers as $index => $value) {
        if ($value === '1' && isset($questions[$index])) {
            $inst = $questions[$index]['institute'] ?? '';
            if (isset($scores[$inst])) $scores[$inst]++;
        }
    }

    // Институт с наибольшим количеством совпадений
    $theme = array_keys($scores, max($scores))[0] ?? 'Не определено';

    // Подсчёт баллов по программам
    $programScores = [];
    foreach ($programsMapping[$theme] as $program) {
        $programScores[$program['code']] = 0;
    }

    foreach ($answers as $index => $value) {
        if ($value === '1' && isset($questions[$index])) {
            $questionPrograms = $questions[$index]['programCodes'] ?? [];
            foreach ($questionPrograms as $progCode) {
                if (isset($programScores[$progCode])) {
                    $programScores[$progCode]++;
                }
            }
        }
    }

    arsort($programScores);
    $top3Programs = array_slice(array_keys($programScores), 0, 3);

    // Формируем список трёх программ
    $top3ProgramsData = array_filter($programsMapping[$theme], function ($p) use ($top3Programs) {
        return in_array($p['code'], $top3Programs);
    });

    array_multisort(array_column($top3ProgramsData, 'code'), SORT_ASC, $top3ProgramsData);

    // Парсим вакансии по этим программам
    $programsWithVacancies = array_map(function ($program) use ($professionsMapping) {
        $allProfessions = $professionsMapping[$program['code']] ?? ['Профессия не определена'];
        $vacanciesByProfession = [];

        foreach ($allProfessions as $profession) {
            $vacanciesByProfession[$profession] = getVacancies($profession);
        }

        return [
            'program' => $program,
            'professions' => $allProfessions,
            'vacancies_by_profession' => $vacanciesByProfession
        ];
    }, $top3ProgramsData);

    // Сохраняем результат в БД
    $stmt = $pdo->prepare("INSERT INTO test_results (user_id, result_json) VALUES (?, ?)");
    $stmt->execute([$userId, json_encode([
        'theme' => $theme,
        'programs' => $top3ProgramsData,
        'professions_mapping' => $professionsMapping,
        'vacancies_by_program' => $programsWithVacancies,
        'scores' => $scores,
        'program_scores' => $programScores,
        'programs_mapping' => array_reduce($programsMapping[$theme], function ($carry, $item) {
            $carry[$item['code']] = $item['name'];
            return $carry;
        }, [])
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]);

    // Отправляем данные в интерфейс
    echo json_encode([
        'theme' => $theme,
        'programs' => $programsWithVacancies,
        'scores' => $scores,
        'program_scores' => $programScores,
        'programs_mapping' => array_reduce($programsMapping[$theme], function ($carry, $item) {
            $carry[$item['code']] = $item['name'];
            return $carry;
        }, [])
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Ошибка сервера']);
}
exit;