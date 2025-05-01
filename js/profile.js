document.addEventListener('DOMContentLoaded', () => {
    const userInfo = document.getElementById('user-info');
    const testResults = document.getElementById('test-results');

    // Получаем email пользователя из localStorage (или другой системы аутентификации)
    const userEmail = localStorage.getItem('userEmail');

    if (!userEmail) {
        alert('Пожалуйста, войдите в систему.');
        window.location.href = 'index.html';
        return;
    }

    // Загружаем данные пользователя
    fetch(`get_user_data.php?email=${userEmail}`)
        .then(response => response.json())
        .then(data => {
            userInfo.innerHTML = `
                <p><strong>Имя:</strong> ${data.name}</p>
                <p><strong>Email:</strong> ${data.email}</p>
                <p><strong>Возраст:</strong> ${data.age}</p>
                <p><strong>Город:</strong> ${data.city}</p>
            `;
        });

    // Загружаем результаты теста
    fetch(`get_test_results.php?email=${userEmail}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                testResults.innerHTML = '<p>Вы еще не проходили тест.</p>';
                return;
            }

            data.forEach(result => {
                testResults.innerHTML += `
                    <div>
                        <p><strong>Результат:</strong> ${result.result}</p>
                        <p><strong>Дата:</strong> ${result.created_at}</p>
                    </div>
                `;
            });
        });
});