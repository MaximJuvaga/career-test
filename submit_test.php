<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q1 = $_POST['q1'] ?? '';
    $q2 = $_POST['q2'] ?? '';
    $q3 = $_POST['q3'] ?? '';

    // Определение базовой тематики на основе ответов
    if ($q1 === 'yes' && $q2 === 'yes' && $q3 === 'high') {
        $theme = "техническая";
    } elseif ($q1 === 'yes' && $q2 === 'no' && $q3 === 'high') {
        $theme = "программирование";
    } elseif ($q1 === 'no' && $q2 === 'yes' && $q3 === 'medium') {
        $theme = "менеджмент";
    } else {
        $theme = "маркетинг";
    }

    error_log("Результат теста: тематика = $theme");

    // Фиктивная связь тематики с направлениями
    $programsMapping = [
        "техническая" => [
            ["code" => "09.03.01", "name" => "Информатика и вычислительная техника"],
            ["code" => "15.03.04", "name" => "Автоматизация технологических процессов"],
            ["code" => "13.03.02", "name" => "Электроэнергетика"]
        ],
        "программирование" => [
            ["code" => "09.03.04", "name" => "Программная инженерия"],
            ["code" => "09.03.01", "name" => "Информатика и вычислительная техника"],
            ["code" => "10.05.01", "name" => "Компьютерная безопасность"]
        ],
        "менеджмент" => [
            ["code" => "38.03.02", "name" => "Менеджмент"],
            ["code" => "38.03.01", "name" => "Экономика"],
            ["code" => "37.03.01", "name" => "Психология"]
        ],
        "маркетинг" => [
            ["code" => "38.03.06", "name" => "Торговое дело"],
            ["code" => "42.03.01", "name" => "Реклама и связи с общественностью"],
            ["code" => "38.03.02", "name" => "Менеджмент"]
        ]
    ];

    // Выбираем подходящие направления
    $relatedPrograms = $programsMapping[$theme] ?? [];

    // Фиктивные профессии для каждого направления
    $professionsMapping = [
        "09.03.01" => ["инженер", "разработчик ПО", "специалист по внедрению"],
        "09.03.04" => ["разработчик программного обеспечения", "инженер по автоматизации", "тестировщик ПО"],
        "15.03.04" => ["инженер-автоматчик", "инженер по внедрению систем", "инженер по эксплуатации"],
        "13.03.02" => ["инженер по электроснабжению", "энергетик", "инженер по сетям"],
        "38.03.02" => ["менеджер проектов", "аналитик", "руководитель отдела продаж"],
        "38.03.01" => ["экономист", "финансовый аналитик", "кредитный специалист"],
        "37.03.01" => ["психолог", "HR-специалист", "коуч"],
        "38.03.06" => ["маркетолог", "PR-менеджер", "копирайтер"],
        "42.03.01" => ["специалист по рекламе", "SMM-менеджер", "медиапланер"]
    ];

    // Подбираем вакансии по профессии (HeadHunter)
    function getVacancies($profession) {
        $url = "https://api.hh.ru/vacancies?text=" . urlencode($profession) . "&area=113&per_page=5";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: CareerTestBot']);
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
                if ($from && $to) {
                    $salary = "от $from до $to $currency";
                } elseif ($from) {
                    $salary = "от $from $currency";
                } elseif ($to) {
                    $salary = "до $to $currency";
                }
            }

            $vacancies[] = [
                'title' => $item['name'],
                'employer' => $item['employer']['name'] ?? 'Не указан',
                'salary' => $salary,
                'url' => $item['alternate_url']
            ];
        }

        return $vacancies;
    }

    // Для каждого направления подставляем профессии и вакансии
    $finalPrograms = [];

    foreach ($relatedPrograms as $program) {
        $professions = $professionsMapping[$program['code']] ?? ['Профессия не определена'];
        $vacancies = [];

        // Берём первую профессию из списка и парсим HH
        if (!empty($professions)) {
            $vacancies = getVacancies($professions[0]);
        }

        $finalPrograms[] = [
            'program' => $program,
            'professions' => $professions,
            'vacancies' => $vacancies
        ];
    }

    // Отправляем результат
    echo json_encode([
        'theme' => $theme,
        'programs' => array_slice($finalPrograms, 0, 3)
    ]);
}
?>