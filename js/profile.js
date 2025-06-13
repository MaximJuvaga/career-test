document.addEventListener('DOMContentLoaded', async () => {
    const storedUser = localStorage.getItem('user');
    const userLoginSpan = document.getElementById('user-login');
    const logoutBtn = document.getElementById('logout-button');
    const profileTitle = document.getElementById('profile-title');
    const userInfoDiv = document.getElementById('user-info');
    const filtersDiv = document.getElementById('filters');
    const testResultsDiv = document.getElementById('test-results');

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

        // Пагинация
        let currentPage = 0;
        const itemsPerPage = 10;

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
                        legend: { position: 'right' },
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

        function renderTestResultCard(result) {
            let parsedResult;
            try {
                // Попытка распарсить JSON
                parsedResult = JSON.parse(result.result_json);
            } catch (error) {
                // Если JSON некорректен, используем дефолтные значения
                console.error("Ошибка при парсинге JSON:", error);
                parsedResult = {
                    theme: "Не определено",
                    programs: [],
                    professions_mapping: {},
                    vacancies_by_program: []
                };
            }

            const institute = parsedResult.theme || 'Не определено';
            const programs = parsedResult.programs || [];
            const vacanciesByProgram = parsedResult.vacancies_by_program || [];
            const professionsMapping = parsedResult.professions_mapping || {};

            const scores = {
                "Политехнический институт": 0,
                "Институт горного дела и строительства": 0,
                "Институт прикладной математики и компьютерных наук": 0,
                "Институт высокоточных систем им. Грязева": 0
            };

            if (parsedResult.theme in scores) {
                scores[parsedResult.theme] = programs.length * 10; // вес по количеству программ
            }

            const card = document.createElement('div');
            card.className = 'test-item';

            let programsHTML = '';

            programs.forEach(program => {
                let vacanciesHTML = '';
                const programCode = program.code;
                const programName = program.name;

                const professions = professionsMapping[programCode] || ['Профессия не определена'];

                professions.forEach(profession => {
                    const vacancies = (vacanciesByProgram.find(p => p.program.code === programCode)?.vacancies_by_profession?.[profession]) || [];

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

                programsHTML += `
                    <div class="program-card">
                        <h4>${programCode} — ${programName}</h4>
                        <p><strong>Подходящие профессии:</strong><br>${professions.join(' | ')}</p>
                        <hr>
                        <h5 style="margin-top: 10px; font-size: 1rem;">Вакансии по профессиям:</h5>
                        ${vacanciesHTML}
                    </div>
                `;
            });

            card.innerHTML = `
                <p><strong>Институт:</strong> ${institute}</p>
                <p><strong>Дата:</strong> ${result.created_at}</p>
            `;

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

        async function loadResults(filters = {}, page = 0) {
            let url = data.role === 'abiturient'
                ? `get_test_results.php?user_id=${data.id}`
                : `get_all_test_results.php`;

            if (filters.date) url += `&date=${filters.date}`;
            if (filters.institute) url += `&institute=${filters.institute}`;
            if (filters.login) url += `&login=${filters.login}`;

            const resultsResponse = await fetch(url);
            const resultsData = await resultsResponse.json();
            console.log("Результаты из API:", resultsData);

            testResultsDiv.innerHTML = '<h3>Ваши результаты:</h3>';

            if (resultsData.length === 0) {
                testResultsDiv.innerHTML += '<p>Нет результатов по заданным критериям.</p>';
                return;
            }

            const start = page * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedResults = resultsData.slice(start, end);

            paginatedResults.forEach(renderTestResultCard);

            // Пагинация UI
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
                    testResultsDiv.innerHTML = '<h3>Ваши результаты:</h3>';
                    resultsData.slice(i * itemsPerPage, (i + 1) * itemsPerPage).forEach(renderTestResultCard);
                };
                if (i === currentPage) btn.disabled = true;
                paginationDiv.appendChild(btn);
            }

            testResultsDiv.appendChild(paginationDiv);
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

            loadResults({}, currentPage); // загрузка без фильтров
        }

        if (data.role === 'teacher') {
            filtersDiv.style.display = 'block';
            const form = document.getElementById('filter-form');

            form.addEventListener('submit', async e => {
                e.preventDefault();
                const formData = new FormData(form);
                const date = formData.get('date');
                const institute = formData.get('institute');
                const login = formData.get('login');
                loadResults({ date, institute, login }, 0);
            });

            loadResults({}, currentPage); // загрузка всех данных
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