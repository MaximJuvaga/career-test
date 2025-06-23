document.addEventListener('DOMContentLoaded', async () => {
    const storedUser = localStorage.getItem('user');
    const userLoginSpan = document.getElementById('user-login');
    const logoutBtn = document.getElementById('logout-button');
    const profileTitle = document.getElementById('profile-title');
    const userInfoDiv = document.getElementById('user-info');
    const filtersDiv = document.getElementById('filters');
    const statisticsSection = document.getElementById('statistics-section');
    const testResultsDiv = document.getElementById('test-results');
    
    let programLinks = {};

// Загружаем ссылки на программы с сервера
    fetch('php/get_uni_programs.php')
        .then(res => res.json())
        .then(data => {
            programLinks = data;
        })
        .catch(err => console.error("Ошибка загрузки программ:", err));

    if (!storedUser) {
        window.location.href = 'login.html';
        return;
    }

    const userData = JSON.parse(storedUser);
    userLoginSpan.textContent = `Добро пожаловать, ${userData.login}`;

    try {
        const response = await fetch('get_user_data.php');
        const data = await response.json();
        if (data.error) throw new Error("Ошибка сервера");

        profileTitle.textContent = data.role === 'abiturient'
            ? 'Личный кабинет абитуриента'
            : 'Личный кабинет преподавателя';

        userInfoDiv.innerHTML = `
            <p><strong>Логин:</strong> ${data.login}</p>
            <p><strong>Роль:</strong> ${data.role === 'abiturient' ? 'Абитуриент' : 'Преподаватель'}</p>
        `;

        if (data.role === 'teacher') {
            statisticsSection.style.display = 'block';
            filtersDiv.style.display = 'block';
        }

        let currentPage = 0;
        const itemsPerPage = 10;

        function renderTestResultCard(result) {
    let parsedResult;
    try {
        parsedResult = JSON.parse(result.result_json);
    } catch (error) {
        console.error("Ошибка парсинга JSON:", error);
        parsedResult = {
            theme: "Не определено",
            programs: [],
            professions_mapping: {},
            vacancies_by_program: []
        };
    }

    let programs = parsedResult.programs || [];
    if (!Array.isArray(programs)) {
        programs = Object.values(programs);
    }

    const institute = parsedResult.theme || 'Не определено';
    const scores = parsedResult.scores || {
        "Политехнический институт": 0,
        "Институт горного дела и строительства": 0,
        "Институт прикладной математики и компьютерных наук": 0,
        "Институт высокоточных систем им. Грязева": 0
    };

    const card = document.createElement('div');
    card.className = 'test-item';

    let programsHTML = '';
    if (Array.isArray(programs)) {
        programs.forEach(program => {
            let vacanciesHTML = '';
            const programCode = program.code;
            const programName = program.name;

            // Получаем ссылку на сайт ТулГУ
            const programLink = programLinks[programCode] || '#';

            const professions = parsedResult.professions_mapping?.[programCode] || ['Профессия не определена'];
            professions.forEach(profession => {
                const vacancies = (parsedResult.vacancies_by_program?.find(p =>
                    p.program && p.program.code === programCode)?.vacancies_by_profession?.[profession]) || [];

                vacanciesHTML += `<strong>${profession}</strong><ul>`;
                if (vacancies.length > 0) {
                    vacancies.forEach(vacancy => {
                        vacanciesHTML += `<li><a href="${vacancy.url}" target="_blank">${vacancy.title}, ${vacancy.employer}, ${vacancy.salary}</a></li>`;
                    });
                } else {
                    vacanciesHTML += `<li>Нет вакансий</li>`;
                }
                vacanciesHTML += '</ul>';
            });

            // Теперь выводим название программы как ссылку
            programsHTML += `
                <div class="program-card">
                    <h4>
                        <a href="${programLink}" target="_blank" style="color: #007bff; text-decoration: underline;">
                            ${programCode} — ${programName}
                        </a>
                    </h4>
                    <p><strong>Подходящие профессии:</strong><br>${professions.join(' | ')}</p>
                    <hr>
                    <h5 style="margin-top: 10px; font-size: 1rem;">Вакансии по профессиям:</h5>
                    ${vacanciesHTML}
                </div>
            `;
        });
    }

    card.innerHTML = `
        <p><strong>Институт:</strong> ${institute}</p>
        <p><strong>Дата:</strong> ${result.created_at}</p>
    `;

    if (data.role === 'teacher') {
        card.innerHTML = `
            <p><strong>Институт:</strong> ${institute}</p>
            <p><strong>Дата:</strong> ${result.created_at}</p>
            <p><strong>Логин:</strong> ${result.login}</p>
        `;
    }

    const chartContainer = document.createElement('div');
    chartContainer.style.width = '400px';
    chartContainer.innerHTML = `<canvas id="chart-${result.id}" width="400" height="300"></canvas>`;

    const programList = document.createElement('div');
    programList.innerHTML = programsHTML;

    card.appendChild(chartContainer);
    card.appendChild(programList);

    testResultsDiv.appendChild(card);

    drawChart(`chart-${result.id}`, scores);
}

        function drawChart(canvasId, instituteCounts) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            const institutes = Object.keys(instituteCounts);
            const values = Object.values(instituteCounts);
            const total = values.reduce((sum, val) => sum + val, 0);
            const percentages = values.map(val => ((val / total) * 100).toFixed(1));

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: institutes.map((label, i) => `${label} (${percentages[i]}%)`),
                    datasets: [{
                        label: 'Распределение ответов',
                        data: values,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 206, 86, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.parsed || 0;
                                    let percent = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percent}%)`;
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Распределение ваших ответов по институтам'
                        }
                    }
                }
            });
        }

       async function loadResults(filters = {}, page = 0) {
    let url = data.role === 'abiturient'
        ? `get_test_results.php?user_id=${data.id}`
        : `get_all_test_results.php`;

    if (filters.date) url += `?date=${filters.date}`;
    if (filters.institute) url += `?institute=${filters.institute}`;
    if (filters.login) url += `?login=${filters.login}`;

    const resultsResponse = await fetch(url);
    const resultsData = await resultsResponse.json();

    testResultsDiv.innerHTML = '<h3>Результаты:</h3>';
    if (resultsData.length === 0) {
        testResultsDiv.innerHTML += '<p>Нет результатов по заданным критериям.</p>';
        return;
    }

    resultsData.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    const paginatedResults = resultsData.slice(page * itemsPerPage, (page + 1) * itemsPerPage);
    paginatedResults.forEach(renderTestResultCard);

    const totalPages = Math.ceil(resultsData.length / itemsPerPage);
    const paginationDiv = document.createElement('div');
    paginationDiv.style.marginTop = '20px';
    paginationDiv.style.display = 'flex';
    paginationDiv.style.gap = '10px';

    for (let i = 0; i < totalPages; i++) {
        const btn = document.createElement('button');
        btn.textContent = i + 1;
        btn.onclick = () => {
            currentPage = i;
            testResultsDiv.innerHTML = '<h3>Результаты:</h3>';
            resultsData.slice(i * itemsPerPage, (i + 1) * itemsPerPage).forEach(renderTestResultCard);
        };
        if (i === currentPage) btn.disabled = true;
        paginationDiv.appendChild(btn);
    }

    testResultsDiv.appendChild(paginationDiv);

    if (data.role === 'teacher') {
        const instituteCount = {};
        const programCount = {};
        resultsData.forEach(result => {
            try {
                const parsed = JSON.parse(result.result_json);
                const institute = parsed.theme;
                if (institute) {
                    instituteCount[institute] = (instituteCount[institute] || 0) + 1;
                }

                let programs = parsed.programs || [];
                if (!Array.isArray(programs)) {
                    programs = Object.values(programs);
                }

                programs.forEach(prog => {
                    const name = prog.name;
                    if (name) {
                        programCount[name] = (programCount[name] || 0) + 1;
                    }
                });
            } catch (e) {
                console.error("Ошибка парсинга результата", e);
            }
        });

        updateStatisticsCharts(instituteCount, programCount);
    }
}
        function updateStatisticsCharts(instituteStats, programStats) {
            const instituteCanvas = document.getElementById('institute-stats-chart').getContext('2d');
            const programCanvas = document.getElementById('program-stats-chart').getContext('2d');

            Chart.getChart(instituteCanvas)?.destroy();
            Chart.getChart(programCanvas)?.destroy();

            const instituteLabels = Object.keys(instituteStats);
            const instituteValues = Object.values(instituteStats);
            const instituteTotal = instituteValues.reduce((a, b) => a + b, 0);
            const institutePercentages = instituteValues.map(v => ((v / instituteTotal) * 100).toFixed(1));

            new Chart(instituteCanvas, {
                type: 'pie',
                data: {
                    labels: instituteLabels.map((label, i) => `${label} (${institutePercentages[i]}%)`),
                    datasets: [{
                        label: 'Рекомендации по институтам',
                        data: instituteValues,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 206, 86, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Рекомендации по институтам'
                        }
                    }
                }
            });

            const programLabels = Object.keys(programStats);
            const programValues = Object.values(programStats);

            new Chart(programCanvas, {
                type: 'bar',
                data: {
                    labels: programLabels,
                    datasets: [{
                        label: 'Частота рекомендаций',
                        data: programValues,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Рекомендации по программам'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Количество: ${context.raw}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        
        if (data.role === 'abiturient') {
            const filtersAbiturient = document.getElementById('filters-abiturient');
            const filterForm = document.getElementById('filter-form-abiturient');
            const resetButton = document.getElementById('reset-filter');

            filtersAbiturient.style.display = 'block';

            const applyFilters = (filters) => {
                currentPage = 0;
                testResultsDiv.innerHTML = '';
                loadResults(filters, currentPage);
            };

            filterForm.addEventListener('submit', e => {
                e.preventDefault();
                const formData = new FormData(filterForm);
                const date = formData.get('date');
                const institute = formData.get('institute');
                applyFilters({ date, institute });
            });

            resetButton.addEventListener('click', () => {
                filterForm.reset();
                applyFilters({});
            });

            loadResults({}, currentPage);
        }

        if (data.role === 'teacher') {
            const form = document.getElementById('filter-form');

            form.addEventListener('submit', async e => {
                e.preventDefault();
                const formData = new FormData(form);
                const date = formData.get('date');
                const institute = formData.get('institute');
                const login = formData.get('login');
                
                // Очистка предыдущих результатов
                testResultsDiv.innerHTML = '<p>Загрузка...</p>';
                currentPage = 0;
                loadResults({ date, institute, login }, currentPage);
            });

    loadResults({}, currentPage);
}

    } catch (err) {
        console.error("Ошибка загрузки данных", err);
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    }

    logoutBtn.addEventListener('click', async () => {
        const res = await fetch('logout.php');
        if (res.ok) {
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        }
    });
});