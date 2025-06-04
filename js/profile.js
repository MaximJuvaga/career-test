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

        if (data.role === 'abiturient') {
        const filtersAbiturient = document.getElementById('filters-abiturient');
        const filterForm = document.getElementById('filter-form-abiturient');
        const resetButton = document.getElementById('reset-filter');
        const testResultsDiv = document.getElementById('test-results');

        filtersAbiturient.style.display = 'block';

        const loadResults = async (filters = {}) => {
            let url = `get_test_results.php?user_id=${data.id}`;
            if (filters.date) url += `&date=${filters.date}`;
            if (filters.institute) url += `&institute=${filters.institute}`;

            const resultsResponse = await fetch(url);
            const resultsData = await resultsResponse.json();

            testResultsDiv.innerHTML = '<h3>Ваши результаты:</h3>';

            if (resultsData.length === 0) {
                testResultsDiv.innerHTML += '<p>Нет результатов по заданным критериям.</p>';
                return;
            }

            resultsData.forEach(result => {
                const parsedResult = JSON.parse(result.result_json);
                const institute = parsedResult.theme || 'Не определено';
                const programs = parsedResult.programs || [];
                const vacancies = parsedResult.vacancies || [];

                const card = document.createElement('div');
                card.className = 'test-item';

                card.innerHTML = `
                    <p><strong>Институт:</strong> ${institute}</p>
                    <p><strong>Дата:</strong> ${result.created_at}</p>
                    <p><strong>Направления:</strong></p>
                    <ul>${programs.map(p => `<li>${p.code} — ${p.name}</li>`).join('')}</ul>
                    <p><strong>Вакансии:</strong></p>
                    <ul>${vacancies.map(v => `<li><a href="${v.url}" target="_blank">${v.title}, ${v.employer}, ${v.salary}</a></li>`).join('')}</ul>
                    <hr>
                `;

                testResultsDiv.appendChild(card);
            });
    };

    // Загрузка без фильтров при открытии
    loadResults();

    // Применение фильтров
    filterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(filterForm);
        const date = formData.get('date');
        const institute = formData.get('institute');
        loadResults({ date, institute });
    });

    // Сброс фильтров
    resetButton.addEventListener('click', () => {
        filterForm.reset();
        loadResults({});
    });
}

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
                        <ul>${r.vacancies.map(v => `<li><a href="${v.url}" target="_blank">${v.title}, ${v.employer}, ${v.salary}</a></li>`).join('')}</ul>
                        <hr>
                    `;
                    testResultsDiv.appendChild(card);
                });
            };

            form.addEventListener('submit', e => {
                e.preventDefault();
                applyFilters();
            });

            applyFilters(); // Загружаем все данные при загрузке
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