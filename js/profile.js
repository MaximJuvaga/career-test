document.addEventListener('DOMContentLoaded', async () => {
    const logoutBtn = document.getElementById('logout-button');
    const userInfoDiv = document.getElementById('user-info');
    const testResultsDiv = document.getElementById('test-results');
    const profileTitle = document.getElementById('profile-title');
    const userLoginSpan = document.getElementById('user-login');

    // Получаем роль из URL
    const urlParams = new URLSearchParams(window.location.search);
    const role = urlParams.get('role') || 'abiturient';

    // Проверяем авторизацию
    const response = await fetch('auth_check.php');
    if (!response.ok) {
        alert('Вы не авторизованы');
        window.location.href = 'login.html';
        return;
    }

    try {
        // Загружаем данные пользователя
        const userResponse = await fetch('get_user_data.php');
        const userData = await userResponse.json();

        if (!userData) {
            alert('Ошибка загрузки данных пользователя');
            window.location.href = 'login.html';
            return;
        }

        // Устанавливаем заголовок
        profileTitle.textContent = role === 'abiturient' ? 'Личный кабинет абитуриента' : 'Личный кабинет преподавателя';

        // Отображаем логин в шапке
        userLoginSpan.textContent = `Добро пожаловать, ${userData.login}`;

        // Отображаем информацию о пользователе
        userInfoDiv.innerHTML = `
            <p><strong>Логин:</strong> ${userData.login}</p>
            <p><strong>Роль:</strong> ${userData.role === 'abiturient' ? 'Абитуриент' : 'Преподаватель'}</p>
        `;

        // Если это абитуриент — показываем его результаты
        if (userData.role === 'abiturient') {
            const resultsResponse = await fetch(`get_test_results.php?user_id=${userData.id}`);
            const resultsData = await resultsResponse.json();

            if (resultsData.length === 0) {
                testResultsDiv.innerHTML = '<p>Вы ещё не проходили тест.</p>';
            } else {
                testResultsDiv.innerHTML = '<h3>Ваши результаты тестов:</h3>';
                resultsData.forEach(result => {
                    const parsedResult = JSON.parse(result.result_json);
                    const theme = parsedResult.theme || 'Не определено';
                    testResultsDiv.innerHTML += `
                        <div>
                            <p><strong>Тема:</strong> ${theme}</p>
                            <p><strong>Дата:</strong> ${result.created_at}</p>
                        </div>
                    `;
                });
            }
        }

        // Если преподаватель — показываем все результаты
        if (userData.role === 'teacher') {
            const allResultsResponse = await fetch('get_all_test_results.php');
            const allResultsData = await allResultsResponse.json();

            if (allResultsData.length === 0) {
                testResultsDiv.innerHTML = '<p>Нет результатов тестов.</p>';
            } else {
                testResultsDiv.innerHTML = '<h3>Результаты всех абитуриентов:</h3>';
                allResultsData.forEach(result => {
                    const parsedResult = JSON.parse(result.result_json);
                    const theme = parsedResult.theme || 'Не определено';
                    testResultsDiv.innerHTML += `
                        <div>
                            <p><strong>ID пользователя:</strong> ${result.user_id}</p>
                            <p><strong>Тема:</strong> ${theme}</p>
                            <p><strong>Дата:</strong> ${result.created_at}</p>
                        </div>
                    `;
                });
            }
        }

    } catch (error) {
        console.error("Ошибка:", error);
        alert("Произошла ошибка при загрузке данных");
        window.location.href = 'login.html';
    }

    // Обработчик выхода
    logoutBtn.addEventListener('click', async () => {
        const response = await fetch('logout.php');
        if (response.ok) {
            window.location.href = 'login.html';
        }
    });
});