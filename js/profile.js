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

        // === Личный кабинет Абитуриента ===
        if (data.role === 'abiturient') {
    const resultsResponse = await fetch(`get_test_results.php?user_id=${userData.id}`);
    const resultsData = await resultsResponse.json();

    testResultsDiv.innerHTML = '<h3>Ваши результаты:</h3>';

    if (resultsData.length === 0) {
        testResultsDiv.innerHTML += '<p>Вы ещё не проходили тест.</p>';
        return;
    }

    resultsData.forEach(result => {
        const parsedResult = JSON.parse(result.result_json);
        const institute = parsedResult.theme || 'Не определено';
        const programs = parsedResult.programs || [];
        const vacanciesByProgram = parsedResult.vacancies_by_program || [];

        // Создаем контейнер для всех программ
        const programsContainer = document.createElement('div');
        programsContainer.className = 'programs-container';

        programs.forEach(program => {
            const programCode = program.code;
            const programName = program.name;

            // Находим вакансии для текущего направления
            const programVacancies = vacanciesByProgram.find(item => item.program.code === programCode)?.vacancies || [];

            const card = document.createElement('div');
            card.className = 'program-card';

            card.innerHTML = `
                <h4>${programCode} — ${programName}</h4>
                <p><strong>Подходящие профессии:</strong> ${
                    parsedResult.professions[programCode]?.join(', ') || 'Не указано'
                }</p>
                <hr>
                <p><strong>Подходящие вакансии:</strong></p>
                <ul>
                    ${programVacancies.map(v => 
                        `<li><a href="${v.url}" target="_blank">${v.title}, ${v.employer}, ${v.salary}</a></li>`
                    ).join('')}
                </ul>
            `;

            card.style = `
                display: flex;
                flex-direction: column;
                gap: 1rem;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                background: #fff;
                transition: transform 0.3s ease;
                width: calc(33.33% - 20px); /* 3 колонки */
                box-sizing: border-box;
            `;

            programsContainer.appendChild(card);
        });

        // Добавляем блок с институтом и датой
        const card = document.createElement('div');
        card.className = 'test-item';

        card.innerHTML = `
            <p><strong>Институт:</strong> ${institute}</p>
            <p><strong>Дата:</strong> ${result.created_at}</p>
        `;

        card.appendChild(programsContainer);

        testResultsDiv.appendChild(card);
    });
}

        // === Личный кабинет Преподавателя (без изменений) ===
        if (data.role === 'teacher') {
            filtersDiv.style.display = 'block';

            const form = document.getElementById('filter-form');
            const applyFilters = async () => {
                const formData = new FormData(form);
                const params = new URLSearchParams(formData).toString();
                const res = await fetch(`get_all_test_results.php?${params}`);
                const results = await res.json();

                testResultsDiv.innerHTML = '<h3>Результаты всех абитуриентов:</h3>';

                if (results.length === 0) {
                    testResultsDiv.innerHTML += '<p>Нет результатов.</p>';
                    return;
                }

                results.forEach(r => {
                    const card = document.createElement('div');
                    card.className = 'test-item';
                    card.innerHTML = `
                        <p><strong>Логин:</strong> ${r.login}</p>
                        <p><strong>Дата:</strong> ${r.created_at}</p>
                        <p><strong>Институт:</strong> ${r.institute}</p>
                        <p><strong>Направления:</strong></p>
                        <ul>${r.programs.map(p => `<li>${p.code} — ${p.name}</li>`).join('')}</ul>
                        <p><strong>Вакансии:</strong></p>
                        <ul>${r.vacancies.map(v => 
                            `<li><a href="${v.url}" target="_blank">${v.title}, ${v.employer}, ${v.salary}</a></li>`
                        ).join('')}</ul>
                        <hr>
                    `;
                    testResultsDiv.appendChild(card);
                });
            };

            form.addEventListener('submit', e => {
                e.preventDefault();
                applyFilters();
            });

            applyFilters(); // Загружаем данные при открытии
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